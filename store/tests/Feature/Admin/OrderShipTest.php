<?php

namespace Tests\Feature\Admin;

use App\Events\OrderShipped;
use App\Models\Admin;
use App\Models\Order;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Coverage task t_34ed789d — Input Resi + transition ke 'shipped'.
 *
 * Source-of-truth status enum: `orders` migration (pending|partial_paid|paid|
 * shipped|completed|cancelled|refunded). Precondition shipping = status='paid'.
 */
class OrderShipTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_ship_requires_authentication(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $this->post(route('admin.orders.ship', $order), [
            'shipping_courier' => 'jne',
            'shipping_resi' => 'JNE1234567890',
        ])
            ->assertRedirect(route('admin.login'));

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNull($order->shipping_resi);
    }

    public function test_ship_happy_path_marks_order_shipped(): void
    {
        Event::fake();

        $order = Order::factory()->create([
            'status' => 'paid',
            'total' => 1_000_000,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'jne',
                'shipping_resi' => 'JNE1234567890',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertSame('jne', $order->shipping_courier);
        $this->assertSame('JNE1234567890', $order->shipping_resi);
        $this->assertNotNull($order->shipped_at);

        Event::assertDispatched(OrderShipped::class, function (OrderShipped $event) use ($order) {
            return $event->order->id === $order->id
                && $event->order->status === 'shipped';
        });
    }

    public function test_ship_trims_whitespace_from_resi(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'jnt',
                'shipping_resi' => '  JNT-RESI-9988  ',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('JNT-RESI-9988', $order->shipping_resi);
    }

    /**
     * @dataProvider invalidStatusProvider
     */
    public function test_ship_rejected_when_order_not_paid(string $status): void
    {
        $order = Order::factory()->create(['status' => $status]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'jne',
                'shipping_resi' => 'JNE1234567890',
            ])
            ->assertStatus(422);

        $order->refresh();
        $this->assertSame($status, $order->status, 'Status must not change on rejection');
        $this->assertNull($order->shipping_resi);
    }

    public static function invalidStatusProvider(): array
    {
        return [
            'pending' => ['pending'],
            'partial_paid' => ['partial_paid'],
            'shipped (already)' => ['shipped'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
            'refunded' => ['refunded'],
        ];
    }

    public function test_ship_validates_courier_in_allowed_list(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'WahanaExpress', // not in list
                'shipping_resi' => 'WAHANA1234',
            ])
            ->assertSessionHasErrors('shipping_courier');

        $order->refresh();
        $this->assertSame('paid', $order->status);
    }

    public function test_ship_validates_resi_required_and_min_length(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        // Missing resi
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'jne',
            ])
            ->assertSessionHasErrors('shipping_resi');

        // Resi too short
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'jne',
                'shipping_resi' => 'ABC',
            ])
            ->assertSessionHasErrors('shipping_resi');

        $order->refresh();
        $this->assertSame('paid', $order->status);
    }

    public function test_ship_validates_resi_max_length(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'jne',
                'shipping_resi' => str_repeat('X', 65), // > 64
            ])
            ->assertSessionHasErrors('shipping_resi');
    }

    public function test_ship_supports_all_active_courier_options(): void
    {
        // Sinkron dengan kurir aktif (config shipping.couriers).
        foreach (['jne', 'jnt', 'sicepat', 'anteraja', 'pos'] as $courier) {
            $order = Order::factory()->create(['status' => 'paid']);

            $this->actingAs($this->admin, 'admin')
                ->post(route('admin.orders.ship', $order), [
                    'shipping_courier' => $courier,
                    'shipping_resi' => $courier.'-12345678',
                ])
                ->assertRedirect();

            $order->refresh();
            $this->assertSame('shipped', $order->status);
            $this->assertSame($courier, $order->shipping_courier);
        }
    }

    public function test_ship_form_syncs_active_couriers_and_preselects_customer_choice(): void
    {
        // Customer memilih SiCepat saat checkout → dropdown harus default ke sicepat.
        $order = Order::factory()->create([
            'status' => 'paid',
            'shipping_courier' => 'sicepat',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        // Opsi kurir sinkron dgn kurir aktif (value = courier_id, label dari config).
        $response->assertSee('value="jne"', false);
        $response->assertSee('value="anteraja"', false);
        $response->assertSee('>SiCepat</option>', false);

        // Default terpilih = kurir pilihan customer.
        $response->assertSee('value="sicepat" selected', false);
    }

    public function test_ship_form_includes_stored_courier_even_if_not_active(): void
    {
        // Order lama dgn kurir yang sudah dinonaktifkan tetap bisa dipilih & valid.
        $order = Order::factory()->create([
            'status' => 'paid',
            'shipping_courier' => 'tiki',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('value="tiki" selected', false);

        // Dan submit dengan kurir tsb tidak ditolak validasi.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'tiki',
                'shipping_resi' => 'TIKI-556677',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame('tiki', $order->fresh()->shipping_courier);
    }

    public function test_show_page_renders_input_resi_form_when_paid(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Aksi Pengiriman')
            ->assertSee('Tandai Dikirim')
            ->assertSee('shipping_courier', false)
            ->assertSee('shipping_resi', false);
    }

    public function test_show_page_renders_resi_readonly_when_shipped(): void
    {
        $order = Order::factory()->create([
            'status' => 'shipped',
            'shipping_courier' => 'sicepat',
            'shipping_resi' => 'SC-99887766',
            'shipped_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Sudah Dikirim')
            ->assertSee('SiCepat') // label dari courier_id 'sicepat'
            ->assertSee('SC-99887766')
            ->assertDontSee('Tandai Dikirim');
    }

    public function test_show_page_renders_not_ready_when_pending(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Belum siap kirim')
            ->assertDontSee('Tandai Dikirim');
    }

    public function test_track_page_renders_real_db_resi_when_order_shipped(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-TEST-SHIP1',
            'status' => 'shipped',
            'shipping_courier' => 'JNE',
            'shipping_resi' => 'JNE9988776655',
            'shipped_at' => now()->subHour(),
        ]);

        $this->get(URL::temporarySignedRoute(
            'track.show',
            now()->addDays(30),
            ['order_number' => $order->order_number],
        ))
            ->assertOk()
            ->assertSee('JNE9988776655')
            ->assertSee('jne.co.id'); // tracking link
    }

    public function test_track_page_falls_back_to_dummy_when_no_real_order(): void
    {
        // Order number ngga ada di DB → fallback ke dummy heuristic.
        $this->get(URL::temporarySignedRoute(
            'track.show',
            now()->addDays(30),
            ['order_number' => 'MFP-NOTEXIST-XYZ'],
        ))
            ->assertOk();
    }
}
