<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Affiliate Webhook
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk mengirim webhook ke sistem Affiliate saat order dibayar
    | atau di-refund. Signature dihitung pakai HMAC-SHA256.
    |
    */

    'affiliate_url' => env('AFFILIATE_WEBHOOK_URL', ''),

    'secret' => env('AFFILIATE_WEBHOOK_SECRET', ''),

    'timeout' => (int) env('AFFILIATE_WEBHOOK_TIMEOUT', 5),

    'retries' => (int) env('AFFILIATE_WEBHOOK_RETRIES', 3),

];
