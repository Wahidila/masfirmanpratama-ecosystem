<?php

namespace Tests\Feature\Shipping;

use App\Services\Shipping\AgenwebsiteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgenwebsitePriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_price_returns_array_with_int_cast(): void
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
                        'etd_from' => 1,
                        'etd_to' => 2,
                    ],
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->price([
            'origin' => 'surabaya',
            'origin_zipcode' => '60111',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'zipcode' => '12110',
            'weight' => 1,
            'courier' => 'jne',
            'length' => 10,
            'width' => 10,
            'height' => 5,
        ]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('jne_reg', $result[0]['service']);
        $this->assertSame(17000, $result[0]['price']);
        $this->assertIsInt($result[0]['price']);
    }

    public function test_price_caches_with_short_ttl(): void
    {
        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG', 'price' => '17000', 'etd' => '1-2 days', 'etd_from' => 1, 'etd_to' => 2],
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $params = [
            'origin' => 'surabaya',
            'origin_zipcode' => '60111',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'zipcode' => '12110',
            'weight' => 1,
            'courier' => 'jne',
            'length' => 10,
            'width' => 10,
            'height' => 5,
        ];

        $client->price($params);
        $client->price($params);

        Http::assertSentCount(1);
    }

    public function test_price_returns_empty_array_on_api_error(): void
    {
        Http::fake([
            '*/shipping/price' => Http::response(['message' => 'Error'], 500),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->price([
            'origin' => 'surabaya',
            'weight' => 1,
            'courier' => 'jne',
        ]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_price_casts_multiple_rows(): void
    {
        Http::fake([
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG', 'price' => '17000', 'etd' => '1-2 days', 'etd_from' => 1, 'etd_to' => 2],
                    ['courier' => 'jne', 'service' => 'jne_yes', 'service_name' => 'YES', 'price' => '28000', 'etd' => '1 day', 'etd_from' => 1, 'etd_to' => 1],
                    ['courier' => 'jnt', 'service' => 'jnt_reg', 'service_name' => 'REG', 'price' => '15000', 'etd' => '2-3 days', 'etd_from' => 2, 'etd_to' => 3],
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->price(['origin' => 'surabaya', 'weight' => 1, 'courier' => 'jne|jnt']);

        $this->assertCount(3, $result);
        $this->assertSame(17000, $result[0]['price']);
        $this->assertSame(28000, $result[1]['price']);
        $this->assertSame(15000, $result[2]['price']);

        foreach ($result as $row) {
            $this->assertIsInt($row['price']);
        }
    }
}
