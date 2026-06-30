<?php

namespace App\Services\Shipping;

use App\Exceptions\ShippingRateException;
use App\Models\Product;
use App\Services\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ShippingRateService
{
    public function __construct(private AgenwebsiteClient $agenwebsite) {}

    public function calculateWeight(array $cartItems): float
    {
        $slugs = array_column($cartItems, 'slug');
        if (empty($slugs)) {
            return 0.0;
        }

        $products = Product::whereIn('slug', $slugs)->get()->keyBy('slug');

        $total = 0.0;
        $hasShippable = false;

        foreach ($cartItems as $item) {
            $product = $products->get($item['slug']);
            if (! $product || $product->is_shippable === false) {
                continue;
            }
            $hasShippable = true;
            $defaultWeight = Settings::get('shipping.default_weight_kg', config('shipping.default_weight_kg', 1));
            $weight = $product->weight_kg ?? $defaultWeight;
            $total += $weight * $item['qty'];
        }

        if (! $hasShippable) {
            return 0.0;
        }

        return round(max($total, 1.0), 2);
    }

    public function calculateDimensions(array $cartItems): array
    {
        $slugs = array_column($cartItems, 'slug');
        $defaults = config('shipping.default_dimensions_cm', ['length' => 10, 'width' => 10, 'height' => 5]);

        if (empty($slugs)) {
            return $this->castDefaults($defaults);
        }

        $products = Product::whereIn('slug', $slugs)->get()->keyBy('slug');

        $hasLength = false;
        $hasWidth = false;
        $hasHeight = false;
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;

        foreach ($cartItems as $item) {
            $product = $products->get($item['slug']);
            if (! $product || $product->is_shippable === false) {
                continue;
            }

            if ($product->length_cm !== null) {
                $hasLength = true;
                $maxLength = max($maxLength, $product->length_cm);
            }
            if ($product->width_cm !== null) {
                $hasWidth = true;
                $maxWidth = max($maxWidth, $product->width_cm);
            }
            if ($product->height_cm !== null) {
                $hasHeight = true;
                $totalHeight += $product->height_cm * $item['qty'];
            }
        }

        return [
            'length' => (int) ($hasLength ? $maxLength : $defaults['length']),
            'width' => (int) ($hasWidth ? $maxWidth : $defaults['width']),
            'height' => (int) ($hasHeight ? $totalHeight : $defaults['height']),
        ];
    }

    /**
     * Resolve shipping rates. Fail-closed: kalau API error/kosong → return []
     * atau rethrow. JANGAN fabrikasi harga — itu pernah jadi bug (fake 9000/kg
     * tersimpan sebagai shipping_cost asli di order).
     *
     * Filter pipeline:
     *   1. Settings 'shipping.couriers' (coarse pre-gate per kurir).
     *   2. services() master dari API → ambil row dengan
     *      enable==1 AND category=='domestic' AND (is_premium==0 OR allow_premium).
     *      Ini source-of-truth: nama service, markup (extra_cost), is_premium.
     *   3. Admin Settings 'shipping.service_markup' di-overlay di atas extra_cost.
     */
    public function getRates(array $destination, array $cartItems): array
    {
        $weight = $this->calculateWeight($cartItems);
        if ($weight === 0.0) {
            return [];
        }

        $shippingEnabled = Settings::get('shipping.shipping_enabled');
        if ($shippingEnabled === false) {
            return [];
        }

        $dimensions = $this->calculateDimensions($cartItems);
        $origin = Settings::get('shipping.origin', config('shipping.origin'));
        $originZipcode = Settings::get('shipping.origin_zipcode', config('shipping.origin_zipcode'));
        $couriers = Settings::get('shipping.couriers', config('shipping.couriers'));

        $zipcode = $destination['zipcode'] ?? '';

        $params = [
            'origin' => $origin,
            'origin_zipcode' => $originZipcode,
            'province' => $destination['province'] ?? '',
            'city' => $destination['city'] ?? '',
            'district' => $destination['district'] ?? '',
            'zipcode' => $zipcode,
            // Kirim berat KG desimal (mis. 1.20), samakan dengan plugin WP
            // (number_format 2dp) — biarkan API yang membulatkan ke tier.
            // ceil() lokal bisa overcharge satu tier untuk berat 1.1–1.4 kg.
            'weight' => round($weight, 2),
            // Mode resolusi destinasi — plugin kirim ini (class-woocommerce-shipping).
            // Pakai 'zipcode' bila kode pos ada, kalau tidak kosong (resolve by nama).
            'method' => $zipcode !== '' ? 'zipcode' : '',
            'courier' => implode('|', $couriers),
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ];

        try {
            $rates = $this->agenwebsite->price($params);
        } catch (ShippingRateException $e) {
            // Bubble up — controllers convert ini ke pesan user-friendly.
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Shipping rate unexpected failure', [
                'exception_message' => $e->getMessage(),
            ]);
            throw new ShippingRateException('Ongkir sementara tidak tersedia. Silakan hubungi admin.', 0, $e);
        }

        if (empty($rates)) {
            return [];
        }

        $master = $this->buildServiceAllowSet();

        // Bila services() master kosong (API down + fallback JSON belum di-seed),
        // jangan kunci checkout: fallback ke filter courier-prefix lama supaya
        // tarif tetap tampil (kurir di Settings tetap di-honor).
        $useMaster = $master->isNotEmpty();
        $serviceMarkup = Settings::get('shipping.service_markup', config('shipping.service_markup', []));

        $couriersTitle = $this->couriersTitleMap();

        $out = [];
        foreach ($rates as $row) {
            // API balikin: `courier` = short id ('jne'), `service` = courier_id ('jne_reg').
            // Master master di-key by courier_id, jadi match pakai `service`, bukan `courier`.
            $courierId = (string) ($row['service'] ?? '');
            $courierPrefix = (string) ($row['courier'] ?? explode('_', $courierId)[0] ?? '');
            if ($courierId === '' || $courierPrefix === '') {
                continue;
            }

            if (! in_array($courierPrefix, $couriers, true)) {
                continue;
            }

            $masterRow = null;
            if ($useMaster) {
                $masterRow = $master->get($courierId);
                if ($masterRow === null) {
                    continue;
                }
            }

            $apiPrice = (int) ($row['price'] ?? 0);
            $extraCost = (int) ($masterRow['extra_cost'] ?? 0);
            $adminMarkup = (int) ($serviceMarkup[$courierId] ?? 0);

            $serviceName = (string) ($masterRow['name']
                ?? ($row['service_name'] ?? $courierId));
            $etd = (string) ($row['etd'] ?? '');
            $courierTitle = $couriersTitle[$courierPrefix] ?? strtoupper($courierPrefix);

            // Cegah duplikasi label saat master 'name' sudah mengandung courier
            // (mis. "JNE REG"). Hanya prefix courierTitle kalau name belum mulai
            // dengan itu (case-insensitive).
            $labelBase = stripos($serviceName, $courierTitle) === 0 || stripos($serviceName, $courierPrefix) === 0
                ? $serviceName
                : trim("{$courierTitle} {$serviceName}");

            $out[] = [
                'courier' => $courierPrefix,
                'service' => $courierId,
                // Plugin WP: etd kosong → tanpa suffix. Tidak pakai '(TBD)'.
                'label' => $labelBase.($etd !== '' ? " ({$etd})" : ''),
                'price' => $apiPrice + $extraCost + $adminMarkup,
                'etd' => $etd,
                'is_premium' => (int) ($masterRow['is_premium'] ?? 0) === 1,
            ];
        }

        return $out;
    }

    /**
     * Build allow-set of services (keyed by courier_id) from API master.
     * Honors enable=1, category=domestic, plus is_premium gate from Settings.
     */
    protected function buildServiceAllowSet(): Collection
    {
        try {
            $services = $this->agenwebsite->services('domestic');
        } catch (\Throwable $e) {
            return collect();
        }

        // Default TRUE — samakan dengan plugin WP yang TIDAK menyaring service
        // premium di jalur domestik. Set false hanya bila admin sengaja
        // menyembunyikan service premium.
        $allowPremium = (bool) Settings::get('shipping.allow_premium', config('shipping.allow_premium', true));

        return collect($services)
            ->filter(function ($s) use ($allowPremium) {
                $enabled = (int) ($s['enable'] ?? 0) === 1;
                $domestic = ($s['category'] ?? '') === 'domestic';
                $premiumOk = (int) ($s['is_premium'] ?? 0) === 0 || $allowPremium;

                return $enabled && $domestic && $premiumOk;
            })
            ->keyBy('courier_id');
    }

    /**
     * Map courier short id ('jne') → human title ('JNE'). API down? lowercase fallback.
     *
     * @return array<string, string>
     */
    protected function couriersTitleMap(): array
    {
        try {
            $couriers = $this->agenwebsite->couriers();
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($couriers as $c) {
            $id = $c['id'] ?? null;
            if ($id && ! isset($map[$id])) {
                $map[$id] = (string) ($c['title'] ?? strtoupper($id));
            }
        }

        return $map;
    }

    private function castDefaults(array $defaults): array
    {
        return [
            'length' => (int) ($defaults['length'] ?? 10),
            'width' => (int) ($defaults['width'] ?? 10),
            'height' => (int) ($defaults['height'] ?? 5),
        ];
    }
}
