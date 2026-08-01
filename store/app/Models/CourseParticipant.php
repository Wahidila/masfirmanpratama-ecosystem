<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Peserta kursus (roster kelas).
 *
 * Sumber: order kelas yang sudah ada pembayaran terverifikasi (lunas / cicilan
 * berjalan) — lihat listener SyncCourseParticipant — atau ditambah manual admin
 * (order_id null).
 */
class CourseParticipant extends Model
{
    use HasFactory;

    /** Status keikutsertaan => label tampil. */
    public const STATUSES = [
        'registered' => 'Terdaftar',
        'active' => 'Aktif',
        'graduated' => 'Lulus',
        'cancelled' => 'Batal',
    ];

    /** Status pembayaran => label tampil. */
    public const PAYMENT_STATUSES = [
        'lunas' => 'Lunas',
        'cicil' => 'Cicilan',
    ];

    protected $fillable = [
        'course_id',
        'order_id',
        'name',
        'email',
        'phone',
        'occupation',
        'motivation',
        'status',
        'payment_status',
        'notes',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    /** Peserta yang ditambahkan manual admin (bukan dari order). */
    public function isManual(): bool
    {
        return $this->order_id === null;
    }
}
