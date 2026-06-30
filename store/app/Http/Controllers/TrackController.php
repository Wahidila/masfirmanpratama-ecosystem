<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Shipping\AgenwebsiteClient;
use Illuminate\View\View;

class TrackController extends Controller
{
    /**
     * Map label kurir (yang admin pilih lewat dropdown markShipped: JNE, SiCepat,
     * Pos, Other, dst) ke API slug yang dipakai Agenwebsite tracking endpoint
     * (lowercase short id: jne, sicepat, pos). 'Other' tidak punya equivalent
     * di API → skip panggilan tracking.
     */
    private const COURIER_SLUG_MAP = [
        'jne' => 'jne',
        'jnt' => 'jnt',
        'j&t' => 'jnt',
        'sicepat' => 'sicepat',
        'pos' => 'pos',
        'tiki' => 'tiki',
        'anteraja' => 'anteraja',
        'spx' => 'spx',
        'lion' => 'lion',
        'paxel' => 'paxel',
        'gosend' => 'gosend',
        'jtc' => 'jtc',
        'j&t cargo' => 'jtc',
    ];

    public function show(string $orderNumber): View
    {
        $order = Order::where('order_number', $orderNumber)->first();

        $trackingHistory = null;

        if ($order && $order->shipping_resi && $order->shipping_courier) {
            $slug = self::COURIER_SLUG_MAP[strtolower(trim($order->shipping_courier))] ?? null;

            if ($slug !== null) {
                $client = AgenwebsiteClient::fromConfig();
                $trackingHistory = $client->tracking($order->shipping_resi, $slug);
            }
        }

        return view('pages.track', [
            'orderNumber' => $orderNumber,
            'dbOrder' => $order,
            'trackingHistory' => $trackingHistory,
        ]);
    }
}
