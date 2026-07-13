<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Trigger scheduler via HTTP.
 *
 * Dipakai sebagai pengganti cron karena cron daemon Hostinger tidak menjalankan
 * cron jobs akun ini. Dipicu eksternal (GitHub Actions terjadwal) memakai token
 * rahasia (config services.cron_trigger.token = CRON_TRIGGER_TOKEN).
 *
 * KEAMANAN:
 * - Hanya menjalankan command yang di-WHITELIST (self::ALLOWED) — BUKAN command
 *   sembarang, jadi bukan lubang remote-command-execution.
 * - Diproteksi token dibandingkan dengan hash_equals (anti timing attack).
 * - Route di-throttle untuk cegah spam/brute-force.
 * - Command-nya idempotent (aman dijalankan berulang).
 */
class CronTriggerController extends Controller
{
    /** Command scheduler yang boleh dipicu. Whitelist ketat. */
    private const ALLOWED = [
        'commissions:release',
        'events:finalize',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('services.cron_trigger.token');
        $given = (string) $request->header('X-Cron-Token', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $ran = [];
        foreach (self::ALLOWED as $command) {
            Artisan::call($command);
            $ran[] = $command;
        }

        return response()->json(['ok' => true, 'ran' => $ran]);
    }
}
