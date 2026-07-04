<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol admin "Tandai Selesai": transisi manual 'shipped' → 'completed'.
 * Perlu karena alur resi-manual tak menerima callback AWB 'delivered', sehingga
 * tanpa aksi ini order macet di 'shipped' selamanya.
 */
class OrderCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_requires_authentication(): void
    {
        $order = Order::factory()->create(['status' => 'shipped']);

        $this->post(route('admin.orders.complete', $order))
            ->assertRedirect(route('admin.login'));
    }

    public function test_marks_shipped_order_completed_and_queues_thankyou_wa(): void
    {
        $order = Order::factory()->create([
            'status' => 'shipped',
            'phone' => '628123456789',
            'fulfillment_status' => 'shipped',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.complete', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $fresh = $order->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertSame('delivered', $fresh->fulfillment_status);

        // WA terima kasih ke pembeli ter-queue (chain event → listener nyata).
        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_order_completed',
        ]);
    }

    public function test_records_manual_completion_audit_meta(): void
    {
        $order = Order::factory()->create(['status' => 'shipped', 'phone' => '628123456789']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.complete', $order));

        $meta = $order->fresh()->order_meta;
        $this->assertTrue((bool) ($meta['completed_manually'] ?? false));
        $this->assertSame($this->admin->id, $meta['completed_by'] ?? null);
        $this->assertNotEmpty($meta['completed_at'] ?? null);
    }

    /**
     * @dataProvider nonShippableStatuses
     */
    public function test_rejects_non_shipped_status(string $status): void
    {
        $order = Order::factory()->create(['status' => $status]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.complete', $order))
            ->assertStatus(422);

        // Status tidak berubah.
        $this->assertSame($status, $order->fresh()->status);
    }

    public static function nonShippableStatuses(): array
    {
        return [
            'pending' => ['pending'],
            'paid' => ['paid'],
            'partial_paid' => ['partial_paid'],
            'completed (sudah)' => ['completed'],
            'cancelled' => ['cancelled'],
            'refunded' => ['refunded'],
        ];
    }

    public function test_already_completed_does_not_send_second_wa(): void
    {
        // Guard 422 untuk status completed → tidak ada notifikasi selesai kedua.
        $order = Order::factory()->create(['status' => 'completed', 'phone' => '628123456789']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.complete', $order))
            ->assertStatus(422);

        $this->assertDatabaseMissing('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_order_completed',
        ]);
    }

    public function test_model_mark_completed_is_idempotent(): void
    {
        $order = Order::factory()->create(['status' => 'shipped']);

        $this->assertTrue($order->markCompleted());              // baru transisi
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->fulfillment_status);
        $this->assertFalse($order->fresh()->markCompleted());    // sudah completed → false
    }
}
