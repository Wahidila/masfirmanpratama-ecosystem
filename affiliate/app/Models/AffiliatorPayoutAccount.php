<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rekening/e-wallet tujuan penarikan yang disimpan affiliator.
 *
 * Menggantikan pengetikan nomor rekening berulang di tiap pengajuan. Nomor dan
 * nama tetap di-snapshot ke baris penarikan saat diajukan, jadi mengganti atau
 * menghapus rekening di sini tidak mengubah riwayat.
 */
class AffiliatorPayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliator_id',
        'withdrawal_method_id',
        'account_number',
        'account_name',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(Affiliator::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(WithdrawalMethod::class, 'withdrawal_method_id');
    }

    public function label(): string
    {
        return trim(($this->method?->name ?? '-').' · '.$this->account_number.' a.n. '.$this->account_name);
    }
}
