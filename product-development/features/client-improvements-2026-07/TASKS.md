# Client Improvements — Batch 2026-07

> Task list & progress tracker untuk 8 permintaan klien.
> Status: 🔴 belum · 🟡 in-progress · 🟢 selesai · ⚪ blocked/nunggu keputusan
> Dibuat: 2026-07-31

## Ringkasan permintaan

| # | Task | App | Status |
|---|------|-----|--------|
| 1 | Tambah **kode unik pembayaran** | store | 🔴 |
| 2 | Input **email opsional** saat order | store | 🔴 |
| 3 | Peserta masuk kelas **hanya saat cicilan lunas** (bukan cicilan pertama) | store | 🟢 |
| 4 | Cicilan **bebas kapan saja & nominal bebas**, hapus jatuh tempo/deadline | store | 🔴 |
| 5 | Fitur **lupa password** | store + affiliate | 🔴 |
| 6 | Halaman affiliator **lihat detail produk** (bukan cuma jumlah sukses) | affiliate | 🔴 |
| 7 | **Icon show password** di semua form login | store + affiliate | 🟢 |
| 8 | Add referral link **langsung pilih produk** (tanpa copy-paste URL) | affiliate | 🔴 |

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

### 2. Email opsional saat order 🔴
- [ ] Ubah validasi email `required` → `nullable` (FormRequest / controller)
- [ ] Update Blade checkout: label tidak wajib, hapus `required` attr
- [ ] Pastikan alur yang pakai email (notifikasi/track) aman kalau email kosong
- [ ] Test

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

### 5. Lupa password 🔴
- [ ] Store: cek password_reset_tokens + controller + view + mailable
- [ ] Affiliate: idem
- [ ] Implement flow yang belum ada (request → email → reset)
- [ ] Konfigurasi mail (cek .env.example)
- [ ] Test

### 6. Halaman affiliator lihat produk 🔴
- [ ] Buat halaman list produk (nama, gambar, komisi, URL store)
- [ ] Tampilkan detail (bukan cuma count sukses)
- [ ] Route + controller + view + nav link
- [ ] Test

### 7. Icon show/hide password 🟢
- [x] Store admin login — **sudah ada** toggle (`showPassword` + eye SVG)
- [x] Affiliate: enhance komponen `x-form.input` → password-aware (Alpine `x-bind:type` + eye/eye-off SVG)
- [x] Otomatis kena: affiliator login, admin login, register (2 field), + bonus profile change-password
- [x] ✅ Affiliate `AuthTest` 13/13 pass
- Catatan: field API key WhatsApp di store settings (bukan login) sengaja dilewati (out of scope).

### 8. Add referral link pilih produk 🔴
- [ ] Ganti input URL manual → dropdown/select produk
- [ ] Auto-build URL store dari produk terpilih
- [ ] Sesuaikan controller + validasi
- [ ] Test

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
