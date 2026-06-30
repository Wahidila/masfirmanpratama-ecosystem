<?php

namespace Tests\Feature\Shipping;

use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests fail-closed shipping behavior. SEBELUMNYA: dummyRates() fabrikasi
 * 9000/kg saat API error/empty → harga palsu disimpan sebagai shipping_cost
 * order. SEKARANG: empty/error → no rates ditampilkan, checkout submit dengan
 * shipping wajib diblokir dengan pesan error. Lihat ShippingRateService::getRates.
 */
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

    public function test_rate_endpoint_returns_error_when_api_errors(): void
    {
        Http::fake([
            '*/shipping/services' => Http::response(['message' => 'Success', 'data' => []], 200),
            '*/shipping/couriers' => Http::response(['message' => 'Success', 'data' => []], 200),
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
        $this->assertSame([], $response->json('rates'));
        $this->assertNotEmpty($response->json('error'));
    }

    public function test_rate_endpoint_genuine_no_coverage_returns_empty(): void
    {
        Http::fake([
            '*/shipping/services' => Http::response(['message' => 'Success', 'data' => []], 200),
            '*/shipping/couriers' => Http::response(['message' => 'Success', 'data' => []], 200),
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
        $this->assertSame([], $response->json('rates'));
        $response->assertJsonMissing(['error']);
    }

    public function test_rate_endpoint_success_unchanged(): void
    {
        Http::fake([
            '*/shipping/services' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                        'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ],
            ], 200),
            '*/shipping/couriers' => Http::response([
                'message' => 'Success',
                'data' => [['id' => 'jne', 'title' => 'JNE', 'category' => 'domestic']],
            ], 200),
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

    public function test_checkout_submit_blocked_when_shipping_api_errors(): void
    {
        Http::fake([
            '*/shipping/services' => Http::response(['message' => 'Success', 'data' => []], 200),
            '*/shipping/couriers' => Http::response(['message' => 'Success', 'data' => []], 200),
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
            'address_district' => 'Kebayoran Baru',
            'address_postal' => '12110',
            'shipping_method' => 'jne_reg',
            'payment_type' => 'lunas',
            'installment_scheme_id' => null,
            'cart_json' => json_encode([
                ['slug' => 'buku-a', 'name' => 'Buku A', 'price' => 100_000, 'qty' => 1],
            ]),
            'cart_total' => 109_000,
            'ref_code' => null,
        ]);

        // Sekarang fail-closed: redirect back dengan error, BUKAN persist order
        // dengan harga palsu.
        $response->assertSessionHasErrors(['shipping_method']);
        $this->assertNull(Order::first(), 'No order should be persisted when shipping rate API fails.');
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
