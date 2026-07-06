<?php

namespace Tests\Feature;

use App\Events\OrderRefunded;
use App\Listeners\DispatchAffiliateOrderRefunded;
use App\Models\Admin;
use App\Models\Order;
use App\Services\Webhook\AffiliateWebhookClient;
use Carbon\Carbon;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test refund order flow: controller action, event dispatch,
 * webhook ke Affiliate system, dan UI refund button.
 */
class OrderRefundedWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    // ─────────────────────────────────────────────────────────────
    // 1. Admin can refund a paid order
    // ─────────────────────────────────────────────────────────────

    public function test_admin_can_refund_paid_order(): void
    {
        Event::fake();

        $order = Order::factory()->create(['status' => 'paid']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.refund', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $order->refresh();
        $this->assertSame('refunded', $order->status);

        Event::assertDispatched(OrderRefunded::class, function (OrderRefunded $event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 2. Admin cannot refund a pending order
    // ─────────────────────────────────────────────────────────────

    public function test_admin_cannot_refund_pending_order(): void
    {
        Event::fake();

        $order = Order::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.refund', $order))
            ->assertStatus(422);

        $order->refresh();
        $this->assertSame('pending', $order->status);

        Event::assertNotDispatched(OrderRefunded::class);
    }

    // ─────────────────────────────────────────────────────────────
    // 3. Admin cannot refund an already refunded order
    // ─────────────────────────────────────────────────────────────

    public function test_admin_cannot_refund_already_refunded_order(): void
    {
        Event::fake();

        $order = Order::factory()->create(['status' => 'refunded']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.refund', $order))
            ->assertStatus(422);

        $order->refresh();
        $this->assertSame('refunded', $order->status);

        Event::assertNotDispatched(OrderRefunded::class);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Refund fires AffiliateWebhookClient with correct payload
    // ─────────────────────────────────────────────────────────────

    public function test_refund_fires_webhook_with_correct_payload(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhook',
            'webhook.secret' => 'test-secret-key',
            'webhook.timeout' => 5,
            'webhook.retries' => 1,
        ]);

        $order = Order::factory()->create([
            'order_number' => 'MFP-REFUND-TEST01',
            'total' => 2500000,
            'status' => 'paid',
            'ref_code' => 'AFF01',
        ]);

        // Call listener directly (like AffiliateWebhookTest pattern)
        $event = new OrderRefunded($order);
        $listener = app(DispatchAffiliateOrderRefunded::class);
        $listener->handle($event);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://affiliate.test/webhook') {
                return false;
            }

            // Verify HMAC signature
            $signature = $request->header('X-Signature')[0] ?? '';
            $body = $request->body();
            $expectedSignature = 'sha256='.hash_hmac('sha256', $body, 'test-secret-key');
            if ($signature !== $expectedSignature) {
                return false;
            }

            $payload = json_decode($body, true);

            // store_order_id HARUS == order_number (kontrak sama dengan order-paid),
            // supaya receiver bisa menemukan referral_order-nya. Ini regression guard BUG-1.
            return $payload['store_order_id'] === 'MFP-REFUND-TEST01'
                && $payload['order_number'] === 'MFP-REFUND-TEST01'
                && ($payload['event'] ?? null) === 'order-refunded'
                && $payload['ref_code'] === 'AFF01'
                && (float) $payload['order_total'] === 2500000.0
                // Bukan sekadar ada — harus string ISO8601 yang valid.
                && is_string($payload['refunded_at'] ?? null)
                && $payload['refunded_at'] !== ''
                && Carbon::hasFormat($payload['refunded_at'], \DateTimeInterface::ATOM)
                // idempotency_key deterministik (bukan berbasis time()).
                && $payload['idempotency_key'] === 'refund-MFP-REFUND-TEST01';
        });
    }

    public function test_refund_does_not_dispatch_webhook_when_order_has_no_ref_code(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhook',
            'webhook.secret' => 'test-secret-key',
            'webhook.retries' => 1,
        ]);

        $order = Order::factory()->create([
            'status' => 'paid',
            'ref_code' => null,
        ]);

        $event = new OrderRefunded($order);
        $listener = app(DispatchAffiliateOrderRefunded::class);
        $listener->handle($event);

        Http::assertNothingSent();
    }

    // ─────────────────────────────────────────────────────────────
    // 5. Non-admin cannot access refund endpoint
    // ─────────────────────────────────────────────────────────────

    public function test_non_admin_cannot_access_refund_endpoint(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $this->post(route('admin.orders.refund', $order))
            ->assertRedirect(route('admin.login'));

        $order->refresh();
        $this->assertSame('paid', $order->status);
    }

    // ─────────────────────────────────────────────────────────────
    // 6. Refund button only shows for refundable statuses
    // ─────────────────────────────────────────────────────────────

    /**
     * @dataProvider refundableStatusProvider
     */
    public function test_refund_button_shows_for_refundable_statuses(string $status): void
    {
        $order = Order::factory()->create(['status' => $status]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Refund Order')
            ->assertSee('form-refund', false);
    }

    /**
     * @dataProvider nonRefundableStatusProvider
     */
    public function test_refund_button_hidden_for_non_refundable_statuses(string $status): void
    {
        $order = Order::factory()->create(['status' => $status]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Refund Order');
    }

    public static function refundableStatusProvider(): array
    {
        return [
            'paid' => ['paid'],
            'partial_paid' => ['partial_paid'],
            'shipped' => ['shipped'],
            'completed' => ['completed'],
        ];
    }

    public static function nonRefundableStatusProvider(): array
    {
        return [
            'pending' => ['pending'],
            'cancelled' => ['cancelled'],
            'refunded' => ['refunded'],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 7. Refund dispatches webhook with 'order-refunded' event type
    // ─────────────────────────────────────────────────────────────

    public function test_refund_dispatches_webhook_with_order_refunded_event_type(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhook',
            'webhook.secret' => 'test-secret-key',
            'webhook.retries' => 1,
        ]);

        $order = Order::factory()->create([
            'status' => 'shipped',
            'ref_code' => 'REF001',
        ]);

        $event = new OrderRefunded($order);
        $listener = app(DispatchAffiliateOrderRefunded::class);
        $listener->handle($event);

        Http::assertSent(function ($request) {
            $eventHeader = $request->header('X-Webhook-Event')[0] ?? '';

            return $eventHeader === 'order-refunded';
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Additional: admin can refund partial_paid, shipped, completed
    // ─────────────────────────────────────────────────────────────

    /**
     * @dataProvider refundableStatusProvider
     */
    public function test_admin_can_refund_all_refundable_statuses(string $status): void
    {
        Event::fake();

        $order = Order::factory()->create(['status' => $status]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.refund', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('refunded', $order->status);

        Event::assertDispatched(OrderRefunded::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Additional: cancelled order cannot be refunded
    // ─────────────────────────────────────────────────────────────

    public function test_admin_cannot_refund_cancelled_order(): void
    {
        $order = Order::factory()->create(['status' => 'cancelled']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.refund', $order))
            ->assertStatus(422);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
    }
}
