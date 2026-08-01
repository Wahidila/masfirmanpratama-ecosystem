<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Installment\InstallmentReminder;
use App\Services\Shipping\AgenwebsiteClient;
use Illuminate\Support\Facades\URL;
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
                $trackingHistory = $client->tracking(
                    $order->shipping_resi,
                    $slug,
                    self::phoneVerification($order->phone),
                );
            }
        }

        // Signed URL ke halaman upload bukti (TTL schedule-aware; untuk cicilan
        // bebas berlaku panjang) supaya customer bisa lanjut bayar cicilan dari
        // halaman lacak. Null bila order tidak ada di DB.
        $uploadUrl = $order
            ? URL::temporarySignedRoute(
                'upload.show',
                app(InstallmentReminder::class)->uploadUrlExpiry($order),
                ['order_number' => $orderNumber],
            )
            : null;

        return view('pages.track', [
            'orderNumber' => $orderNumber,
            'dbOrder' => $order,
            'trackingHistory' => $trackingHistory,
            'uploadUrl' => $uploadUrl,
        ]);
    }

    /**
     * 5 digit terakhir no HP penerima (angka saja) — dipakai API tracking sebagai
     * verifikasi. Null bila tak ada nomor.
     */
    public static function phoneVerification(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return $digits !== '' ? substr($digits, -5) : null;
    }
}
