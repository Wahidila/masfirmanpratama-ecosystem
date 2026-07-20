<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Services\CourseParticipantSync;

/**
 * Masukkan pembeli kelas ke daftar peserta saat pembayaran diverifikasi.
 *
 * Aturan (lihat CourseParticipantSync):
 * - Order tanpa item kelas → dilewati.
 * - Belum ada pembayaran terverifikasi → TIDAK masuk daftar peserta.
 * - Sudah bayar sebagian (cicilan berjalan) → masuk, payment_status 'cicil'.
 * - Sudah lunas → masuk / diperbarui jadi 'lunas'.
 */
class SyncCourseParticipant
{
    public function __construct(private CourseParticipantSync $sync) {}

    public function handle(PaymentVerified $event): void
    {
        $this->sync->fromOrder($event->order);
    }
}
