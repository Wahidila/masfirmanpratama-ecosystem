<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // M2 task t_a3f2fe94: checkout flow now hits DB (CheckoutController).
        // Seed minimum product so any POST /checkout test has resolvable cart.
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
    // ─── GET /checkout ──────────────────────────────────────────────────────

    public function test_checkout_page_returns_200(): void
    {
        $this->get('/checkout')->assertStatus(200);
    }

    public function test_checkout_page_uses_store_layout_assets(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        // Vite-injected CSS + JS markers
        $response->assertSeeInOrder(['/build/assets/app-', '.css'], false);
        $response->assertSeeInOrder(['/build/assets/app-', '.js'], false);
        $response->assertSee('csrf-token', false);
        $response->assertSee('unpkg.com/lucide', false);
        // Page chrome
        $response->assertSee('Checkout Pembelian', false);
    }

    public function test_checkout_page_renders_customer_form_fields(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        // Required fields per task spec (nama, email, HP, alamat).
        $response->assertSee('id="customer_name"', false);
        $response->assertSee('id="customer_email"', false);
        $response->assertSee('id="customer_phone"', false);
        $response->assertSee('id="address_line"', false);

        // Form alamat terstruktur sesuai kebutuhan plugin Agenwebsite:
        // Provinsi (dropdown kanonik) → autocomplete Kota/Kecamatan (API) →
        // Desa/Kelurahan → Kode Pos → Detail Alamat.
        $response->assertSee('id="address_province"', false);
        $response->assertSee('id="dest_search"', false);
        $response->assertSee('/shipping/destinations', false);
        $response->assertSee('id="address_city"', false);
        $response->assertSee('id="address_district"', false);
        $response->assertSee('id="address_village"', false);
        $response->assertSee('id="address_postal"', false);

        // Dropdown provinsi pakai penamaan kanonik API.
        $response->assertSee('Daerah Istimewa Yogyakarta', false);
        $response->assertSee('Nanggroe Aceh Darussalam', false);
        $response->assertSee('Jawa Timur', false);
    }

    public function test_checkout_page_renders_shipping_method_dropdown(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        // Shipping method select (M1 hardcoded, M2 wire ke Agenwebsite.com)
        $response->assertSee('id="shipping_method"', false);
        // Dropdown rendered via Alpine x-for over shippingMethods, not server-rendered <option>s.
        // Assert the codes/labels are injected as JSON config to Alpine instead.
        $response->assertSee('"REG"', false);
        $response->assertSee('"YES"', false);
        $response->assertSee('"OKE"', false);
        $response->assertSee('JNE Reguler', false);
    }

    public function test_checkout_page_renders_payment_type_radio(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        // Payment type is always lunas (hidden input)
        $response->assertSee('name="payment_type"', false);
        $response->assertSee('value="lunas"', false);
        $response->assertDontSee('value="cicilan"', false);
    }

    public function test_checkout_page_exposes_alpine_store_cart_bindings(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        // Page reads cart from $store.cart, not server-rendered cart state.
        $response->assertSee('$store.cart.isEmpty', false);
        $response->assertSee('$store.cart.items', false);
        $response->assertSee('$store.cart.subtotal', false);
        // Cart payload pushed to backend on submit.
        $response->assertSee('name="cart_json"', false);
    }

    public function test_checkout_page_renders_summary_with_subtotal_total_and_cta(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Pesanan', false);
        $response->assertSee('Subtotal', false);
        $response->assertSee('Ongkir', false);
        $response->assertSee('Proses Pembayaran', false);
    }

    public function test_checkout_page_form_posts_to_checkout_store_route(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        // Form action wired to named POST route, not legacy prototype URL.
        $response->assertSee('action="'.route('checkout.store').'"', false);
        $response->assertSee('method="POST"', false);
        $response->assertDontSee('action="checkout-success.html"', false);
    }

    // ─── POST /checkout → halaman success ───────────────────────────────────

    public function test_checkout_post_redirects_to_success_page(): void
    {
        $response = $this->post('/checkout', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'address_line' => 'Jl. Mawar No. 12 RT 03 RW 04',
            'address_city' => 'Surabaya',
            'address_province' => 'Jawa Timur',
            'payment_type' => 'lunas',
            'cart_json' => json_encode([
                ['slug' => 'kelas-amc-reguler', 'qty' => 1, 'price' => 4_500_000],
            ]),
            'cart_total' => 4_500_000,
        ]);

        // Sekarang redirect ke halaman "Order berhasil dibuat" (bukan langsung upload).
        $response->assertStatus(302);
        $response->assertRedirectContains('/checkout/success/MFP-');
    }

    /**
     * Seed Order + payments untuk test halaman success (DB-backed via firstOrFail).
     */
    private function seedOrder(string $orderNumber, int $total, int $paymentCount = 1): Order
    {
        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'address' => 'Jl. Mawar No. 12, Surabaya, Jawa Timur',
            'total' => $total,
            'status' => 'pending',
        ]);

        $per = (int) floor($total / $paymentCount);
        for ($i = 0; $i < $paymentCount; $i++) {
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $i === $paymentCount - 1 ? $total - $per * ($paymentCount - 1) : $per,
                'method' => 'transfer',
                'status' => 'pending',
            ]);
        }

        return $order;
    }

    public function test_checkout_success_page_shows_order_number(): void
    {
        $this->seedOrder('MFP-20260516-ABC123', 4_500_000);

        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        $response->assertSee('MFP-20260516-ABC123', false);
        $response->assertSee('data-testid="order-number"', false);
        $response->assertSee('Salin nomor pesanan', false);
    }

    public function test_checkout_success_page_uses_store_layout_assets(): void
    {
        $this->seedOrder('MFP-20260516-ABC123', 4_500_000);

        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        $response->assertSee('Order berhasil dibuat', false);
        $response->assertSee('csrf-token', false);
        $response->assertSeeInOrder(['/build/assets/app-', '.css'], false);
        $response->assertSeeInOrder(['/build/assets/app-', '.js'], false);
        $response->assertSee('unpkg.com/lucide', false);
    }

    public function test_checkout_success_page_renders_dummy_bank_accounts_from_config(): void
    {
        $this->seedOrder('MFP-20260516-ABC123', 4_500_000);

        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        $response->assertSee('BCA', false);
        $response->assertSee('Mandiri', false);
        $response->assertSee('PT. Dummy AMC', false);
        $response->assertSee('1234-5678-9012', false);
        $response->assertSee('0987-6543-2109', false);
        $response->assertSee('data-testid="bank-account"', false);
    }

    public function test_checkout_success_page_renders_lunas_total(): void
    {
        // Book selalu lunas → 1 payment = total. Total transfer = total order.
        $this->seedOrder('MFP-20260516-LUNAS1', 4_525_000, 1);

        $response = $this->get('/checkout/success/MFP-20260516-LUNAS1');

        $response->assertStatus(200);
        $response->assertSee('Total Transfer (Lunas)', false);
        $response->assertSee('Rp 4.525.000', false);
        $response->assertDontSee('Total Transfer (Down Payment)', false);
    }

    public function test_checkout_success_page_shows_dp_total_when_order_has_multiple_payments(): void
    {
        // Order dgn >1 payment (mis. dari flow kelas) → success page generik
        // menampilkan DP (payment pertama). Book tidak menghasilkan ini, tapi
        // view harus benar bila order punya cicilan.
        $order = $this->seedOrder('MFP-20260516-CIC123', 4_500_000, 3); // 3 payment: 1.5jt each
        $first = $order->payments()->orderBy('id')->first();

        $response = $this->get('/checkout/success/MFP-20260516-CIC123');

        $response->assertStatus(200);
        $response->assertSee('Total Transfer (Down Payment)', false);
        $response->assertSee('Rp '.number_format((int) $first->amount, 0, ',', '.'), false);
    }

    public function test_checkout_success_page_links_to_signed_upload_and_track(): void
    {
        $this->seedOrder('MFP-20260516-ABC123', 4_500_000);

        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        // Tombol upload + track HARUS pakai signed URL (bukan unsigned route).
        $response->assertSee('data-testid="cta-upload"', false);
        $response->assertSee('Upload bukti bayar sekarang', false);
        $response->assertSee('/upload/MFP-20260516-ABC123?', false);
        $response->assertSee('/track/MFP-20260516-ABC123?', false);
        $response->assertSee('signature=', false);
        $response->assertSee('Track order', false);
    }

    public function test_checkout_success_page_renders_wa_admin_link(): void
    {
        $this->seedOrder('MFP-20260516-ABC123', 4_500_000);

        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        $waNumber = config('store.wa_admin.number');
        $response->assertSee('https://wa.me/'.$waNumber, false);
        $response->assertSee('Chat admin di WhatsApp', false);
    }

    public function test_checkout_success_page_404_for_unknown_order(): void
    {
        $this->get('/checkout/success/MFP-99999999-NOPE99')->assertStatus(404);
    }

    // ─── Cart link integrity (regression) ───────────────────────────────────

    public function test_navbar_checkout_url_uses_named_route(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        $response->assertSee(route('cart.index'), false); // navbar link
    }
}
