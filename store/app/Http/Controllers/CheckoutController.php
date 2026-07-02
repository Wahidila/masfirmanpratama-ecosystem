<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Exceptions\ShippingRateException;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Services\Settings;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CheckoutController — wire FE checkout form ke DB persistence (task t_a3f2fe94).
 *
 * Flow:
 *   1. Validate FE payload (customer, address, cart_json, payment_type=lunas).
 *   2. Re-resolve produk dari slug + harga server-side (jangan trust client price).
 *   3. Generate order_number unik (MFP-YYYYMMDD-XXXXXX).
 *   4. Insert orders + order_items + order_payments dalam DB transaction.
 *   5. Generate payment: 1 row pending sebesar grand_total (lunas only).
 *   6. Status order awal: 'pending' (schema source-of-truth — task body sebut
 *      'awaiting_payment' yang ngga ada di enum, default ke schema).
 *   7. Redirect ke halaman "Order berhasil dibuat" (checkout.success, DB-backed).
 *      Dari sana customer klik "Upload Bukti Bayar Sekarang" → signed URL
 *      /upload/{order_number}. Signed URL di-generate di success() (refresh-safe).
 *
 * Catatan ref_code: optional, di-attach apa adanya (validation oleh affiliate side
 * via webhook M3, di-store sebagai string biasa).
 */
class CheckoutController extends Controller
{
    public function __construct(
        private ShippingRateService $shippingRateService,
    ) {}

