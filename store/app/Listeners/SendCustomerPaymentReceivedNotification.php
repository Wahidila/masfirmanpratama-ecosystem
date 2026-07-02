<?php

namespace App\Listeners;

use App\Events\PaymentSubmitted;
use App\Services\WhatsappNotifier;
use Illuminate\Support\Facades\URL;

/**
 * SendCustomerPaymentReceivedNotification — listener untuk PaymentSubmitted.
 *
 * Trigger: pembeli upload bukti bayar → POST /upload/{order} sukses.
 * Action: queue WA konfirmasi ke PEMBELI (template: customer_payment_received)
 * bahwa bukti sudah diterima & sedang diverifikasi. (Terpisah dari
 * SendAdminPaymentReviewAlert yang notify admin.)
 */
class SendCustomerPaymentReceivedNotification
{
    public function __construct(protected WhatsappNotifier $notifier) {}

    public function handle(PaymentSubmitted $event): void
    {
        $recipient = (string) ($event->order->phone ?? '');
        if ($recipient === '') {
            return;
        }

        // Signed track URL (pola sama dgn payment_verified & order_shipped) supaya
        // pembeli bisa langsung pantau status pesanan tanpa login.
        $ttlDays = (int) config('checkout.track_url_ttl_days', 30);
        $trackUrl = URL::temporarySignedRoute(
            'track.show',
            now()->addDays($ttlDays),
            ['order_number' => $event->order->order_number],
        );

        $this->notifier->send(
            template: 'customer_payment_received',
            recipient: $recipient,
            payload: [
                'order_number' => $event->order->order_number,
                'customer_name' => $event->order->customer_name,
                'amount' => number_format((int) $event->payment->amount, 0, ',', '.'),
                'sequence' => $event->sequence,
                'track_url' => $trackUrl,
            ],
            order: $event->order,
        );
    }
}
