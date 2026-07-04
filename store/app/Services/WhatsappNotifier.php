<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WaNotification;
use Illuminate\Support\Facades\Log;

/**
 * WhatsappNotifier — sends WhatsApp notifications via XSender.
 *
 * Flow:
 *   1. INSERT row ke `wa_notifications` (status='queued')
 *   2. Attempt kirim via XSenderService
 *   3. Update status ke 'sent' atau 'failed'
 *
 * Pattern usage:
 *
 *     app(WhatsappNotifier::class)->send(
 *         template: 'admin_payment_review_alert',
 *         recipient: '081234567890',
 *         payload: ['order_number' => 'MFP-...', 'amount' => 1500000],
 *         order: $order,
 *     );
 *
 * Recipient format: 08xxx, +62xxx, atau 628xxx (akan di-normalize).
 */
class WhatsappNotifier
{
    public function __construct(
        protected XSenderService $xsender,
    ) {}

    /**
     * Send WhatsApp notification — write row + fire XSender API.
     *
     * @param  string  $template  template identifier (mis. 'admin_payment_review_alert')
     * @param  string  $recipient  phone (mis. '081234567890')
     * @param  array<string, mixed>  $payload  variabel template (di-serialize JSON)
     * @param  Order|null  $order  optional FK ke orders.id
     */
    public function send(
        string $template,
        string $recipient,
        array $payload = [],
        ?Order $order = null,
    ): WaNotification {
        $notification = WaNotification::create([
            'order_id' => $order?->id,
            'recipient' => $recipient,
            'template' => $template,
            'payload_json' => $payload,
            'status' => 'queued',
        ]);

        // Build message from template + payload
        $message = $this->buildMessage($template, $payload);

        return $this->deliver($notification, $recipient, $message);
    }

    /**
     * Kirim ulang notifikasi yang sudah ada (mitigasi gagal kirim). Pesan
     * dibangun ulang dari template + payload_json yang tersimpan, lalu dikirim
     * ke recipient yang sama. Status di-reset ke 'queued' dulu, lalu di-update
     * ke 'sent'/'failed' sesuai hasil — tidak membuat row baru.
     */
    public function resend(WaNotification $notification): WaNotification
    {
        $notification->update(['status' => 'queued', 'error' => null]);

        $message = $this->buildMessage(
            $notification->template,
            (array) ($notification->payload_json ?? []),
        );

        return $this->deliver($notification, (string) $notification->recipient, $message);
    }

    /**
     * Kirim $message ke gateway XSender lalu update status row. Dipakai bersama
     * oleh send() (kirim pertama) dan resend() (kirim ulang manual admin).
     */
    protected function deliver(WaNotification $notification, string $recipient, string $message): WaNotification
    {
        $result = $this->xsender->send($recipient, $message);

        if ($result['status'] === 0 && str_contains($result['body'] ?? '', 'not configured')) {
            // XSender not configured — keep as queued (stub mode)
            return $notification;
        }

        if ($result['ok']) {
            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error' => null,
            ]);
        } else {
            $notification->update([
                'status' => 'failed',
                'error' => mb_substr($result['body'] ?? 'Unknown error', 0, 500),
            ]);

            Log::warning('[WhatsappNotifier] Failed to send', [
                'template' => $notification->template,
                'recipient' => $recipient,
                'response' => $result,
            ]);
        }

        return $notification;
    }

    /**
     * Build pesan dari template name + payload placeholders.
     *
     * Template message di-hardcode di sini (simple approach).
     * Bisa di-migrate ke DB/config nanti kalau butuh editable dari admin.
     */
    protected function buildMessage(string $template, array $payload): string
    {
        $templates = [
            'admin_payment_review_alert' => "🔔 *Pembayaran Baru*\n\nOrder: {order_number}\nNama: {customer_name}\nTotal: Rp {amount}\n\nSegera verifikasi pembayaran di dashboard admin.",

            'customer_order_created' => "🛍️ *Pesanan Diterima*\n\nHalo {customer_name},\nPesanan *{order_number}* berhasil dibuat. ✅\n\n*Detail Pesanan:*\n{items}\n\n🚚 Kurir: {courier}\n💰 Total: *Rp {amount}*\n\nSilakan transfer ke rekening yang tertera, lalu upload bukti bayar di sini:\n{upload_url}\n\nKami tunggu ya, terima kasih! 🙏",

            'customer_payment_received' => "📤 *Bukti Bayar Diterima*\n\nHalo {customer_name},\nBukti pembayaran untuk order *{order_number}* sudah kami terima dan sedang diverifikasi tim kami (maks. 1×24 jam kerja).\n\nLacak status pesanan kamu di sini:\n{track_url}\n\nKamu akan dapat notifikasi lagi begitu pembayaran dikonfirmasi. Terima kasih! 🙏",

            'customer_order_completed' => "🎉 *Pesanan Selesai*\n\nHalo {customer_name},\nOrder *{order_number}* sudah selesai. Terima kasih sudah berbelanja di Firman Pratama! 🙏\n\nSemoga bermanfaat. Kalau berkenan, ceritakan pengalamanmu ke kami ya. 🌟",

            'customer_payment_verified' => "✅ *Pembayaran Dikonfirmasi*\n\nHalo {customer_name},\nPembayaran untuk order *{order_number}* sudah diverifikasi.\n\nPesanan kamu sedang diproses. Terima kasih! 🙏",

            'customer_payment_rejected' => "❌ *Pembayaran Ditolak*\n\nHalo {customer_name},\nPembayaran untuk order *{order_number}* tidak dapat diverifikasi.\nAlasan: {reason}\n\nSilakan upload ulang bukti pembayaran yang valid.",

            'customer_order_shipped' => "📦 *Pesanan Dikirim*\n\nHalo {customer_name},\nOrder *{order_number}* sudah dikirim!\n\nKurir: {courier}\nResi: {tracking_number}\n\nLacak paket kamu di sini:\n{track_url}\n\nTerima kasih! 🙏",

            'course_registration_success' => "🎓 *PENDAFTARAN KELAS BERHASIL*\n\nHalo {customer_name},\nTerima kasih sudah mendaftar!\n\nKelas: {course_title}\nOrder ID: {order_number}\nTotal: Rp {amount}\n\nDetail pembayaran dan rekening sudah dikirim. Lakukan transfer dalam 1x24 jam. 🙏",
        ];

        $text = $templates[$template] ?? "Notifikasi: {$template}\n\n".json_encode($payload, JSON_PRETTY_PRINT);

        // Replace placeholders {key} dengan value dari payload
        foreach ($payload as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace('{'.$key.'}', (string) $value, $text);
            }
        }

        return $text;
    }
}
