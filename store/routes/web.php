<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UploadController;
use App\Models\Order;
use Illuminate\Http\Request;
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
Route::get('/', fn () => view('pages.home'))->name('home');

// Katalog produk
Route::get('/produk', fn () => view('pages.products.index'))->name('products.index');

Route::get('/produk/{slug}', fn (string $slug) => view('pages.products.show', ['slug' => $slug]))
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
 * TODO (task t_8a063559): wajib harden token-protect (signed URL Laravel atau
 * JWT) — saat ini signed URL TTL 24h dari CheckoutController, tapi route ini
 * belum require signed middleware (biar M1 stub flow tetap jalan).
 */
Route::get('/upload/{order_number}', [UploadController::class, 'show'])
    ->where('order_number', '[A-Za-z0-9\\-]+')
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
 */
Route::post('/upload/{order_number}', [UploadController::class, 'store'])
    ->where('order_number', '[A-Za-z0-9\\-]+')
    ->name('upload.store');

// Order tracking — M1 dummy + M2 hydrate (task t_34ed789d):
// Kalau order_number cocok ke DB, pass real Order ke view supaya track page
// bisa override shipment block dengan data shipping_courier/shipping_resi/shipped_at
// yang baru di-input admin. Kalau ngga ada, fallback ke dummy heuristic lama.
Route::get('/track/{order_number}', function (string $order_number) {
    $order = Order::where('order_number', $order_number)->first();

    return view('pages.track', [
        'orderNumber' => $order_number,
        'dbOrder' => $order,
    ]);
})
    ->where('order_number', '[A-Za-z0-9\\-]+')
    ->name('track.show');

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
|
| Guest routes: GET /admin/login + POST /admin/login (login attempt).
| Protected routes: semua di belakang middleware `auth:admin`.
| Logout via POST /admin/logout (CSRF + session invalidate).
|
*/

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstallmentSchemeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingsController;

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest (login form + attempt)
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('login.attempt');
    });

    // Authenticated
    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Produk CRUD (M2 — task t_2dce058d, t_e51df9e5)
        // Bind {product} pakai slug (Product::getRouteKeyName()).
        Route::post('products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
        Route::post('products/{product}/restore', [ProductController::class, 'restore'])
            ->withTrashed()
            ->name('products.restore');
        Route::resource('products', ProductController::class)
            ->except(['show'])
            ->parameters(['products' => 'product']);

        // Pesanan (M2 — task t_b543e461) — index list dengan filter & pagination
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        // Pesanan detail (M2 — task t_11e4dc6b) — items, payments, customer info
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        // Verifikasi bayar (M2 — task t_812d1980) — approve/reject payment, recalc order status
        Route::post('orders/{order}/payments/{payment}/approve', [OrderController::class, 'approvePayment'])
            ->name('orders.payments.approve');
        Route::post('orders/{order}/payments/{payment}/reject', [OrderController::class, 'rejectPayment'])
            ->name('orders.payments.reject');

        // Input resi + transition ke shipped (M2 — task t_34ed789d)
        // Precondition: order.status='paid'. Validate kurir + resi, fire OrderShipped event.
        Route::post('orders/{order}/ship', [OrderController::class, 'markShipped'])
            ->name('orders.ship');

        // Settings (M2 — task t_6be9a4e4) — store info + bank accounts CRUD
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings/store-info', [SettingsController::class, 'updateStoreInfo'])
            ->name('settings.store-info.update');
        Route::put('settings/bank-accounts', [SettingsController::class, 'updateBankAccounts'])
            ->name('settings.bank-accounts.update');

        // Installment schemes (M2 — task t_8446fbd4) — global + per-product CRUD
        Route::post('installment-schemes/{installment_scheme}/toggle',
            [InstallmentSchemeController::class, 'toggle'])
            ->name('installment-schemes.toggle');
        Route::resource('installment-schemes', InstallmentSchemeController::class)
            ->except(['show'])
            ->parameters(['installment-schemes' => 'installment_scheme']);
    });
});
