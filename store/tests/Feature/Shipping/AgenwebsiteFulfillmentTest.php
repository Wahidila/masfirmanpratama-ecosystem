<?php

namespace Tests\Feature\Shipping;

use App\Services\Shipping\AgenwebsiteClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgenwebsiteFulfillmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // FIX-11: fulfillmentRates() dihapus — endpoint /shipment/rates tidak ada
    // di license set (probe live returns "Cannot POST /v2/shipment/rates" / array msg).
    // Method itu dead code dan dihapus dari client.

    public function test_create_shipment_awb_ready(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Success',
                'data' => [
                    'airwaybill' => 'AGN123456789',
                    'reference_id' => 'REF-001',
                    'order_id' => 'ORD-001',
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->createShipment([
            'origin' => 'surabaya',
            'destination' => 'jakarta',
        ]);

        $this->assertSame('awb_ready', $result['status']);
        $this->assertSame('AGN123456789', $result['airwaybill']);
        $this->assertSame('REF-001', $result['reference_id']);
        $this->assertSame('ORD-001', $result['order_id']);
    }

    public function test_create_shipment_waiting_awb(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Success',
                'data' => [
                    'reference_id' => 'REF-002',
                    'order_id' => 'ORD-002',
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->createShipment([
            'origin' => 'surabaya',
            'destination' => 'jakarta',
        ]);

        $this->assertSame('waiting_awb', $result['status']);
        $this->assertArrayNotHasKey('airwaybill', $result);
        $this->assertArrayNotHasKey('payment_url', $result);
        $this->assertSame('REF-002', $result['reference_id']);
        $this->assertSame('ORD-002', $result['order_id']);
    }

    public function test_create_shipment_pending_payment(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Success',
                'data' => [
                    'payment_url' => 'https://payment.example.com/pay/abc123',
                    'reference_id' => 'REF-003',
                    'order_id' => 'ORD-003',
                ],
            ], 200),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->createShipment([
            'origin' => 'surabaya',
            'destination' => 'jakarta',
        ]);

        $this->assertSame('pending_payment', $result['status']);
        $this->assertSame('https://payment.example.com/pay/abc123', $result['payment_url']);
        $this->assertSame('REF-003', $result['reference_id']);
        $this->assertSame('ORD-003', $result['order_id']);
    }

    public function test_create_shipment_returns_error_on_api_failure(): void
    {
        Http::fake([
            '*/shipment/create-order' => Http::response([
                'message' => 'Invalid destination',
            ], 400),
        ]);

        $client = app(AgenwebsiteClient::class);
        $result = $client->createShipment([
            'origin' => 'surabaya',
            'destination' => 'unknown',
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Invalid destination', $result['message']);
    }
}
