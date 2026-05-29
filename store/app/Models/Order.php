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
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'shipped_at' => 'datetime',
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
