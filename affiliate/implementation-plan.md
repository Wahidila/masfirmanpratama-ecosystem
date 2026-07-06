# 🛠️ Implementation Plan — Perbaikan Bug Sistem Affiliate

> Dibuat: 2026-07-06 · Basis: temuan audit `affiliate-plans-combined.md`
> Monorepo: `D:\laravel\masfirmanpratama-ecosystem` (apps: `affiliate/`, `store/`)
> Status keseluruhan: ✅ **SELESAI** (lihat bagian [Status Akhir](#-status-akhir))

## Ruang lingkup

Yang **diperbaiki** di plan ini adalah **bug** dan **celah keamanan yang dijanjikan plan M4** — bukan
fitur yang belum dibangun. Item "fitur belum ada" (webhook logs UI, QR code, export CSV, email
Mailable, upload bukti transfer, avatar, chart) sengaja **di luar ruang lingkup** karena itu
penambahan fitur, bukan bug; didaftar di [Out of scope](#out-of-scope) supaya bisa diminta terpisah.

---

## 🐞 Bug & celah yang diperbaiki

### BUG-1 🔴 CRITICAL — Kontrak `store_order_id` tidak konsisten → refund tidak pernah membatalkan komisi
- **Akar masalah:** listener `order-paid` mengirim `store_order_id = order_number` (string "MFP-…"),
  sedangkan listener `order-refunded` mengirim `store_order_id = $order->id` (angka). Receiver
  affiliate mencari `ReferralOrder::where('store_order_id', …)` sehingga refund **tidak pernah**
  menemukan order → komisi order yang direfund tidak dibatalkan (affiliator tetap dibayar).
- **Dampak:** uang keluar untuk order yang sudah dikembalikan. Lolos dari test karena tiap app
  hanya menguji sisinya sendiri (gap fase P6 "integration smoke" di plan M4).
- **Fix:** `store/app/Listeners/DispatchAffiliateOrderRefunded.php` → `store_order_id = $order->order_number`.
- **Tambahan sekalian:** (a) tambah guard `if (empty($order->ref_code)) return;` agar konsisten dengan
  `order-paid` dan tidak mengirim webhook noise; (b) `idempotency_key` deterministik
  (`'refund-'.$order->order_number`) menggantikan `…-.time()` yang non-deterministik.

### BUG-2 🟠 HIGH (celah keamanan, janji plan) — Endpoint webhook tanpa rate limit
- **Akar masalah:** `POST /webhooks/store` (endpoint publik) tidak punya throttle; plan M4 §Keamanan
  mensyaratkan "throttle middleware".
- **Fix:** `affiliate/routes/web.php` → tambahkan `->middleware('throttle:60,1')`.

### BUG-3 🟠 HIGH (celah keamanan, janji plan) — Tidak ada deteksi self-referral
- **Akar masalah:** affiliator bisa membeli produk memakai kode referralnya sendiri dan mendapat
  komisi atas dirinya sendiri. Plan menjanjikan "Self-referral detection". Payload `order-paid`
  bahkan belum membawa identitas pembeli (hanya `buyer_name`), jadi mustahil dideteksi.
- **Fix (dua sisi):**
  - `store` — kirim `buyer_email = $order->email` di payload `order-paid`.
  - `affiliate` — simpan `buyer_email` di `referral_orders` (migration + fillable); di
    `StoreWebhookController::handleOrderPaid`, jika `buyer_email` sama dengan email affiliator
    (case-insensitive), **skip** pembuatan referral_order & komisi, log webhook `processed` dengan
    catatan self-referral. (Deteksi via email — garis pertahanan pertama standar.)
- **BUG-3b (ditemukan review adversarial) 🟠 — lubang null-email.** Guard `if ($buyerEmail && …)`
  di-short-circuit saat `buyer_email` null, dan checkout buku di Store mengizinkan email kosong →
  affiliator bisa beli buku via kodenya sendiri tanpa email dan tetap dibayar. **Fix dua lapis:**
  (a) **Store (akar):** `CheckoutController` mewajibkan `customer_email` bila ada referral (form
  ATAU cookie), jadi order referral yang sah selalu bawa email; (b) **Affiliate (fail-closed):**
  kalau `buyer_email` kosong, komisi **ditahan** (tidak dibuat) dan dicatat untuk tinjauan manual —
  tidak lagi lolos begitu saja.

### BUG-4 🟡 LOW — `config('app.store_url')` selalu jatuh ke fallback (env diabaikan)
- **Akar masalah:** `ReferralController` memakai `config('app.store_url', …)` tapi key `store_url`
  tak pernah didefinisikan di `config/app.php`, jadi `env('STORE_URL')` diabaikan diam-diam.
- **Fix:** tambah `'store_url' => env('STORE_URL', 'https://masfirmanpratama.com')` di
  `affiliate/config/app.php`; dokumentasikan `STORE_URL=` di `.env.example`.

---

## ✅ Checklist tugas

**Store (emitter):**
- [x] BUG-1: perbaiki `DispatchAffiliateOrderRefunded` (store_order_id, guard ref_code, idempotency deterministik)
- [x] BUG-3: tambah `buyer_email` ke payload `DispatchAffiliateOrderPaid`
- [x] BUG-3b: `CheckoutController` wajibkan `customer_email` bila ada referral (form/cookie)
- [x] Update test `OrderRefundedWebhookTest` (assert `store_order_id == order_number`, event body, ISO8601 refunded_at, idempotency deterministik, + test guard no-ref_code)
- [x] Update test `AffiliateWebhookTest` (assert `buyer_email` di payload order-paid + test wajib-email)

**Affiliate (receiver):**
- [x] BUG-2: throttle di route `webhooks/store`
- [x] BUG-3: migration `add_buyer_email_to_referral_orders`
- [x] BUG-3: `buyer_email` di fillable `ReferralOrder`
- [x] BUG-3: simpan `buyer_email` + guard self-referral di `StoreWebhookController::handleOrderPaid`
- [x] BUG-3b: fail-closed saat `buyer_email` kosong (tahan komisi + log)
- [x] BUG-4: `store_url` di `config/app.php` + `STORE_URL` di `.env.example`
- [x] Update test `StoreWebhookTest` (payload default `buyer_email`, assert tersimpan, self-referral, case-insensitive+log, missing-email)
- [x] Jalankan `php artisan migrate` di affiliate

**Verifikasi:**
- [x] `php artisan test` affiliate hijau (74 passed, 236 assertions)
- [x] Store webhook + checkout LOGIC tests hijau (sisa gagal hanya Vite-manifest, lihat catatan)
- [x] Review adversarial atas seluruh diff (4 temuan → semua sudah diperbaiki)
- [x] `pint` bersih di semua file yang diubah

---

## Out of scope
Fitur (bukan bug) yang tetap terbuka — bisa diminta sebagai pekerjaan terpisah:
webhook logs UI di admin · QR code referral · export CSV komisi · Mailable/email nyata ·
upload bukti transfer withdrawal · upload avatar · chart dashboard.

---

## 📌 Status Akhir

✅ **Semua bug di ruang lingkup selesai & terverifikasi** (2026-07-06).

**Hasil test:**
- Affiliate: `php artisan test` → **74 passed (236 assertions)**, termasuk 3 test self-referral baru.
- Store (webhook + checkout logic): **semua hijau** — `AffiliateWebhookTest`, `OrderRefundedWebhookTest`
  (bagian logika), `CheckoutCourseTest`, `CheckoutShippingIntegrationTest`, dan `CheckoutStoreTest`
  minus 1 test render halaman.
- `pint` bersih di semua file yang diubah.

**Review adversarial (workflow 4 dimensi):** dimensi kontrak-lintas-app & throttle/config → 0 temuan.
Dimensi self-referral & test → 4 temuan terkonfirmasi, **semua sudah diperbaiki**: (1) HIGH lubang
null-email → BUG-3b; (2) assertion `refunded_at` diperkuat ke ISO8601; (3) test case-insensitive
kini assert webhook-log; (4) test refund assert field `event` body.

**Catatan lingkungan (bukan bug kode):** app `store` di checkout ini belum ter-provision penuh
(`vendor`, `.env`, dan **Vite build** awalnya tidak ada). Saya sudah `composer install` + buat `.env`,
tapi **tidak** build frontend. Akibatnya test store yang me-render blade gagal dengan
`Vite manifest not found` — ini gap lingkungan (perlu `npm install && npm run build`), **tidak
tersentuh** oleh perubahan ini (perubahan saya nol menyentuh view). Semua test logika sudah hijau.

**Residual (opsional, di luar bug):** belum ada test integrasi end-to-end lintas-app (payload Store
benar-benar dikonsumsi receiver Affiliate) — dimitigasi dengan meng-assert kontrak `store_order_id ==
order_number` di KEDUA sisi. Deteksi self-referral berbasis email (bisa dielakkan dgn email lain);
match tambahan (phone/akun) bisa ditambah nanti bila diperlukan.

**File yang diubah:**
- Store: `app/Listeners/DispatchAffiliateOrderPaid.php`, `app/Listeners/DispatchAffiliateOrderRefunded.php`,
  `app/Http/Controllers/CheckoutController.php`, `tests/Feature/AffiliateWebhookTest.php`,
  `tests/Feature/OrderRefundedWebhookTest.php`
- Affiliate: `app/Http/Controllers/Webhooks/StoreWebhookController.php`, `app/Models/ReferralOrder.php`,
  `config/app.php`, `routes/web.php`, `.env.example`,
  `database/migrations/2026_07_06_000000_add_buyer_email_to_referral_orders_table.php`,
  `tests/Feature/StoreWebhookTest.php`
