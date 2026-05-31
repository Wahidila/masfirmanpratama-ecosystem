<?php

namespace App\Services\Shipping;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AgenwebsiteClient
{
    public function __construct(private array $cfg) {}

    public static function fromConfig(): self
    {
        return new self(config('shipping'));
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

    public function services(string $category = 'domestic'): array
    {
        $cacheKey = 'shipping.services.'.$category;

        return Cache::remember($cacheKey, $this->cfg['cache_master_ttl'], function () use ($category) {
            $result = $this->post('shipping/services', [], ['category' => $category]);

            if ($result['status'] === 'success' && is_array($result['result']) && count($result['result']) > 0) {
                return $result['result'];
            }

            return $this->fallbackJson('services.json');
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

            if ($result['status'] !== 'success' || ! is_array($result['result'])) {
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
}
