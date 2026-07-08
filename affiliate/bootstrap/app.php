<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Cookie referral dibaca lintas-app oleh Store (APP_KEY berbeda). Jangan
        // enkripsi supaya Store bisa membacanya sebagai plaintext. Kode referral
        // bukan rahasia (sudah ada di URL /ref/{code}). HARUS sama persis dengan
        // except di app Store.
        $middleware->encryptCookies(except: ['referral_code']);

        // App jalan di belakang TLS-proxy (domain .test HTTPS → 127.0.0.1 HTTP).
        // Trust proxy + honor X-Forwarded-Proto supaya URL/redirect yang
        // di-generate ikut skema https, bukan http.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
