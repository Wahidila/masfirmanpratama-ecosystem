<?php

namespace Tests\Feature\Shipping;

use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ShippingErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'price' => 100_000,
            'weight_kg' => 1.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'is_shippable' => true,
            'status' => 'active',
        ]);

        Course::factory()->create([
            'slug' => 'course-a',
            'price' => 500_000,
            'status' => 'active',
        ]);
    }

    public function test_rate_endpoint_returns_dummy_rates_when_api_errors(): void
    {
        Log::spy();

        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'License Anda sudah expired.',
            ], 403),
        ]);

        $response = $this->postJson('/shipping/rates', [
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'zipcode' => '12110',
            'cart_json' => json_encode([
                ['slug' => 'buku-a', 'qty' => 1],
            ]),
        ]);

        $response->assertStatus(200);
        // Fallback dummy rates returned instead of empty
        $rates = $response->json('rates');
        $this->assertNotEmpty($rates);
        $this->assertSame('jne', $rates[0]['courier']);
        $response->assertJsonMissing(['error']);
    }

    public function test_rate_endpoint_genuine_no_coverage_returns_dummy(): void
    {
        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => [],
            ], 200),
        ]);

        $response = $this->postJson('/shipping/rates', [
            'city' => 'Remote Area',
            'province' => 'Papua',
            'zipcode' => '99999',
            'cart_json' => json_encode([
                ['slug' => 'buku-a', 'qty' => 1],
            ]),
        ]);

        $response->assertStatus(200);
        // Fallback dummy rates when API returns empty data
        $rates = $response->json('rates');
        $this->assertNotEmpty($rates);
        $response->assertJsonMissing(['error']);
    }

    public function test_rate_endpoint_success_unchanged(): void
    {
        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => [
                    [
                        'courier' => 'jne',
                        'service' => 'jne_reg',
                        'service_name' => 'REG',
                        'price' => '17000',
                        'etd' => '1-2 days',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/shipping/rates', [
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'zipcode' => '12110',
            'cart_json' => json_encode([
                ['slug' => 'buku-a', 'qty' => 1],
            ]),
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'rates');
        $response->assertJsonPath('rates.0.courier', 'jne');
        $response->assertJsonPath('rates.0.price', 17000);
        $response->assertJsonMissing(['error']);
    }

    public function test_checkout_submit_succeeds_with_dummy_rates_when_shipping_api_errors(): void
    {
        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'License Anda sudah expired.',
            ], 403),
        ]);

        $response = $this->post('/checkout', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'address_line' => 'Jl. Merdeka No. 12',
            'address_city' => 'Jakarta Selatan',
            'address_province' => 'DKI Jakarta',
            'address_postal' => '12110',
            'shipping_method' => 'REG',
            'payment_type' => 'lunas',
            'installment_scheme_id' => null,
            'cart_json' => json_encode([
                ['slug' => 'buku-a', 'name' => 'Buku A', 'price' => 100_000, 'qty' => 1],
            ]),
            'cart_total' => 109_000,
            'ref_code' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertNotNull($order);
        // Shipping cost from dummy: 1kg * 9000 = 9000
        $this->assertSame(9000, (int) $order->shipping_cost);
        $this->assertSame('REG', $order->shipping_courier);
    }

    public function test_checkout_class_only_unaffected(): void
    {
        $response = $this->post('/checkout', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'address_line' => 'Jl. Merdeka No. 12',
            'address_city' => 'Malang',
            'address_province' => 'Jawa Timur',
            'address_postal' => '65111',
            'shipping_method' => '',
            'payment_type' => 'lunas',
            'installment_scheme_id' => null,
            'cart_json' => json_encode([
                ['slug' => 'course-a', 'name' => 'Course A', 'price' => 500_000, 'qty' => 1],
            ]),
            'cart_total' => 500_000,
            'ref_code' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('500000.00', $order->total);
        $this->assertNull($order->shipping_courier);
    }
}
