<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderCreated — dispatched saat order book/produk berhasil dibuat di checkout.
 *
 * Listener: SendCustomerOrderCreatedNotification → WA ke pembeli berisi nomor
 * order + total + link upload bukti bayar (signed).
 *
 * Catatan: flow KELAS punya notifikasi sendiri (course_registration_success di
 * CourseCheckoutController), jadi event ini khusus CheckoutController (book/produk).
 */
class OrderCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Order $order) {}
}
