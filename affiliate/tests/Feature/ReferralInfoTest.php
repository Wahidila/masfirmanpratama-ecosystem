<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\AffiliatorType;
use App\Models\ReferralCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralInfoTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-webhook-secret-123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.store_webhook.secret' => $this->secret]);

        $type = AffiliatorType::factory()->create();
        $affiliator = Affiliator::factory()->create([
            'affiliator_type_id' => $type->id,
            'name' => 'Naufal Ulinnuha',
            'status' => 'active',
        ]);
        ReferralCode::factory()->create([
            'affiliator_id' => $affiliator->id,
            'code' => 'HUQJUMKG',
        ]);
    }

    private function sign(string $code): string
    {
        return 'sha256='.hash_hmac('sha256', $code, $this->secret);
    }

    public function test_returns_affiliator_name_with_valid_signature(): void
    {
        $this->withHeaders(['X-Signature' => $this->sign('HUQJUMKG')])
            ->getJson('/referral-info/HUQJUMKG')
            ->assertOk()
            ->assertJson([
                'code' => 'HUQJUMKG',
                'affiliator_name' => 'Naufal Ulinnuha',
                'status' => 'active',
            ]);
    }

    public function test_rejects_invalid_signature(): void
    {
        $this->withHeaders(['X-Signature' => 'sha256=deadbeef'])
            ->getJson('/referral-info/HUQJUMKG')
            ->assertStatus(401);
    }

    public function test_returns_404_for_unknown_code(): void
    {
        $this->withHeaders(['X-Signature' => $this->sign('NOPE')])
            ->getJson('/referral-info/NOPE')
            ->assertStatus(404);
    }
}
