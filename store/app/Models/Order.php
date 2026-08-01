<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'phone',
        'email',
        'address',
        'shipping_city',
        'shipping_province',
        'shipping_district',
        'shipping_village',
        'shipping_zipcode',
        'total',
        'unique_code',
        'status',
        'ref_code',
        'shipping_courier',
        'shipping_resi',
        'shipped_at',
        'shipping_service',
        'shipping_cost',
        'shipping_etd',
        'fulfillment_status',
        'tracking_status',
        'fulfillment_reference_id',
        'fulfillment_api_order_id',
        'label_url',
        'fulfillment_payment_url',
        'fulfillment_payload',
        'shipped_email_sent_at',
        'order_meta',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'unique_code' => 'integer',
            'shipped_at' => 'datetime',
            'shipping_cost' => 'integer',
            'fulfillment_payload' => 'array',
            'shipped_email_sent_at' => 'datetime',
            'order_meta' => 'array',
        ];
    }

    /**
     * Nominal yang harus ditransfer = total + kode unik. Kode unik membuat tiap
     * order punya nominal khas supaya admin gampang mencocokkan transfer masuk.
     * Order lama (unique_code null) → sama dengan total.
     */
    public function payableTotal(): int
    {
        return (int) $this->total + (int) $this->unique_code;
    }

    /**
     * Ambil kode unik 1–999 yang tidak menghasilkan nominal transfer (total +
     * kode) yang sama dengan order lain yang masih menunggu pembayaran — supaya
     * nominal benar-benar unik antar order aktif dengan harga sama.
     */
    public static function generateUniqueCode(int $total): int
    {
        for ($i = 0; $i < 40; $i++) {
            $code = random_int(1, 999);

            $clash = static::query()
                ->whereIn('status', ['pending', 'partial_paid'])
                ->where('total', $total)
                ->where('unique_code', $code)
                ->exists();

            if (! $clash) {
                return $code;
            }
        }

        return random_int(1, 999); // fallback sangat jarang (≥40 order harga sama pending)
    }

    /**
     * Transisi order ke 'completed' + fulfillment 'delivered'. Idempotent:
     * mengembalikan true HANYA saat benar-benar baru transisi (bukan sudah
     * completed sebelumnya) supaya caller bisa men-dispatch OrderCompleted
     * tepat sekali. Sumber tunggal transisi selesai — dipakai bersama oleh
     * webhook AWB, tombol admin "Tandai Selesai", dan cek-resi otomatis.
     */
    public function markCompleted(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->status = 'completed';
        $this->fulfillment_status = 'delivered';
        $this->save();

        return true;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function waNotifications(): HasMany
    {
        return $this->hasMany(WaNotification::class);
    }
}
