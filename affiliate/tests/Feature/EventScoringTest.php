<?php

namespace Tests\Feature;

use App\Models\AffiliateEvent;
use App\Models\AffiliateEventParticipant;
use App\Models\Affiliator;
use App\Models\ReferralCode;
use App\Models\ReferralOrder;
use App\Services\Gamification\EventScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventScoringTest extends TestCase
{
    use RefreshDatabase;

    private EventScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventScoringService::class);
    }

    public function test_join_event_score_starts_at_zero(): void
    {
        $affiliator = Affiliator::factory()->create();
        $event = AffiliateEvent::factory()->active()->create();

        $participant = AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator->id,
        ]);

        $this->assertEquals(0, $participant->score);
        $this->assertNull($participant->rank);
    }

    public function test_two_paid_orders_in_window_gives_score_two_rank_one(): void
    {
        $affiliator = Affiliator::factory()->create();
        $referralCode = ReferralCode::factory()->create(['affiliator_id' => $affiliator->id]);
        $event = AffiliateEvent::factory()->active()->create();

        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator->id,
        ]);

        // 2 paid orders dalam window event
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliator->id,
            'referral_code_id' => $referralCode->id,
            'ordered_at' => now(),
            'order_total' => 500000,
        ]);
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliator->id,
            'referral_code_id' => $referralCode->id,
            'ordered_at' => now()->subDays(1),
            'order_total' => 300000,
        ]);

        $this->service->recomputeForAffiliator($affiliator);

        $participant = AffiliateEventParticipant::where('affiliator_id', $affiliator->id)
            ->where('affiliate_event_id', $event->id)
            ->first();

        $this->assertEquals(2, $participant->score);
        $this->assertEquals(1, $participant->rank);
    }

    public function test_order_outside_window_not_counted(): void
    {
        $affiliator = Affiliator::factory()->create();
        $referralCode = ReferralCode::factory()->create(['affiliator_id' => $affiliator->id]);

        // Event window: 7 hari lalu sampai 7 hari ke depan
        $event = AffiliateEvent::factory()->active()->create([
            'start_date' => now()->subDays(7),
            'end_date' => now()->addDays(7),
        ]);

        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator->id,
        ]);

        // 1 order dalam window
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliator->id,
            'referral_code_id' => $referralCode->id,
            'ordered_at' => now(),
            'order_total' => 500000,
        ]);

        // 1 order DI LUAR window (setelah end_date)
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliator->id,
            'referral_code_id' => $referralCode->id,
            'ordered_at' => now()->addDays(10),
            'order_total' => 300000,
        ]);

        $this->service->recomputeForAffiliator($affiliator);

        $participant = AffiliateEventParticipant::where('affiliator_id', $affiliator->id)
            ->where('affiliate_event_id', $event->id)
            ->first();

        $this->assertEquals(1, $participant->score);
    }

    public function test_refund_decreases_score(): void
    {
        $affiliator = Affiliator::factory()->create();
        $referralCode = ReferralCode::factory()->create(['affiliator_id' => $affiliator->id]);
        $event = AffiliateEvent::factory()->active()->create();

        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator->id,
        ]);

        // 2 paid orders
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliator->id,
            'referral_code_id' => $referralCode->id,
            'ordered_at' => now(),
            'order_total' => 500000,
        ]);
        $orderToRefund = ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliator->id,
            'referral_code_id' => $referralCode->id,
            'ordered_at' => now()->subDays(1),
            'order_total' => 300000,
        ]);

        $this->service->recomputeForAffiliator($affiliator);

        $participant = AffiliateEventParticipant::where('affiliator_id', $affiliator->id)
            ->where('affiliate_event_id', $event->id)
            ->first();
        $this->assertEquals(2, $participant->score);

        // Refund 1 order
        $orderToRefund->update(['status' => 'refunded']);

        $this->service->recomputeForAffiliator($affiliator);

        $participant->refresh();
        $this->assertEquals(1, $participant->score);
    }

    public function test_two_affiliators_ranked_correctly_with_tiebreak(): void
    {
        $affiliatorA = Affiliator::factory()->create();
        $affiliatorB = Affiliator::factory()->create();
        $codeA = ReferralCode::factory()->create(['affiliator_id' => $affiliatorA->id]);
        $codeB = ReferralCode::factory()->create(['affiliator_id' => $affiliatorB->id]);

        $event = AffiliateEvent::factory()->active()->create();

        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliatorA->id,
        ]);
        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliatorB->id,
        ]);

        // AffiliatorA: 2 orders, total 800k
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliatorA->id,
            'referral_code_id' => $codeA->id,
            'ordered_at' => now(),
            'order_total' => 500000,
        ]);
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliatorA->id,
            'referral_code_id' => $codeA->id,
            'ordered_at' => now()->subDays(1),
            'order_total' => 300000,
        ]);

        // AffiliatorB: 2 orders, total 1.2M (tie score, higher total → rank 1)
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliatorB->id,
            'referral_code_id' => $codeB->id,
            'ordered_at' => now(),
            'order_total' => 700000,
        ]);
        ReferralOrder::factory()->paid()->create([
            'affiliator_id' => $affiliatorB->id,
            'referral_code_id' => $codeB->id,
            'ordered_at' => now()->subDays(2),
            'order_total' => 500000,
        ]);

        $this->service->recomputeForAffiliator($affiliatorA);
        $this->service->recomputeForAffiliator($affiliatorB);

        $participantA = AffiliateEventParticipant::where('affiliator_id', $affiliatorA->id)
            ->where('affiliate_event_id', $event->id)
            ->first();
        $participantB = AffiliateEventParticipant::where('affiliator_id', $affiliatorB->id)
            ->where('affiliate_event_id', $event->id)
            ->first();

        // Keduanya score 2, tapi B punya total lebih besar → rank 1
        $this->assertEquals(2, $participantA->score);
        $this->assertEquals(2, $participantB->score);
        $this->assertEquals(1, $participantB->rank);
        $this->assertEquals(2, $participantA->rank);
    }

    public function test_affiliator_not_joined_event_has_no_participant_row(): void
    {
        $affiliator = Affiliator::factory()->create();
        $event = AffiliateEvent::factory()->active()->create();

        // Affiliator TIDAK join event — tidak ada AffiliateEventParticipant

        $this->service->recomputeForAffiliator($affiliator);

        $participant = AffiliateEventParticipant::where('affiliator_id', $affiliator->id)
            ->where('affiliate_event_id', $event->id)
            ->first();

        $this->assertNull($participant);
    }
}
