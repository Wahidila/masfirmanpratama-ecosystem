<?php

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ambil nama affiliator dari kode referral via endpoint app Affiliate
 * (GET /referral-info/{code}). Dipakai detail order admin untuk menampilkan
 * siapa yang mereferralkan order.
 *
 * Signature HMAC-SHA256 atas kode, secret & base URL diturunkan dari config
 * webhook yang sudah ada. Hasil di-cache 6 jam; HANYA nama non-kosong yang
 * di-cache supaya kegagalan sesaat tidak "meracuni" cache.
 */
class AffiliateLookupClient
{
    public function affiliatorName(string $code): ?string
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $cacheKey = 'affiliate.name.'.md5($code);
        $cached = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        $name = $this->fetch($code);
        if (is_string($name) && $name !== '') {
            Cache::put($cacheKey, $name, now()->addHours(6));

            return $name;
        }

        return null;
    }

    protected function fetch(string $code): ?string
    {
        $webhookUrl = (string) config('webhook.affiliate_url');
        $secret = (string) config('webhook.secret');

        if ($webhookUrl === '' || $secret === '') {
            return null;
        }

        $parts = parse_url($webhookUrl);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $base = $parts['scheme'].'://'.$parts['host'];
        $url = $base.'/referral-info/'.rawurlencode($code);
        $signature = 'sha256='.hash_hmac('sha256', $code, $secret);

        try {
            $response = Http::timeout((int) config('webhook.timeout', 5))
                ->withHeaders(['X-Signature' => $signature])
                ->get($url);

            if ($response->successful()) {
                $name = $response->json('affiliator_name');

                return is_string($name) ? $name : null;
            }
        } catch (\Throwable $e) {
            Log::warning('Affiliate lookup gagal.', ['code' => $code, 'error' => $e->getMessage()]);
        }

        return null;
    }
}
