<?php

namespace App\Services\Gamification;

use App\Models\AffiliateEvent;
use App\Models\AffiliateEventParticipant;
use App\Models\Affiliator;
use App\Models\ReferralOrder;

/**
 * Service untuk menghitung skor dan ranking peserta event gamifikasi.
 *
 * Skor = jumlah ReferralOrder berstatus 'paid' milik affiliator
 * yang ordered_at-nya masuk window event (start_date..end_date inklusif).
 */
class EventScoringService
{
    /**
     * Recompute score untuk semua event aktif yang affiliator ikuti.
     */
    public function recomputeForAffiliator(Affiliator $affiliator): void
    {
        // Ambil semua event aktif yang affiliator sudah join
        $participations = AffiliateEventParticipant::where('affiliator_id', $affiliator->id)
            ->whereHas('event', function ($query) {
                $query->where('status', 'active');
            })
            ->with('event')
            ->get();

        foreach ($participations as $participation) {
            $event = $participation->event;

            // Hitung score: count referral orders paid dalam window event
            $score = ReferralOrder::where('affiliator_id', $affiliator->id)
                ->where('status', 'paid')
                ->whereBetween('ordered_at', [$event->start_date, $event->end_date])
                ->count();

            $participation->update(['score' => $score]);

            // Recompute rank semua participant di event ini
            $this->recomputeEventRanks($event);
        }
    }

    /**
     * Recompute ranking semua participant di sebuah event.
     * Tie-break: score desc, lalu total order_total desc.
     */
    public function recomputeEventRanks(AffiliateEvent $event): void
    {
        // Ambil semua participant event
        $participants = AffiliateEventParticipant::where('affiliate_event_id', $event->id)
            ->get();

        // Hitung total order_total per affiliator untuk tie-break
        $totals = [];
        foreach ($participants as $participant) {
            $totals[$participant->id] = (float) ReferralOrder::where('affiliator_id', $participant->affiliator_id)
                ->where('status', 'paid')
                ->whereBetween('ordered_at', [$event->start_date, $event->end_date])
                ->sum('order_total');
        }

        // Sort: score desc, lalu total order_total desc (tie-break)
        $sorted = $participants->sort(function ($a, $b) use ($totals) {
            if ($a->score !== $b->score) {
                return $b->score <=> $a->score;
            }

            return ($totals[$b->id] ?? 0) <=> ($totals[$a->id] ?? 0);
        })->values();

        // Assign rank 1..N
        $rank = 1;
        foreach ($sorted as $participant) {
            $participant->update(['rank' => $rank]);
            $rank++;
        }
    }
}
