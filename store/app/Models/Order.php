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
        'total',
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
        'fulfillment_payload',
        'shipped_email_sent_at',
        'order_meta',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'shipped_at' => 'datetime',
            'shipping_cost' => 'integer',
            'fulfillment_payload' => 'array',
            'shipped_email_sent_at' => 'datetime',
            'order_meta' => 'array',
        ];
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
