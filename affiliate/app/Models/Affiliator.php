<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Affiliator extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'affiliator_type_id',
        'name',
        'email',
        'password',
        'phone',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'bio',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AffiliatorType::class, 'affiliator_type_id');
    }

    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class);
    }

    public function referralOrders(): HasMany
    {
        return $this->hasMany(ReferralOrder::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(AffiliatorPayoutAccount::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function eventParticipations(): HasMany
    {
        return $this->hasMany(AffiliateEventParticipant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function availableBalance(): float
    {
        return $this->commissions()
            ->where('status', 'available')
            ->sum('amount');
    }

    public function totalEarnings(): float
    {
        return $this->commissions()
            ->whereIn('status', ['available', 'withdrawn'])
            ->sum('amount');
    }
}
