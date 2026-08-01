<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\WhatsappNotifier;
use Illuminate\Support\Facades\URL;

/**
 * SendCustomerPaymentVerifiedNotification — listener untuk PaymentVerified.
 *
 * Trigger: admin approve bukti bayar → OrderController::approvePayment.
 * Action: queue WA notif ke customer (template: customer_payment_verified)
 * supaya customer tau bukti bayar udah diverifikasi + signed track URL.
 *
 * M2 stub: cuma INSERT row ke wa_notifications status='queued'.
 */
class SendCustomerPaymentVerifiedNotification
{
    public function __construct(protected WhatsappNotifier $notifier) {}

    public function handle(PaymentVerified $event): void
    {
        $recipient = (string) ($event->order->phone ?? '');
        if ($recipient === '') {
            return;
        }

        // Derive 0-indexed sequence dari posisi payment di order siblings (order-by-id),
        // matching pattern UploadController. Tidak ada `sequence` column di schema.
        $sequence = $this->derivePaymentSequence($event->order, $event->payment);

        $trackUrl = URL::signedRoute('track.show', ['order_number' => $event->order->order_number]);

        $this->notifier->send(
            template: 'customer_payment_verified',
            recipient: $recipient,
            payload: [
                'order_number' => $event->order->order_number,
                'customer_name' => $event->order->customer_name,
                'payment_id' => $event->payment->id,
                'amount_verified' => (int) $event->payment->amount,
                'sequence' => $sequence,
                'order_status' => $event->order->status,
                'track_url' => $trackUrl,
            ],
            order: $event->order,
        );
    }

    /**
     * Compute 0-indexed payment sequence by id position in order's payments.
     * Returns 0 untuk lunas (single payment) atau cicilan ke-N untuk multi-payment.
     */
    protected function derivePaymentSequence(Order $order, OrderPayment $payment): int
    {
        $ids = $order->payments()->orderBy('id')->pluck('id')->all();
        $position = array_search($payment->id, $ids, true);

        return $position === false ? 0 : (int) $position;
    }
}
