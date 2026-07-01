<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\WhatsappNotifier;
use Illuminate\Support\Facades\URL;

/**
 * SendCustomerOrderCreatedNotification — listener untuk OrderCreated.
 *
 * Trigger: checkout book/produk sukses → order dibuat.
 * Action: queue WA ke pembeli (template: customer_order_created) berisi nomor
 * order + total + signed upload URL.
 */
class SendCustomerOrderCreatedNotification
{
    public function __construct(protected WhatsappNotifier $notifier) {}

    public function handle(OrderCreated $event): void
    {
        $recipient = (string) ($event->order->phone ?? '');
        if ($recipient === '') {
            return;
        }

        $ttlDays = max(1, (int) config('checkout.upload_url_ttl_days', 7));
        $uploadUrl = URL::temporarySignedRoute(
            'upload.show',
            now()->addDays($ttlDays),
            ['order_number' => $event->order->order_number],
        );

        $this->notifier->send(
            template: 'customer_order_created',
            recipient: $recipient,
            payload: [
                'order_number' => $event->order->order_number,
                'customer_name' => $event->order->customer_name,
                'amount' => number_format((int) $event->order->total, 0, ',', '.'),
                'upload_url' => $uploadUrl,
            ],
            order: $event->order,
        );
    }
}
