<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Services\WhatsappNotifier;

/**
 * SendCustomerOrderCompletedNotification — listener untuk OrderCompleted.
 *
 * Trigger: order transition ke 'completed' (mis. delivered via AWB callback).
 * Action: queue WA ucapan terima kasih ke pembeli (template:
 * customer_order_completed).
 */
class SendCustomerOrderCompletedNotification
{
    public function __construct(protected WhatsappNotifier $notifier) {}

    public function handle(OrderCompleted $event): void
    {
        $recipient = (string) ($event->order->phone ?? '');
        if ($recipient === '') {
            return;
        }

        $this->notifier->send(
            template: 'customer_order_completed',
            recipient: $recipient,
            payload: [
                'order_number' => $event->order->order_number,
                'customer_name' => $event->order->customer_name,
            ],
            order: $event->order,
        );
    }
}
