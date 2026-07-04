<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderCompleted — dispatched saat order transition ke status 'completed'
 * (mis. paket delivered via AWB callback).
 *
 * Listener: SendCustomerOrderCompletedNotification → WA ucapan terima kasih
 * ke pembeli.
 */
class OrderCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Order $order) {}
}
