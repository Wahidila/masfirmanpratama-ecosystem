<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CourseCheckoutController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingRateController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Webhooks\AwbCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Store routes (M1 — placeholders)
|--------------------------------------------------------------------------
|
| Routes berikut adalah placeholder untuk Sprint M1. Controller akan
| menggantikan closure ini di task M1 selanjutnya (homepage, katalog,
| product detail, cart, checkout, upload, tracking).
|
*/

// Homepage
Route::get('/', HomeController::class)->name('home');

// Static pages
Route::get('/tentang', [PageController::class, 'tentang'])->name('pages.tentang');
Route::get('/kontak', [PageController::class, 'kontak'])->name('pages.kontak');

// Katalog produk
Route::get('/produk', [ProductController::class, 'index'])->name('products.index');

Route::get('/kelas/{slug}', [CourseController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('courses.show');

Route::get('/kelas/{slug}/checkout', [CourseCheckoutController::class, 'create'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('courses.checkout');
Route::post('/kelas/{slug}/checkout', [CourseCheckoutController::class, 'store'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('courses.checkout.store');
Route::get('/kelas/{slug}/checkout/success/{order}', [CourseCheckoutController::class, 'success'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('courses.checkout.success');

Route::get('/produk/{slug}', [ProductController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('products.show');

// Cart
Route::get('/cart', fn () => view('pages.cart'))->name('cart.index');

// Checkout
Route::get('/checkout', fn () => view('pages.checkout.index'))->name('checkout.index');

// POST /checkout — M2 task t_a3f2fe94: persist orders + items + payments,
// generate payment schedule based on installment_scheme, redirect ke
// signed URL /upload/{order_number}.
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

/*
 * GET /checkout/success/{order}
 * --------------------------------------------------------------------------
 * Membaca payload yang di-flash di POST /checkout (M1 stub) supaya view
 * tahu total transfer (DP atau lunas) tanpa harus refetch dari DB.
 *
 * M2: ganti closure ini dengan CheckoutController@success → fetch order
 *     dari `orders` + `order_payments`, hitung total transfer dari
 *     schema cicilan yang tersimpan, dan render dengan data DB-driven.
 */
Route::get('/checkout/success/{order}', function (string $order) {
    /** @var array<string, mixed> $payload */
    $payload = (array) session('checkout.payload', []);

    $paymentType = in_array(($payload['payment_type'] ?? null), ['lunas', 'cicilan'], true)
        ? $payload['payment_type']
        : 'lunas';

    $cartTotal = (int) ($payload['cart_total'] ?? 0);

    // schedule_json di-serialize dari Alpine; row 0 = DP saat cicilan.
    $schedule = [];
    if (! empty($payload['schedule_json']) && is_string($payload['schedule_json'])) {
        $decoded = json_decode($payload['schedule_json'], true);
        if (is_array($decoded)) {
            $schedule = $decoded;
        }
    }

    $totalTransfer = $cartTotal;
    if ($paymentType === 'cicilan' && isset($schedule[0]['amount'])) {
        $totalTransfer = (int) $schedule[0]['amount'];
    }

    return view('pages.checkout.success', [
        'order' => $order,
        'paymentType' => $paymentType,
        'cartTotal' => $cartTotal,
        'totalTransfer' => $totalTransfer,
        'schedule' => $schedule,
    ]);
})
    ->where('order', '[A-Za-z0-9\-]+')
    ->name('checkout.success');

/*
 * GET /upload/{order_number}
 * --------------------------------------------------------------------------
 * M2 (task t_c0616c67): UploadController@show — fetch real Order kalau ada di
 * DB, render view dengan pending payments + auto-detect cicilan/lunas dari
 * payments count. Fallback ke M1 stub kalau order_number ngga match (backward
 * compat dengan prototype + signed URL dari checkout).
 *
 * Token-protect (task t_8a063559): require Laravel signed middleware. URL
 * generated di CheckoutController::store via URL::temporarySignedRoute.
 * TTL configurable via config('checkout.upload_url_ttl_seconds') default 7d.
 */
Route::get('/upload/{order_number}', [UploadController::class, 'show'])
    ->where('order_number', '[A-Za-z0-9\\-]+')
    ->middleware('signed')
    ->name('upload.show');

/*
 * POST /upload/{order_number}
 * --------------------------------------------------------------------------
 * M2 (task t_c0616c67): UploadController@store — validate file (image, max
 * 2MB), match payment by sequence, save ke storage/app/public/payment-proofs/,
 * update order_payment.proof_path + paid_at, fire PaymentSubmitted event.
 *
 * Order.status TIDAK transition saat upload (schema source-of-truth: enum
 * pending|partial_paid|paid|... ngga punya 'payment_review'). Status
 * transition ke 'paid' / 'partial_paid' di OrderController::approvePayment
 * setelah admin verify.
 *
 * Token-protect (task t_8a063559): require signed middleware juga — form di
 * upload page submit ulang URL yang sama dengan signature.
 */
Route::post('/upload/{order_number}', [UploadController::class, 'store'])
    ->where('order_number', '[A-Za-z0-9\\-]+')
    ->middleware('signed')
    ->name('upload.store');

/*
 * GET /track/{order_number}
 * --------------------------------------------------------------------------
 * Customer track order via signed URL (task t_8a063559). Akses tanpa signature
 * → 403, expired (>30d) → 410.
 *
 * M2 hydrate (task t_34ed789d): kalau order_number cocok ke DB, pass real Order
 * ke view supaya track page bisa override shipment block dengan data
 * shipping_courier/shipping_resi/shipped_at yang baru di-input admin. Kalau
 * ngga ada, fallback ke dummy heuristic lama (backward compat M1).
 */
Route::get('/track/{order_number}', [TrackController::class, 'show'])
    ->where('order_number', '[A-Za-z0-9\\-]+')
    ->middleware('signed')
    ->name('track.show');

// Shipping rate lookup (AJAX from checkout page)
Route::post('/shipping/rates', [ShippingRateController::class, 'rates'])
    ->middleware('throttle:30,1')
    ->name('shipping.rates');

// Agenwebsite AWB callback webhook (HMAC-SHA256 signed, CSRF-exempt)
Route::post('/webhooks/agenwebsite/awb', [AwbCallbackController::class, 'handle'])
    ->name('webhooks.agenwebsite.awb');

/*
|--------------------------------------------------------------------------
| Dev-only routes
|--------------------------------------------------------------------------
*/

// Component gallery (smoke-test) — non-production only
if (! app()->environment('production')) {
    Route::get('/__components', fn () => view('components-gallery'))->name('dev.components');
}

/*
|--------------------------------------------------------------------------
| Admin (M2 — auth + dashboard)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BlogCategoryController as AdminBlogCategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstallmentSchemeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\WaNotificationController;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('login.attempt');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('products/bulk', [AdminProductController::class, 'bulk'])->name('products.bulk');
        Route::post('products/{product}/restore', [AdminProductController::class, 'restore'])
            ->withTrashed()
            ->name('products.restore');
        Route::resource('products', AdminProductController::class)
            ->except(['show'])
            ->parameters(['products' => 'product']);

        // Blog — Artikel CRUD (WordPress import routes added in blog import phase)
        Route::post('posts/bulk', [AdminPostController::class, 'bulk'])->name('posts.bulk');
        Route::post('posts/{post}/restore', [AdminPostController::class, 'restore'])
            ->withTrashed()
            ->name('posts.restore');
        Route::resource('posts', AdminPostController::class)
            ->except(['show'])
            ->parameters(['posts' => 'post']);
        Route::resource('blog-categories', AdminBlogCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('courses/bulk', [AdminCourseController::class, 'bulk'])->name('courses.bulk');
        Route::post('courses/{course}/restore', [AdminCourseController::class, 'restore'])
            ->withTrashed()
            ->name('courses.restore');
        Route::resource('courses', AdminCourseController::class)
            ->except(['show'])
            ->parameters(['courses' => 'course']);

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/payments/{payment}/approve', [OrderController::class, 'approvePayment'])
            ->name('orders.payments.approve');
        Route::post('orders/{order}/payments/{payment}/reject', [OrderController::class, 'rejectPayment'])
            ->name('orders.payments.reject');

        Route::post('orders/{order}/ship', [OrderController::class, 'markShipped'])
            ->name('orders.ship');

        Route::post('orders/{order}/generate-shipment', [OrderController::class, 'generateShipment'])
            ->name('orders.generate-shipment');

        Route::post('orders/{order}/refund', [OrderController::class, 'refund'])
            ->name('orders.refund');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings/store-info', [SettingsController::class, 'updateStoreInfo'])
            ->name('settings.store-info.update');
        Route::put('settings/bank-accounts', [SettingsController::class, 'updateBankAccounts'])
            ->name('settings.bank-accounts.update');
        Route::put('settings/shipping', [SettingsController::class, 'updateShipping'])
            ->name('settings.shipping.update');
        Route::put('settings/whatsapp', [SettingsController::class, 'updateWhatsapp'])
            ->name('settings.whatsapp.update');
        Route::post('settings/whatsapp/test', [SettingsController::class, 'testWhatsapp'])
            ->name('settings.whatsapp.test');

        Route::post('installment-schemes/{installment_scheme}/toggle',
            [InstallmentSchemeController::class, 'toggle'])
            ->name('installment-schemes.toggle');
        Route::resource('installment-schemes', InstallmentSchemeController::class)
            ->except(['show'])
            ->parameters(['installment-schemes' => 'installment_scheme']);

        // WA Notifications (M2 — task t_e5d877f3) — read-only list, queued log.
        // Gateway sender M3+. Pakai dash di URL ('wa-notifications') tapi
        // route name pakai dash juga biar konsisten.
        Route::get('wa-notifications', [WaNotificationController::class, 'index'])
            ->name('wa-notifications.index');

        // Sales Reports (Stream A — Laporan Penjualan)
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    });
});
