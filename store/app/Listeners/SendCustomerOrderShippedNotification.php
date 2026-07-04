<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Services\WhatsappNotifier;
use Illuminate\Support\Facades\URL;

/**
 * SendCustomerOrderShippedNotification — listener untuk OrderShipped.
 *
 * Trigger: admin input resi → OrderController::markShipped → status 'shipped'.
 * Action: queue WA notif ke customer (template: customer_order_shipped) dengan
 * resi + courier + signed track URL.
 *
 * M2 stub: cuma INSERT row ke wa_notifications status='queued'.
 */
class SendCustomerOrderShippedNotification
{
    public function __construct(protected WhatsappNotifier $notifier) {}

    public function handle(OrderShipped $event): void
    {
        $recipient = (string) ($event->order->phone ?? '');
        if ($recipient === '') {
            return;
        }

        $ttlDays = (int) config('checkout.track_url_ttl_days', 30);
        $trackUrl = URL::temporarySignedRoute(
            'track.show',
            now()->addDays($ttlDays),
            ['order_number' => $event->order->order_number],
        );

        // Label kurir yang manusiawi (JNE, SiCepat) dari courier_id tersimpan (jne,
        // sicepat). Fallback strtoupper bila id tak ada di map.
        $courierId = trim((string) ($event->order->shipping_courier ?? ''));
        $courierLabel = $courierId === ''
            ? '-'
            : (config('shipping.courier_labels.'.strtolower($courierId)) ?: strtoupper($courierId));

        $this->notifier->send(
            template: 'customer_order_shipped',
            recipient: $recipient,
            payload: [
                'order_number' => $event->order->order_number,
                'customer_name' => $event->order->customer_name,
                // Kunci HARUS cocok dengan placeholder template: {courier},
                // {tracking_number}, {track_url}.
                'courier' => $courierLabel,
                'tracking_number' => (string) ($event->order->shipping_resi ?? '-'),
                'track_url' => $trackUrl,
                // Data mentah disimpan juga untuk record/debug di payload_json.
                'shipping_courier' => $event->order->shipping_courier,
                'shipping_resi' => $event->order->shipping_resi,
                'shipped_at' => optional($event->order->shipped_at)->toIso8601String(),
            ],
            order: $event->order,
        );
    }
}
