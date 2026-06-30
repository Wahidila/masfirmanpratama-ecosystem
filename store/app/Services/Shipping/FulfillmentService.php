<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class FulfillmentService
{
    public function __construct(private AgenwebsiteClient $agenwebsite) {}

    public function createShipment(Order $order): array
    {
        $shipmentData = $this->buildShipmentData($order);

        $response = $this->agenwebsite->createShipment($shipmentData);

        $status = strtolower($response['status'] ?? 'error');

        $updates = [
            'fulfillment_api_order_id' => $response['order_id'] ?? null,
            'fulfillment_reference_id' => $response['reference_id'] ?? null,
            'fulfillment_payload' => $shipmentData,
        ];

        if (isset($response['label_url'])) {
            $updates['label_url'] = $response['label_url'];
        }
        if (isset($response['payment_url'])) {
            $updates['fulfillment_payment_url'] = $response['payment_url'];
        }

        switch ($status) {
            case 'awb_ready':
                $updates['shipping_resi'] = $response['airwaybill'];
                $updates['fulfillment_status'] = 'shipped';
                $updates['status'] = 'shipped';
                $updates['shipped_at'] = now();
                break;
            case 'waiting_awb':
                $updates['fulfillment_status'] = 'waiting_awb';
                break;
            case 'pending_payment':
                $updates['fulfillment_status'] = 'pending_payment';
                break;
        }

        $order->update($updates);

        return match ($status) {
            'awb_ready' => [
                'status' => 'awb_ready',
                'message' => 'Shipment created, AWB ready',
                'tracking_number' => $response['airwaybill'],
            ],
            'waiting_awb' => [
                'status' => 'waiting_awb',
                'message' => 'Shipment created, waiting for AWB',
            ],
            'pending_payment' => [
                'status' => 'pending_payment',
                'message' => 'Shipment requires payment',
                'payment_url' => $response['payment_url'] ?? null,
            ],
            default => [
                'status' => 'error',
                'message' => $response['message'] ?? 'Unknown error',
            ],
        };
    }

    public function buildShipmentData(Order $order): array
    {
        $items = $order->items;

        $totalWeight = 0.0;
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;
        $shipmentItems = [];

        /** @var OrderItem $item */
        foreach ($items as $item) {
            /** @var Product|null $product */
            $product = $item->product;
            $weight = (float) ($product?->weight_kg ?? config('shipping.default_weight_kg', 1));
            $totalWeight += $weight * $item->qty;

            if ($product?->length_cm !== null) {
                $maxLength = max($maxLength, (int) $product->length_cm);
            }
            if ($product?->width_cm !== null) {
                $maxWidth = max($maxWidth, (int) $product->width_cm);
            }
            if ($product?->height_cm !== null) {
                $totalHeight += (int) $product->height_cm * $item->qty;
            }

            $shipmentItems[] = [
                'name' => $product?->title ?? 'Product',
                'qty' => $item->qty,
                'weight' => (int) ceil($weight),
            ];
        }

        $totalWeight = max($totalWeight, 1.0);

        $defaultDims = config('shipping.default_dimensions_cm', ['length' => 10, 'width' => 10, 'height' => 5]);
        $maxLength = $maxLength ?: (int) $defaultDims['length'];
        $maxWidth = $maxWidth ?: (int) $defaultDims['width'];
        $totalHeight = $totalHeight ?: (int) $defaultDims['height'];

        $phone = $this->normalizePhone($order->phone);

        // Prefer discrete columns (canonical via autocomplete shipping/data).
        // Legacy fallback: parse composed address string only when those columns
        // are NULL (pre-FIX-4 orders) — explode is unreliable when address_line
        // contains commas, so we only use it as a last resort.
        $city = $order->shipping_city;
        $province = $order->shipping_province;
        $district = $order->shipping_district;
        $zipcode = $order->shipping_zipcode;
        $street = $order->address;

        if ($city === null && $province === null) {
            $addressParts = array_map('trim', explode(',', (string) $order->address));
            $street = $addressParts[0] ?? (string) $order->address;
            $city = $addressParts[1] ?? '';
            $province = $addressParts[2] ?? '';
            $zipcode = $zipcode ?? ($addressParts[3] ?? '');
        }

        return [
            'shipper' => [
                'name' => config('shipping.origin'),
                'zipcode' => config('shipping.origin_zipcode'),
            ],
            'receiver' => [
                'name' => $order->customer_name,
                'phone' => $phone,
                'email' => $order->email,
                'address' => $street,
                'city' => (string) $city,
                'province' => (string) $province,
                'district' => (string) ($district ?? ''),
                'zipcode' => (string) ($zipcode ?? ''),
            ],
            'items' => $shipmentItems,
            'weight' => (int) ceil($totalWeight),
            'length' => $maxLength,
            'width' => $maxWidth,
            'height' => $totalHeight,
            'courier' => $order->shipping_courier,
            'service' => $order->shipping_service,
        ];
    }

    public function normalizePhone(string $phone): string
    {
        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        return $phone;
    }
}
