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
}
