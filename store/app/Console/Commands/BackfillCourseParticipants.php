<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\CourseParticipantSync;
use Illuminate\Console\Command;

/**
 * Isi tabel peserta dari order kelas yang SUDAH ada sebelum modul ini dibuat.
 * Idempotent — aman dijalankan berulang (order_id unique + firstOrNew).
 */
class BackfillCourseParticipants extends Command
{
    protected $signature = 'participants:backfill';

    protected $description = 'Buat peserta kursus dari order kelas lama yang sudah ada pembayaran terverifikasi';

    public function handle(CourseParticipantSync $sync): int
    {
        $synced = 0;
        $skipped = 0;

        Order::whereHas('items', fn ($q) => $q->whereNotNull('course_id'))
            ->orderBy('id')
            ->chunk(100, function ($orders) use ($sync, &$synced, &$skipped) {
                foreach ($orders as $order) {
                    if ($sync->fromOrder($order)) {
                        $synced++;
                    } else {
                        $skipped++;
                    }
                }
            });

        $this->info("Backfill selesai: {$synced} peserta tersinkron, {$skipped} order dilewati (belum ada pembayaran terverifikasi).");

        return self::SUCCESS;
    }
}
