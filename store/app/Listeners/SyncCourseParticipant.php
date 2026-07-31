<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Services\CourseParticipantSync;

/**
 * Masukkan pembeli kelas ke daftar peserta saat pembayaran diverifikasi.
 *
 * Aturan (lihat CourseParticipantSync):
 * - Order tanpa item kelas → dilewati.
 * - Belum lunas (belum bayar / cicilan berjalan) → TIDAK masuk daftar peserta.
 * - Sudah lunas (total terbayar penuh) → masuk sebagai peserta 'lunas'.
 */
class SyncCourseParticipant
{
    public function __construct(private CourseParticipantSync $sync) {}

    public function handle(PaymentVerified $event): void
    {
        $this->sync->fromOrder($event->order);
    }
}
