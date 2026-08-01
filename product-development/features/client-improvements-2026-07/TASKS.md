# Client Improvements — Batch 2026-07

> Task list & progress tracker untuk 8 permintaan klien.
> Status: 🔴 belum · 🟡 in-progress · 🟢 selesai · ⚪ blocked/nunggu keputusan
> Dibuat: 2026-07-31 · **SELESAI: 8/8 task 🟢 (2026-08-01)** di branch `feat/client-improvements-2026-07`

## Ringkasan permintaan

| # | Task | App | Status |
|---|------|-----|--------|
| 1 | Tambah **kode unik pembayaran** | store | 🟢 |
| 2 | Input **email opsional** saat order | store | 🟢 |
| 3 | Peserta masuk kelas **hanya saat cicilan lunas** (bukan cicilan pertama) | store | 🟢 |
| 4 | Cicilan **bebas kapan saja & nominal bebas**, hapus jatuh tempo/deadline | store | 🟢 |
| 5 | Fitur **lupa password** | store + affiliate | 🟢 |
| 6 | Halaman affiliator **lihat detail produk** (bukan cuma jumlah sukses) | affiliate | 🟢 |
| 7 | **Icon show password** di semua form login | store + affiliate | 🟢 |
| 8 | Add referral link **langsung pilih produk** (tanpa copy-paste URL) | affiliate | 🟢 |

---

## Detail & sub-task

### 1. Kode unik pembayaran 🟢
Nominal transfer = total + kode unik (1–999) → tiap order punya nominal khas.
- [x] Migration `unique_code` (unsignedSmallInteger nullable) di `orders`
- [x] `Order::generateUniqueCode($total)` (hindari tabrakan nominal antar order pending harga sama)
- [x] `Order::payableTotal()` = total + kode unik (order lama tanpa kode = total)
- [x] Kode dibebankan ke **pembayaran pertama**: lunas = payableTotal; cicilan = DP + kode
      (plan total tetap = payableTotal). Threshold "lunas"/enroll tetap `verified >= total` → aman
- [x] Generate di CheckoutController (buku) + CourseCheckoutController (kelas)
- [x] Admin order show: tampil kode unik + total transfer
- [x] Success page (buku + kelas): callout "3 digit terakhir XXX = kode unik, transfer PERSIS"
- [x] ✅ Test: UniquePaymentCodeTest 4/4; CheckoutStore & CourseAddToCart disesuaikan; store 758 pass
- Catatan: webhook affiliate tetap kirim `order_total = total` (base) → komisi tidak kena kode unik.

### 2. Email opsional saat order 🟢
Keputusan final (2026-08-01): email **sepenuhnya opsional** — termasuk order via link referral.
(Awalnya conditional-required untuk referral; dilepas setelah self-referral check dihapus — lihat §9.)
- [x] Product checkout (`CheckoutController`): validasi email `nullable` polos
- [x] Course checkout (`CourseCheckoutController`): idem
- [x] Blade `pages/checkout/index` + `pages/courses/checkout`: label selalu `(opsional)`, tanpa `required`, Alpine hanya cek format kalau diisi
- [x] ✅ Test: CheckoutStore + CourseRegistrationEmail + AffiliateWebhook (email opsional walau ada referral) — pass

### 3. Peserta hanya masuk kelas saat lunas 🟢
- [x] Titik pembuatan: `CourseParticipantSync::fromOrder()` — dulu enroll saat `verified > 0`
- [x] Ubah guard → hanya enroll saat `verified >= total` (lunas); DP/cicilan pertama tidak enroll
- [x] payment_status order-linked selalu `lunas`; idempotent (firstOrNew order_id) → tidak dobel
- [x] Update doc listener `SyncCourseParticipant` + 4 test disesuaikan
- [x] ✅ `CourseParticipantTest` 20/20 pass (termasuk XLSX setelah `composer install`)
- Catatan: peserta manual (order_id null) tetap bisa `cicil`/`lunas` (tak terpengaruh).

