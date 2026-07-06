<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Services\Webhook\AffiliateWebhookClient;

/**
 * Listener yang mengirim webhook 'order-refunded' ke Affiliate system
 * saat admin melakukan refund order. Affiliate akan cancel komisi
 * yang masih cooling atau available (preserve yang sudah withdrawn).
 */
class DispatchAffiliateOrderRefunded
{
    public function __construct(protected AffiliateWebhookClient $client) {}

    public function handle(OrderRefunded $event): void
    {
        $order = $event->order;

        // Guard: skip kalau order tidak punya referral code (konsisten dgn order-paid).
        if (empty($order->ref_code)) {
            return;
        }

        $payload = [
            'event' => 'order-refunded',
            // HARUS sama dengan store_order_id yang dikirim saat order-paid (order_number),
            // supaya receiver bisa menemukan referral_order-nya dan cancel komisi.
            'store_order_id' => $order->order_number,
            'order_number' => $order->order_number,
            'ref_code' => $order->ref_code,
            'order_total' => (float) $order->total,
            'refunded_at' => now()->toIso8601String(),
            // Deterministik supaya retry/duplikat idempotent di sisi receiver.
            'idempotency_key' => 'refund-'.$order->order_number,
        ];

        $this->client->dispatch('order-refunded', $payload);
    }
}
