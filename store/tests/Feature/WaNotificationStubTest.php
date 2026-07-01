<?php

namespace Tests\Feature;

use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderShipped;
use App\Events\PaymentRejected;
use App\Events\PaymentSubmitted;
use App\Events\PaymentVerified;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\WaNotification;
use App\Services\Settings;
use App\Services\WhatsappNotifier;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Coverage task t_e5d877f3 — WA Notification stub.
 *
 * Tiga lapis:
 * 1. Service unit — WhatsappNotifier::send() insert row dengan payload + status queued.
 * 2. Listener integration — fire event → row otomatis ke-create di wa_notifications.
 * 3. Controller wire — POST /admin/orders/.../approve, /reject, /ship, /upload
 *    semuanya end-to-end fire event → row ada.
 *
 * Stub M2: status='queued', no actual gateway call. Test cuma cek INSERT row +
 * payload JSON valid + recipient + template name.
 */
class WaNotificationStubTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::query()->first();

        // Set admin WA contact biar listener punya recipient.
        Settings::set('wa_admin', ['number' => '628111000999', 'label' => 'Admin Test'], 'array');
    }

    // ─── Service unit ─────────────────────────────────────────────────────

    public function test_whatsapp_notifier_inserts_queued_row(): void
    {
        $notifier = app(WhatsappNotifier::class);
        $order = Order::factory()->create();

        $notifier->send(
            template: 'admin_payment_review_alert',
            recipient: '628111222333',
            payload: ['order_number' => $order->order_number, 'amount' => 1500000],
            order: $order,
        );

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628111222333',
            'template' => 'admin_payment_review_alert',
            'status' => 'queued',
        ]);

        $row = WaNotification::first();
        $this->assertSame($order->order_number, $row->payload_json['order_number']);
        $this->assertSame(1500000, $row->payload_json['amount']);
        $this->assertNull($row->sent_at);
    }

    public function test_whatsapp_notifier_works_without_order(): void
    {
        $notifier = app(WhatsappNotifier::class);

        $notifier->send(
            template: 'system_alert',
            recipient: '628111222333',
            payload: ['msg' => 'hello'],
        );

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => null,
            'template' => 'system_alert',
            'status' => 'queued',
        ]);
    }

    // ─── Listener integration via event dispatch ───────────────────────────

    public function test_payment_submitted_event_queues_admin_alert(): void
    {
        $order = Order::factory()->create(['order_number' => 'MFP-WA-PSUB1']);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 750000,
            'status' => 'pending',
        ]);

        event(new PaymentSubmitted($order, $payment, 0));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628111000999',
            'template' => 'admin_payment_review_alert',
            'status' => 'queued',
        ]);

        $row = WaNotification::first();
        $this->assertSame('MFP-WA-PSUB1', $row->payload_json['order_number']);
        $this->assertSame(750000, $row->payload_json['amount']);
        $this->assertSame(0, $row->payload_json['sequence']);
        $this->assertArrayHasKey('review_url', $row->payload_json);
    }

    public function test_payment_verified_event_queues_customer_notif(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-PVER1',
            'phone' => '628999111222',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 500000,
            'status' => 'verified',
        ]);

        event(new PaymentVerified($order, $payment));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628999111222',
            'template' => 'customer_payment_verified',
            'status' => 'queued',
        ]);

        $row = WaNotification::first();
        $this->assertSame(500000, $row->payload_json['amount_verified']);
        $this->assertArrayHasKey('track_url', $row->payload_json);
        $this->assertStringContainsString('/track/MFP-WA-PVER1', $row->payload_json['track_url']);
        $this->assertStringContainsString('signature=', $row->payload_json['track_url']);
    }

    public function test_payment_rejected_event_queues_customer_notif_with_reason(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-PREJ1',
            'phone' => '628999111222',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 250000,
            'status' => 'rejected',
        ]);

        event(new PaymentRejected($order, $payment, 'Bukti transfer buram, tolong upload ulang yang jelas.'));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_payment_rejected',
            'status' => 'queued',
        ]);

        $row = WaNotification::first();
        $this->assertSame('Bukti transfer buram, tolong upload ulang yang jelas.', $row->payload_json['rejection_reason']);
        $this->assertArrayHasKey('reupload_url', $row->payload_json);
        $this->assertStringContainsString('/upload/MFP-WA-PREJ1', $row->payload_json['reupload_url']);
        $this->assertStringContainsString('signature=', $row->payload_json['reupload_url']);
    }

    public function test_order_shipped_event_queues_customer_notif_with_resi(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-SHIP1',
            'phone' => '628999111222',
            'status' => 'shipped',
            'shipping_courier' => 'JNE',
            'shipping_resi' => 'JNE998877665544',
            'shipped_at' => now(),
        ]);

        event(new OrderShipped($order));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628999111222',
            'template' => 'customer_order_shipped',
            'status' => 'queued',
        ]);

        $row = WaNotification::first();
        $this->assertSame('JNE', $row->payload_json['shipping_courier']);
        $this->assertSame('JNE998877665544', $row->payload_json['shipping_resi']);
        $this->assertNotNull($row->payload_json['shipped_at']);
    }

    public function test_order_created_event_queues_customer_notif_with_upload_link(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-NEW01',
            'phone' => '628999111222',
            'total' => 165000,
        ]);

        event(new OrderCreated($order));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628999111222',
            'template' => 'customer_order_created',
            'status' => 'queued',
        ]);

        $row = WaNotification::where('template', 'customer_order_created')->first();
        $this->assertStringContainsString('/upload/MFP-WA-NEW01', $row->payload_json['upload_url']);
        $this->assertStringContainsString('signature=', $row->payload_json['upload_url']);
        $this->assertSame('165.000', $row->payload_json['amount']);
    }

    public function test_payment_submitted_also_queues_customer_received_notif(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-RCV01',
            'phone' => '628999111222',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 165000,
            'status' => 'pending',
        ]);

        event(new PaymentSubmitted($order, $payment, 0));

        // Dua notif: alert admin + konfirmasi ke pembeli.
        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'admin_payment_review_alert',
        ]);
        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628999111222',
            'template' => 'customer_payment_received',
            'status' => 'queued',
        ]);
    }

    public function test_order_completed_event_queues_customer_thankyou(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-DONE1',
            'phone' => '628999111222',
            'status' => 'completed',
        ]);

        event(new OrderCompleted($order));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628999111222',
            'template' => 'customer_order_completed',
            'status' => 'queued',
        ]);
    }

    public function test_delivered_awb_callback_dispatches_order_completed(): void
    {
        config(['shipping.license' => 'test-license-key']);
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-DLVR1',
            'phone' => '628999111222',
            'status' => 'shipped',
            'fulfillment_api_order_id' => 'ORD-DLVR-1',
            'fulfillment_reference_id' => 'REF-DLVR-1',
        ]);

        $payload = [
            'status' => 'status_update',
            'order_id' => 'ORD-DLVR-1',
            'reference_id' => 'REF-DLVR-1',
            'tracking_status' => 'DELIVERED',
        ];
        $signature = hash('sha256', 'test-license-key'.json_encode($payload));

        $this->postJson(route('webhooks.agenwebsite.awb'), $payload, ['AW-Signature' => $signature])
            ->assertOk();

        // Order completed + WA terima kasih ke pembeli ter-queue.
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_order_completed',
        ]);
    }

    public function test_listener_skips_when_recipient_empty(): void
    {
        // Order tanpa phone → customer notif harus skip (defensive).
        $order = Order::factory()->create(['phone' => '']);
        $payment = OrderPayment::factory()->create(['order_id' => $order->id, 'status' => 'verified']);

        event(new PaymentVerified($order, $payment));

        $this->assertDatabaseCount('wa_notifications', 0);
    }

    // ─── Controller wire (full HTTP flow) ──────────────────────────────────

    public function test_admin_approve_payment_dispatches_payment_verified_event(): void
    {
        $order = Order::factory()->create([
            'phone' => '628999111222',
            'total' => 1000000,
            'status' => 'pending',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_payment_verified',
            'status' => 'queued',
        ]);
    }

    public function test_admin_reject_payment_dispatches_payment_rejected_event(): void
    {
        $order = Order::factory()->create([
            'phone' => '628999111222',
            'status' => 'pending',
        ]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.reject', [$order, $payment]), [
                'reason' => 'Nominal tidak sesuai dengan total order.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_payment_rejected',
            'status' => 'queued',
        ]);

        $row = WaNotification::first();
        $this->assertSame('Nominal tidak sesuai dengan total order.', $row->payload_json['rejection_reason']);
    }

    public function test_admin_mark_shipped_dispatches_order_shipped_event(): void
    {
        $order = Order::factory()->create([
            'phone' => '628999111222',
            'status' => 'paid',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.ship', $order), [
                'shipping_courier' => 'JNE',
                'shipping_resi' => 'JNE12345678',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_order_shipped',
            'status' => 'queued',
        ]);
    }

    public function test_customer_upload_proof_dispatches_payment_submitted_event(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'MFP-WA-UPLOAD1',
            'phone' => '628999111222',
        ]);
        OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 500000,
            'status' => 'pending',
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'upload.store',
            now()->addDays(7),
            ['order_number' => $order->order_number],
        );

        $file = UploadedFile::fake()->image('proof.jpg', 800, 600)->size(500);

        $this->post($signedUrl, ['proof_file' => $file, 'installment_sequence' => 0])
            ->assertRedirect();

        $this->assertDatabaseHas('wa_notifications', [
            'order_id' => $order->id,
            'recipient' => '628111000999', // admin recipient
            'template' => 'admin_payment_review_alert',
            'status' => 'queued',
        ]);
    }

    // ─── Admin index page (read-only list) ─────────────────────────────────

    public function test_admin_wa_notifications_index_renders(): void
    {
        $order = Order::factory()->create();
        WaNotification::create([
            'order_id' => $order->id,
            'recipient' => '628111222333',
            'template' => 'admin_payment_review_alert',
            'payload_json' => ['order_number' => $order->order_number],
            'status' => 'queued',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.wa-notifications.index'))
            ->assertOk()
            ->assertSee('WA Notifikasi')
            ->assertSee('admin_payment_review_alert', false)
            ->assertSee('628111222333', false)
            ->assertSee('data-testid="wa-notifications-table"', false);
    }

    public function test_admin_wa_notifications_index_filters_by_status(): void
    {
        WaNotification::create([
            'recipient' => '628111111111',
            'template' => 't1',
            'payload_json' => [],
            'status' => 'queued',
        ]);
        WaNotification::create([
            'recipient' => '628222222222',
            'template' => 't2',
            'payload_json' => [],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.wa-notifications.index', ['status' => 'queued']))
            ->assertOk()
            ->assertSee('628111111111', false)
            ->assertDontSee('628222222222', false);
    }

    public function test_admin_wa_notifications_index_renders_empty_state(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.wa-notifications.index'))
            ->assertOk()
            ->assertSee('Belum ada notifikasi WhatsApp.', false)
            ->assertSee('data-testid="empty-state"', false);
    }

    public function test_admin_wa_notifications_index_requires_auth(): void
    {
        $this->get(route('admin.wa-notifications.index'))
            ->assertRedirect(route('admin.login'));
    }
}
