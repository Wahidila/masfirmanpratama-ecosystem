<?php

namespace Tests\Feature\Shipping;

use App\Events\OrderShipped;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwbCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_LICENSE = 'test-license-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['shipping.license' => self::TEST_LICENSE]);
    }

    private function signPayload(array $payload): string
    {
        return hash('sha256', self::TEST_LICENSE.json_encode($payload));
    }

    private function headersWithSignature(array $payload): array
    {
        return ['AW-Signature' => $this->signPayload($payload)];
    }

    public function test_missing_signature_returns_401(): void
    {
        $response = $this->postJson(route('webhooks.agenwebsite.awb'), [
            'order_id' => 'ORD-001',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Missing signature',
            ]);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $response = $this->postJson(route('webhooks.agenwebsite.awb'), [
            'order_id' => 'ORD-001',
        ], ['AW-Signature' => 'invalid-signature']);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid signature',
            ]);
    }

    public function test_success_callback_updates_awb_and_dispatches_event(): void
    {
        Event::fake();

        $order = Order::factory()->create([
            'fulfillment_api_order_id' => 'ORD-AWB-001',
            'fulfillment_reference_id' => 'REF-AWB-001',
            'status' => 'paid',
            'fulfillment_status' => 'waiting_awb',
        ]);

        $payload = [
            'status' => null,
            'airwaybill' => 'AGN123456789',
            'order_id' => 'ORD-AWB-001',
            'reference_id' => 'REF-AWB-001',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload)
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'AWB updated',
            ]);

        $order->refresh();

        $this->assertSame('AGN123456789', $order->shipping_resi);
        $this->assertSame('shipped', $order->fulfillment_status);
        $this->assertSame('shipped', $order->status);
        $this->assertNotNull($order->shipped_at);
        $this->assertNull($order->tracking_status);

        Event::assertDispatched(OrderShipped::class, function (OrderShipped $event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    public function test_success_callback_looks_up_by_reference_id(): void
    {
        $order = Order::factory()->create([
            'fulfillment_api_order_id' => null,
            'fulfillment_reference_id' => 'REF-ONLY-001',
            'status' => 'paid',
            'fulfillment_status' => 'waiting_awb',
        ]);

        $payload = [
            'status' => null,
            'airwaybill' => 'AGN987654321',
            'order_id' => 'ORD-UNKNOWN',
            'reference_id' => 'REF-ONLY-001',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload)
        );

        $response->assertOk();

        $order->refresh();
        $this->assertSame('AGN987654321', $order->shipping_resi);
        $this->assertSame('shipped', $order->fulfillment_status);
    }

    public function test_order_not_found_returns_404(): void
    {
        $payload = [
            'status' => null,
            'airwaybill' => 'AGN123456789',
            'order_id' => 'ORD-NONEXISTENT',
            'reference_id' => 'REF-NONEXISTENT',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload)
        );

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);
    }

    public function test_failed_callback_clears_fulfillment_fields(): void
    {
        $order = Order::factory()->create([
            'fulfillment_api_order_id' => 'ORD-FAIL-001',
            'fulfillment_reference_id' => 'REF-FAIL-001',
            'fulfillment_status' => 'waiting_awb',
            'shipping_resi' => 'AGN-OLD-RESI',
            'label_url' => 'https://label.example.com/old',
        ]);

        $payload = [
            'status' => 'failed',
            'order_id' => 'ORD-FAIL-001',
            'reference_id' => 'REF-FAIL-001',
            'error_message' => 'Invalid destination address',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload)
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Failure recorded',
            ]);

        $order->refresh();

        $this->assertSame('failed', $order->fulfillment_status);
        $this->assertNull($order->shipping_resi);
        $this->assertNull($order->fulfillment_api_order_id);
        $this->assertNull($order->fulfillment_reference_id);
        $this->assertNull($order->label_url);
    }

    public function test_status_update_normalizes_tracking_status(): void
    {
        $order = Order::factory()->create([
            'fulfillment_api_order_id' => 'ORD-STATUS-001',
            'fulfillment_reference_id' => 'REF-STATUS-001',
            'status' => 'shipped',
            'tracking_status' => null,
        ]);

        $payload = [
            'status' => 'status_update',
            'order_id' => 'ORD-STATUS-001',
            'reference_id' => 'REF-STATUS-001',
            'tracking_status' => 'IN_TRANSIT',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload)
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Status updated',
            ]);

        $order->refresh();

        $this->assertSame('in_transit', $order->tracking_status);
        $this->assertSame('shipped', $order->status);
    }

    public function test_status_update_with_delivered_completes_order(): void
    {
        $order = Order::factory()->create([
            'fulfillment_api_order_id' => 'ORD-DELIVERED-001',
            'fulfillment_reference_id' => 'REF-DELIVERED-001',
            'status' => 'shipped',
            'fulfillment_status' => 'shipped',
            'tracking_status' => null,
        ]);

        $payload = [
            'status' => 'status_update',
            'order_id' => 'ORD-DELIVERED-001',
            'reference_id' => 'REF-DELIVERED-001',
            'tracking_status' => 'DELIVERED',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload)
        );

        $response->assertOk();

        $order->refresh();

        $this->assertSame('delivered', $order->tracking_status);
        $this->assertSame('completed', $order->status);
        // FIX-9: fulfillment_status sebelumnya stuck di 'shipped' walau paket
        // sudah delivered → admin filter "delivered" return kosong.
        $this->assertSame('delivered', $order->fulfillment_status);
    }

    public function test_lookup_with_only_order_number_works(): void
    {
        // FIX-5: order_number sebagai fallback ketiga selain api_order_id
        // dan reference_id.
        $order = Order::factory()->create([
            'fulfillment_api_order_id' => null,
            'fulfillment_reference_id' => null,
            'order_number' => 'MFP-20260701-ABC123',
            'status' => 'paid',
            'fulfillment_status' => 'waiting_awb',
        ]);

        $payload = [
            'status' => null,
            'airwaybill' => 'AGN-VIA-ORDER-NUMBER',
            'order_number' => 'MFP-20260701-ABC123',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload),
        );

        $response->assertOk();
        $order->refresh();
        $this->assertSame('AGN-VIA-ORDER-NUMBER', $order->shipping_resi);
    }

    public function test_partial_payload_does_not_match_random_null_order(): void
    {
        // FIX-5 inti: dulu where(api_order_id,null)->orWhere(reference_id,'REF-X')
        // menjadikan order LAIN yang punya api_order_id=null cocok kalau
        // reference_id juga sama-sama null. Sekarang clause hanya ditambah untuk
        // identifier non-null.
        Order::factory()->create([
            'fulfillment_api_order_id' => null,
            'fulfillment_reference_id' => null,
            'status' => 'paid',
        ]);

        $payload = [
            'status' => null,
            'airwaybill' => 'AGN-SHOULD-NOT-LAND',
            'reference_id' => 'REF-NONEXISTENT-12345',
            // order_id sengaja tidak dikirim
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload),
        );

        // Tidak ada order yang punya reference_id ini → 404, bukan random match.
        $response->assertStatus(404);
    }

    public function test_payload_without_any_identifier_returns_400(): void
    {
        $payload = ['status' => null, 'airwaybill' => 'AGN-NOPE'];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload),
        );

        $response->assertStatus(400);
    }

    public function test_status_update_with_delivered_case_insensitive(): void
    {
        $order = Order::factory()->create([
            'fulfillment_api_order_id' => 'ORD-DELIVERED-002',
            'fulfillment_reference_id' => 'REF-DELIVERED-002',
            'status' => 'shipped',
        ]);

        $payload = [
            'status' => 'status_update',
            'order_id' => 'ORD-DELIVERED-002',
            'reference_id' => 'REF-DELIVERED-002',
            'tracking_status' => 'Package Delivered Successfully',
        ];

        $response = $this->postJson(
            route('webhooks.agenwebsite.awb'),
            $payload,
            $this->headersWithSignature($payload)
        );

        $response->assertOk();

        $order->refresh();
        $this->assertSame('completed', $order->status);
    }
}
