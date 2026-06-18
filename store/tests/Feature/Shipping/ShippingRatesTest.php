<?php

namespace Tests\Feature\Shipping;

use App\Models\Product;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingRatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_get_rates_returns_mapped_rates_from_api(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'is_shippable' => true,
        ]);

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
                    [
                        'courier' => 'jnt',
                        'service' => 'jnt_reg',
                        'service_name' => 'REG',
                        'price' => '15000',
                        'etd' => '2-3 days',
                    ],
                ],
            ], 200),
        ]);

        $result = app(ShippingRateService::class)->getRates(
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'zipcode' => '12110',
            ],
            [
                ['slug' => 'buku-a', 'qty' => 1],
            ]
        );

        $this->assertCount(2, $result);

        $this->assertSame('jne', $result[0]['courier']);
        $this->assertSame('jne_reg', $result[0]['service']);
        $this->assertSame('REG (1-2 days)', $result[0]['label']);
        $this->assertSame(17000, $result[0]['price']);
        $this->assertSame('1-2 days', $result[0]['etd']);

        $this->assertSame('jnt', $result[1]['courier']);
        $this->assertSame('jnt_reg', $result[1]['service']);
        $this->assertSame('REG (2-3 days)', $result[1]['label']);
        $this->assertSame(15000, $result[1]['price']);
        $this->assertSame('2-3 days', $result[1]['etd']);
    }

    public function test_get_rates_applies_markup_from_config(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'is_shippable' => true,
        ]);

        Config::set('shipping.service_markup', [
            'jne_reg' => 5000,
            'jnt_reg' => 3000,
        ]);

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
                    [
                        'courier' => 'jnt',
                        'service' => 'jnt_reg',
                        'service_name' => 'REG',
                        'price' => '15000',
                        'etd' => '2-3 days',
                    ],
                ],
            ], 200),
        ]);

        $result = app(ShippingRateService::class)->getRates(
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'zipcode' => '12110',
            ],
            [
                ['slug' => 'buku-a', 'qty' => 1],
            ]
        );

        $this->assertSame(22000, $result[0]['price']);
        $this->assertSame(18000, $result[1]['price']);
    }

    public function test_get_rates_returns_empty_array_when_weight_is_zero(): void
    {
        Product::factory()->create([
            'slug' => 'course-a',
            'type' => 'course',
            'weight_kg' => null,
            'is_shippable' => false,
        ]);

        $result = app(ShippingRateService::class)->getRates(
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'zipcode' => '12110',
            ],
            [
                ['slug' => 'course-a', 'qty' => 1],
            ]
        );

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_rates_returns_dummy_on_api_error(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'is_shippable' => true,
        ]);

        Http::fake([
            '*/shipping/price' => Http::response(['message' => 'License Anda sudah expired.'], 403),
        ]);

        $result = app(ShippingRateService::class)->getRates(
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'zipcode' => '12110',
            ],
            [
                ['slug' => 'buku-a', 'qty' => 1],
            ]
        );

        // Dummy fallback rates returned
        $this->assertNotEmpty($result);
        $this->assertSame('jne', $result[0]['courier']);
        $this->assertSame('REG', $result[0]['service']);
    }

    public function test_get_rates_filters_only_active_couriers(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'is_shippable' => true,
        ]);

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
                    [
                        'courier' => 'unknown',
                        'service' => 'unknown_eco',
                        'service_name' => 'ECO',
                        'price' => '10000',
                        'etd' => '3-5 days',
                    ],
                    [
                        'courier' => 'jnt',
                        'service' => 'jnt_reg',
                        'service_name' => 'REG',
                        'price' => '15000',
                        'etd' => '2-3 days',
                    ],
                ],
            ], 200),
        ]);

        $result = app(ShippingRateService::class)->getRates(
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'zipcode' => '12110',
            ],
            [
                ['slug' => 'buku-a', 'qty' => 1],
            ]
        );

        $this->assertCount(2, $result);
        $this->assertSame('jne', $result[0]['courier']);
        $this->assertSame('jnt', $result[1]['courier']);
    }

    public function test_get_rates_returns_dummy_when_no_active_couriers_match(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'is_shippable' => true,
        ]);

        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => [
                    [
                        'courier' => 'unknown',
                        'service' => 'unknown_eco',
                        'service_name' => 'ECO',
                        'price' => '10000',
                        'etd' => '3-5 days',
                    ],
                ],
            ], 200),
        ]);

        $result = app(ShippingRateService::class)->getRates(
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'zipcode' => '12110',
            ],
            [
                ['slug' => 'buku-a', 'qty' => 1],
            ]
        );

        // Dummy fallback when no active couriers match
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertSame('jne', $result[0]['courier']);
    }

    public function test_get_rates_fallback_label_when_service_name_missing(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 3,
            'is_shippable' => true,
        ]);

        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => [
                    [
                        'courier' => 'jne',
                        'service' => 'jne_reg',
                        'price' => '17000',
                    ],
                ],
            ], 200),
        ]);

        $result = app(ShippingRateService::class)->getRates(
            [
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'zipcode' => '12110',
            ],
            [
                ['slug' => 'buku-a', 'qty' => 1],
            ]
        );

        $this->assertCount(1, $result);
        $this->assertSame('jne_reg (TBD)', $result[0]['label']);
        $this->assertSame('', $result[0]['etd']);
    }
}
