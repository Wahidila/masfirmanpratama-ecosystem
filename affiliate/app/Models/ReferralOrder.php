<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReferralOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code_id',
        'affiliator_id',
        'store_order_id',
        'buyer_name',
        'buyer_email',
        'order_total',
        'status',
        'ordered_at',
    ];

    protected $casts = [
        'order_total' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(Affiliator::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }
}
