<?php

namespace Tests\Feature;

use App\Events\PaymentSubmitted;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Coverage task t_c0616c67 — Wire FE→BE POST /upload/{order}.
 *
 * Schema source-of-truth: order_payments.status enum (pending|verified|rejected).
 * Task body sebut 'pending_verification' yang ngga ada — payment yang udah upload
 * tetap status='pending' (waiting admin verify), bukti exist via proof_path
 * not null + paid_at not null. Order.status tidak transition saat upload.
 */
class UploadStoreDbTest extends TestCase
{
    use RefreshDatabase;

    private function signedShow(string $orderNumber, array $query = []): string
    {
        return URL::temporarySignedRoute(
            'upload.show',
            now()->addDays(7),
            array_merge(['order_number' => $orderNumber], $query),
        );
    }

    private function signedStore(string $orderNumber): string
    {
        return URL::temporarySignedRoute(
            'upload.store',
            now()->addDays(7),
            ['order_number' => $orderNumber],
        );
    }

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $product = Product::factory()->create([
            'slug' => 'kelas-amc-reguler',
            'price' => 4_500_000,
            'status' => 'active',
            'type' => 'course',
        ]);

        // Order lunas dengan 1 pending payment.
        $this->order = Order::create([
            'order_number' => 'MFP-20260520-TEST01',
            'customer_name' => 'Customer Test',
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

    public function test_show_real_order_renders_view_with_db_data(): void
    {
        $this->get($this->signedShow($this->order->order_number))
            ->assertOk()
            ->assertSee($this->order->order_number);
    }

    public function test_show_unknown_order_falls_back_to_m1_stub(): void
    {
        $this->get($this->signedShow('MFP-NOT-EXIST', ['type' => 'lunas', 'total' => 4500000, 'n' => 1]))
            ->assertOk();
    }

    public function test_store_happy_path_saves_proof_and_updates_payment(): void
    {
        Event::fake();

        $file = UploadedFile::fake()->image('bukti.jpg');

        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $file,
            'installment_sequence' => 0,
        ])
            ->assertStatus(302)
            ->assertRedirectContains('/upload/'.$this->order->order_number)
            ->assertSessionHas('upload.success', true);

        $payment = OrderPayment::where('order_id', $this->order->id)->first();

        $this->assertNotNull($payment->proof_path, 'proof_path must be set after upload');
        $this->assertNotNull($payment->paid_at, 'paid_at must be set after upload');
        $this->assertSame('pending', $payment->status, 'Status stays pending until admin verify');
        $this->assertStringContainsString('payment-proofs/', $payment->proof_path);

        // File actually saved.
        Storage::disk('public')->assertExists($payment->proof_path);

        // Order status NOT mutated (waiting admin verify).
        $this->order->refresh();
        $this->assertSame('pending', $this->order->status);

        // Event fired.
        Event::assertDispatched(PaymentSubmitted::class, function ($event) use ($payment) {
            return $event->payment->id === $payment->id
                && $event->order->id === $this->order->id;
        });
    }

    public function test_store_validates_file_required(): void
    {
        $this->post($this->signedStore($this->order->order_number), [
            'installment_sequence' => 0,
        ])
            ->assertSessionHasErrors('proof_file');

        $payment = OrderPayment::where('order_id', $this->order->id)->first();
        $this->assertNull($payment->proof_path);
    }

