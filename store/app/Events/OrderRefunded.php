<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderRefunded — dispatched saat admin melakukan refund order.
 *
 * Listener: DispatchAffiliateOrderRefunded → kirim webhook 'order-refunded'
 * ke Affiliate system untuk cancel komisi yang masih cooling / available.
 */
class OrderRefunded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Order $order,
    ) {}
}
