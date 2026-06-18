<?php

namespace Tests\Feature\Admin;

use App\Models\AffiliateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): array
    {
        return ['admin_authenticated' => true, 'admin_email' => 'admin@masfirmanpratama.com'];
    }

    public function test_admin_can_view_events_index(): void
    {
        AffiliateEvent::factory()->count(3)->create();

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.events.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.events.index');
        $response->assertViewHas('events');
    }

    public function test_guest_cannot_access_events_index(): void
    {
        $response = $this->get(route('admin.events.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('admin.events.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.events.create');
    }

    public function test_admin_can_store_event_with_valid_data(): void
    {
        $rewards = [
            ['rank' => 1, 'reward_type' => 'cash', 'reward_value' => 500000, 'description' => 'Juara 1'],
            ['rank' => 2, 'reward_type' => 'voucher', 'reward_value' => 200000, 'description' => 'Juara 2'],
        ];

        $response = $this->withSession($this->adminSession())
            ->post(route('admin.events.store'), [
                'title' => 'Event Test Baru',
                'description' => 'Deskripsi event test',
                'type' => 'challenge',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-15',
                'status' => 'draft',
                'rewards_json' => json_encode($rewards),
            ]);

        $response->assertRedirect(route('admin.events.index'));
        $response->assertSessionHas('success', 'Event berhasil dibuat.');

        $this->assertDatabaseHas('affiliate_events', [
            'title' => 'Event Test Baru',
            'type' => 'challenge',
            'status' => 'draft',
        ]);

        $event = AffiliateEvent::where('title', 'Event Test Baru')->first();
        $this->assertCount(2, $event->rewards);
        $this->assertEquals(1, $event->rewards[0]['rank']);
        $this->assertEquals('cash', $event->rewards[0]['reward_type']);
        $this->assertEquals(500000, $event->rewards[0]['reward_value']);
    }

    public function test_store_fails_with_end_date_before_start_date(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post(route('admin.events.store'), [
                'title' => 'Event Invalid',
                'type' => 'challenge',
                'start_date' => '2026-07-15',
                'end_date' => '2026-07-01',
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('end_date');
        $this->assertDatabaseMissing('affiliate_events', ['title' => 'Event Invalid']);
    }

    public function test_store_fails_with_invalid_rewards_json(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post(route('admin.events.store'), [
                'title' => 'Event Rewards Invalid',
                'type' => 'contest',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-15',
                'status' => 'draft',
                'rewards_json' => 'bukan json valid',
            ]);

        $response->assertSessionHasErrors('rewards_json');
        $this->assertDatabaseMissing('affiliate_events', ['title' => 'Event Rewards Invalid']);
    }

    public function test_admin_can_update_event(): void
    {
        $event = AffiliateEvent::factory()->create([
            'title' => 'Event Lama',
            'type' => 'challenge',
            'status' => 'draft',
        ]);

        $response = $this->withSession($this->adminSession())
            ->put(route('admin.events.update', $event), [
                'title' => 'Event Diperbarui',
                'type' => 'contest',
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-15',
                'status' => 'draft',
                'rewards_json' => '',
            ]);

        $response->assertRedirect(route('admin.events.index'));
        $response->assertSessionHas('success', 'Event berhasil diperbarui.');

        $event->refresh();
        $this->assertEquals('Event Diperbarui', $event->title);
        $this->assertEquals('contest', $event->type);
    }

    public function test_admin_can_delete_event(): void
    {
        $event = AffiliateEvent::factory()->create();

        $response = $this->withSession($this->adminSession())
            ->delete(route('admin.events.destroy', $event));

        $response->assertRedirect(route('admin.events.index'));
        $response->assertSessionHas('success', 'Event berhasil dihapus.');
        $this->assertDatabaseMissing('affiliate_events', ['id' => $event->id]);
    }

    public function test_admin_can_activate_draft_event(): void
    {
        $event = AffiliateEvent::factory()->create(['status' => 'draft']);

        $response = $this->withSession($this->adminSession())
            ->post(route('admin.events.activate', $event));

        $response->assertRedirect(route('admin.events.index'));
        $response->assertSessionHas('success', 'Event berhasil diaktifkan.');

        $event->refresh();
        $this->assertEquals('active', $event->status);
    }

    public function test_activate_fails_for_non_draft_event(): void
    {
        $event = AffiliateEvent::factory()->create(['status' => 'active']);

        $response = $this->withSession($this->adminSession())
            ->post(route('admin.events.activate', $event));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Hanya event berstatus draft yang dapat diaktifkan.');

        $event->refresh();
        $this->assertEquals('active', $event->status);
    }
}