    public function test_store_validates_file_max_2mb(): void
    {
        $oversized = UploadedFile::fake()->image('huge.jpg')->size(2049); // 2049 KB > 2 MB

        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $oversized,
            'installment_sequence' => 0,
        ])
            ->assertSessionHasErrors('proof_file');
    }

    public function test_store_validates_file_must_be_image(): void
    {
        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $pdf,
            'installment_sequence' => 0,
        ])
            ->assertSessionHasErrors('proof_file');
    }

    public function test_store_rejects_invalid_sequence_index(): void
    {
        $file = UploadedFile::fake()->image('bukti.jpg');

        // Order cuma 1 payment (lunas) → seq=5 invalid
        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $file,
            'installment_sequence' => 5,
        ])
            ->assertSessionHasErrors('installment_sequence');
    }

    public function test_store_rejects_double_upload_for_same_payment(): void
    {
        $file1 = UploadedFile::fake()->image('first.jpg');

        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $file1,
            'installment_sequence' => 0,
        ])->assertRedirect();

        // Second upload for same payment → reject.
        $file2 = UploadedFile::fake()->image('second.jpg');
        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $file2,
            'installment_sequence' => 0,
        ])
            ->assertSessionHasErrors('proof_file');
    }

    public function test_store_unknown_order_falls_back_to_m1_stub_flash(): void
    {
        $file = UploadedFile::fake()->image('bukti.jpg');

        $this->post($this->signedStore('MFP-NOT-EXIST'), [
            'proof_file' => $file,
            'installment_sequence' => 0,
        ])
            ->assertRedirect()
            ->assertSessionHas('upload.success', true);

        // No payment row should be touched.
        $payment = OrderPayment::where('order_id', $this->order->id)->first();
        $this->assertNull($payment->proof_path);
    }

    public function test_store_cicilan_uploads_to_correct_payment_by_sequence(): void
    {
        // Add 2 more payments to make it cicilan (3 total).
        OrderPayment::where('order_id', $this->order->id)->delete();
        $payments = collect([
            OrderPayment::create(['order_id' => $this->order->id, 'amount' => 1_350_000, 'method' => 'transfer', 'status' => 'pending']),
            OrderPayment::create(['order_id' => $this->order->id, 'amount' => 1_575_000, 'method' => 'transfer', 'status' => 'pending']),
            OrderPayment::create(['order_id' => $this->order->id, 'amount' => 1_575_000, 'method' => 'transfer', 'status' => 'pending']),
        ]);

        $file = UploadedFile::fake()->image('cicilan2.jpg');

        // Upload untuk cicilan ke-2 (seq=1, 0-indexed)
        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $file,
            'installment_sequence' => 1,
        ])->assertRedirect();

        // Cuma payment[1] yang punya proof — yang lain tetep null
        $payments[0]->refresh();
        $payments[1]->refresh();
        $payments[2]->refresh();

        $this->assertNull($payments[0]->proof_path);
        $this->assertNotNull($payments[1]->proof_path);
        $this->assertNull($payments[2]->proof_path);
    }

    public function test_store_rejects_upload_when_payment_already_verified(): void
    {
        $payment = OrderPayment::where('order_id', $this->order->id)->first();
        $payment->update([
            'status' => 'verified',
            'verified_at' => now(),
            'amount' => 4_500_000,
        ]);

        $file = UploadedFile::fake()->image('late.jpg');
        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $file,
            'installment_sequence' => 0,
        ])
            ->assertSessionHasErrors('installment_sequence');
    }

    public function test_store_filename_is_randomized_not_user_provided(): void
    {
        // Custom filename "evil-injection.jpg" — server should rename.
        $file = UploadedFile::fake()->image('evil-injection.jpg');

        $this->post($this->signedStore($this->order->order_number), [
            'proof_file' => $file,
            'installment_sequence' => 0,
        ])->assertRedirect();

        $payment = OrderPayment::where('order_id', $this->order->id)->first();

        // Path harus contain random suffix, bukan "evil-injection".
        $this->assertStringNotContainsString('evil-injection', $payment->proof_path);
        $this->assertMatchesRegularExpression(
            '#payment-proofs/MFP-\d+-TEST01/\d+-[A-Za-z0-9]{8}\.(jpg|jpeg|png|webp)#',
            $payment->proof_path,
        );
    }

    public function test_full_checkout_to_upload_flow_end_to_end(): void
    {
        // E2E: checkout → halaman success → klik signed upload → upload bukti.
        $product = Product::where('slug', 'kelas-amc-reguler')->first();

        $checkoutResponse = $this->post('/checkout', [
            'customer_name' => 'E2E Customer',
            'customer_phone' => '08111122223',
            'address_line' => 'Jl. E2E',
            'payment_type' => 'lunas',
            'cart_json' => json_encode([
                ['slug' => $product->slug, 'qty' => 1, 'price' => 4_500_000],
            ]),
            'cart_total' => 4_500_000,
        ]);

        // Redirect ke halaman success dulu.
        $location = $checkoutResponse->headers->get('Location');
        $this->assertStringContainsString('/checkout/success/MFP-', $location);
        preg_match('#/checkout/success/(MFP-\d{8}-[A-Z0-9]+)#', $location, $matches);
        $orderNumber = $matches[1];

        // Success page render + ekstrak signed upload URL dari tombol.
        $success = $this->get($location)->assertOk()->assertSee('Order berhasil dibuat', false);
        preg_match('/href="([^"]*\/upload\/'.preg_quote($orderNumber, '/').'[^"]*)"/', $success->getContent(), $m);
        $this->assertNotEmpty($m, 'Signed upload URL harus ada di success page');
        $uploadUrl = html_entity_decode($m[1]);

        // Visit signed upload URL — render real order.
        $this->get($uploadUrl)->assertOk()->assertSee($orderNumber);

        // Upload bukti
        $file = UploadedFile::fake()->image('bukti-e2e.jpg');
        $this->post(URL::temporarySignedRoute('upload.store', now()->addDays(7), ['order_number' => $orderNumber]), [
            'proof_file' => $file,
            'installment_sequence' => 0,
        ])->assertRedirect();

        $order = Order::where('order_number', $orderNumber)->first();
        $payment = $order->payments()->first();
        $this->assertNotNull($payment->proof_path);
        $this->assertNotNull($payment->paid_at);
    }
}
