<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Opsi B — cek non-blocking apakah resi manual sudah terdeteksi di sistem kurir
 * lewat endpoint tracking Agenwebsite. Tidak menolak input; hanya indikator +
 * refresh tracking_status.
 */
class CheckResiTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['shipping.license' => 'test-license-key']);
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_requires_authentication(): void
    {
        $order = Order::factory()->create([
            'status' => 'shipped',
            'shipping_courier' => 'jne',
            'shipping_resi' => 'JNE1234567890',
        ]);

        $this->post(route('admin.orders.check-resi', $order))
            ->assertRedirect(route('admin.login'));
    }

    public function test_returns_422_when_order_has_no_resi(): void
    {
        $order = Order::factory()->create([
            'status' => 'paid',
            'shipping_courier' => null,
            'shipping_resi' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.check-resi', $order))
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'detected' => false]);
    }

    public function test_detected_true_and_refreshes_tracking_status(): void
    {
        Http::fake([
            '*/shipping/tracking' => Http::response([
                'data' => [
                    'history' => [
                        ['date' => '2026-06-30 10:00', 'description' => 'Diterima di agen', 'location' => 'Surabaya'],
                        ['date' => '2026-07-01 09:00', 'description' => 'Dalam pengiriman', 'location' => 'Jakarta'],
                    ],
                ],
                'message' => 'OK',
            ], 200),
        ]);

        $order = Order::factory()->create([
            'status' => 'shipped',
            'shipping_courier' => 'jne',
            'shipping_resi' => 'JNE-DETECTED-1',
            'tracking_status' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.check-resi', $order))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'detected' => true,
                'status' => 'Dalam pengiriman', // status terbaru = row terakhir
            ]);

        // Order manual tak dapat callback → status terbaru ikut tersimpan.
        $this->assertSame('Dalam pengiriman', $order->fresh()->tracking_status);
    }

    public function test_detected_false_when_history_empty_and_does_not_touch_status(): void
    {
        Http::fake([
            '*/shipping/tracking' => Http::response(['data' => [], 'message' => 'Not found'], 200),
        ]);

        $order = Order::factory()->create([
            'status' => 'shipped',
            'shipping_courier' => 'jne',
            'shipping_resi' => 'JNE-UNKNOWN-9',
            'tracking_status' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.check-resi', $order))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'detected' => false,
            ]);

        // Belum terdeteksi → status tidak dipaksa berubah (bukan hard-block).
        $this->assertNull($order->fresh()->tracking_status);
    }

    public function test_api_error_is_handled_gracefully_as_not_detected(): void
    {
        Http::fake([
            '*/shipping/tracking' => Http::response(['message' => 'Server error'], 500),
        ]);

        $order = Order::factory()->create([
            'status' => 'shipped',
            'shipping_courier' => 'jne',
            'shipping_resi' => 'JNE-ERR-5',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.check-resi', $order))
            ->assertOk()
            ->assertJson(['ok' => true, 'detected' => false]);
    }

    /**
     * Auto-complete: status kurir "delivered" → order 'shipped' otomatis ditutup
     * ke 'completed' (setara webhook AWB, tapi untuk resi manual yang tak dapat
     * callback). WA terima kasih ke pembeli ikut ter-queue.
     */
    public function test_auto_completes_when_courier_reports_delivered(): void
    {
        Http::fake([
            '*/shipping/tracking' => Http::response([
                'data' => [
                    'history' => [
                        ['date' => '2026-07-01 09:00', 'description' => 'Dalam pengiriman'],
                        ['date' => '2026-07-02 14:00', 'description' => 'DELIVERED - diterima ybs'],
                    ],
                ],
                'message' => 'OK',
            ], 200),
        ]);

        $order = Order::factory()->create([
            'status' => 'shipped',
            'phone' => '628123456789',
            'shipping_courier' => 'jne',
            'shipping_resi' => 'JNE-DLVR-1',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.check-resi', $order))
            ->assertOk()
            ->assertJson(['ok' => true, 'detected' => true, 'completed' => true]);

        $fresh = $order->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertSame('delivered', $fresh->fulfillment_status);
        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_order_completed',
        ]);
    }

    /** Status kurir belum delivered → hanya refresh tracking, TIDAK menyelesaikan. */
    public function test_does_not_complete_when_not_delivered(): void
    {
        Http::fake([
            '*/shipping/tracking' => Http::response([
                'data' => ['history' => [
                    ['date' => '2026-07-01 09:00', 'description' => 'Dalam pengiriman'],
                ]],
                'message' => 'OK',
            ], 200),
        ]);

        $order = Order::factory()->create([
            'status' => 'shipped',
            'shipping_courier' => 'jne',
            'shipping_resi' => 'JNE-ONWAY-1',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.check-resi', $order))
            ->assertOk()
            ->assertJson(['completed' => false]);

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertDatabaseMissing('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_order_completed',
        ]);
    }
}
