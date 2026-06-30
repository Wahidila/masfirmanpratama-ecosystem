<?php

namespace App\Services\Shipping;

use App\Exceptions\ShippingRateException;
use App\Services\Settings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgenwebsiteClient
{
    public function __construct(private array $cfg) {}

    public static function fromConfig(): self
    {
        $cfg = config('shipping');

        // License + domain bisa di-override dari panel admin (Settings DB),
        // fallback ke .env. Domain agenwebsite license-bound, jadi user_agent
        // (yang menyertakan site_url) HARUS ikut berubah saat site_url diganti.
        $license = Settings::get('shipping.license', $cfg['license']);
        if (is_string($license) && $license !== '') {
            $cfg['license'] = $license;
        }

        $siteUrl = Settings::get('shipping.site_url', $cfg['site_url']);
        if (is_string($siteUrl) && $siteUrl !== '') {
            $cfg['site_url'] = $siteUrl;
            $cfg['user_agent'] = 'WordPress/'.$cfg['wordpress_version'].'; '.$siteUrl;
        }

        return new self($cfg);
    }

    /** Base request meniru wp_remote_post: UA WordPress + header plugin + form body. */
    protected function http(): PendingRequest
    {
        return Http::asForm()
            ->withUserAgent($this->cfg['user_agent'])
            ->withHeaders([
                'plugin-version' => $this->cfg['plugin_version'],
                'wordpress-version' => $this->cfg['wordpress_version'],
                'woocommerce-version' => $this->cfg['woocommerce_version'],
                'php-version' => PHP_VERSION,
                'site-url' => $this->cfg['site_url'],
            ])
            ->timeout($this->cfg['timeout']);
    }

    protected function baseBody(array $extra = []): array
    {
        return array_merge([
            'license' => $this->cfg['license'],
            'product' => $this->cfg['product'],
        ], $extra);
    }

    /** POST ke endpoint, normalisasi hasil ke {status,message,result}. */
    public function post(string $path, array $body = [], array $query = []): array
    {
        if (($this->cfg['license'] ?? '') === '') {
            return ['status' => 'error', 'message' => 'Kode Lisensi belum diisi.', 'result' => null];
        }

        $url = rtrim($this->cfg['api_url'], '/').'/'.ltrim($path, '/');
        if ($query) {
            $url .= '?'.http_build_query($query);
        }

        try {
            $resp = $this->http()->post($url, $this->baseBody($body));
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Gagal terhubung dengan Agenwebsite', 'result' => null];
        }

        $json = $resp->json() ?? [];
        if ($resp->successful()) {
            return ['status' => 'success', 'message' => $json['message'] ?? 'OK', 'result' => $json['data'] ?? null];
        }

        return ['status' => 'error', 'message' => $json['message'] ?? 'Gagal terhubung dengan Agenwebsite', 'result' => null];
    }

    public function activateLicense(): array
    {
        return $this->post('license/activate');
    }

    public function couriers(): array
    {
        return Cache::remember('shipping.couriers', $this->cfg['cache_master_ttl'], function () {
            $result = $this->post('shipping/couriers');

            if ($result['status'] === 'success' && is_array($result['result']) && count($result['result']) > 0) {
                return $result['result'];
            }

            return $this->fallbackJson('couriers.json');
        });
    }

    /**
     * Fetch service master, optionally filtered by category.
     *
     * IMPORTANT: API mengabaikan `?category=` query param dan selalu balik 44 row.
     * Kita cache satu kali (semua kategori) lalu filter di sisi client by row.category.
     * Kalau dulu kita pakai query, cache key per-kategori akan menyimpan row poison
     * dari kategori lain (instant/international bocor ke domestic).
     */
    public function services(?string $category = null): array
    {
        $all = Cache::remember('shipping.services.all', $this->cfg['cache_master_ttl'], function () {
            $result = $this->post('shipping/services');

            if ($result['status'] === 'success' && is_array($result['result']) && count($result['result']) > 0) {
                return $result['result'];
            }

            return $this->fallbackJson('services.json');
        });

        if ($category === null || $category === '') {
            return $all;
        }

        return array_values(array_filter(
            $all,
            fn ($row) => ($row['category'] ?? '') === $category,
        ));
    }

    /**
     * Autocomplete kota/kecamatan via /shipping/data. Min 3 chars (keyword pendek
     * bikin response gemuk + noisy). Keyword by NAME only — zipcode = 0 hits.
     *
     * @return array<int, array{province:string, city:string, district:string}>
     */
    public function searchData(string $keyword): array
    {
        $keyword = trim($keyword);
        if (strlen($keyword) < 3) {
            return [];
        }

        $cacheKey = 'shipping.data.'.md5(strtolower($keyword));

        return Cache::remember($cacheKey, $this->cfg['cache_master_ttl'], function () use ($keyword) {
            $result = $this->post('shipping/data', ['keyword' => $keyword]);

            if ($result['status'] !== 'success' || ! is_array($result['result'])) {
                return [];
            }

            return array_values(array_map(fn ($r) => [
                'province' => (string) ($r['province'] ?? ''),
                'city' => (string) ($r['city'] ?? ''),
                'district' => (string) ($r['district'] ?? ''),
            ], $result['result']));
        });
    }

    /**
     * Get shipping price rates. Casts price to int. Cached with short TTL.
     *
     * @param  array<string, mixed>  $params  Rate query parameters (origin, dest, weight, courier, etc.)
     * @return array<int, array<string, mixed>> Array of rate rows with price cast to int.
     */
    public function price(array $params): array
    {
        $cacheKey = 'shipping.rate.'.md5(json_encode($params));

        return Cache::remember($cacheKey, $this->cfg['cache_rate_ttl'], function () use ($params) {
            $result = $this->post('shipping/price', $params);

            if ($result['status'] === 'error') {
                Log::warning('Shipping rate API error', [
                    'endpoint' => 'shipping/price',
                    'api_message' => $result['message'] ?? null,
                    'province' => $params['province'] ?? null,
                    'city' => $params['city'] ?? null,
                ]);
                throw new ShippingRateException($result['message'] ?? 'Gagal memuat tarif pengiriman.');
            }

            if (! is_array($result['result'])) {
                return [];
            }

            return array_map(function (array $row) {
                $row['price'] = (int) ($row['price'] ?? 0);

                return $row;
            }, $result['result']);
        });
    }

    protected function fallbackJson(string $filename): array
    {
        $path = storage_path('app/shipping/'.$filename);

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?? [];
        }

        return [];
    }

    public function createShipment(array $data): array
    {
        $response = $this->post('shipment/create-order', $data);

        if ($response['status'] === 'error') {
            return [
                'status' => 'error',
                'message' => $response['message'] ?? 'Unknown error',
            ];
        }

        $result = $response['result'] ?? [];

        if (! is_array($result)) {
            return ['status' => 'error', 'message' => 'Invalid response'];
        }

        $normalized = [
            'reference_id' => $result['reference_id'] ?? null,
            'order_id' => $result['order_id'] ?? null,
        ];

        if (isset($result['airwaybill'])) {
            $normalized['status'] = 'awb_ready';
            $normalized['airwaybill'] = $result['airwaybill'];
        } elseif (isset($result['payment_url'])) {
            $normalized['status'] = 'pending_payment';
            $normalized['payment_url'] = $result['payment_url'];
        } else {
            $normalized['status'] = 'waiting_awb';
        }

        if (isset($result['label_url'])) {
            $normalized['label_url'] = $result['label_url'];
        }

        return $normalized;
    }

    public function requestPickup(array $data): array
    {
        return $this->post('shipment/request-pickup', $data);
    }

    public function eligibility(array $data): array
    {
        return $this->post('shipment/eligibility', $data);
    }

    public function tracking(string $awb, string $courier): array
    {
        $cacheKey = 'shipping.tracking.'.md5($awb.'|'.$courier);

        return Cache::remember($cacheKey, 900, function () use ($awb, $courier) {
            $response = $this->post('shipping/tracking', [
                'awb' => $awb,
                'courier' => $courier,
            ]);

            if ($response['status'] !== 'success' || ! is_array($response['result'])) {
                return [];
            }

            return $response['result'];
        });
    }
}
