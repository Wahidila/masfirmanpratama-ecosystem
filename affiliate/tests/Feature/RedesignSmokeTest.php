<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\AffiliateEvent;
use App\Models\ReferralCode;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedesignSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_affiliator_pages_render(): void
    {
        $aff = Affiliator::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $code = ReferralCode::factory()->create(['affiliator_id' => $aff->id]);
        $event = AffiliateEvent::factory()->create(['status' => 'active']);
        $this->actingAs($aff, 'affiliator');

        $routes = [
            '/dashboard',
            '/referrals', '/referrals/create', "/referrals/{$code->id}/edit",
            '/commissions',
            '/withdrawals', '/withdrawals/create',
            '/materials',
            '/events', "/events/{$event->id}", '/leaderboard', '/rewards',
            '/profile', '/notifications',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertStatus(200);
        }
    }

    public function test_all_admin_pages_render(): void
    {
        $aff = Affiliator::factory()->create(['status' => 'pending']);
        $event = AffiliateEvent::factory()->create();

        $this->post(route('admin.login.submit'), [
            'email' => config('admin.email'),
            'password' => config('admin.password'),
        ]);

        $routes = [
            route('admin.dashboard'),
            route('admin.affiliators.index'),
            route('admin.affiliators.show', $aff),
            route('admin.commissions.index'),
            route('admin.commissions.settings'),
            route('admin.withdrawals.index'),
            route('admin.materials.index'),
            route('admin.materials.create'),
            route('admin.events.index'),
            route('admin.events.create'),
            route('admin.events.edit', $event),
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertStatus(200);
        }
    }
}