    /**
     * Kurir whitelist — dari config/store.php shipping_methods (pakai key 'code').
     * Cart bisa cuma kelas (digital, ngga butuh shipping) — shipping_method optional.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'customer_phone' => ['required', 'string', 'min:8', 'max:25'],
            'address_line' => ['required', 'string', 'max:500'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_province' => ['nullable', 'string', 'max:120'],
            'address_district' => ['nullable', 'string', 'max:120'],
            'address_village' => ['nullable', 'string', 'max:120'],
            'address_postal' => ['nullable', 'string', 'max:20'],
            'shipping_method' => ['nullable', 'string', 'max:50'],
            'payment_type' => ['required', 'string', 'in:lunas'],
            'cart_json' => ['required', 'string', 'min:2'],
            'cart_total' => ['required', 'integer', 'min:1'],
            'ref_code' => ['nullable', 'string', 'max:64'],
        ]);

        $cart = $this->parseCartJson($validated['cart_json']);

        // Resolve produk dari slug + recalc subtotal server-side. Checkout produk
        // selalu lunas (payment_type in:lunas) — cicilan hanya untuk kelas/kursus.
        [$resolvedItems, $serverSubtotal, $hasShippable] = $this->resolveCartItems($cart);

        if (empty($resolvedItems)) {
            throw ValidationException::withMessages([
                'cart_json' => 'Cart kosong atau tidak ada produk yang valid.',
            ]);
        }

        // Shipping cost: try dynamic rate via API first, fallback ke flat config.
        $shippingMethod = $validated['shipping_method'] ?? null;

        // Cart berisi produk fisik WAJIB punya metode pengiriman. Tanpa ini,
        // server diam-diam menghitung ongkir=0 lalu anti-tamper menolak dengan
        // pesan "total mismatch" yang membingungkan. Pesan eksplisit lebih baik.
        if ($hasShippable && empty($shippingMethod)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['shipping_method' => 'Silakan pilih metode pengiriman untuk produk fisik.']);
        }

        $address = [
            'province' => $validated['address_province'] ?? '',
            'city' => $validated['address_city'] ?? '',
            'district' => $validated['address_district'] ?? '',
            'village' => $validated['address_village'] ?? '',
            'postal' => $validated['address_postal'] ?? '',
        ];

        $shippingService = null;
        $shippingCost = 0;
        $shippingEtd = null;
        $shippingCourier = null;

        if ($shippingMethod) {
            $cartItems = array_map(fn ($item) => [
                'slug' => $item['slug'] ?? '',
                'qty' => (int) ($item['qty'] ?? 1),
            ], $cart);

            $destination = [
                'province' => $address['province'],
                'city' => $address['city'],
                'district' => $address['district'],
                'zipcode' => $address['postal'],
            ];

            try {
                $rates = $this->shippingRateService->getRates($destination, $cartItems);

                if (! empty($rates)) {
                    foreach ($rates as $rate) {
                        if (($rate['service'] ?? '') === $shippingMethod) {
                            $shippingCost = (int) ($rate['price'] ?? 0);
                            $shippingService = $rate['service'] ?? null;
                            $shippingEtd = $rate['etd'] ?? null;
                            $shippingCourier = explode('_', $shippingMethod)[0];
                            break;
                        }
                    }
                }
            } catch (ShippingRateException $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['shipping_method' => 'Ongkir sementara tidak tersedia. Silakan hubungi admin via WhatsApp.']);
            }
        }

        if ($shippingCost === 0) {
            $shippingCost = $this->resolveShippingCost($shippingMethod);
        }

        // Produk fisik + metode dipilih tapi ongkir tetap 0 → metode tidak match
        // tarif untuk alamat ini (mis. pilihan basi setelah ganti alamat). Tolak
        // supaya tidak terbentuk order fisik dengan ongkir Rp0.
        if ($hasShippable && $shippingMethod && $shippingCost === 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['shipping_method' => 'Metode pengiriman yang dipilih tidak tersedia untuk alamat ini. Silakan pilih ulang.']);
        }

        $grandTotal = $serverSubtotal + $shippingCost;

        // Total order SELALU dihitung server (harga DB + ongkir API) → otoritatif,
        // dan itulah yang tampil di halaman upload/pembayaran. cart_total dari
        // client cuma sanity check: cart bisa "stale" (harga di localStorage beda
        // dgn DB sekarang) sehingga sedikit menyimpang — JANGAN dead-end user,
        // pakai saja total server. Hanya tolak bila client mencoba bayar jauh di
        // bawah server (indikasi korupsi/tamper, bukan drift wajar).
        $clientTotal = (int) $validated['cart_total'];
        if ($clientTotal > 0 && $clientTotal < (int) floor($grandTotal * 0.5)) {
            throw ValidationException::withMessages([
                'cart_total' => 'Total pesanan tidak valid. Muat ulang halaman dan coba lagi.',
            ]);
        }

        // Referral code: input form override, fallback ke cookie affiliate
        $refCode = $validated['ref_code'] ?? Cookie::get('referral_code');

        $order = DB::transaction(function () use (
            $validated,
            $address,
            $resolvedItems,
            $grandTotal,
            $shippingCourier,
            $shippingService,
            $shippingCost,
            $shippingEtd,
            $refCode,
        ) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'email' => $validated['customer_email'] ?? null,
                'address' => $this->composeAddress($validated['address_line'], $address),
                'shipping_city' => $address['city'] ?: null,
                'shipping_province' => $address['province'] ?: null,
                'shipping_district' => $address['district'] ?: null,
                'shipping_village' => $address['village'] ?: null,
                'shipping_zipcode' => $address['postal'] ?: null,
                'total' => $grandTotal,
                'status' => 'pending',
                'ref_code' => $refCode ?: null,
                'shipping_courier' => $shippingCourier,
                'shipping_service' => $shippingService,
                'shipping_cost' => $shippingCost,
                'shipping_etd' => $shippingEtd,
            ]);

            foreach ($resolvedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'course_id' => $item['course_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $this->generatePaymentSchedule($order, $grandTotal);

            return $order;
        });

        // WA konfirmasi ke pembeli (nomor order + total + link upload bukti).
        // Dispatch SETELAH transaksi commit supaya listener baca state final.
        OrderCreated::dispatch($order->fresh());

        // Arahkan ke halaman "Order berhasil dibuat" dulu (bukan langsung ke
        // form upload). Di sana customer lihat nomor order + rekening + total,
        // lalu klik "Upload Bukti Bayar Sekarang". Success page DB-backed
        // (tahan refresh) — signed upload/track URL di-generate di success().
        //
        // Flash one-time signal untuk reset cart: hanya muncul tepat setelah
        // checkout (next request). Pada refresh/share-link/back ke success page,
        // flash sudah hilang → cart yang baru diisi user TIDAK ikut terhapus.
        return redirect()
            ->route('checkout.success', ['order' => $order->order_number])
            ->with('checkout.clear_cart', true);
    }

    /**
     * Halaman sukses "Order berhasil dibuat" (flow book/produk).
     *
     * DB-backed (fetch Order by order_number) supaya tahan refresh & share-link.
     * Signed upload + track URL di-generate ulang tiap request — view TIDAK
     * boleh memanggil route('upload.show') langsung (unsigned → 403). Pola sama
     * dengan CourseCheckoutController::success().
     */
    public function success(string $order): View
    {
        $orderModel = Order::where('order_number', $order)->firstOrFail();
        $orderModel->load(['payments' => fn ($q) => $q->orderBy('id')]);

        $payments = $orderModel->payments;
        $paymentType = $payments->count() > 1 ? 'cicilan' : 'lunas';
        $firstPayment = $payments->first();
        $cartTotal = (int) $orderModel->total;
        $totalTransfer = (int) ($firstPayment->amount ?? $cartTotal);

        // Jadwal hanya relevan untuk cicilan (>1 payment). Book selalu lunas.
        $schedule = $payments->count() > 1
            ? $payments->map(fn ($p) => ['amount' => (int) $p->amount])->all()
            : [];

        return view('pages.checkout.success', [
            'order' => $order,
            'paymentType' => $paymentType,
            'cartTotal' => $cartTotal,
            'totalTransfer' => $totalTransfer,
            'schedule' => $schedule,
            'uploadUrl' => $this->generateUploadUrl($order),
            'trackUrl' => $this->generateTrackUrl($order),
            'bankAccounts' => Settings::getBankAccounts(),
            'waAdmin' => Settings::getWaAdmin(),
            // Sinyal one-time reset cart (true hanya tepat setelah checkout).
            'clearCart' => (bool) session('checkout.clear_cart', false),
        ]);
    }

