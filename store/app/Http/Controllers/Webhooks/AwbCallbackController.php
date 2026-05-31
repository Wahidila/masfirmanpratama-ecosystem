<?php

namespace App\Http\Controllers\Webhooks;

use App\Events\OrderShipped;
use App\Http\Controllers\Controller;
use App\Models\Order;
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

        $license = config('shipping.license');

        if (empty($license)) {
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
        $trackingStatus = $payload['tracking_status'] ?? null;

        $order = Order::where('fulfillment_api_order_id', $orderId)
            ->orWhere('fulfillment_reference_id', $referenceId)
            ->first();

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
