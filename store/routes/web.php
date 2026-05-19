<?php

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

// POST stub — M1: balikan dummy order_number tanpa simpan DB.
// M2 akan replace ke CheckoutController@store: validate → simpan orders +
// order_items + order_payments (kalau cicilan) → redirect ke upload bukti.
Route::post('/checkout', function (Request $request) {
    $orderNumber = 'MFP-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    return redirect()
        ->route('checkout.success', ['order' => $orderNumber])
        ->with('checkout.payload', $request->except(['_token']));
})->name('checkout.store');

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
 * M1 stateless: payment context (lunas/cicilan, nominal, jumlah pembayaran,
 * cicilan ke-berapa) di-pass via query string dari halaman checkout success.
 * M2: ganti dengan UploadController@show — fetch order dari `orders` +
 *     `order_payments` untuk auto-detect status (paid/partial_paid),
 *     hitung nominal dari skema cicilan, dan disable opsi cicilan yang
 *     belum jatuh tempo / sudah lunas.
 *
 * TODO (M2 — KRITIS KEAMANAN): route ini WAJIB di-token-protect (signed URL
 * Laravel atau JWT) sebelum production. Lihat catatan di view.
 */
Route::get('/upload/{order_number}', function (string $order_number, Request $request) {
    $paymentType = in_array($request->query('type'), ['lunas', 'cicilan'], true)
        ? $request->query('type')
        : 'lunas';

    $totalTransfer = max(0, (int) $request->query('total', 0));

    $totalPayments = (int) $request->query('n', $paymentType === 'cicilan' ? 2 : 1);
    if ($paymentType === 'cicilan') {
        $totalPayments = max(2, min(24, $totalPayments));
    } else {
        $totalPayments = 1;
    }

    $defaultSequence = (int) $request->query('seq', 0);
    $defaultSequence = max(0, min($totalPayments - 1, $defaultSequence));

    return view('pages.upload', [
        'orderNumber' => $order_number,
        'paymentType' => $paymentType,
        'totalTransfer' => $totalTransfer,
        'totalPayments' => $totalPayments,
        'defaultSequence' => $defaultSequence,
    ]);
})
    ->where('order_number', '[A-Za-z0-9\-]+')
    ->name('upload.show');

/*
 * POST /upload/{order_number}
 * --------------------------------------------------------------------------
 * M1 stub: validasi format + ukuran, lalu kembali ke halaman upload dengan
 * flash success state. File NOT disimpan ke storage di M1 (UI smoke test only).
 *
 * M2: ganti closure ini dengan UploadController@store →
 *   - validate signed URL / JWT (TODO keamanan di atas)
 *   - simpan file ke storage/app/private/proofs/{YYYY}/{MM}/{order}-{seq}.{ext}
 *     dengan rename random hex (jangan trust filename client)
 *   - update orders.status + order_payments[seq].status = 'awaiting_review'
 *   - kirim WA notification ke admin via Fonnte/Wablas
 */
Route::post('/upload/{order_number}', function (string $order_number, Request $request) {
    $request->validate([
        'proof_file' => [
            'required',
            'file',
            'image',
            'mimes:jpeg,jpg,png,webp',
            'max:2048', // KB → 2 MB
        ],
        'installment_sequence' => ['nullable', 'integer', 'min:0', 'max:23'],
        'note' => ['nullable', 'string', 'max:500'],
    ], [
        'proof_file.required' => 'Pilih file bukti transfer dulu sebelum mengirim.',
        'proof_file.image' => 'File harus berupa gambar (JPG, PNG, atau WebP).',
        'proof_file.mimes' => 'Format tidak didukung. Pakai JPG, PNG, atau WebP.',
        'proof_file.max' => 'Ukuran file terlalu besar. Maksimal 2 MB.',
    ]);

    return redirect()
        ->route('upload.show', array_filter([
            'order_number' => $order_number,
            'type' => $request->query('type'),
            'total' => $request->query('total'),
            'n' => $request->query('n'),
            'seq' => $request->query('seq'),
        ]))
        ->with('upload.success', true)
        ->with('upload.sequence', (int) $request->input('installment_sequence', 0));
})
    ->where('order_number', '[A-Za-z0-9\-]+')
    ->name('upload.store');

// Order tracking
Route::get('/track/{order_number}', fn (string $order_number) => view('pages.track', ['orderNumber' => $order_number]))
    ->where('order_number', '[A-Za-z0-9\-]+')
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
use App\Http\Controllers\Admin\ProductController;

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

        // Produk CRUD (M2 — task t_2dce058d)
        // Bind {product} pakai slug (Product::getRouteKeyName()).
        Route::resource('products', ProductController::class)
            ->except(['show'])
            ->parameters(['products' => 'product']);
    });
});
