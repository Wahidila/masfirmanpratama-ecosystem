<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliator_id',
        'withdrawal_method_id',
        'method_name',
        'amount',
        'fee',
        'net_amount',
        'account_number',
        'account_name',
        'status',
        'admin_note',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function affiliator(): BelongsTo
    {
        return $this->belongsTo(Affiliator::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(WithdrawalMethod::class, 'withdrawal_method_id');
    }

    /**
     * Nama metode sebagaimana tercatat saat pengajuan.
     *
     * Selalu pakai ini untuk menampilkan riwayat — membaca `method->name` lewat
     * relasi hidup membuat rename metode di panel admin ikut mengubah bunyi
     * penarikan lama.
     */
    public function methodName(): string
    {
        return $this->method_name ?: ($this->method?->name ?? '-');
    }
}