### 4. Cicilan bebas (no deadline) 🟢
Keputusan klien: **free-form penuh** + **DP nominal bebas diisi customer** saat checkout.
- [x] Checkout kelas: skema cicilan (DP%/N/interval) DIGANTI input **DP nominal bebas** (Alpine di-rework)
- [x] `CourseCheckoutController`: validasi `dp_amount` (bukan `installment_scheme_id`); 1 payment DP = dp + kode unik; `order_meta.installment = {free_form:true, dp}`
- [x] Upload: form **"Bayar cicilan lagi"** (nominal bebas + bukti) → `UploadController::storeFreeFormPayment` buat OrderPayment baru pending
- [x] Success page kelas: pesan free-form ("sisa dicicil bebas kapan saja, tanpa jatuh tempo"), tanpa jadwal H+30
- [x] Hapus deadline: `InstallmentReminder` → `due_date=null` + tanpa overdue untuk free-form; link upload TTL panjang (~1 th) supaya bisa nyicil lama
- [x] Admin order show sudah null-safe (jatuh tempo di-skip untuk free-form); peserta enroll tetap saat lunas (task 3)
- [x] ✅ Test: FreeFormInstallmentTest 4/4 (DP, wajib DP, tambah bayar, enroll saat lunas), CourseAddToCart + UniquePaymentCode disesuaikan; store **762 pass** (2 sisa env pre-existing); InstallmentReminder/Upload lama 46 pass
- ⚠️ Catatan: modul admin **Skema Cicilan** (`InstallmentScheme` CRUD) kini **vestigial** (checkout tak pakai skema lagi). Dibiarkan; bisa dihapus di follow-up.

### 5. Lupa password 🟢
Model Affiliator & Admin extend `Foundation\Auth\User` → `CanResetPassword` sudah built-in.
- [x] Affiliate: broker `affiliators` sudah ada. Controllers PasswordResetLink + NewPassword,
      views forgot/reset, routes `password.*`, link "Lupa password?" di login.
