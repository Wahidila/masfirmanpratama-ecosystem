<?php

namespace Tests\Feature\Shipping;

use App\Services\Shipping\AgenwebsiteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgenwebsiteMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_couriers_returns_array_from_api(): void
    {
        Http::fake([
            '*/shipping/couriers' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['id' => 'jne', 'title' => 'JNE'],
                    ['id' => 'jnt', 'title' => 'J&T Express'],
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->couriers();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('jne', $result[0]['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/shipping/couriers')
                && $request['product'] === 'agenwebsite-shipping';
        });
    }

    public function test_couriers_caches_result(): void
    {
        Http::fake([
            '*/shipping/couriers' => Http::response([
                'message' => 'Success',
                'data' => [['id' => 'jne', 'title' => 'JNE']],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $client->couriers();
        $client->couriers();

        Http::assertSentCount(1);
    }

    public function test_couriers_falls_back_to_json_on_api_error(): void
    {
        Http::fake([
            '*/shipping/couriers' => Http::response(['message' => 'Error'], 500),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->couriers();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $ids = array_column($result, 'id');
        $this->assertContains('jne', $ids);
    }

    public function test_services_returns_array_from_api(): void
    {
        // FIX-2: API mengabaikan ?category=, klien sekarang fetch SEMUA lalu filter
        // by row.category. Test wajib include `category` di tiap row.
        Http::fake([
            '*/shipping/services*' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['courier_id' => 'jne_reg', 'name' => 'JNE REG', 'courier' => 'jne',
                        'category' => 'domestic', 'enable' => '1', 'extra_cost' => 0],
                    ['courier_id' => 'gosend_instant', 'name' => 'Gosend Instant', 'courier' => 'gosend',
                        'category' => 'instant', 'enable' => '1', 'extra_cost' => 0],
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->services('domestic');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('jne_reg', $result[0]['courier_id']);
    }

    public function test_services_caches_result(): void
    {
        Http::fake([
            '*/shipping/services*' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['courier_id' => 'jne_reg', 'name' => 'JNE REG', 'courier' => 'jne',
                        'category' => 'domestic', 'enable' => '1', 'extra_cost' => 0],
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $client->services('domestic');
        $client->services('domestic');

        Http::assertSentCount(1);
    }

    public function test_services_falls_back_to_json_on_api_error(): void
    {
        Http::fake([
            '*/shipping/services*' => Http::response(['message' => 'Error'], 500),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->services('domestic');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $ids = array_column($result, 'courier_id');
        $this->assertContains('jne_reg', $ids);
    }

    public function test_search_data_returns_parsed_results_from_api(): void
    {
        Http::fake([
            '*/shipping/data' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['province' => 'Jawa Timur', 'city' => 'Malang', 'district' => 'Kalipare'],
                ],
            ], 200),
        ]);

        $result = app(AgenwebsiteClient::class)->searchData('Kalipare');

        $this->assertCount(1, $result);
        $this->assertSame(
            ['province' => 'Jawa Timur', 'city' => 'Malang', 'district' => 'Kalipare'],
            $result[0],
        );
    }

    public function test_search_data_caches_non_empty_results(): void
    {
        Http::fake([
            '*/shipping/data' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['province' => 'Jawa Timur', 'city' => 'Malang', 'district' => 'Kalipare'],
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $client->searchData('Kalipare');
        $client->searchData('Kalipare');

        Http::assertSentCount(1);
    }

    /**
     * Regression: an empty / failed response must NOT be cached, otherwise a
     * transient blip pins "no results" for cache_master_ttl (24h) even after the
     * API recovers — the "kecamatan tidak muncul padahal ada" bug.
     */
    public function test_search_data_does_not_cache_empty_results(): void
    {
        Http::fake([
            '*/shipping/data' => Http::sequence()
                ->push(['message' => 'Success', 'data' => []], 200)
                ->push(['message' => 'Success', 'data' => [
                    ['province' => 'Jawa Timur', 'city' => 'Malang', 'district' => 'Kalipare'],
                ]], 200),
        ]);

        $client = app(AgenwebsiteClient::class);

        $this->assertSame([], $client->searchData('Kalipare'));

        $second = $client->searchData('Kalipare');
        $this->assertCount(1, $second);
        $this->assertSame('Kalipare', $second[0]['district']);

        // Two real API calls => the empty first response was never cached.
        Http::assertSentCount(2);
    }
}
