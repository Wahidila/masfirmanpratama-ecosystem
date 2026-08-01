<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutShippingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Product::factory()->create([
            'slug' => 'buku-mpl',
            'title' => 'Buku MPL',
            'price' => 185_000,
            'status' => 'active',
            'type' => 'book',
            'is_shippable' => true,
            'weight_kg' => 0.5,
        ]);

        Product::factory()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas AMC Reguler',
            'price' => 4_500_000,
            'status' => 'active',
            'type' => 'course',
            'is_shippable' => false,
            'weight_kg' => null,
        ]);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'address_line' => 'Jl. Merdeka No. 12',
            'address_city' => 'Jakarta Selatan',
            'address_province' => 'DKI Jakarta',
            'address_postal' => '12110',
            'shipping_method' => '',
            'payment_type' => 'lunas',
            'installment_scheme_id' => null,
            'cart_json' => json_encode([
                ['slug' => 'buku-mpl', 'name' => 'Buku MPL', 'price' => 185_000, 'qty' => 1],
            ]),
            'cart_total' => 185_000,
            'ref_code' => null,
        ], $overrides);
    }

    public function test_checkout_with_dynamic_shipping_correctly_calculates_total(): void
    {
        Http::fake([
            '*/shipping/services' => Http::response([
                'message' => 'OK',
                'data' => [
                    ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                        'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ],
            ], 200),
            '*/shipping/couriers' => Http::response([
                'message' => 'OK',
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

        $this->post('/checkout', $this->validPayload([
            'shipping_method' => 'jne_reg',
            'cart_total' => 185_000 + 17_000,
        ]));

        $order = Order::first();
        $this->assertSame('202000.00', $order->total);
        $this->assertSame('jne', $order->shipping_courier);
    }

    public function test_server_total_is_authoritative_over_client_cart_total(): void
    {
        Http::fake([
            '*/shipping/services' => Http::response([
                'message' => 'OK',
                'data' => [
                    ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                        'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ],
            ], 200),
            '*/shipping/couriers' => Http::response([
                'message' => 'OK',
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

        // Client kirim cart_total tanpa ongkir (185.000), tapi server otoritatif:
        // subtotal 185.000 (harga DB) + ongkir API 17.000 = 202.000. Order DIBUAT
        // dengan total server (cart client yang "stale"/kurang TIDAK memblokir
        // checkout — itu yang dulu bikin false-reject "Total cart berbeda").
        // Customer tetap bayar total server yang tampil di halaman upload.
        $this->post('/checkout', $this->validPayload([
            'shipping_method' => 'jne_reg',
            'cart_total' => 185_000,
        ]))->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('202000.00', $order->total);
        $this->assertSame(17000, (int) $order->shipping_cost);
    }

    public function test_grossly_underpaid_total_is_rejected(): void
    {
        // cart_total jauh di bawah server (< 50%) → indikasi korupsi/tamper → tolak.
        $this->post('/checkout', $this->validPayload([
            'shipping_method' => '',
            'cart_total' => 1,
        ]))->assertSessionHasErrors();

        $this->assertNull(Order::first());
    }

    public function test_physical_cart_requires_shipping_method(): void
    {
        // Cart berisi buku fisik tanpa metode pengiriman → ditolak dengan pesan
        // jelas (bukan "Total cart berbeda" yang membingungkan).
        $this->post('/checkout', $this->validPayload([
            'shipping_method' => '',
        ]))->assertSessionHasErrors('shipping_method');

        $this->assertNull(Order::first());
    }

    public function test_digital_cart_works_without_shipping(): void
    {
        $this->post('/checkout', [
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
                ['slug' => 'kelas-amc-reguler', 'name' => 'Kelas AMC Reguler', 'price' => 4_500_000, 'qty' => 1],
            ]),
            'cart_total' => 4_500_000,
            'ref_code' => null,
        ]);

        $order = Order::first();
        $this->assertSame('4500000.00', $order->total);
        $this->assertNull($order->shipping_courier);
    }

    public function test_structured_address_persists_discrete_columns_and_village(): void
    {
        Http::fake([
            '*/shipping/services' => Http::response([
                'message' => 'OK',
                'data' => [
                    ['courier_id' => 'jne_reg', 'name' => 'REG', 'courier' => 'jne',
                        'category' => 'domestic', 'is_premium' => 0, 'enable' => 1, 'extra_cost' => 0],
                ],
            ], 200),
            '*/shipping/couriers' => Http::response([
                'message' => 'OK',
                'data' => [['id' => 'jne', 'title' => 'JNE', 'category' => 'domestic']],
            ], 200),
            '*/shipping/price' => Http::response([
                'message' => 'Success',
                'data' => [
                    ['courier' => 'jne', 'service' => 'jne_reg', 'service_name' => 'REG',
                        'price' => '17000', 'etd' => '1-2 days'],
                ],
            ], 200),
        ]);

        $this->post('/checkout', $this->validPayload([
            'address_line' => 'Jl. Merdeka No. 12, RT 03/RW 04',
            'address_province' => 'Jawa Barat',
            'address_city' => 'Bandung',
            'address_district' => 'Coblong',
            'address_village' => 'Dago',
            'address_postal' => '40135',
            'shipping_method' => 'jne_reg',
            'cart_total' => 185_000 + 17_000,
        ]));

        $order = Order::first();
        $this->assertNotNull($order);

        // Kolom diskrit tersimpan (dipakai ongkir + fulfillment).
        $this->assertSame('Jawa Barat', $order->shipping_province);
        $this->assertSame('Bandung', $order->shipping_city);
        $this->assertSame('Coblong', $order->shipping_district);
        $this->assertSame('Dago', $order->shipping_village);
        $this->assertSame('40135', $order->shipping_zipcode);

        // Alamat lengkap terangkai terstruktur: jalan, desa, kecamatan, kota, prov, kodepos.
        $this->assertSame(
            'Jl. Merdeka No. 12, RT 03/RW 04, Dago, Coblong, Bandung, Jawa Barat, 40135',
            $order->address,
        );
    }
}
