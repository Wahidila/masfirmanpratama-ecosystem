<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderPayment;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPaymentVerifyTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_approve_requires_authentication(): void
    {
        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->create(['order_id' => $order->id]);

        $this->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertRedirect(route('admin.login'));
    }

    public function test_reject_requires_authentication(): void
    {
        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->create(['order_id' => $order->id]);

        $this->post(route('admin.orders.payments.reject', [$order, $payment]))
            ->assertRedirect(route('admin.login'));
    }

    public function test_approve_full_payment_marks_order_paid(): void
    {
        $order = Order::factory()->create([
            'total' => 1_000_000,
            'status' => 'pending',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 1_000_000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]));

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('status');

        $payment->refresh();
        $order->refresh();

        $this->assertSame('verified', $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame($this->admin->id, $payment->verified_by);
        $this->assertSame('paid', $order->status);
    }

    public function test_approve_partial_payment_marks_order_partial_paid(): void
    {
        $order = Order::factory()->create([
            'total' => 1_000_000,
            'status' => 'pending',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 400_000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('partial_paid', $order->status);
    }

    public function test_approve_with_amount_override(): void
    {
        $order = Order::factory()->create(['total' => 1_000_000, 'status' => 'pending']);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 1_000_000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]), [
                'amount' => 500_000,
            ])
            ->assertRedirect();

        $payment->refresh();
        $order->refresh();

        $this->assertSame('500000.00', $payment->amount);
        // 500k of 1M -> partial_paid
        $this->assertSame('partial_paid', $order->status);
    }

    public function test_approve_second_partial_completes_order(): void
    {
        $order = Order::factory()->create(['total' => 1_000_000, 'status' => 'pending']);

        $first = OrderPayment::factory()->verified()->create([
            'order_id' => $order->id,
            'amount' => 600_000,
        ]);
        // Manually set order to partial_paid so we test transition
        $order->update(['status' => 'partial_paid']);

        $second = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 400_000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $second]))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('paid', $order->status);
    }

    public function test_reject_payment_keeps_order_status(): void
    {
        $order = Order::factory()->create([
            'total' => 1_000_000,
            'status' => 'pending',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 1_000_000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.reject', [$order, $payment]), [
                'reason' => 'Bukti tidak jelas, mohon upload ulang.',
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        $payment->refresh();
        $order->refresh();

        $this->assertSame('rejected', $payment->status);
        $this->assertSame('Bukti tidak jelas, mohon upload ulang.', $payment->rejection_reason);
        $this->assertSame('pending', $order->status);
    }

    public function test_reject_requires_reason(): void
    {
        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.reject', [$order, $payment]), [
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');

        $payment->refresh();
        $this->assertSame('pending', $payment->status);
    }

    public function test_reject_minimum_reason_length(): void
    {
        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.reject', [$order, $payment]), [
                'reason' => 'no',
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_cannot_reapprove_already_processed_payment(): void
    {
        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->verified()->create([
            'order_id' => $order->id,
        ]);

        // Double-submit → redirect ramah dengan pesan, bukan error 422.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('error');
    }

    public function test_cannot_reject_already_processed_payment(): void
    {
        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->rejected()->create([
            'order_id' => $order->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.reject', [$order, $payment]), [
                'reason' => 'Whatever',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('error');
    }

    public function test_reapprove_with_proof_does_not_store_orphan_file(): void
    {
        Storage::fake('public');

        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->verified()->create(['order_id' => $order->id]);

        // Klik kedua (double-submit) yang membawa file: guard trip SEBELUM file
        // disimpan → tidak ada bukti orphan tertulis ke disk.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]), [
                'proof_file' => UploadedFile::fake()->image('dobel.jpg'),
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('error');

        $this->assertSame([], Storage::disk('public')->allFiles('payment-proofs'));
    }

    public function test_payment_must_belong_to_order(): void
    {
        $order = Order::factory()->create();
        $otherOrder = Order::factory()->create();
        $payment = OrderPayment::factory()->create([
            'order_id' => $otherOrder->id,
            'status' => 'pending',
        ]);

        // Mismatched order/payment route param -> 404
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertStatus(404);
    }

    public function test_show_renders_approve_button_for_pending_payments(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);
        OrderPayment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Approve');
        $response->assertSee('Reject');
        $response->assertSee('Alasan tolak');
    }

    public function test_show_does_not_render_actions_for_processed_payments(): void
    {
        $order = Order::factory()->create();
        OrderPayment::factory()->verified()->create([
            'order_id' => $order->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        // For verified-only payments, the approve form should not appear
        $response->assertDontSee('Konfirmasi Approve');
        $response->assertDontSee('Konfirmasi Reject');
    }

    public function test_rejected_payment_displays_reason(): void
    {
        $order = Order::factory()->create();
        OrderPayment::factory()->rejected()->create([
            'order_id' => $order->id,
            'rejection_reason' => 'Nominal beda 100rb dari order.',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Alasan tolak');
        $response->assertSee('Nominal beda 100rb dari order.');
    }

    public function test_shipped_order_status_not_overwritten_by_recalc(): void
    {
        $order = Order::factory()->create([
            'total' => 1_000_000,
            'status' => 'shipped',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 500_000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertRedirect();

        $order->refresh();
        // Status sticky on shipped — recalc tidak boleh overwrite ke partial_paid
        $this->assertSame('shipped', $order->status);
    }

    // ── Approve manual + lampiran bukti ─────────────────────

    public function test_approve_attaches_uploaded_proof(): void
    {
        Storage::fake('public');

        $order = Order::factory()->create(['total' => 1_750_000, 'status' => 'pending']);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 1_750_000,
            'status' => 'pending',
            'proof_path' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]), [
                'proof_file' => UploadedFile::fake()->image('bukti-wa.jpg'),
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $payment->refresh();
        $this->assertSame('verified', $payment->status);
        $this->assertNotNull($payment->proof_path);
        $this->assertStringStartsWith('payment-proofs/'.$order->order_number, $payment->proof_path);
        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_approve_rejects_non_image_proof(): void
    {
        Storage::fake('public');

        $order = Order::factory()->create();
        $payment = OrderPayment::factory()->create(['order_id' => $order->id, 'status' => 'pending', 'proof_path' => null]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]), [
                'proof_file' => UploadedFile::fake()->create('bukti.pdf', 80, 'application/pdf'),
            ])
            ->assertSessionHasErrors('proof_file');

        $this->assertSame('pending', $payment->fresh()->status); // tidak ter-approve
    }

    public function test_approve_without_proof_keeps_proof_null(): void
    {
        $order = Order::factory()->create(['total' => 1_000_000, 'status' => 'pending']);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 1_000_000,
            'status' => 'pending',
            'proof_path' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame('verified', $payment->status);
        $this->assertNull($payment->proof_path);
    }

    public function test_approve_form_shows_proof_input_for_payment_without_proof(): void
    {
        $order = Order::factory()->create();
        OrderPayment::factory()->create(['order_id' => $order->id, 'status' => 'pending', 'proof_path' => null]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertStatus(200)
            ->assertSee('name="proof_file"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('Upload bukti bayar');
    }
}
