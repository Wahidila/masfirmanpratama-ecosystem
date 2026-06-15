<?php

namespace Tests\Feature\Shipping;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ShippingRateEndpointTest extends TestCase
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
    }

    public function test_valid_payload_returns_rates(): void
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
        $response->assertJsonStructure(['rates']);
        $response->assertJsonCount(1, 'rates');
        $response->assertJsonPath('rates.0.courier', 'jne');
        $response->assertJsonPath('rates.0.price', 17000);
    }

    public function test_missing_fields_returns_422(): void
    {
        $response = $this->postJson('/shipping/rates', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['city', 'province', 'zipcode', 'cart_json']);
    }

    public function test_invalid_cart_json_returns_422(): void
    {
        $response = $this->postJson('/shipping/rates', [
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'zipcode' => '12110',
            'cart_json' => 'not-json',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cart_json']);
    }

    public function test_api_failure_returns_dummy_rates(): void
    {
        Http::fake([
            '*/shipping/price' => Http::response(['message' => 'License Anda sudah expired.'], 403),
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
        $rates = $response->json('rates');
        $this->assertNotEmpty($rates);
        $this->assertSame('jne', $rates[0]['courier']);
        $response->assertJsonMissing(['error']);
    }

    public function test_route_has_csrf_protection(): void
    {
        $route = Route::getRoutes()->getByName('shipping.rates');

        $this->assertNotNull($route, 'Route shipping.rates must be registered');
        $this->assertTrue(
            in_array('web', $route->gatherMiddleware()),
            'Route must be in web middleware group for CSRF protection'
        );
    }
}
