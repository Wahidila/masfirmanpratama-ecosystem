<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use App\Models\AffiliatorType;
use App\Models\Commission;
use App\Models\CommissionSetting;
use App\Models\ReferralCode;
use App\Models\ReferralOrder;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StoreWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-webhook-secret-123';

    private AffiliatorType $affiliatorType;

    private Affiliator $affiliator;

    private ReferralCode $referralCode;

    protected function setUp(): void
    {
        parent::setUp();

        // Set secret di config
        config(['services.store_webhook.secret' => $this->secret]);

        // Buat affiliator type + affiliator + referral code
        $this->affiliatorType = AffiliatorType::factory()->create();
        $this->affiliator = Affiliator::factory()->create([
            'affiliator_type_id' => $this->affiliatorType->id,
        ]);
        $this->referralCode = ReferralCode::factory()->create([
            'affiliator_id' => $this->affiliator->id,
            'code' => 'FIRMAN123',
        ]);

        // Buat commission setting global (rate 10%, cooling 7 hari, min_amount 0)
        CommissionSetting::factory()->create([
            'affiliator_type_id' => $this->affiliatorType->id,
            'product_type' => null,
            'rate' => 10.00,
            'min_amount' => 0,
            'cooling_days' => 7,
            'is_active' => true,
        ]);
    }

    /**
     * Helper: sign payload dengan HMAC-SHA256.
     */
    private function signPayload(string $rawBody): string
    {
        return 'sha256='.hash_hmac('sha256', $rawBody, $this->secret);
    }

    /**
     * Helper: kirim webhook request.
     */
    private function sendWebhook(array $payload, ?string $signature = null, string $event = 'order-paid'): TestResponse
    {
        $rawBody = json_encode($payload);
        $signature = $signature ?? $this->signPayload($rawBody);

        return $this->call(
            'POST',
            route('webhooks.store'),
            [],
            [],
            [],
            [
                'HTTP_X_SIGNATURE' => $signature,
                'HTTP_X_WEBHOOK_EVENT' => $event,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );
    }

    /**
     * Helper: buat payload order-paid standar.
     */
    private function makeOrderPaidPayload(array $overrides = []): array
    {
        return array_merge([
            'event' => 'order-paid',
            'store_order_id' => 'ORD-'.uniqid(),
            'ref_code' => 'FIRMAN123',
            'buyer_name' => 'John Doe',
            'buyer_email' => 'buyer@example.com',
            'order_total' => 500000,
            'product_type' => 'course',
            'ordered_at' => now()->toIso8601String(),
            'idempotency_key' => 'idem-'.uniqid(),
        ], $overrides);
    }

    /** @test */
    public function valid_signature_creates_referral_order_and_commission(): void
    {
        $payload = $this->makeOrderPaidPayload([
            'store_order_id' => 'ORD-001',
            'order_total' => 500000,
        ]);

        $response = $this->sendWebhook($payload);

        $response->assertStatus(200);

        // Referral order dibuat dengan status paid
        $this->assertDatabaseHas('referral_orders', [
            'store_order_id' => 'ORD-001',
            'affiliator_id' => $this->affiliator->id,
            'buyer_name' => 'John Doe',
            'buyer_email' => 'buyer@example.com',
            'order_total' => 500000,
            'status' => 'paid',
        ]);

        // Commission dibuat dengan status cooling
        $commission = Commission::where('affiliator_id', $this->affiliator->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals('cooling', $commission->status);
        $this->assertEquals('10.00', $commission->rate_applied);
        // amount = 500000 * 10/100 = 50000
        $this->assertEquals('50000.00', $commission->amount);
        // available_at kira-kira 7 hari dari sekarang
        $this->assertTrue($commission->available_at->isFuture());
        $this->assertTrue(now()->diffInDays($commission->available_at, false) >= 6);

        // Webhook log processed
        $this->assertDatabaseHas('webhook_logs', [
            'event_type' => 'order-paid',
            'status' => 'processed',
        ]);
    }

    /** @test */
    public function invalid_signature_returns_401_and_logs(): void
    {
        $payload = $this->makeOrderPaidPayload();
        $invalidSignature = 'sha256=invalid-signature-here';

        $response = $this->sendWebhook($payload, $invalidSignature);

        $response->assertStatus(401);

        // Webhook log invalid_signature
        $this->assertDatabaseHas('webhook_logs', [
            'status' => 'invalid_signature',
        ]);

        // Tidak ada commission yang dibuat
        $this->assertDatabaseCount('commissions', 0);
        $this->assertDatabaseCount('referral_orders', 0);
    }

    /** @test */
    public function duplicate_store_order_id_is_idempotent(): void
    {
        $payload = $this->makeOrderPaidPayload([
            'store_order_id' => 'ORD-DUPLICATE',
        ]);

        // Kirim pertama kali
        $response1 = $this->sendWebhook($payload);
        $response1->assertStatus(200);

        // Kirim kedua kali (duplicate)
        $response2 = $this->sendWebhook($payload);
        $response2->assertStatus(200);

        // Hanya 1 referral order dan 1 commission
        $this->assertDatabaseCount('referral_orders', 1);
        $this->assertDatabaseCount('commissions', 1);
    }

    /** @test */
    public function unknown_referral_code_returns_200_and_logs_failed(): void
    {
        $payload = $this->makeOrderPaidPayload([
            'ref_code' => 'NONEXISTENT-CODE',
        ]);

        $response = $this->sendWebhook($payload);

        $response->assertStatus(200);

        // Webhook log failed
        $this->assertDatabaseHas('webhook_logs', [
            'status' => 'failed',
            'error_message' => 'Referral code not found: NONEXISTENT-CODE',
        ]);

        // Tidak ada referral order atau commission
        $this->assertDatabaseCount('referral_orders', 0);
        $this->assertDatabaseCount('commissions', 0);
    }

    /** @test */
    public function order_total_below_min_amount_skips_commission(): void
    {
        // Update commission setting: min_amount = 100000
        CommissionSetting::query()->update(['min_amount' => 100000]);

        $payload = $this->makeOrderPaidPayload([
            'store_order_id' => 'ORD-SMALL',
            'order_total' => 50000, // Di bawah min_amount
        ]);

        $response = $this->sendWebhook($payload);

        $response->assertStatus(200);

        // Referral order tetap dibuat
        $this->assertDatabaseHas('referral_orders', [
            'store_order_id' => 'ORD-SMALL',
            'status' => 'paid',
        ]);

        // Tapi commission TIDAK dibuat
        $this->assertDatabaseCount('commissions', 0);

        // Webhook log processed (bukan failed)
        $this->assertDatabaseHas('webhook_logs', [
            'event_type' => 'order-paid',
            'status' => 'processed',
        ]);
    }

    /** @test */
    public function self_referral_is_blocked_no_order_and_no_commission(): void
    {
        // Pembeli memakai email yang sama dengan affiliator → self-referral.
        $payload = $this->makeOrderPaidPayload([
            'store_order_id' => 'ORD-SELF',
            'buyer_email' => $this->affiliator->email,
        ]);

        $response = $this->sendWebhook($payload);

        $response->assertStatus(200);

        // Tidak ada referral order maupun commission yang dibuat.
        $this->assertDatabaseCount('referral_orders', 0);
        $this->assertDatabaseCount('commissions', 0);

        // Webhook log processed dengan catatan self-referral.
        $log = WebhookLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('processed', $log->status);
        $this->assertStringContainsString('Self-referral', $log->error_message);
    }

    /** @test */
    public function self_referral_detection_is_case_insensitive(): void
    {
        $payload = $this->makeOrderPaidPayload([
            'store_order_id' => 'ORD-SELF-CASE',
            'buyer_email' => strtoupper($this->affiliator->email),
        ]);

        $response = $this->sendWebhook($payload);

        $response->assertStatus(200);
        $this->assertDatabaseCount('referral_orders', 0);
        $this->assertDatabaseCount('commissions', 0);

        // Pastikan skip memang karena deteksi self-referral (bukan early-return lain).
        $log = WebhookLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('processed', $log->status);
        $this->assertStringContainsString('Self-referral', $log->error_message);
    }

    /** @test */
    public function missing_buyer_email_withholds_commission(): void
    {
        // Tanpa email pembeli, receiver tidak bisa memastikan bukan self-referral →
        // fail-closed: order & komisi di-skip, dicatat untuk tinjauan manual.
        $payload = $this->makeOrderPaidPayload([
            'store_order_id' => 'ORD-NO-EMAIL',
            'buyer_email' => null,
        ]);

        $response = $this->sendWebhook($payload);

        $response->assertStatus(200);
        $this->assertDatabaseCount('referral_orders', 0);
        $this->assertDatabaseCount('commissions', 0);

        $log = WebhookLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('processed', $log->status);
        $this->assertStringContainsString('withheld', $log->error_message);
    }

    /** @test */
    public function order_refunded_cancels_cooling_and_available_commissions(): void
    {
        // Setup: buat referral order + 3 commission (cooling, available, withdrawn)
        $referralOrder = ReferralOrder::create([
            'referral_code_id' => $this->referralCode->id,
            'affiliator_id' => $this->affiliator->id,
            'store_order_id' => 'ORD-REFUND-TEST',
            'buyer_name' => 'Jane Doe',
            'order_total' => 500000,
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        $coolingCommission = Commission::create([
            'affiliator_id' => $this->affiliator->id,
            'referral_order_id' => $referralOrder->id,
            'amount' => 50000,
            'rate_applied' => 10,
            'status' => 'cooling',
            'available_at' => now()->addDays(7),
        ]);

        $availableCommission = Commission::create([
            'affiliator_id' => $this->affiliator->id,
            'referral_order_id' => $referralOrder->id,
            'amount' => 50000,
            'rate_applied' => 10,
            'status' => 'available',
            'available_at' => now()->subDay(),
        ]);

        $withdrawnCommission = Commission::create([
            'affiliator_id' => $this->affiliator->id,
            'referral_order_id' => $referralOrder->id,
            'amount' => 50000,
            'rate_applied' => 10,
            'status' => 'withdrawn',
            'available_at' => now()->subDays(10),
            'withdrawn_at' => now()->subDays(3),
        ]);

        // Kirim order-refunded
        $payload = [
            'event' => 'order-refunded',
            'store_order_id' => 'ORD-REFUND-TEST',
            'idempotency_key' => 'idem-refund-'.uniqid(),
        ];

        $response = $this->sendWebhook($payload, event: 'order-refunded');

        $response->assertStatus(200);

        // Referral order status refunded
        $this->assertDatabaseHas('referral_orders', [
            'store_order_id' => 'ORD-REFUND-TEST',
            'status' => 'refunded',
        ]);

        // Cooling dan available → cancelled
        $this->assertEquals('cancelled', $coolingCommission->fresh()->status);
        $this->assertEquals('cancelled', $availableCommission->fresh()->status);

        // Withdrawn TETAP withdrawn (tidak di-cancel)
        $this->assertEquals('withdrawn', $withdrawnCommission->fresh()->status);
    }

    /** @test */
    public function commissions_release_command_flips_cooling_to_available(): void
    {
        // Buat referral order dummy untuk FK constraint
        $referralOrder = ReferralOrder::create([
            'referral_code_id' => $this->referralCode->id,
            'affiliator_id' => $this->affiliator->id,
            'store_order_id' => 'ORD-RELEASE-TEST',
            'buyer_name' => 'Test Buyer',
            'order_total' => 500000,
            'status' => 'paid',
            'ordered_at' => now(),
        ]);

        // Buat commission cooling yang sudah lewat available_at
        $expiredCooling = Commission::create([
            'affiliator_id' => $this->affiliator->id,
            'referral_order_id' => $referralOrder->id,
            'amount' => 50000,
            'rate_applied' => 10,
            'status' => 'cooling',
            'available_at' => now()->subDay(), // Sudah lewat
        ]);

        // Buat commission cooling yang belum lewat
        $futureCooling = Commission::create([
            'affiliator_id' => $this->affiliator->id,
            'referral_order_id' => $referralOrder->id,
            'amount' => 30000,
            'rate_applied' => 10,
            'status' => 'cooling',
            'available_at' => now()->addDays(5), // Belum lewat
        ]);

        // Jalankan command
        $this->artisan('commissions:release')
            ->expectsOutputToContain('1')
            ->assertExitCode(0);

        // Yang sudah lewat → available
        $this->assertEquals('available', $expiredCooling->fresh()->status);

        // Yang belum lewat → tetap cooling
        $this->assertEquals('cooling', $futureCooling->fresh()->status);
    }
}
