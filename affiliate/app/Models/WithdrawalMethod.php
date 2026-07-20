<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawalMethod extends Model
{
    use HasFactory;

    /**
     * Kosakata tertutup untuk `type`. Sumber tunggal untuk pilihan di form admin
     * sekaligus label yang tampil ke affiliator — sebelumnya label ditentukan
     * ternary yang menganggap apa pun selain bank_transfer sebagai E-Wallet.
     */
    public const TYPES = [
        'bank_transfer' => 'Bank',
        'e_wallet' => 'E-Wallet',
    ];

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'min_withdrawal',
        'fee_flat',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_withdrawal' => 'decimal:2',
        'fee_flat' => 'decimal:2',
    ];

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(AffiliatorPayoutAccount::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Biaya admin untuk satu penarikan. Dijepit pada nominal penarikan supaya
     * neto tidak pernah negatif, walau validasi sudah menjaga biaya < minimum.
     */
    public function feeFor(float $amount): float
    {
        return round(min((float) $this->fee_flat, max($amount, 0)), 2);
    }

    /** Jumlah yang benar-benar ditransfer ke affiliator. */
    public function netAmountFor(float $amount): float
    {
        return round($amount - $this->feeFor($amount), 2);
    }

    /** Metode yang sudah terpakai tidak boleh dihapus, cukup dinonaktifkan. */
    public function isInUse(): bool
    {
        return $this->withdrawals()->exists() || $this->payoutAccounts()->exists();
    }
}
