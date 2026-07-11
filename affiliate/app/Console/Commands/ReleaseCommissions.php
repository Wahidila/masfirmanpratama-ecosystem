<?php

namespace App\Console\Commands;

use App\Models\Commission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Release komisi yang sudah melewati cooling period.
 * Flip status 'cooling' -> 'available' jika available_at <= sekarang.
 */
class ReleaseCommissions extends Command
{
    /**
     * Signature command artisan.
     *
     * @var string
     */
    protected $signature = 'commissions:release';

    /**
     * Deskripsi command.
     *
     * @var string
     */
    protected $description = 'Release komisi yang sudah melewati cooling period (cooling -> available)';

    /**
     * Jalankan command.
     */
    public function handle(): int
    {
        $released = Commission::where('status', 'cooling')
            ->where('available_at', '<=', now())
            ->update(['status' => 'available']);

        $this->info("Berhasil release {$released} komisi dari cooling ke available.");

        Log::channel('cron')->info('commissions:release selesai', ['released' => $released]);

        return self::SUCCESS;
    }
}