- [x] Store admin: tambah broker `admins` (config/auth.php), controllers Admin\*, views admin/auth/*,
      routes `password.*` di bawah `/admin` (nama global, bukan `admin.*`, agar notif bawaan jalan), link login.
- [x] Password field di form reset dapat toggle show/hide otomatis (affiliate) / manual (admin).
- [x] ✅ Test: Affiliate PasswordResetTest 6/6, Store AdminPasswordResetTest 5/5.
- ⚠️ **Deploy**: `MAIL_MAILER` default `log`. Untuk email sungguhan set SMTP di `.env`
      (MAIL_MAILER=smtp + host/port/user/pass) di KEDUA app.

### 6. Halaman affiliator lihat produk 🟢
Affiliate & Store app/DB terpisah → Store expose katalog JSON, Affiliate fetch (cached).
- [x] Store: `GET /api/affiliate/products` (AffiliateCatalogController) — buku (products) + kelas (courses) aktif
- [x] Affiliate: `StoreCatalog` service (Http fetch + cache 30 mnt, hanya cache sukses)
- [x] Affiliate: `ProductController@index` + view `products/index` (grid kartu: gambar, tipe, harga, **komisi Anda**, tombol buat link, link ke store)
- [x] Rate komisi resolver per (tipe affiliator, tipe produk) — prioritas sama seperti webhook
- [x] Nav "Produk" + route `products.index`
- [x] ✅ Test: store AffiliateCatalogTest 2/2, affiliate ProductCatalogTest (list + empty-state) pass

### 7. Icon show/hide password 🟢
- [x] Store admin login — **sudah ada** toggle (`showPassword` + eye SVG)
- [x] Affiliate: enhance komponen `x-form.input` → password-aware (Alpine `x-bind:type` + eye/eye-off SVG)
- [x] Otomatis kena: affiliator login, admin login, register (2 field), + bonus profile change-password
- [x] ✅ Affiliate `AuthTest` 13/13 pass
- Catatan: field API key WhatsApp di store settings (bukan login) sengaja dilewati (out of scope).

### 8. Add referral link pilih produk 🟢
- [x] Form create/edit referral: toggle **Pilih produk** (dropdown dari katalog) vs **URL custom** (Alpine)
- [x] Pilih produk → `target_url` otomatis terisi URL store (`/produk/{slug}` atau `/kelas/{slug}`)
- [x] Tombol "Buat Link" di halaman Produk → prefill create form dgn produk terpilih
- [x] `ReferralController` create/edit inject `StoreCatalog`; validasi `target_url` (nullable url) tetap
- [x] ✅ Test: affiliate ProductCatalogTest (picker render + create pakai product url) pass; full affiliate suite 131 pass

---

## Catatan / keputusan
- **Split-brain repo**: store terupdate di `feat/shipping-domestic-parity`, affiliate terupdate di `main`.
  Keputusan klien (2026-07-31): **merge dulu → main**, lalu semua task di atas branch terpadu. Store deploy dari `main`.
- Branch kerja: `feat/client-improvements-2026-07` (base = main + merge feat/shipping).
- Modul **Peserta Kursus** (`CourseParticipant`, `CourseParticipantSync`) ikut masuk lewat merge ini.
- Kode unik pembayaran: **belum ada** — sekarang cuma andalkan "cocokkan 3 digit terakhir" manual.
- Email order: **sudah** nullable di server, tapi conditionally-required kalau ada `ref_code`/cookie referral; Blade masih `required`.
- Affiliate **tidak punya tabel products** — komisi by `product_type` saja. Task 6 & 8 butuh sumber produk.

## Status test (baseline setelah merge, sebelum task)
- Store: **741 pass / 4 fail** — 4 fail pre-existing & environmental (bukan dari task):
  2× XLSX export peserta (PhpSpreadsheet dev Windows), 2× Agenwebsite fallback (`storage/app/shipping/*.json` gitignored).
  ✅ Semua test enrollment peserta PASS (relevan task 3).
- Affiliate: **121 pass / 0 fail**.

## Log progress
- 2026-07-31 — task list dibuat, eksplorasi codebase (4 agent paralel)
- 2026-07-31 — ✅ Merge `feat/shipping-domestic-parity` → `feat/client-improvements-2026-07` (base main).
  4 konflik (comment-only + hidden select + test fixture) resolved. Test store+affiliate hijau (selain 4 env-fail).
- 2026-07-31 — ✅ Task 3 (peserta lunas), 7 (show password), 2 (email opsional), 5 (lupa password) — commit + test hijau.
- 2026-08-01 — ✅ Task 6 & 8 (produk affiliator + pilih produk), 1 (kode unik), 4 (cicilan bebas) — commit + test hijau.
- 2026-08-01 — 🎉 **8/8 task selesai.** Store 762 pass / affiliate 131 pass (2 store-fail sisa = env shipping fallback, pre-existing).
  Belum di-merge ke `main` & belum push — menunggu review/keputusan klien.
  Follow-up opsional: SMTP untuk email reset (task 5), hapus modul Skema Cicilan vestigial (task 4), build assets saat deploy.
- 2026-08-01 — **§9 Hapus pengecekan self-referral** (permintaan klien): komisi baru cair setelah pembayaran
  diverifikasi admin, jadi pembelian via link sendiri tetap transaksi nyata yang menguntungkan.
  - Affiliate `StoreWebhookController`: guard self-referral + "buyer unverifiable (no email)" DIHAPUS → order + komisi tetap dibuat.
  - Konsekuensi: email **tak lagi dipakai untuk verifikasi** → task 2 dijadikan email **sepenuhnya opsional** (lepas requiredIf).
  - Test disesuaikan: affiliate StoreWebhook (self-referral & no-email → dapat komisi), store email/webhook. Store 762 / affiliate 130 pass.
