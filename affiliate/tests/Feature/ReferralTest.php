<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\ReferralCode;
use Database\Seeders\AffiliatorTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
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

    public function test_referral_index_loads(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->actingAs($affiliator, 'affiliator');

        $response = $this->get('/referrals');
        $response->assertStatus(200);
        $response->assertSee('Link Referral');
    }

    public function test_can_create_referral_code(): void
    {
        $affiliator = $this->activeAffiliator();
        $this->actingAs($affiliator, 'affiliator');

        $response = $this->post('/referrals', [
            'label' => 'Instagram Bio',
            'target_url' => null,
        ]);

        $response->assertRedirect(route('referrals.index'));
        $this->assertDatabaseHas('referral_codes', [
            'affiliator_id' => $affiliator->id,
            'label' => 'Instagram Bio',
        ]);
    }

    public function test_can_edit_referral_code(): void
    {
        $affiliator = $this->activeAffiliator();
        $referral = ReferralCode::create([
            'affiliator_id' => $affiliator->id,
            'code' => 'TESTCODE',
            'label' => 'Old Label',
        ]);

        $this->actingAs($affiliator, 'affiliator');

        $response = $this->put("/referrals/{$referral->id}", [
            'label' => 'New Label',
            'target_url' => 'https://masfirmanpratama.com/produk/kelas-amc',
        ]);

        $response->assertRedirect(route('referrals.index'));
        $this->assertDatabaseHas('referral_codes', [
            'id' => $referral->id,
            'label' => 'New Label',
        ]);
    }

    public function test_can_toggle_referral_code(): void
    {
        $affiliator = $this->activeAffiliator();
        $referral = ReferralCode::create([
            'affiliator_id' => $affiliator->id,
            'code' => 'TOGGLE01',
            'is_active' => true,
        ]);

        $this->actingAs($affiliator, 'affiliator');

        $response = $this->post("/referrals/{$referral->id}/toggle");
        $response->assertRedirect(route('referrals.index'));
        $this->assertDatabaseHas('referral_codes', ['id' => $referral->id, 'is_active' => false]);
    }

    public function test_can_delete_referral_code(): void
    {
        $affiliator = $this->activeAffiliator();
        $referral = ReferralCode::create([
            'affiliator_id' => $affiliator->id,
            'code' => 'DELCODE1',
        ]);

        $this->actingAs($affiliator, 'affiliator');

        $response = $this->delete("/referrals/{$referral->id}");
        $response->assertRedirect(route('referrals.index'));
        $this->assertDatabaseMissing('referral_codes', ['id' => $referral->id]);
    }

    public function test_cannot_edit_other_affiliator_referral(): void
    {
        $affiliator = $this->activeAffiliator();
        $other = $this->activeAffiliator();
        $referral = ReferralCode::create([
            'affiliator_id' => $other->id,
            'code' => 'OTHERCD1',
        ]);

        $this->actingAs($affiliator, 'affiliator');

        $response = $this->put("/referrals/{$referral->id}", ['label' => 'hack']);
        $response->assertStatus(403);
    }

    public function test_referral_tracking_sets_cookie_and_redirects(): void
    {
        $affiliator = $this->activeAffiliator();
        $referral = ReferralCode::create([
            'affiliator_id' => $affiliator->id,
            'code' => 'TRACK001',
            'is_active' => true,
        ]);

        $response = $this->get('/ref/TRACK001');
        $response->assertRedirect();
        // Cookie sengaja TIDAK dienkripsi (except di bootstrap/app.php) supaya bisa
        // dibaca app Store yang APP_KEY-nya berbeda → assert sebagai plain cookie.
        $response->assertPlainCookie('referral_code', 'TRACK001');

        $this->assertDatabaseHas('referral_clicks', [
            'referral_code_id' => $referral->id,
        ]);
    }

    public function test_invalid_referral_code_redirects_to_landing(): void
    {
        $response = $this->get('/ref/INVALID1');
        $response->assertRedirect(route('landing'));
    }
}
