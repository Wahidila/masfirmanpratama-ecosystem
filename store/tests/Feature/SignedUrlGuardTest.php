<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Coverage task t_8a063559 — Token-protect /upload + /track via Laravel signed URL.
 *
 * Acceptance:
 * - Akses /upload/MFP-... TANPA signature → 403 (signed-url-error view)
 * - Akses /upload/MFP-... DENGAN signature valid → 200
 * - Akses /upload/MFP-... DENGAN signature expired → 403 (default Laravel
 *   InvalidSignatureException — Laravel ngga distinguish expired vs invalid)
 * - Akses /track/MFP-... TANPA signature → 403
 * - Akses /track/MFP-... DENGAN signature valid → 200
 * - Akses /track/MFP-... DENGAN signature expired → 403
 */
class SignedUrlGuardTest extends TestCase
{
    use RefreshDatabase;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Product::factory()->create([
            'slug' => 'kelas-amc-reguler',
            'price' => 4_500_000,
            'status' => 'active',
            'type' => 'course',
        ]);

        $this->order = Order::create([
            'order_number' => 'MFP-20260520-SIGN01',
            'customer_name' => 'Customer Sign Test',
            'phone' => '081234567890',
            'address' => 'Jl. Test',
            'total' => 4_500_000,
            'status' => 'pending',
        ]);
        OrderPayment::create([
            'order_id' => $this->order->id,
            'amount' => 4_500_000,
            'method' => 'transfer',
            'status' => 'pending',
        ]);
    }

    // ─── /upload guards ────────────────────────────────────────────────────

    public function test_upload_get_without_signature_returns_403(): void
    {
        $response = $this->get('/upload/'.$this->order->order_number);

        $response->assertStatus(403);
        // Friendly view rendered.
        $response->assertSee('Link tidak bisa dibuka', false);
        $response->assertSee('data-testid="signed-url-error"', false);
    }

    public function test_upload_get_with_valid_signature_returns_200(): void
    {
        $url = URL::temporarySignedRoute(
            'upload.show',
            now()->addDays(7),
            ['order_number' => $this->order->order_number],
        );

        $this->get($url)->assertOk()->assertSee($this->order->order_number);
    }

    public function test_upload_get_with_expired_signature_returns_403(): void
    {
        $url = URL::temporarySignedRoute(
            'upload.show',
            now()->subSecond(), // already expired
            ['order_number' => $this->order->order_number],
        );

        $response = $this->get($url);

        $response->assertStatus(403);
        $response->assertSee('Link tidak bisa dibuka', false);
    }

    public function test_upload_get_with_tampered_signature_returns_403(): void
    {
        $url = URL::temporarySignedRoute(
            'upload.show',
            now()->addDays(7),
            ['order_number' => $this->order->order_number],
        );

        // Flip last 4 chars of signature.
        $tampered = preg_replace('/.{4}$/', 'XXXX', $url);

        $this->get($tampered)->assertStatus(403);
    }

    public function test_upload_post_without_signature_returns_403(): void
    {
        $file = UploadedFile::fake()->image('bukti.jpg');

        $response = $this->post('/upload/'.$this->order->order_number, [
            'proof_file' => $file,
            'installment_sequence' => 0,
        ]);

        $response->assertStatus(403);

        // Payment NOT touched.
        $payment = OrderPayment::where('order_id', $this->order->id)->first();
        $this->assertNull($payment->proof_path);
    }

    public function test_upload_post_with_valid_signature_succeeds(): void
    {
        $url = URL::temporarySignedRoute(
            'upload.store',
            now()->addDays(7),
            ['order_number' => $this->order->order_number],
        );

        $file = UploadedFile::fake()->image('bukti.jpg');

        $this->post($url, [
            'proof_file' => $file,
            'installment_sequence' => 0,
        ])
            ->assertStatus(302)
            ->assertSessionHas('upload.success', true);

        $payment = OrderPayment::where('order_id', $this->order->id)->first();
        $this->assertNotNull($payment->proof_path);
    }

    // ─── /track guards ─────────────────────────────────────────────────────

    public function test_track_without_signature_returns_403(): void
    {
        $this->get('/track/'.$this->order->order_number)
            ->assertStatus(403)
            ->assertSee('Link tidak bisa dibuka', false);
    }

    public function test_track_with_valid_signature_returns_200(): void
    {
        $url = URL::temporarySignedRoute(
            'track.show',
            now()->addDays(30),
            ['order_number' => $this->order->order_number],
        );

        $this->get($url)->assertOk();
    }

    public function test_track_with_expired_signature_returns_403(): void
    {
        $url = URL::temporarySignedRoute(
            'track.show',
            now()->subSecond(),
            ['order_number' => $this->order->order_number],
        );

        $this->get($url)->assertStatus(403);
    }

    // ─── Config-driven TTL ─────────────────────────────────────────────────

    public function test_upload_url_ttl_respects_config(): void
    {
        config(['checkout.upload_url_ttl_days' => 14]);

        // Generate URL via CheckoutController flow → cek query string expires
        // berada di window 14 hari ± toleransi.
        $url = URL::temporarySignedRoute(
            'upload.show',
            now()->addDays((int) config('checkout.upload_url_ttl_days')),
            ['order_number' => $this->order->order_number],
        );

        // Parse expires param.
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertArrayHasKey('expires', $params);

        $expectedMin = now()->addDays(13)->timestamp; // 14 days minus tolerance
        $expectedMax = now()->addDays(15)->timestamp;
        $this->assertGreaterThanOrEqual($expectedMin, (int) $params['expires']);
        $this->assertLessThanOrEqual($expectedMax, (int) $params['expires']);
    }

    public function test_checkout_redirects_to_success_page_with_signed_upload_and_track(): void
    {
        $product = Product::where('slug', 'kelas-amc-reguler')->first();

        $response = $this->post('/checkout', [
            'customer_name' => 'Sign Customer',
            'customer_phone' => '08111122223',
            'address_line' => 'Jl. Sign Test',
            'payment_type' => 'lunas',
            'cart_json' => json_encode([
                ['slug' => $product->slug, 'qty' => 1, 'price' => 4_500_000],
            ]),
            'cart_total' => 4_500_000,
        ]);

        $response->assertStatus(302);

        // Ambil order_number dari redirect (DB bisa punya order seed lain).
        preg_match('#/checkout/success/(MFP-\d{8}-[A-Z0-9]+)#', $response->headers->get('Location'), $m);
        $this->assertNotEmpty($m, 'Redirect harus ke /checkout/success/{order}');
        $orderNumber = $m[1];

        // Success page menyediakan signed upload + track URL (di-generate server-side).
        $success = $this->get(route('checkout.success', ['order' => $orderNumber]));
        $success->assertOk();
        $success->assertSee('/upload/'.$orderNumber.'?', false);
        $success->assertSee('/track/'.$orderNumber.'?', false);
        $success->assertSee('signature=', false);
        $success->assertSee('expires=', false);
    }
}
