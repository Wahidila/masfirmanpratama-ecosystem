<?php

namespace Tests\Feature\Shipping;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShippingMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_model_has_shipping_meta_in_fillable(): void
    {
        $order = new Order;

        $this->assertContains('shipping_service', $order->getFillable());
        $this->assertContains('shipping_cost', $order->getFillable());
        $this->assertContains('shipping_etd', $order->getFillable());
    }

    public function test_creating_order_with_shipping_meta_persists_correctly(): void
    {
        $order = Order::create([
            'order_number' => 'MFP-20260531-TEST01',
            'customer_name' => 'Test Customer',
            'phone' => '081234567890',
            'address' => 'Test Address',
            'total' => 500_000,
            'status' => 'pending',
            'shipping_service' => 'jne_reg',
            'shipping_cost' => 25_000,
            'shipping_etd' => '2-3 hari',
        ]);

        $this->assertNotNull($order->id);
        $this->assertSame('jne_reg', $order->shipping_service);
        $this->assertSame(25_000, $order->shipping_cost);
        $this->assertSame('2-3 hari', $order->shipping_etd);
    }

    public function test_shipping_cost_casts_to_integer(): void
    {
        $order = Order::create([
            'order_number' => 'MFP-20260531-TEST02',
            'customer_name' => 'Test Customer',
            'phone' => '081234567890',
            'address' => 'Test Address',
            'total' => 500_000,
            'status' => 'pending',
            'shipping_service' => 'jne_oke',
            'shipping_cost' => '15000',
            'shipping_etd' => '3-5 hari',
        ]);

        $this->assertIsInt($order->shipping_cost);
        $this->assertSame(15000, $order->shipping_cost);
    }

    public function test_shipping_meta_defaults_to_null_and_zero(): void
    {
        $order = Order::create([
            'order_number' => 'MFP-20260531-TEST03',
            'customer_name' => 'Test Customer',
            'phone' => '081234567890',
            'address' => 'Test Address',
            'total' => 100_000,
            'status' => 'pending',
        ]);

        $order = $order->fresh();

        $this->assertNull($order->shipping_service);
        $this->assertSame(0, $order->shipping_cost);
        $this->assertNull($order->shipping_etd);
    }
}
