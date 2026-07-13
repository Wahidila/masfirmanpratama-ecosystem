<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Webhook
    |--------------------------------------------------------------------------
    |
    | Secret key untuk verifikasi HMAC-SHA256 webhook dari Store.
    | Harus sama dengan AFFILIATE_WEBHOOK_SECRET di sisi Store.
    |
    */

    'store_webhook' => [
        'secret' => env('STORE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cron Trigger (HTTP)
    |--------------------------------------------------------------------------
    |
    | Token untuk memicu scheduler via HTTP, karena cron daemon Hostinger tidak
    | berjalan. Dipanggil oleh GitHub Actions terjadwal. Lihat CronTriggerController
    | — hanya menjalankan whitelist command, bukan command sembarang.
    |
    */

    'cron_trigger' => [
        'token' => env('CRON_TRIGGER_TOKEN'),
    ],

];
