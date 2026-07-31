<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Services\Webhook\AffiliateWebhookClient;

/**
 * Listener yang mengirim webhook 'order-paid' ke Affiliate system
 * saat pembayaran diverifikasi dan order memiliki ref_code.
 */
class DispatchAffiliateOrderPaid
{
    public function __construct(protected AffiliateWebhookClient $client) {}

    public function handle(PaymentVerified $event): void
    {
        $order = $event->order;

        // Guard: skip kalau order tidak punya referral code
        if (empty($order->ref_code)) {
            return;
        }

        $productType = $this->detectProductType($order);

        $payload = [
            'event' => 'order-paid',
            'store_order_id' => $order->order_number,
            'ref_code' => $order->ref_code,
            'buyer_name' => $order->customer_name,
            // Affiliate butuh buyer_email untuk verifikasi anti self-referral;
            // tanpa ini komisi ditahan ("Buyer unverifiable, commission withheld").
            'buyer_email' => $order->email,
            'order_total' => (float) $order->total,
            'product_type' => $productType,
            'ordered_at' => $order->created_at->toIso8601String(),
            'idempotency_key' => sha1($order->order_number.'-order-paid'),
        ];

        $this->client->dispatch('order-paid', $payload);
    }

    /**
     * Deteksi tipe produk dari order items.
     * - 'book' kalau semua item punya product_id (buku fisik)
     * - 'course' kalau semua item punya course_id (kelas online)
     * - 'mixed' kalau campuran keduanya
     */
    protected function detectProductType($order): string
    {
        $items = $order->items;

        $hasBook = $items->whereNotNull('product_id')->isNotEmpty();
        $hasCourse = $items->whereNotNull('course_id')->isNotEmpty();

        if ($hasBook && $hasCourse) {
            return 'mixed';
        }

        if ($hasCourse) {
            return 'course';
        }

        return 'book';
    }
}
