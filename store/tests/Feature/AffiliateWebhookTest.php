<?php

namespace Tests\Feature;

use App\Events\PaymentVerified;
use App\Listeners\DispatchAffiliateOrderPaid;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test webhook dispatch ke Affiliate system (order-paid)
 * dan referral cookie capture saat checkout.
 */
class AffiliateWebhookTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // Webhook dispatch tests
    // ─────────────────────────────────────────────────────────────

    public function test_webhook_dispatched_with_correct_signature_when_order_has_ref_code(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhook',
            'webhook.secret' => 'test-secret-key',
            'webhook.timeout' => 5,
            'webhook.retries' => 1,
        ]);

        $order = Order::factory()->create([
            'order_number' => 'MFP-20260618-ABC123',
            'customer_name' => 'Budi Santoso',
            'total' => 4500000,
            'status' => 'paid',
            'ref_code' => 'FIRMAN01',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
            'course_id' => null,
            'qty' => 1,
            'unit_price' => 4500000,
            'subtotal' => 4500000,
        ]);

        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 4500000,
            'status' => 'verified',
        ]);

        // Dispatch event seperti yang dilakukan admin approve
        $event = new PaymentVerified($order, $payment);
        $listener = app(DispatchAffiliateOrderPaid::class);
        $listener->handle($event);

        Http::assertSent(function ($request) {
            // Cek URL benar
            if ($request->url() !== 'https://affiliate.test/webhook') {
                return false;
            }

            // Cek header X-Webhook-Event
            if ($request->header('X-Webhook-Event')[0] !== 'order-paid') {
                return false;
            }

            // Cek signature format 'sha256=<hex>'
            $signature = $request->header('X-Signature')[0] ?? '';
            if (! str_starts_with($signature, 'sha256=')) {
                return false;
            }

            // Verify HMAC signature valid
            $body = $request->body();
            $expectedSignature = 'sha256='.hash_hmac('sha256', $body, 'test-secret-key');
            if ($signature !== $expectedSignature) {
                return false;
            }

            // Cek payload content
            $payload = json_decode($body, true);
            if ($payload['event'] !== 'order-paid') {
                return false;
            }
            if ($payload['store_order_id'] !== 'MFP-20260618-ABC123') {
                return false;
            }
            if ($payload['ref_code'] !== 'FIRMAN01') {
                return false;
            }
            if ($payload['buyer_name'] !== 'Budi Santoso') {
                return false;
            }
            if ((float) $payload['order_total'] !== 4500000.0) {
                return false;
            }
            if ($payload['product_type'] !== 'book') {
                return false;
            }
            if ($payload['idempotency_key'] !== sha1('MFP-20260618-ABC123-order-paid')) {
                return false;
            }

            return true;
        });
    }

    public function test_webhook_not_dispatched_when_order_has_no_ref_code(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhook',
            'webhook.secret' => 'test-secret-key',
        ]);

        $order = Order::factory()->create([
            'order_number' => 'MFP-20260618-DEF456',
            'customer_name' => 'Siti Rahayu',
            'total' => 185000,
            'status' => 'paid',
            'ref_code' => null,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
            'course_id' => null,
        ]);

        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 185000,
            'status' => 'verified',
        ]);

        $event = new PaymentVerified($order, $payment);
        $listener = app(DispatchAffiliateOrderPaid::class);
        $listener->handle($event);

        Http::assertNothingSent();
    }

    public function test_webhook_skipped_when_url_or_secret_empty(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => '',
            'webhook.secret' => '',
        ]);

        $order = Order::factory()->create([
            'order_number' => 'MFP-20260618-GHI789',
            'customer_name' => 'Andi Wijaya',
            'total' => 300000,
            'status' => 'paid',
            'ref_code' => 'FIRMAN02',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
            'course_id' => null,
        ]);

        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 300000,
            'status' => 'verified',
        ]);

        $event = new PaymentVerified($order, $payment);
        $listener = app(DispatchAffiliateOrderPaid::class);
        $listener->handle($event);

        Http::assertNothingSent();
    }

    public function test_webhook_detects_course_product_type(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhook',
            'webhook.secret' => 'secret123',
            'webhook.retries' => 1,
        ]);

        $order = Order::factory()->create([
            'order_number' => 'COURSE-20260618-XYZ-ABCDEF',
            'customer_name' => 'Dewi Lestari',
            'total' => 4500000,
            'status' => 'paid',
            'ref_code' => 'ALUMNI01',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'course_id' => Course::factory()->active()->create()->id,
        ]);

        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 4500000,
            'status' => 'verified',
        ]);

        $event = new PaymentVerified($order, $payment);
        $listener = app(DispatchAffiliateOrderPaid::class);
        $listener->handle($event);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);

            return $payload['product_type'] === 'course';
        });
    }

    public function test_webhook_detects_mixed_product_type(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        config([
            'webhook.affiliate_url' => 'https://affiliate.test/webhook',
            'webhook.secret' => 'secret123',
            'webhook.retries' => 1,
        ]);

        $order = Order::factory()->create([
            'order_number' => 'MFP-20260618-MIX001',
            'customer_name' => 'Rudi Hartono',
            'total' => 4685000,
            'status' => 'paid',
            'ref_code' => 'PESERTA01',
        ]);

        // Item buku
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
            'course_id' => null,
        ]);

        // Item kelas
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'course_id' => Course::factory()->active()->create()->id,
        ]);

        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 4685000,
            'status' => 'verified',
        ]);

        $event = new PaymentVerified($order, $payment);
        $listener = app(DispatchAffiliateOrderPaid::class);
        $listener->handle($event);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);

            return $payload['product_type'] === 'mixed';
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Referral cookie capture tests
    // ─────────────────────────────────────────────────────────────

    public function test_book_checkout_captures_referral_code_from_cookie(): void
    {
        Product::factory()->create([
            'slug' => 'buku-mpl',
            'title' => 'Buku MPL',
            'price' => 185_000,
            'status' => 'active',
            'type' => 'book',
        ]);

        $response = $this->withCookie('referral_code', 'AFFILIATE99')
            ->post('/checkout', [
                'customer_name' => 'Budi Test',
                'customer_email' => 'budi@test.com',
                'customer_phone' => '081234567890',
                'address_line' => 'Jl. Test No. 1',
                'address_city' => 'Malang',
                'address_province' => 'Jawa Timur',
                'address_postal' => '65111',
                'shipping_method' => null,
                'payment_type' => 'lunas',
                'cart_json' => json_encode([
                    ['slug' => 'buku-mpl', 'name' => 'Buku MPL', 'price' => 185000, 'qty' => 1],
                ]),
                'cart_total' => 185000,
                'ref_code' => null,
            ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('AFFILIATE99', $order->ref_code);
    }

    public function test_book_checkout_form_input_overrides_cookie(): void
    {
        Product::factory()->create([
            'slug' => 'buku-mpl',
            'title' => 'Buku MPL',
            'price' => 185_000,
            'status' => 'active',
            'type' => 'book',
        ]);

        $response = $this->withCookie('referral_code', 'COOKIE_CODE')
            ->post('/checkout', [
                'customer_name' => 'Budi Test',
                'customer_email' => 'budi@test.com',
                'customer_phone' => '081234567890',
                'address_line' => 'Jl. Test No. 1',
                'address_city' => 'Malang',
                'address_province' => 'Jawa Timur',
                'address_postal' => '65111',
                'shipping_method' => null,
                'payment_type' => 'lunas',
                'cart_json' => json_encode([
                    ['slug' => 'buku-mpl', 'name' => 'Buku MPL', 'price' => 185000, 'qty' => 1],
                ]),
                'cart_total' => 185000,
                'ref_code' => 'FORM_CODE',
            ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('FORM_CODE', $order->ref_code);
    }

    public function test_course_checkout_captures_referral_code_from_cookie(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-amc-test',
            'title' => 'Kelas AMC Test',
            'price' => 4500000,
        ]);

        $response = $this->withCookie('referral_code', 'REFCOURSE01')
            ->post("/kelas/{$course->slug}/checkout", [
                'customer_name' => 'Siti Test',
                'customer_email' => 'siti@test.com',
                'customer_phone' => '081234567891',
                'occupation' => 'Mahasiswa',
                'motivation' => 'Ingin belajar',
                'payment_type' => 'lunas',
            ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('REFCOURSE01', $order->ref_code);
    }

    public function test_course_checkout_form_ref_code_overrides_cookie(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-amc-test2',
            'title' => 'Kelas AMC Test 2',
            'price' => 4500000,
        ]);

        $response = $this->withCookie('referral_code', 'COOKIE_REF')
            ->post("/kelas/{$course->slug}/checkout", [
                'customer_name' => 'Andi Test',
                'customer_email' => 'andi@test.com',
                'customer_phone' => '081234567892',
                'occupation' => 'PNS',
                'motivation' => 'Pengembangan diri',
                'payment_type' => 'lunas',
                'ref_code' => 'FORM_REF',
            ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('FORM_REF', $order->ref_code);
    }

    public function test_course_checkout_stores_occupation_in_order_meta_not_ref_code(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-meta-test',
            'title' => 'Kelas Meta Test',
            'price' => 4500000,
        ]);

        $this->post("/kelas/{$course->slug}/checkout", [
            'customer_name' => 'Meta Test',
            'customer_email' => 'meta@test.com',
            'customer_phone' => '081234567893',
            'occupation' => 'Dokter',
            'motivation' => 'Belajar mindset',
            'payment_type' => 'lunas',
        ]);

        $order = Order::first();
        $this->assertNotNull($order);
        // ref_code harus null (bukan JSON occupation/motivation)
        $this->assertNull($order->ref_code);
        // Data occupation/motivation ada di order_meta
        $this->assertIsArray($order->order_meta);
        $this->assertSame('Dokter', $order->order_meta['occupation']);
        $this->assertSame('Belajar mindset', $order->order_meta['motivation']);
    }
}
