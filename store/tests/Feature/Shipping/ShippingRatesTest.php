<?php

namespace Tests\Feature\Shipping;

use App\Exceptions\ShippingRateException;
use App\Models\Product;
use App\Services\Settings;
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

    /**
     * Helper untuk fake response API agenwebsite secara konsisten.
     *
     * @param  array<int, array<string, mixed>>  $priceRows
     * @param  array<int, array<string, mixed>>|null  $servicesRows  null = pakai default sample
     */
    private function fakeAgenwebsite(array $priceRows, ?array $servicesRows = null): void
    {
        $servicesRows ??= [
            ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
            ['courier_id' => 'jnt_reg', 'name' => 'EZ', 'courier' => 'jnt',
                'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
        ];

        Http::fake([
            '*/shipping/services' => Http::response(['message' => 'OK', 'data' => $servicesRows], 200),
            '*/shipping/couriers' => Http::response([
                'message' => 'OK',
                'data' => [
                    ['id' => 'jne', 'title' => 'JNE', 'category' => 'domestic'],
                    ['id' => 'jnt', 'title' => 'J&T Express', 'category' => 'domestic'],
                ],
            ], 200),
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => $priceRows,
            ], 200),
        ]);
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

        $this->fakeAgenwebsite([
            // courier field = courier_id pakai master sebagai source of truth
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
                'service_name' => 'EZ',
                'price' => '15000',
                'etd' => '2-3 days',
            ],
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
        $this->assertSame('JNE REG (1-2 days)', $result[0]['label']);
        $this->assertSame(17000, $result[0]['price']);
        $this->assertSame('1-2 days', $result[0]['etd']);

        $this->assertSame('jnt', $result[1]['courier']);
        $this->assertSame('jnt_reg', $result[1]['service']);
        $this->assertSame('J&T Express EZ (2-3 days)', $result[1]['label']);
        $this->assertSame(15000, $result[1]['price']);
        $this->assertSame('2-3 days', $result[1]['etd']);
    }

    public function test_get_rates_layers_admin_markup_on_top_of_extra_cost(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'is_shippable' => true,
        ]);

        // extra_cost di master + service_markup di Settings → keduanya dijumlahkan.
        $this->fakeAgenwebsite(
            priceRows: [
                ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG',
                    'price' => '17000', 'etd' => '1-2 days'],
                ['courier' => 'jnt', 'service' => 'jnt_reg', 'service_name' => 'EZ',
                    'price' => '15000', 'etd' => '2-3 days'],
            ],
            servicesRows: [
                ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                    'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 1000],
                ['courier_id' => 'jnt_reg', 'name' => 'EZ', 'courier' => 'jnt',
                    'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
            ],
        );

        Config::set('shipping.service_markup', [
            'jne_reg' => 5000,
            'jnt_reg' => 3000,
        ]);

        $result = app(ShippingRateService::class)->getRates(
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru', 'zipcode' => '12110'],
            [['slug' => 'buku-a', 'qty' => 1]],
        );

        // 17000 + extra_cost 1000 + markup 5000 = 23000
        $this->assertSame(23000, $result[0]['price']);
        // 15000 + extra_cost 0 + markup 3000 = 18000
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

    public function test_get_rates_rethrows_shipping_exception_on_api_error(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'is_shippable' => true,
        ]);

        Http::fake([
            '*/shipping/services' => Http::response(['message' => 'OK', 'data' => []], 200),
            '*/shipping/couriers' => Http::response(['message' => 'OK', 'data' => []], 200),
            '*/shipping/price' => Http::response(['message' => 'License Anda sudah expired.'], 403),
        ]);

        $this->expectException(ShippingRateException::class);

        app(ShippingRateService::class)->getRates(
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru', 'zipcode' => '12110'],
            [['slug' => 'buku-a', 'qty' => 1]],
        );
    }

    public function test_get_rates_filters_to_master_allow_set(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'is_shippable' => true,
        ]);

        // Service 'unknown_eco' tidak ada di master → harus di-drop.
        // Service 'gosend_instant' kategorinya instant → harus di-drop walau price ada.
        $this->fakeAgenwebsite(
            priceRows: [
                ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG',
                    'price' => '17000', 'etd' => '1-2 days'],
                ['courier' => 'unknown', 'service' => 'unknown_eco', 'service_name' => 'ECO',
                    'price' => '10000', 'etd' => '3-5 days'],
                ['courier' => 'gosend', 'service' => 'gosend_instant', 'service_name' => 'Instant',
                    'price' => '25000', 'etd' => '1-3 hours'],
                ['courier' => 'jnt', 'service' => 'jnt_reg', 'service_name' => 'EZ',
                    'price' => '15000', 'etd' => '2-3 days'],
            ],
            servicesRows: [
                ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                    'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ['courier_id' => 'jnt_reg', 'name' => 'EZ', 'courier' => 'jnt',
                    'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ['courier_id' => 'gosend_instant', 'name' => 'Instant', 'courier' => 'gosend',
                    'category' => 'instant', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
            ],
        );

        $result = app(ShippingRateService::class)->getRates(
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru', 'zipcode' => '12110'],
            [['slug' => 'buku-a', 'qty' => 1]],
        );

        $this->assertCount(2, $result);
        $services = array_column($result, 'service');
        $this->assertContains('jne_reg', $services);
        $this->assertContains('jnt_reg', $services);
        $this->assertNotContains('unknown_eco', $services);
        $this->assertNotContains('gosend_instant', $services);
    }

    public function test_get_rates_returns_empty_when_no_courier_matches(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'is_shippable' => true,
        ]);

        $this->fakeAgenwebsite([
            ['courier' => 'unknown', 'service' => 'unknown_eco', 'service_name' => 'ECO',
                'price' => '10000', 'etd' => '3-5 days'],
        ]);

        $result = app(ShippingRateService::class)->getRates(
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru', 'zipcode' => '12110'],
            [['slug' => 'buku-a', 'qty' => 1]],
        );

        // Fail-closed: tidak ada match → empty array, BUKAN harga fabrikasi.
        $this->assertSame([], $result);
    }

    public function test_get_rates_shows_premium_by_default_matching_plugin(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'is_shippable' => true,
        ]);

        // Plugin WP TIDAK menyaring premium di jalur domestik → default tampil.
        $this->fakeAgenwebsite(
            priceRows: [
                ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG',
                    'price' => '17000', 'etd' => '1-2 days'],
                ['courier' => 'jne', 'service' => 'jne_yes', 'service_name' => 'YES',
                    'price' => '43000', 'etd' => '1 days'],
            ],
            servicesRows: [
                ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                    'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ['courier_id' => 'jne_yes', 'name' => 'YES', 'courier' => 'jne',
                    'category' => 'domestic', 'is_premium' => 1, 'enable' => 1, 'extra_cost' => 0],
            ],
        );

        $result = app(ShippingRateService::class)->getRates(
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru', 'zipcode' => '12110'],
            [['slug' => 'buku-a', 'qty' => 1]],
        );

        $this->assertCount(2, $result);
        $this->assertSame(['jne_reg', 'jne_yes'], array_column($result, 'service'));
    }

    public function test_get_rates_hides_premium_when_admin_opts_out(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.0,
            'is_shippable' => true,
        ]);

        Settings::set('shipping.allow_premium', false, 'bool');

        $this->fakeAgenwebsite(
            priceRows: [
                ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG',
                    'price' => '17000', 'etd' => '1-2 days'],
                ['courier' => 'jne', 'service' => 'jne_yes', 'service_name' => 'YES',
                    'price' => '43000', 'etd' => '1 days'],
            ],
            servicesRows: [
                ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                    'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ['courier_id' => 'jne_yes', 'name' => 'YES', 'courier' => 'jne',
                    'category' => 'domestic', 'is_premium' => 1, 'enable' => 1, 'extra_cost' => 0],
            ],
        );

        $result = app(ShippingRateService::class)->getRates(
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru', 'zipcode' => '12110'],
            [['slug' => 'buku-a', 'qty' => 1]],
        );

        $this->assertCount(1, $result);
        $this->assertSame('jne_reg', $result[0]['service']);
    }

    public function test_get_rates_sends_decimal_weight_and_method(): void
    {
        Product::factory()->create([
            'slug' => 'buku-a',
            'type' => 'book',
            'weight_kg' => 1.2,
            'is_shippable' => true,
        ]);

        $this->fakeAgenwebsite([
            ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG',
                'price' => '17000', 'etd' => '1-2 days'],
        ]);

        app(ShippingRateService::class)->getRates(
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru', 'zipcode' => '12110'],
            [['slug' => 'buku-a', 'qty' => 1]],
        );

        // Berat dikirim desimal (bukan ceil ke 2) + method=zipcode, samakan plugin.
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/shipping/price')) {
                return false;
            }

            return (string) $request['weight'] === '1.2'
                && $request['method'] === 'zipcode';
        });
    }
}
