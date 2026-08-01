<?php

namespace Tests\Feature\Shipping;

use App\Models\Order;
use App\Models\Product;
use App\Services\Shipping\FulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FulfillmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function createOrderWithItem(array $overrides = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'shipping_courier' => 'jne',
            'shipping_service' => 'jne_reg',
            'shipping_cost' => 25000,
            'shipping_etd' => '2-3 hari',
        ], $overrides));

        $order->items()->create([
            'product_id' => $this->product->id,
            'qty' => 2,
            'unit_price' => 100000,
            'subtotal' => 200000,
        ]);

        return $order->fresh();
    }

    public function test_phone_normalization(): void
    {
        $service = app(FulfillmentService::class);

        $this->assertSame('6281234567890', $service->normalizePhone('081234567890'));
        $this->assertSame('6281234567890', $service->normalizePhone('6281234567890'));
        $this->assertSame('6212345', $service->normalizePhone('012345'));
    }

    public function test_phone_normalization_via_shipment_data(): void
    {
        $order = $this->createOrderWithItem([
            'phone' => '081234567890',
        ]);

        $service = app(FulfillmentService::class);
        $data = $service->buildShipmentData($order);

        $this->assertSame('6281234567890', $data['receiver']['phone']);
    }

    public function test_create_shipment_awb_ready(): void
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

        $order = $this->createOrderWithItem([
            'phone' => '081234567890',
        ]);

        $service = app(FulfillmentService::class);
        $result = $service->createShipment($order);

        $this->assertSame('awb_ready', $result['status']);
        $this->assertSame('AGN987654321', $result['tracking_number']);

        $order = $order->fresh();
        $this->assertSame('AGN987654321', $order->shipping_resi);
        $this->assertSame('shipped', $order->fulfillment_status);
        $this->assertSame('shipped', $order->status);
        $this->assertNotNull($order->shipped_at);
        $this->assertSame('ORD-AWB-001', $order->fulfillment_api_order_id);
        $this->assertSame('REF-AWB-001', $order->fulfillment_reference_id);
        $this->assertSame('https://label.example.com/awb987654321', $order->label_url);
    }

    public function test_create_shipment_waiting_awb(): void
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

        $order = $this->createOrderWithItem();

        $service = app(FulfillmentService::class);
        $result = $service->createShipment($order);

        $this->assertSame('waiting_awb', $result['status']);

        $order = $order->fresh();
        $this->assertSame('waiting_awb', $order->fulfillment_status);
        $this->assertNull($order->shipping_resi);
        $this->assertSame('ORD-WAIT-001', $order->fulfillment_api_order_id);
        $this->assertSame('REF-WAIT-001', $order->fulfillment_reference_id);
    }

    public function test_create_shipment_pending_payment(): void
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

        $order = $this->createOrderWithItem();

        $order->update(['shipping_courier' => 'jne', 'shipping_service' => 'jne_reg']);

        $service = app(FulfillmentService::class);
        $result = $service->createShipment($order);

        $this->assertSame('pending_payment', $result['status']);
        $this->assertSame('https://payment.example.com/pay/abc123', $result['payment_url']);

        $order = $order->fresh();
        $this->assertSame('pending_payment', $order->fulfillment_status);
        $this->assertNull($order->shipping_resi);
        $this->assertSame('ORD-PAY-001', $order->fulfillment_api_order_id);
        $this->assertSame('REF-PAY-001', $order->fulfillment_reference_id);
        // FIX-8: payment_url di-persist sehingga admin bisa render tombol "Bayar".
        $this->assertSame('https://payment.example.com/pay/abc123', $order->fulfillment_payment_url);
    }

    public function test_build_shipment_data_uses_discrete_columns(): void
    {
        $order = $this->createOrderWithItem([
            'customer_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            // address_line saja, koma di dalamnya BUKAN delimiter city/province.
            'address' => 'Jl. Merdeka No. 12, RT 03/RW 04, Kel. Tegalsari',
            'shipping_city' => 'Surabaya',
            'shipping_province' => 'Jawa Timur',
            'shipping_district' => 'Tegalsari',
            'shipping_zipcode' => '60111',
            'shipping_courier' => 'jne',
            'shipping_service' => 'jne_reg',
        ]);

        $service = app(FulfillmentService::class);
        $data = $service->buildShipmentData($order);

        $this->assertSame(config('shipping.origin'), $data['shipper']['name']);
        $this->assertSame(config('shipping.origin_zipcode'), $data['shipper']['zipcode']);
        $this->assertSame('Budi Santoso', $data['receiver']['name']);
        $this->assertSame('budi@example.com', $data['receiver']['email']);
        // Full address_line preserved (was previously corrupted by explode).
        $this->assertSame('Jl. Merdeka No. 12, RT 03/RW 04, Kel. Tegalsari', $data['receiver']['address']);
        $this->assertSame('Surabaya', $data['receiver']['city']);
        $this->assertSame('Jawa Timur', $data['receiver']['province']);
        $this->assertSame('Tegalsari', $data['receiver']['district']);
        $this->assertSame('60111', $data['receiver']['zipcode']);
        $this->assertSame('jne', $data['courier']);
        $this->assertSame('jne_reg', $data['service']);
    }

    public function test_build_shipment_data_legacy_fallback_when_discrete_columns_null(): void
    {
        // Pre-FIX-4 orders punya 'address' tergabung dan tidak ada kolom diskrit.
        // Legacy fallback (explode) tetap harus jalan supaya tidak break.
        $order = $this->createOrderWithItem([
            'customer_name' => 'Legacy User',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka 12, Surabaya, Jawa Timur, 60111',
            'shipping_city' => null,
            'shipping_province' => null,
            'shipping_district' => null,
            'shipping_zipcode' => null,
        ]);

        $service = app(FulfillmentService::class);
        $data = $service->buildShipmentData($order);

        $this->assertSame('Jl. Merdeka 12', $data['receiver']['address']);
        $this->assertSame('Surabaya', $data['receiver']['city']);
        $this->assertSame('Jawa Timur', $data['receiver']['province']);
        $this->assertSame('60111', $data['receiver']['zipcode']);
    }

    public function test_build_shipment_data_min_weight(): void
    {
        $product = Product::factory()->create([
            'type' => 'book',
            'is_shippable' => true,
            'weight_kg' => 0.1,
            'length_cm' => 10,
            'width_cm' => 10,
            'height_cm' => 2,
            'price' => 50000,
            'status' => 'active',
        ]);

        $order = Order::factory()->create([
            'shipping_courier' => 'jne',
            'shipping_service' => 'jne_reg',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'unit_price' => 50000,
            'subtotal' => 50000,
        ]);

        $service = app(FulfillmentService::class);
        $data = $service->buildShipmentData($order);

        $this->assertGreaterThanOrEqual(1, $data['weight']);
    }

    public function test_fulfillment_payload_saved(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Success',
                'data' => [
                    'airwaybill' => 'AGN555555',
                    'reference_id' => 'REF-PAYLOAD-001',
                    'order_id' => 'ORD-PAYLOAD-001',
                ],
            ], 200),
        ]);

        $order = $this->createOrderWithItem([
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 1, Jakarta, DKI Jakarta, 10110',
        ]);

        $service = app(FulfillmentService::class);
        $service->createShipment($order);

        $order = $order->fresh();
        $this->assertNotNull($order->fulfillment_payload);
        $this->assertIsArray($order->fulfillment_payload);
        $this->assertSame('jne', $order->fulfillment_payload['courier']);
        $this->assertSame('jne_reg', $order->fulfillment_payload['service']);
        $this->assertSame('6281234567890', $order->fulfillment_payload['receiver']['phone']);
    }
}
