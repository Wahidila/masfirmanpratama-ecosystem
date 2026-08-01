# Client Improvements — Batch 2026-07

> Task list & progress tracker untuk 8 permintaan klien.
> Status: 🔴 belum · 🟡 in-progress · 🟢 selesai · ⚪ blocked/nunggu keputusan
> Dibuat: 2026-07-31

## Ringkasan permintaan

| # | Task | App | Status |
|---|------|-----|--------|
| 1 | Tambah **kode unik pembayaran** | store | 🔴 |
| 2 | Input **email opsional** saat order | store | 🟢 |
| 3 | Peserta masuk kelas **hanya saat cicilan lunas** (bukan cicilan pertama) | store | 🟢 |
| 4 | Cicilan **bebas kapan saja & nominal bebas**, hapus jatuh tempo/deadline | store | 🔴 |
| 5 | Fitur **lupa password** | store + affiliate | 🟢 |
| 6 | Halaman affiliator **lihat detail produk** (bukan cuma jumlah sukses) | affiliate | 🟢 |
| 7 | **Icon show password** di semua form login | store + affiliate | 🟢 |
| 8 | Add referral link **langsung pilih produk** (tanpa copy-paste URL) | affiliate | 🟢 |

---

## Detail & sub-task

### 1. Kode unik pembayaran 🔴
Tujuan: tambahkan angka unik (mis. 3 digit) ke total transfer supaya admin bisa
identifikasi transfer per order. Ditampilkan ke customer di halaman pembayaran.
- [ ] Riset: cek apakah sudah ada konsep kode unik (hasil eksplorasi)
- [ ] Migration: kolom `unique_code` (+ mungkin `grand_total`) di `orders`
- [ ] Generate kode unik saat order dibuat (hindari tabrakan per nominal)
- [ ] Tampilkan total + kode unik di halaman payment / upload bukti
- [ ] Admin: tampilkan kode unik di detail order
- [ ] Test

### 2. Email opsional saat order 🟢
Keputusan: email **opsional**, KECUALI order via link referral (cookie/ref_code) —
karena sisi affiliate butuh email untuk atribusi komisi (cek self-referral).
- [x] Product checkout (`CheckoutController`) server sudah conditional (mirror di Blade)
- [x] Blade `pages/checkout/index`: label `(opsional)` vs `*`, `required` kondisional, Alpine validate kondisional
- [x] Course checkout (`CourseCheckoutController`): validasi `required` → conditional `requiredIf(referral)`
- [x] Blade `pages/courses/checkout`: label + `required` kondisional
- [x] ✅ Test: CheckoutStore (email optional non-referral / required referral) + CourseRegistrationEmail — semua pass
- Catatan klien: kalau mau email **sepenuhnya opsional** (termasuk order referral), tinggal lepas `requiredIf`.

### 3. Peserta hanya masuk kelas saat lunas 🟢
- [x] Titik pembuatan: `CourseParticipantSync::fromOrder()` — dulu enroll saat `verified > 0`
- [x] Ubah guard → hanya enroll saat `verified >= total` (lunas); DP/cicilan pertama tidak enroll
- [x] payment_status order-linked selalu `lunas`; idempotent (firstOrNew order_id) → tidak dobel
- [x] Update doc listener `SyncCourseParticipant` + 4 test disesuaikan
- [x] ✅ `CourseParticipantTest` 20/20 pass (termasuk XLSX setelah `composer install`)
- Catatan: peserta manual (order_id null) tetap bisa `cicil`/`lunas` (tak terpengaruh).

### 4. Cicilan bebas (no deadline) 🔴
- [ ] Hapus/nonaktifkan konsep jatuh tempo / due_date
- [ ] Hapus batasan nominal minimum & jumlah tahapan tetap
- [ ] Izinkan bayar kapan saja, nominal berapa saja sampai lunas
- [ ] Sesuaikan admin UI + validasi verifikasi cicilan
- [ ] Test

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
