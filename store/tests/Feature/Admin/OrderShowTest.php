<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_redirects_unauthenticated_to_login(): void
    {
        $order = Order::factory()->create();

        $this->get(route('admin.orders.show', $order))
            ->assertRedirect(route('admin.login'));
    }

    public function test_renders_order_detail_for_authenticated_admin(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-DETAIL1',
            'customer_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'address' => 'Jl. Mawar 12, Malang',
            'total' => 750000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('MFP-DETAIL1');
        $response->assertSee('Budi Santoso');
        $response->assertSee('081234567890');
        $response->assertSee('budi@example.com');
        $response->assertSee('Jl. Mawar 12, Malang');
    }

    public function test_shows_order_items_with_product_name(): void
    {
        $product = Product::factory()->create([
            'title' => 'Kelas AMC Reguler',
            'slug' => 'kelas-amc-reguler',
        ]);
        $order = Order::factory()->create(['total' => 1000000]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 2,
            'unit_price' => 500000,
            'subtotal' => 1000000,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Kelas AMC Reguler');
        $response->assertSee('kelas-amc-reguler');
    }

    public function test_shows_empty_state_when_no_items(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Belum ada item');
    }

    public function test_displays_payment_entries_with_status(): void
    {
        $order = Order::factory()->create(['total' => 1000000]);

        OrderPayment::factory()->verified()->create([
            'order_id' => $order->id,
            'amount' => 600000,
        ]);
        OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 400000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Terverifikasi');
        $response->assertSee('Menunggu');
        $response->assertSeeText('600.000');
        $response->assertSeeText('400.000');
    }

    public function test_calculates_total_paid_and_remaining(): void
    {
        $order = Order::factory()->create(['total' => 1000000]);

        OrderPayment::factory()->verified()->create([
            'order_id' => $order->id,
            'amount' => 600000,
        ]);
        OrderPayment::factory()->rejected()->create([
            'order_id' => $order->id,
            'amount' => 200000,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        // Sudah lunas (verified only) = 600.000
        $response->assertSeeText('600.000');
        // Sisa (1.000.000 - 600.000) = 400.000
        $response->assertSeeText('400.000');
        // Rejected ngga ke-count di paid total — tapi entry harus tetap muncul
        $response->assertSee('Ditolak');
    }

    public function test_shows_empty_payments_state(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Belum ada bukti bayar');
    }

    public function test_returns_404_for_non_existent_order(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/orders/999999');

        $response->assertStatus(404);
    }

    public function test_handles_deleted_product_gracefully(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['total' => 500000]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 1,
            'unit_price' => 500000,
            'subtotal' => 500000,
        ]);

        // Soft delete the product (Product uses SoftDeletes)
        $product->delete();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        // Item should still render — product relation returns null when soft-deleted
        $response->assertStatus(200);
        $response->assertSee('(produk dihapus)');
    }

    public function test_back_link_to_index_present(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee(route('admin.orders.index'));
        $response->assertSee('Kembali');
    }

    public function test_displays_customer_info_section(): void
    {
        $order = Order::factory()->create([
            'customer_name' => 'Siti Aminah',
            'phone' => '08987654321',
            'email' => 'siti@test.com',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Customer');
        $response->assertSee('Siti Aminah');
        $response->assertSee('08987654321');
        $response->assertSee('siti@test.com');
    }

    public function test_renders_index_link_to_detail(): void
    {
        $order = Order::factory()->create(['order_number' => 'MFP-LINK001']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.orders.show', $order));
        $response->assertSee('MFP-LINK001');
    }
}
