<?php

namespace App\Providers;

use App\Services\Shipping\AgenwebsiteClient;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AgenwebsiteClient::class, fn () => AgenwebsiteClient::fromConfig());
        $this->app->singleton(ShippingRateService::class, fn ($app) => new ShippingRateService($app->make(AgenwebsiteClient::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CATATAN: listener di app/Listeners/* di-AUTO-DISCOVER oleh Laravel 11
        // (via type-hint handle(EventType)). JANGAN daftarkan lagi via
        // Event::listen di sini — dulu keduanya aktif → setiap notifikasi WA
        // ter-fire 2× (pembeli terima pesan dobel + biaya gateway dobel).
        //
        // Mapping event → listener (semua auto-discovered):
        //   OrderCreated       → SendCustomerOrderCreatedNotification (WA pembeli + link upload)
        //   PaymentSubmitted   → SendAdminPaymentReviewAlert (admin)
        //                      + SendCustomerPaymentReceivedNotification (konfirmasi pembeli)
        //   PaymentVerified    → SendCustomerPaymentVerifiedNotification (pembeli)
        //                      + DispatchAffiliateOrderPaid
        //   PaymentRejected    → SendCustomerPaymentRejectedNotification (pembeli)
        //   OrderShipped       → SendCustomerOrderShippedNotification (pembeli) + SendOrderShippedEmail
        //   OrderRefunded      → DispatchAffiliateOrderRefunded
        //   OrderCompleted     → SendCustomerOrderCompletedNotification (pembeli, terima kasih)
    }
}
