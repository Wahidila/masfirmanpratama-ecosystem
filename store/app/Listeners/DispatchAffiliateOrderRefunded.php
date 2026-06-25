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

        $payload = [
            'store_order_id' => $order->id,
            'order_number' => $order->order_number,
            'ref_code' => $order->ref_code,
            'order_total' => (float) $order->total,
            'refunded_at' => now()->toIso8601String(),
            'idempotency_key' => 'refund-'.$order->id.'-'.time(),
        ];

        $this->client->dispatch('order-refunded', $payload);
    }
}
