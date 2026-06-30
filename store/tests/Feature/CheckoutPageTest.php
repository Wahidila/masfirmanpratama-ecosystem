<?php

namespace Tests\Feature;

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
        // FIX-3: city/province sekarang autocomplete dari /shipping/destinations
        // (Agenwebsite). Hidden inputs tetap ada agar form submit valid; UI
        // dropdown statis dengan 30 kota di-replace search box.
        $response->assertSee('id="address_city"', false);
        $response->assertSee('id="address_province"', false);
        $response->assertSee('id="address_district"', false);
        $response->assertSee('id="dest_search"', false);
        $response->assertSee('/shipping/destinations', false);
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

    // ─── POST /checkout (M1 stub) ───────────────────────────────────────────

    public function test_checkout_post_redirects_to_upload_signed_url(): void
    {
        $response = $this->post('/checkout', [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'address_line' => 'Jl. Mawar No. 12 RT 03 RW 04',
            'address_city' => 'Surabaya',
            'address_province' => 'Jawa Timur',
            'shipping_method' => 'REG',
            'payment_type' => 'lunas',
            'cart_json' => json_encode([
                ['slug' => 'kelas-amc-reguler', 'qty' => 1, 'price' => 4_500_000],
            ]),
            'cart_total' => 4_500_000,
        ]);

        $response->assertStatus(302);
        // M2: redirects to /upload/{order_number} signed URL with MFP- prefix.
        $response->assertRedirectContains('/upload/MFP-');
        $this->assertStringContainsString('signature=', $response->headers->get('Location'));
    }

    public function test_checkout_success_page_shows_order_number(): void
    {
        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        $response->assertSee('MFP-20260516-ABC123', false);
        // Order number rendered in the prominent slot with copy button.
        $response->assertSee('data-testid="order-number"', false);
        $response->assertSee('Salin nomor pesanan', false);
    }

    // ─── Checkout success page (port checkout-success.html) ─────────────────

    public function test_checkout_success_page_uses_store_layout_assets(): void
    {
        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        // Layout chrome
        $response->assertSee('Order berhasil dibuat', false);
        $response->assertSee('csrf-token', false);
        $response->assertSeeInOrder(['/build/assets/app-', '.css'], false);
        $response->assertSeeInOrder(['/build/assets/app-', '.js'], false);
        $response->assertSee('unpkg.com/lucide', false);
    }

    public function test_checkout_success_page_renders_dummy_bank_accounts_from_config(): void
    {
        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        // 2 dummy bank accounts dari config/store.php
        $response->assertSee('BCA', false);
        $response->assertSee('Mandiri', false);
        $response->assertSee('PT. Dummy AMC', false);
        // Format nomor rekening (dash-separated)
        $response->assertSee('1234-5678-9012', false);
        $response->assertSee('0987-6543-2109', false);
        // Card markers
        $response->assertSee('data-testid="bank-account"', false);
    }

    public function test_checkout_success_page_renders_lunas_total_when_payment_type_lunas(): void
    {
        // M1 stub flow: POST /checkout → flash payload → redirect ke /checkout/success/{order}
        // → page baca session payload. M2 flow: POST /checkout → redirect ke /upload signed URL,
        // success page no longer in main flow but masih ke-register sebagai legacy view.
        // Test direct GET dengan session payload simulasi (cara M1 success page baca data).
        $response = $this->withSession([
            'checkout.payload' => [
                'payment_type' => 'lunas',
                'cart_total' => 4525000,
            ],
        ])->get('/checkout/success/MFP-20260516-LUNAS1');

        $response->assertStatus(200);
        $response->assertSee('Total Transfer (Lunas)', false);
        $response->assertSee('Rp 4.525.000', false);
        $response->assertDontSee('Total Transfer (Down Payment)', false);
    }

    public function test_checkout_success_page_renders_dp_total_and_schedule_when_cicilan(): void
    {
        $schedule = json_encode([
            ['label' => 'Down Payment', 'note' => 'Bayar sekarang (30% dari total)', 'due_label' => 'Hari ini', 'amount' => 1357500],
            ['label' => 'Cicilan ke-1 dari 2', 'note' => '', 'due_label' => '16 Jun 2026', 'amount' => 1583750],
            ['label' => 'Cicilan ke-2 dari 2', 'note' => 'Cicilan terakhir', 'due_label' => '16 Jul 2026', 'amount' => 1583750],
        ]);

        $response = $this->withSession([
            'checkout.payload' => [
                'payment_type' => 'cicilan',
                'cart_total' => 4525000,
                'schedule_json' => $schedule,
            ],
        ])->get('/checkout/success/MFP-20260516-CIC123');

        $response->assertStatus(200);
        $response->assertSee('Total Transfer (Down Payment)', false);
        $response->assertSee('Rp 1.357.500', false);
        $response->assertSee('Jadwal Pembayaran', false);
        $response->assertSee('Cicilan ke-1 dari 2', false);
        $response->assertSee('Cicilan ke-2 dari 2', false);
        $response->assertSee('16 Jul 2026', false);
        $response->assertSee('Rp 1.583.750', false);
    }

    public function test_checkout_success_page_links_to_upload_and_track(): void
    {
        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        // Upload CTA wired to upload route — query string carries M1 payment
        // context (type/total/n/seq) so upload page can pre-fill, so we check
        // the path-only prefix rather than a fully literal href.
        $response->assertSee('href="'.url('/upload/MFP-20260516-ABC123'), false);
        $response->assertSee('Upload bukti bayar sekarang', false);
        $response->assertSee('href="'.url('/track/MFP-20260516-ABC123').'"', false);
        $response->assertSee('Track order', false);
    }

    public function test_checkout_success_page_renders_wa_admin_link(): void
    {
        $response = $this->get('/checkout/success/MFP-20260516-ABC123');

        $response->assertStatus(200);
        $waNumber = config('store.wa_admin.number');
        $response->assertSee('https://wa.me/'.$waNumber, false);
        $response->assertSee('Chat admin di WhatsApp', false);
    }

    // ─── Cart link integrity (regression) ───────────────────────────────────

    public function test_navbar_checkout_url_uses_named_route(): void
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        $response->assertSee(route('cart.index'), false); // navbar link
    }
}
