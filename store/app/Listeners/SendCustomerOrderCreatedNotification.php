<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Order;
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
        $order = $event->order;

        $recipient = (string) ($order->phone ?? '');
        if ($recipient === '') {
            return;
        }

        $ttlDays = max(1, (int) config('checkout.upload_url_ttl_days', 7));
        $uploadUrl = URL::temporarySignedRoute(
            'upload.show',
            now()->addDays($ttlDays),
            ['order_number' => $order->order_number],
        );

        $this->notifier->send(
            template: 'customer_order_created',
            recipient: $recipient,
            payload: [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'items' => $this->formatItems($order),
                'courier' => $this->formatCourier($order),
                'amount' => number_format((int) $order->total, 0, ',', '.'),
                'upload_url' => $uploadUrl,
            ],
            order: $order,
        );
    }

    /**
     * Daftar produk yang dipesan, satu baris per item: "• Nama (2×) — Rp 180.000".
     * OrderItem tak menyimpan snapshot judul → ambil dari relasi product/course.
     */
    protected function formatItems(Order $order): string
    {
        $order->loadMissing(['items.product', 'items.course']);

        $lines = $order->items->map(function ($item) {
            $name = $item->product?->title
                ?? $item->course?->title
                ?? 'Produk';
            $qty = (int) $item->qty;
            $subtotal = number_format((float) $item->subtotal, 0, ',', '.');

            return "• {$name} ({$qty}×) — Rp {$subtotal}";
        });

        return $lines->isNotEmpty() ? $lines->implode("\n") : '• (detail tidak tersedia)';
    }

    /**
     * Label kurir yang dipilih pembeli saat checkout (+ layanan bila ada),
     * mis. "JNE — REG". id kurir tak dikenal → uppercase; tanpa kurir → "—".
     */
    protected function formatCourier(Order $order): string
    {
        $courierId = strtolower(trim((string) ($order->shipping_courier ?? '')));
        if ($courierId === '') {
            return '—';
        }

        $label = config('shipping.courier_labels')[$courierId] ?? strtoupper($courierId);
        $service = trim((string) ($order->shipping_service ?? ''));

        return $service !== '' ? "{$label} — {$service}" : $label;
    }
}
