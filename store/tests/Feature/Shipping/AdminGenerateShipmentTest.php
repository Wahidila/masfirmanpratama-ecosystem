<?php

namespace Tests\Feature\Shipping;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminGenerateShipmentTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();

        $this->product = Product::factory()->create([
            'type' => 'book',
            'is_shippable' => true,
            'weight_kg' => 0.5,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'price' => 100000,
            'status' => 'active',
        ]);
    }

    private function createPaidOrder(array $overrides = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'status' => 'paid',
            'shipping_courier' => 'jne',
            'shipping_service' => 'jne_reg',
            'shipping_cost' => 25000,
        ], $overrides));

        $order->items()->create([
            'product_id' => $this->product->id,
            'qty' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        return $order->fresh();
    }

    /** (b) Non-admin -> redirect to login */
    public function test_guest_redirected_to_login(): void
    {
        $order = $this->createPaidOrder();

        $this->post(route('admin.orders.generate-shipment', $order))
            ->assertRedirect(route('admin.login'));
    }

    /** (a) Admin auth + paid order + Http::fake -> success awb_ready */
    public function test_generate_shipment_awb_ready(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Success',
                'data' => [
                    'airwaybill' => 'AGN987654321',
                    'reference_id' => 'REF-AWB-001',
                    'order_id' => 'ORD-AWB-001',
                    'label_url' => 'https://label.example.com/awb987654321',
                ],
            ], 200),
        ]);

        $order = $this->createPaidOrder();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $order->refresh();
        $this->assertSame('shipped', $order->fulfillment_status);
        $this->assertSame('AGN987654321', $order->shipping_resi);
    }

    /** (c) Order not paid -> 422 */
    public function test_rejected_when_order_not_paid(): void
    {
        $order = $this->createPaidOrder(['status' => 'pending']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertStatus(422);
    }

    /** (c) Order missing shipping_courier -> 422 */
    public function test_rejected_when_missing_shipping_courier(): void
    {
        $order = $this->createPaidOrder(['shipping_courier' => null]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertStatus(422);
    }

    /** (c) Order missing shipping_service -> 422 */
    public function test_rejected_when_missing_shipping_service(): void
    {
        $order = $this->createPaidOrder(['shipping_service' => null]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertStatus(422);
    }

    /** (d) Order already has AWB -> redirect with info, no API call */
    public function test_redirects_with_info_when_already_shipped(): void
    {
        $order = $this->createPaidOrder([
            'fulfillment_status' => 'shipped',
            'shipping_resi' => 'EXISTING-RESI-001',
        ]);

        // No Http::fake — if API is called it will fail the test with connection error
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('info');

        $order->refresh();
        $this->assertSame('EXISTING-RESI-001', $order->shipping_resi);
    }

    /** (e) Fulfillment error -> redirect with error flash */
    public function test_fulfillment_error_shows_error_flash(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'API Error occurred',
            ], 500),
        ]);

        $order = $this->createPaidOrder();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('error');
    }

    /** (a) waiting_awb status -> redirect with status message */
    public function test_generate_shipment_waiting_awb(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Success',
                'data' => [
                    'reference_id' => 'REF-WAIT-001',
                    'order_id' => 'ORD-WAIT-001',
                ],
            ], 200),
        ]);

        $order = $this->createPaidOrder();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $order->refresh();
        $this->assertSame('waiting_awb', $order->fulfillment_status);
    }

    /** (a) pending_payment status -> redirect with info */
    public function test_generate_shipment_pending_payment(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Success',
                'data' => [
                    'payment_url' => 'https://payment.example.com/pay/abc123',
                    'reference_id' => 'REF-PAY-001',
                    'order_id' => 'ORD-PAY-001',
                ],
            ], 200),
        ]);

        $order = $this->createPaidOrder();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.generate-shipment', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('info');

        $order->refresh();
        $this->assertSame('pending_payment', $order->fulfillment_status);
    }

    /** View: show page renders auto-generate button when eligible */
    public function test_show_page_renders_generate_button_when_eligible(): void
    {
        $order = $this->createPaidOrder();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Generate Resi Otomatis')
            ->assertSee(route('admin.orders.generate-shipment', $order), false);
    }

    /** View: show page hides generate button when missing courier */
    public function test_show_page_hides_generate_button_when_missing_courier(): void
    {
        $order = $this->createPaidOrder(['shipping_courier' => null]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Generate Resi Otomatis');
    }

    /** View: show page hides generate button when missing service */
    public function test_show_page_hides_generate_button_when_missing_service(): void
    {
        $order = $this->createPaidOrder(['shipping_service' => null]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Generate Resi Otomatis');
    }

    /** View: show page shows fulfillment info when fulfillment_status exists */
    public function test_show_page_shows_fulfillment_status_badge(): void
    {
        $order = $this->createPaidOrder([
            'fulfillment_status' => 'shipped',
            'shipping_resi' => 'RESI-LABEL-001',
            'label_url' => 'https://label.example.com/label',
            'tracking_status' => 'Dalam perjalanan',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Terkirim')
            ->assertSee('RESI-LABEL-001')
            ->assertSee('label.example.com/label')
            ->assertSee('Dalam perjalanan');
    }
}
