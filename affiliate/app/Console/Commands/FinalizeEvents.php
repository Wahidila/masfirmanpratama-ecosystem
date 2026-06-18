<?php

namespace App\Console\Commands;

use App\Models\AffiliateEvent;
use App\Models\AffiliateEventReward;
use App\Services\Gamification\EventScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finalisasi event yang sudah melewati end_date.
 * Grant reward ke pemenang berdasarkan ranking final.
 */
class FinalizeEvents extends Command
{
    protected $signature = 'events:finalize';

    protected $description = 'Finalisasi event aktif yang sudah melewati end_date, grant reward ke pemenang';

    public function handle(EventScoringService $scoringService): int
    {
        $events = AffiliateEvent::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();

        $totalFinalized = 0;
        $totalRewardsGranted = 0;

        foreach ($events as $event) {
            DB::transaction(function () use ($event, $scoringService, &$totalFinalized, &$totalRewardsGranted) {
                // Final ranking recompute
                $scoringService->recomputeEventRanks($event);

                // Grant rewards
                $rewards = $event->rewards ?? [];

                foreach ($rewards as $rewardRule) {
                    $rank = $rewardRule['rank'] ?? null;
                    $rewardType = $rewardRule['reward_type'] ?? 'cash';
                    $rewardValue = $rewardRule['reward_value'] ?? 0;
                    $description = $rewardRule['description'] ?? null;

                    if ($rank === null) {
                        continue;
                    }

                    // Cari participant dengan rank ini
                    $participant = $event->participants()
                        ->where('rank', $rank)
                        ->first();

                    if (! $participant) {
                        continue;
                    }

                    // Idempotent: skip kalau reward untuk (event, affiliator, rank/reward_type) sudah ada
                    $exists = AffiliateEventReward::where('affiliate_event_id', $event->id)
                        ->where('affiliator_id', $participant->affiliator_id)
                        ->where('reward_type', $rewardType)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    AffiliateEventReward::create([
                        'affiliate_event_id' => $event->id,
                        'affiliator_id' => $participant->affiliator_id,
                        'reward_type' => $rewardType,
                        'reward_value' => $rewardValue,
                        'description' => $description,
                        'is_claimed' => false,
                    ]);

                    $totalRewardsGranted++;
                }

                // Mark event as ended
                $event->update(['status' => 'ended']);
                $totalFinalized++;
            });
        }

        $this->info("Berhasil finalisasi {$totalFinalized} event, {$totalRewardsGranted} reward diberikan.");

        return self::SUCCESS;
    }
}