    /**
     * Signed URL ke /upload/{order_number} (TTL config-driven, default 7 hari).
     */
    protected function generateUploadUrl(string $orderNumber): string
    {
        $ttlDays = max(1, (int) config('checkout.upload_url_ttl_days', 7));

        return URL::temporarySignedRoute(
            'upload.show',
            now()->addDays($ttlDays),
            ['order_number' => $orderNumber],
        );
    }

    /**
     * Signed URL ke /track/{order_number} (TTL lebih panjang, default 30 hari).
     */
    protected function generateTrackUrl(string $orderNumber): string
    {
        $ttlDays = max(1, (int) config('checkout.track_url_ttl_days', 30));

        return URL::temporarySignedRoute(
            'track.show',
            now()->addDays($ttlDays),
            ['order_number' => $orderNumber],
        );
    }

    /**
     * Parse cart JSON dengan defensive guards. Cart shape dari Alpine store/cart.js:
     *   [{ slug, name, price, qty, image?, category? }, ...]
     */
    protected function parseCartJson(string $cartJson): array
    {
        $decoded = json_decode($cartJson, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'cart_json' => 'Format cart tidak valid.',
            ]);
        }

        return array_values(array_filter($decoded, fn ($i) => is_array($i) && ! empty($i['slug'])));
    }

    /**
     * Resolve cart items: lookup produk dari slug, gunakan harga DB (bukan client),
     * agregasi qty per slug, hasilkan subtotal server-side. Produk yang ngga ada
     * di DB / non-active di-skip silently (atau bisa raise — pilihan: raise untuk
     * fail-loud lebih baik UX).
     *
     * @return array{0: array<int, array{product_id:int|null, course_id:int|null, qty:int, unit_price:int, subtotal:int}>, 1: int}
     */
    protected function resolveCartItems(array $cart): array
    {
        $slugs = array_unique(array_map(fn ($i) => (string) $i['slug'], $cart));
        $products = Product::whereIn('slug', $slugs)
            ->where('status', 'active')
            ->get()
            ->keyBy('slug');

        $courses = Course::whereIn('slug', $slugs)
            ->where('status', 'active')
            ->get()
            ->keyBy('slug');

        $items = [];
        $subtotal = 0;
        $hasShippable = false;

        foreach ($cart as $entry) {
            $slug = (string) ($entry['slug'] ?? '');
            $qty = max(1, (int) ($entry['qty'] ?? 1));
            $product = $products->get($slug);
            $course = $courses->get($slug);

            if ($product) {
                $unitPrice = (int) $product->price;
                // Shippable hanya untuk produk fisik: is_shippable true DAN bukan
                // tipe 'course' (kelas digital). is_shippable default true di
                // migration, jadi tipe course bisa keliru terhitung shippable.
                if ($product->is_shippable && $product->type !== 'course') {
                    $hasShippable = true;
                }
                $items[] = [
                    'product_id' => $product->id,
                    'course_id' => null,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $qty,
                ];
            } elseif ($course) {
                $unitPrice = (int) $course->price;
                $items[] = [
                    'product_id' => null,
                    'course_id' => $course->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $qty,
                ];
            } else {
                throw ValidationException::withMessages([
                    'cart_json' => "Item '{$slug}' tidak ditemukan atau tidak aktif.",
                ]);
            }

            $subtotal += $items[array_key_last($items)]['subtotal'];
        }

        return [$items, $subtotal, $hasShippable];
    }

    protected function resolveShippingCost(?string $code): int
    {
        if (! $code) {
            return 0;
        }
        $methods = config('store.shipping_methods', []);
        foreach ($methods as $method) {
            if (($method['code'] ?? null) === $code) {
                return (int) ($method['price'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Rangkai alamat lengkap Indonesia terstruktur untuk label kirim:
     * detail jalan, desa/kelurahan, kecamatan, kota, provinsi, kode pos.
     *
     * @param  array{province?:string, city?:string, district?:string, village?:string, postal?:string}  $address
     */
    protected function composeAddress(string $line, array $address): string
    {
        return collect([
            $line,
            $address['village'] ?? '',
            $address['district'] ?? '',
            $address['city'] ?? '',
            $address['province'] ?? '',
            $address['postal'] ?? '',
        ])
            ->map(fn ($v) => is_string($v) ? trim($v) : '')
            ->filter(fn ($v) => $v !== '')
            ->implode(', ');
    }

    /**
     * Generate unique order_number. Format: MFP-YYYYMMDD-XXXXXX (6 hex upper).
     * Retry up to 5x kalau bentrok (probability sangat rendah, tapi safe-guard).
     */
    protected function generateOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = 'MFP-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
            if (! Order::where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback: append microsecond random — astronomically rare to collide.
        return 'MFP-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
    }

    /**
     * Generate single payment row (lunas) di order_payments (status='pending').
     * paid_at di-set null. Akan di-update saat customer upload bukti +
     * admin verify (handled task t_812d1980 udah merged).
     */
    protected function generatePaymentSchedule(Order $order, int $grandTotal): void
    {
        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $grandTotal,
            'method' => 'transfer',
            'status' => 'pending',
        ]);
    }
}
