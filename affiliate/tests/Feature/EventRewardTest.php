<?php

namespace Tests\Feature;

use App\Models\AffiliateEvent;
use App\Models\AffiliateEventParticipant;
use App\Models\AffiliateEventReward;
use App\Models\Affiliator;
use App\Models\ReferralOrder;
use Database\Seeders\AffiliatorTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRewardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AffiliatorTypeSeeder::class);
    }

    private function activeAffiliator(): Affiliator
    {
        return Affiliator::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    // ─── Finalize Tests ───────────────────────────────────────────────

    public function test_finalize_ends_event_and_grants_rewards(): void
    {
        $event = AffiliateEvent::factory()->ended()->create([
            'rewards' => [
                ['rank' => 1, 'reward_type' => 'cash', 'reward_value' => 500000, 'description' => 'Juara 1'],
                ['rank' => 2, 'reward_type' => 'cash', 'reward_value' => 300000, 'description' => 'Juara 2'],
            ],
        ]);

        $affiliator1 = $this->activeAffiliator();
        $affiliator2 = $this->activeAffiliator();

        // Create participants with scores (rank will be set by recomputeEventRanks)
        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator1->id,
            'score' => 10,
        ]);

        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator2->id,
            'score' => 5,
        ]);

        // Create referral orders in the event window to back up scores
        ReferralOrder::factory()->count(10)->create([
            'affiliator_id' => $affiliator1->id,
            'status' => 'paid',
            'ordered_at' => $event->start_date->addDay(),
            'order_total' => 100000,
        ]);

        ReferralOrder::factory()->count(5)->create([
            'affiliator_id' => $affiliator2->id,
            'status' => 'paid',
            'ordered_at' => $event->start_date->addDay(),
            'order_total' => 100000,
        ]);

        $this->artisan('events:finalize')
            ->expectsOutputToContain('Berhasil finalisasi 1 event')
            ->assertExitCode(0);

        $event->refresh();
        $this->assertEquals('ended', $event->status);

        // Rewards should be granted
        $this->assertDatabaseHas('affiliate_event_rewards', [
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator1->id,
            'reward_type' => 'cash',
            'reward_value' => 500000.00,
            'is_claimed' => false,
        ]);

        $this->assertDatabaseHas('affiliate_event_rewards', [
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator2->id,
            'reward_type' => 'cash',
            'reward_value' => 300000.00,
            'is_claimed' => false,
        ]);
    }

    public function test_finalize_is_idempotent(): void
    {
        $event = AffiliateEvent::factory()->ended()->create([
            'rewards' => [
                ['rank' => 1, 'reward_type' => 'cash', 'reward_value' => 500000, 'description' => 'Juara 1'],
            ],
        ]);

        $affiliator = $this->activeAffiliator();

        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator->id,
            'score' => 5,
        ]);

        ReferralOrder::factory()->count(5)->create([
            'affiliator_id' => $affiliator->id,
            'status' => 'paid',
            'ordered_at' => $event->start_date->addDay(),
            'order_total' => 100000,
        ]);

        // Run twice
        $this->artisan('events:finalize')->assertExitCode(0);
        $this->artisan('events:finalize')->assertExitCode(0);

        // Only one reward should exist
        $this->assertEquals(1, AffiliateEventReward::where('affiliate_event_id', $event->id)->count());
    }

    public function test_finalize_does_not_affect_future_events(): void
    {
        $event = AffiliateEvent::factory()->active()->create([
            'rewards' => [
                ['rank' => 1, 'reward_type' => 'cash', 'reward_value' => 500000, 'description' => 'Juara 1'],
            ],
        ]);

        $affiliator = $this->activeAffiliator();

        AffiliateEventParticipant::factory()->create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator->id,
            'score' => 5,
        ]);

        $this->artisan('events:finalize')->assertExitCode(0);

        $event->refresh();
        $this->assertEquals('active', $event->status);
        $this->assertEquals(0, AffiliateEventReward::count());
    }

    // ─── Claim Tests ──────────────────────────────────────────────────

    public function test_affiliator_can_claim_own_reward(): void
    {
        $affiliator = $this->activeAffiliator();

        $reward = AffiliateEventReward::factory()->create([
            'affiliator_id' => $affiliator->id,
            'is_claimed' => false,
            'claimed_at' => null,
        ]);

        $this->actingAs($affiliator, 'affiliator')
            ->post(route('rewards.claim', $reward))
            ->assertRedirect();

        $reward->refresh();
        $this->assertTrue($reward->is_claimed);
        $this->assertNotNull($reward->claimed_at);
    }

    public function test_affiliator_cannot_claim_others_reward(): void
    {
        $affiliator = $this->activeAffiliator();
        $other = $this->activeAffiliator();

        $reward = AffiliateEventReward::factory()->create([
            'affiliator_id' => $other->id,
            'is_claimed' => false,
        ]);

        $this->actingAs($affiliator, 'affiliator')
            ->post(route('rewards.claim', $reward))
            ->assertStatus(403);
    }

    public function test_affiliator_cannot_claim_already_claimed_reward(): void
    {
        $affiliator = $this->activeAffiliator();

        $reward = AffiliateEventReward::factory()->claimed()->create([
            'affiliator_id' => $affiliator->id,
        ]);

        $this->actingAs($affiliator, 'affiliator')
            ->post(route('rewards.claim', $reward))
            ->assertRedirect();

        // Should show error
        $this->actingAs($affiliator, 'affiliator')
            ->post(route('rewards.claim', $reward))
            ->assertSessionHasErrors('reward');

        // claimed_at should not change
        $originalClaimedAt = $reward->claimed_at;
        $reward->refresh();
        $this->assertEquals($originalClaimedAt->timestamp, $reward->claimed_at->timestamp);
    }

    public function test_claim_bonus_commission_creates_available_commission(): void
    {
        $affiliator = $this->activeAffiliator();

        $reward = AffiliateEventReward::factory()->create([
            'affiliator_id' => $affiliator->id,
            'reward_type' => 'bonus_commission',
            'reward_value' => 200000,
            'is_claimed' => false,
            'claimed_at' => null,
        ]);

        $this->actingAs($affiliator, 'affiliator')
            ->post(route('rewards.claim', $reward))
            ->assertRedirect();

        $reward->refresh();
        $this->assertTrue($reward->is_claimed);
        $this->assertNotNull($reward->claimed_at);

        // Bonus commission langsung jadi Commission available tanpa referral_order
        $this->assertDatabaseHas('commissions', [
            'affiliator_id' => $affiliator->id,
            'referral_order_id' => null,
            'amount' => 200000.00,
            'rate_applied' => 0,
            'status' => 'available',
        ]);
    }
}
