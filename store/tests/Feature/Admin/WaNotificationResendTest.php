<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\WaNotification;
use App\Services\XSenderService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kirim ulang manual notifikasi WhatsApp dari detail order (mitigasi gagal kirim).
 * Pesan dibangun ulang dari template + payload tersimpan; status row di-update
 * in-place (tanpa membuat row baru).
 */
class WaNotificationResendTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    protected function makeNotif(array $overrides = []): WaNotification
    {
        $order = Order::factory()->create(['order_number' => 'MFP-RS-0001']);

        return WaNotification::create(array_merge([
            'order_id' => $order->id,
            'recipient' => '628123456789',
            'template' => 'customer_payment_received',
            'payload_json' => [
                'customer_name' => 'Budi',
                'order_number' => 'MFP-RS-0001',
                'track_url' => 'https://masfirmanpratama.test/track/MFP-RS-0001',
            ],
            'status' => 'failed',
            'error' => 'timeout',
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $notif = $this->makeNotif();

        $this->post(route('admin.wa-notifications.resend', $notif))
            ->assertRedirect(route('admin.login'));
    }

    public function test_resend_marks_sent_and_rebuilds_message_from_stored_payload(): void
    {
        $captured = null;
        $this->mock(XSenderService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('send')->andReturnUsing(function ($to, $msg) use (&$captured) {
                $captured = ['to' => $to, 'msg' => $msg];

                return ['ok' => true, 'status' => 1, 'body' => 'sent'];
            });
        });

        $notif = $this->makeNotif(['status' => 'failed', 'error' => 'timeout']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.wa-notifications.resend', $notif))
            ->assertRedirect()
            ->assertSessionHas('status');

        $fresh = $notif->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertNull($fresh->error);
        $this->assertNotNull($fresh->sent_at);

        // Terkirim ke recipient tersimpan; pesan dibangun ulang dari template+payload.
        $this->assertSame('628123456789', $captured['to']);
        $this->assertStringContainsString('Bukti Bayar Diterima', $captured['msg']);
        $this->assertStringContainsString('MFP-RS-0001', $captured['msg']);
        $this->assertStringContainsString('/track/MFP-RS-0001', $captured['msg']);
    }

    public function test_resend_does_not_create_new_row(): void
    {
        $this->mock(XSenderService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturn(['ok' => true, 'status' => 1, 'body' => 'sent']);
        });

        $notif = $this->makeNotif();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.wa-notifications.resend', $notif));

        $this->assertSame(1, WaNotification::count());
    }

    public function test_resend_failure_records_error_and_status_failed(): void
    {
        $this->mock(XSenderService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturn(['ok' => false, 'status' => 500, 'body' => 'gateway down']);
        });

        $notif = $this->makeNotif(['status' => 'sent', 'error' => null]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.wa-notifications.resend', $notif))
            ->assertRedirect()
            ->assertSessionHas('error');

        $fresh = $notif->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertStringContainsString('gateway down', (string) $fresh->error);
    }

    public function test_resend_with_empty_recipient_is_blocked(): void
    {
        // Recipient kosong → guard controller cegah, gateway tak dipanggil.
        $this->mock(XSenderService::class, function ($mock) {
            $mock->shouldNotReceive('send');
        });

        $notif = $this->makeNotif(['recipient' => '', 'status' => 'failed']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.wa-notifications.resend', $notif))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Status tidak berubah jadi sent.
        $this->assertNotSame('sent', $notif->fresh()->status);
    }
}
