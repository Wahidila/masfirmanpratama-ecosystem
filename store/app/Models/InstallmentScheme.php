<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Skema cicilan — HANYA berlaku untuk kelas/kursus (produk/buku selalu lunas).
 * Scope: global (course_id null = semua kelas) ATAU dikunci ke satu kelas.
 */
class InstallmentScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'dp_pct',
        'n_installments',
        'interval_days',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'dp_pct' => 'decimal:2',
            'n_installments' => 'integer',
            'interval_days' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true);
    }

    /**
     * Schemes for a given course: course-specific + global (course_id=null).
     * Pass null to get global-only.
     */
    public function scopeForCourse(Builder $q, ?int $courseId): Builder
    {
        if ($courseId === null) {
            return $q->whereNull('course_id');
        }

        return $q->where(function ($inner) use ($courseId) {
            $inner->whereNull('course_id')->orWhere('course_id', $courseId);
        });
    }

    /** Skema berlaku untuk semua kelas (tidak dikunci ke satu kelas). */
    public function isGlobal(): bool
    {
        return $this->course_id === null;
    }

    /** DP dalam persen tanpa trailing-zero, mis. "15" atau "12.5". */
    public function getDpLabelAttribute(): string
    {
        return rtrim(rtrim(number_format((float) $this->dp_pct, 2, '.', ''), '0'), '.');
    }

    /**
     * Ringkasan tenor untuk list admin (price-agnostic), mis.
     * "DP 15% + 12× cicilan / 30 hari" atau "Bayar penuh (lunas)".
     */
    public function getTenorSummaryAttribute(): string
    {
        if ($this->n_installments <= 1 && (float) $this->dp_pct >= 100) {
            return 'Bayar penuh (lunas)';
        }

        return "DP {$this->dp_label}% + {$this->n_installments}× cicilan / {$this->interval_days} hari";
    }

    /**
     * Pratinjau jadwal pembayaran untuk sebuah harga total — mirror logika
     * CourseCheckoutController::generatePaymentSchedule (DP + n_installments,
     * cicilan terakhir menyerap pembulatan). Untuk pratinjau, bukan sumber
     * tunggal checkout.
     *
     * @return list<array{label: string, amount: int}>
     */
    public function previewSchedule(int $total): array
    {
        $dpAmount = (int) ceil($total * ((float) $this->dp_pct / 100));
        $remaining = max(0, $total - $dpAmount);
        $n = max(1, $this->n_installments);
        $perInstallment = (int) ceil($remaining / $n);

        $rows = [['label' => 'DP', 'amount' => max(0, $dpAmount)]];
        for ($i = 1; $i <= $n; $i++) {
            $amount = ($i === $n)
                ? $remaining - ($perInstallment * ($n - 1))
                : $perInstallment;
            $rows[] = ['label' => "Cicilan {$i}", 'amount' => max(0, $amount)];
        }

        return $rows;
    }
}
