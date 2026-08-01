<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Services\Installment\InstallmentReminder;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * "Reminder Cicilan" — kartu jadwal cicilan + tombol kirim WA di detail order,
 * dan service InstallmentReminder yang menghitung jadwal/tagihan berikutnya.
 */
class InstallmentReminderTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    /**
     * @param  list<string>  $statuses  status tiap payment (DP dulu, lalu angsuran)
     */
    private function installmentOrder(array $statuses, array $overrides = []): Order
    {
        $course = Course::factory()->create(['title' => 'Kelas AMC', 'price' => 10_000_000]);

        $order = Order::factory()->create(array_merge([
            'order_number' => 'COURSE-'.now()->format('Ymd').'-ABC-'.strtoupper(Str::random(6)),
            'total' => 10_000_000,
            'status' => 'partial_paid',
            'phone' => '081234567890',
            'order_meta' => ['installment' => [
                'scheme_name' => 'DP 40% + 2x',
                'dp_pct' => 40,
                'n_installments' => 2,
                'interval_days' => 30,
            ]],
            'created_at' => now()->subDays(10),
        ], $overrides));

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'product_id' => null,
            'qty' => 1,
            'unit_price' => 10_000_000,
            'subtotal' => 10_000_000,
        ]);

        foreach ($statuses as $i => $status) {
            OrderPayment::factory()->create([
                'order_id' => $order->id,
                'amount' => $i === 0 ? 4_000_000 : 3_000_000, // DP 40%, 2x 30%
                'status' => $status,
            ]);
        }

        return $order->fresh()->load(['payments', 'items.course']);
    }

    private function lunasOrder(string $status = 'pending'): Order
    {
        $course = Course::factory()->create(['price' => 5_000_000]);
        $order = Order::factory()->create([
            'order_number' => 'COURSE-'.now()->format('Ymd').'-XYZ-'.strtoupper(Str::random(6)),
            'total' => 5_000_000,
            'status' => $status === 'verified' ? 'paid' : 'pending',
            'phone' => '081200000000',
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'course_id' => $course->id, 'product_id' => null, 'qty' => 1, 'unit_price' => 5_000_000, 'subtotal' => 5_000_000]);
        OrderPayment::factory()->create(['order_id' => $order->id, 'amount' => 5_000_000, 'status' => $status]);

        return $order->fresh()->load(['payments', 'items.course']);
    }

    // ── Service ─────────────────────────────────────────────

    public function test_schedule_labels_dp_and_installments_and_picks_next(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending']);
        $svc = new InstallmentReminder;

        $steps = $svc->schedule($order);

        $this->assertCount(3, $steps);
        $this->assertSame(['DP', 'Cicilan ke-1', 'Cicilan ke-2'], array_column($steps, 'label'));
        $this->assertTrue($steps[0]['status'] === 'verified');
        // First non-verified is the "next due".
        $this->assertFalse($steps[0]['is_next']);
        $this->assertTrue($steps[1]['is_next']);
        $this->assertFalse($steps[2]['is_next']);
        $this->assertSame('Cicilan ke-1', $svc->nextDue($order)['label']);
        $this->assertSame(1, $svc->paidCount($order));
        $this->assertSame(3, $svc->totalCount($order));
        $this->assertEqualsWithDelta(6_000_000, $svc->remaining($order), 0.01);
        $this->assertTrue($svc->hasOutstanding($order));
    }

    public function test_due_dates_follow_interval_from_checkout(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['created_at' => now()->subDays(5)]);
        $steps = (new InstallmentReminder)->schedule($order);

        $checkout = $order->created_at;
        $this->assertSame($checkout->toDateString(), $steps[0]['due_date']->toDateString());
        $this->assertSame($checkout->copy()->addDays(30)->toDateString(), $steps[1]['due_date']->toDateString());
        $this->assertSame($checkout->copy()->addDays(60)->toDateString(), $steps[2]['due_date']->toDateString());
    }

    public function test_overdue_days_computed_for_past_due_installment(): void
    {
        // Checkout 100 days ago, interval 30 → angsuran ke-1 due H+30 (70 hari lalu).
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['created_at' => now()->subDays(100)]);
        $next = (new InstallmentReminder)->nextDue($order);

        $this->assertSame('Cicilan ke-1', $next['label']);
        $this->assertGreaterThanOrEqual(69, $next['overdue_days']);
    }

    public function test_rejected_dp_is_the_next_due(): void
    {
        $order = $this->installmentOrder(['rejected', 'pending', 'pending']);
        $this->assertSame('DP', (new InstallmentReminder)->nextDue($order)['label']);
    }

    public function test_lunas_single_payment_order_is_not_installment(): void
    {
        $order = $this->lunasOrder('pending');
        $svc = new InstallmentReminder;

        $this->assertFalse($svc->isInstallment($order));
        $this->assertFalse($svc->hasOutstanding($order));
    }

    public function test_fully_paid_installment_has_no_outstanding(): void
    {
        $order = $this->installmentOrder(['verified', 'verified', 'verified']);
        $svc = new InstallmentReminder;

        $this->assertTrue($svc->isInstallment($order));
        $this->assertFalse($svc->hasOutstanding($order));
        $this->assertEqualsWithDelta(0, $svc->remaining($order), 0.01);
        $this->assertNull($svc->nextDue($order));
    }

    // ── Upload URL TTL (schedule-aware) ─────────────────────

    public function test_upload_url_expiry_uses_default_for_non_installment(): void
    {
        $this->travelTo(now());
        $order = $this->lunasOrder('pending');

        $this->assertSame(
            now()->addDays(7)->toDateTimeString(),
            (new InstallmentReminder)->uploadUrlExpiry($order)->toDateTimeString(),
        );
    }

    public function test_upload_url_expiry_extends_to_last_unpaid_due_plus_grace(): void
    {
        $this->travelTo(now());
        // Checkout now, interval 30: angsuran terakhir yg belum lunas = Cicilan
        // ke-2 (index 2 → now+60). + grace 14 → now+74.
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['created_at' => now()]);

        $this->assertSame(
            now()->addDays(60 + 14)->toDateString(),
            (new InstallmentReminder)->uploadUrlExpiry($order)->toDateString(),
        );
    }

    public function test_upload_url_expiry_floors_at_default_when_schedule_is_in_the_past(): void
    {
        $this->travelTo(now());
        // Checkout 200 hari lalu → semua jatuh tempo sudah lewat; expiry tak boleh
        // lebih pendek dari TTL default.
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['created_at' => now()->subDays(200)]);

        $this->assertSame(
            now()->addDays(7)->toDateTimeString(),
            (new InstallmentReminder)->uploadUrlExpiry($order)->toDateTimeString(),
        );
    }

    public function test_reminder_upload_url_ttl_is_schedule_aware(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['created_at' => now()]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.remind-installment', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $notif = $order->waNotifications()->where('template', 'customer_installment_reminder')->firstOrFail();
        parse_str((string) parse_url($notif->payload_json['upload_url'], PHP_URL_QUERY), $q);

        $this->assertArrayHasKey('expires', $q);
        // Far beyond the flat 7-day default (last angsuran ~60d out + grace).
        $this->assertGreaterThan(now()->addDays(30)->timestamp, (int) $q['expires']);
    }

    // ── Detail page (show) ──────────────────────────────────

    public function test_show_renders_reminder_button_for_outstanding_installment(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Cicilan')
            ->assertSee('Kirim Reminder Cicilan')
            ->assertSee('1/3 lunas');
    }

    public function test_show_hides_button_when_fully_paid(): void
    {
        $order = $this->installmentOrder(['verified', 'verified', 'verified']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Kirim Reminder Cicilan')
            ->assertSee('Semua cicilan sudah lunas');
    }

    public function test_show_has_no_installment_card_for_lunas_order(): void
    {
        $order = $this->lunasOrder('pending');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Kirim Reminder Cicilan');
    }

    // ── Remind endpoint ─────────────────────────────────────

    public function test_remind_installment_creates_wa_notification(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.remind-installment', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $notif = $order->waNotifications()->where('template', 'customer_installment_reminder')->first();
        $this->assertNotNull($notif);
        $this->assertSame('081234567890', $notif->recipient);
        // Payload carries next-due + remaining so a later "kirim ulang" rebuilds it.
        $this->assertStringContainsString('Cicilan ke-1', $notif->payload_json['next_due']);
        $this->assertSame('6.000.000', $notif->payload_json['remaining']);
        $this->assertStringContainsString('Kelas AMC', $notif->payload_json['course_title']);
    }

    public function test_remind_installment_rejects_non_installment_order(): void
    {
        $order = $this->lunasOrder('pending');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.remind-installment', $order))
            ->assertStatus(422);

        $this->assertDatabaseMissing('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_installment_reminder',
        ]);
    }

    public function test_remind_installment_rejects_fully_paid_order(): void
    {
        $order = $this->installmentOrder(['verified', 'verified', 'verified']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.remind-installment', $order))
            ->assertStatus(422);
    }

    public function test_remind_installment_requires_auth(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending']);

        $this->post(route('admin.orders.remind-installment', $order))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_installment_reminder',
        ]);
    }

    public function test_remind_installment_without_phone_shows_error(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['phone' => '']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.remind-installment', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_installment_reminder',
        ]);
    }

    public function test_remind_installment_rejects_refunded_order(): void
    {
        // refund() flips status but leaves angsuran pending; a direct POST must
        // still be blocked server-side (not just hidden in the view).
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['status' => 'refunded']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.remind-installment', $order))
            ->assertStatus(422);

        $this->assertDatabaseMissing('wa_notifications', [
            'order_id' => $order->id,
            'template' => 'customer_installment_reminder',
        ]);
    }

    public function test_show_hides_button_for_refunded_installment_order(): void
    {
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['status' => 'refunded']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Kirim Reminder Cicilan')
            ->assertSee('reminder cicilan dinonaktifkan');
    }

    public function test_reminder_confirm_does_not_inline_customer_phone(): void
    {
        // Stored-XSS guard: a customer-controlled phone must never break out of
        // the inline confirm() JS string. The confirm text is static.
        $order = $this->installmentOrder(['verified', 'pending', 'pending'], ['phone' => "08123');alert(1);//"]);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString("');alert(1)", $html); // no unescaped breakout anywhere
        $this->assertStringContainsString("confirm('Kirim reminder cicilan via WhatsApp ke customer?')", $html);
    }

    public function test_installment_reminder_row_has_no_replay_resend_button(): void
    {
        // A reminder embeds time-sensitive state + a TTL upload URL; the generic
        // "Kirim ulang" (which replays stored payload) must be suppressed for it.
        $order = $this->installmentOrder(['verified', 'pending', 'pending']);
        $notif = $order->waNotifications()->create([
            'recipient' => $order->phone,
            'template' => 'customer_installment_reminder',
            'payload_json' => ['customer_name' => 'X'],
            'status' => 'sent',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Untuk kirim ulang, pakai tombol')
            ->assertDontSee(route('admin.wa-notifications.resend', $notif));
    }
}
