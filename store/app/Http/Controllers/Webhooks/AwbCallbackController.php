<?php

namespace App\Http\Controllers\Webhooks;

use App\Events\OrderShipped;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AwbCallbackController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('AW-Signature');

        if ($signature === null) {
            return response()->json([
                'success' => false,
                'message' => 'Missing signature',
            ], 401);
        }

        // License resolution: DB Settings → .env fallback (sama dengan
        // AgenwebsiteClient::fromConfig() — kalau admin override license di
        // /admin/settings tapi callback masih baca config saja, semua callback
        // ditolak. Sumber harus konsisten.
        $license = Settings::get('shipping.license', config('shipping.license'));
        if (! is_string($license) || $license === '') {
            $license = (string) config('shipping.license');
        }

        if ($license === '') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        $calculated = hash('sha256', $license.$request->getContent());

        if (! hash_equals($calculated, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        $payload = $request->json()->all();
        $status = $payload['status'] ?? null;
        $airwaybill = $payload['airwaybill'] ?? null;
        $orderId = $payload['order_id'] ?? null;
        $referenceId = $payload['reference_id'] ?? null;
        $orderNumber = $payload['order_number'] ?? null;
        $trackingStatus = $payload['tracking_status'] ?? null;

        // Lookup HARUS scoped ke identifier non-null. Sebelumnya pakai
        // `where(api_order_id,$x)->orWhere(reference_id,$y)` dengan $x/$y bisa
        // null → query jadi `WHERE col IS NULL OR ...` yang match order acak
        // yang juga punya null di kolom itu. order_number jadi fallback ketiga
        // bila provider cuma kirim itu di payload.
        if ($orderId === null && $referenceId === null && $orderNumber === null) {
            return response()->json([
                'success' => false,
                'message' => 'Missing order identifier',
            ], 400);
        }

        $order = Order::where(function ($q) use ($orderId, $referenceId, $orderNumber) {
            if ($orderId !== null) {
                $q->orWhere('fulfillment_api_order_id', $orderId);
            }
            if ($referenceId !== null) {
                $q->orWhere('fulfillment_reference_id', $referenceId);
            }
            if ($orderNumber !== null) {
                $q->orWhere('order_number', $orderNumber);
            }
        })->first();

        if ($order === null) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if (empty($status) && $airwaybill !== null) {
            $order->shipping_resi = $airwaybill;
            $order->fulfillment_status = 'shipped';
            $order->status = 'shipped';
            $order->shipped_at = now();
            $order->tracking_status = null;
            $order->save();

            OrderShipped::dispatch($order);

            return response()->json([
                'success' => true,
                'message' => 'AWB updated',
            ]);
        }

        if ($status === 'failed') {
            $order->fulfillment_status = 'failed';
            $order->shipping_resi = null;
            $order->fulfillment_api_order_id = null;
            $order->fulfillment_reference_id = null;
            $order->label_url = null;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Failure recorded',
            ]);
        }

        if ($status === 'status_update') {
            $order->tracking_status = strtolower($trackingStatus ?? '');

            if (stripos($trackingStatus ?? '', 'deliver') !== false) {
                $order->status = 'completed';
                // Sebelumnya fulfillment_status stuck di 'shipped' walau paket
                // sudah delivered → filter admin "delivered" return kosong.
                $order->fulfillment_status = 'delivered';
            }

            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unknown status',
        ], 400);
    }
}
