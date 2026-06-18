<?php

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client untuk mengirim webhook ke sistem Affiliate.
 *
 * Signature dihitung pakai HMAC-SHA256 dari body JSON.
 * Kalau URL atau secret kosong, skip tanpa throw exception.
 */
class AffiliateWebhookClient
{
    /**
     * Kirim webhook event ke Affiliate system.
     *
     * @param  string  $event  Nama event (misal: 'order-paid', 'order-refunded')
     * @param  array<string, mixed>  $payload  Data payload yang dikirim
     */
    public function dispatch(string $event, array $payload): void
    {
        $url = (string) config('webhook.affiliate_url');
        $secret = (string) config('webhook.secret');

        if ($url === '' || $secret === '') {
            Log::warning('Webhook affiliate: URL atau secret kosong, skip dispatch.', [
                'event' => $event,
            ]);

            return;
        }

        $timeout = (int) config('webhook.timeout', 5);
        $retries = (int) config('webhook.retries', 3);

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

        try {
            $response = Http::timeout($timeout)
                ->retry($retries, 200)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $event,
                    'X-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($url);

            Log::info('Webhook affiliate terkirim.', [
                'event' => $event,
                'status_code' => $response->status(),
                'url' => $url,
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook affiliate gagal.', [
                'event' => $event,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
