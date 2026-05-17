# Plan Revamp Prototype Sistem Affiliate

## 1) Tujuan Revamp

- Menyatukan alur kerja affiliate agar konsisten di seluruh halaman: registrasi -> login -> dashboard -> referral -> komisi -> withdrawal -> monitoring.
- Menstandarkan UI ke Bootstrap 5 + Bootstrap Icons, tanpa Tailwind.
- Menerapkan color system tunggal:
  - Primary/Navigasi: `#283d8f`
  - Aksi penting/Notifikasi penting: `#d42427`
  - Background + teks kontras: `#fff`
- Menambahkan step-by-step bubble tips pada prototype agar alur mudah dipresentasikan.

---

## 2) Scope Halaman Prototype

### Affiliate Core
- `prototype/affiliate.html` (landing program affiliate)
- `prototype/register.html`
- `prototype/register-pending.html`
- `prototype/login.html`
- `prototype/dashboard-nonpeserta.html`
- `prototype/dashboard-peserta.html`
- `prototype/affiliator-links.html`
- `prototype/affiliator-commissions.html`
- `prototype/affiliator-withdrawals.html`
- `prototype/affiliator-marketing.html`
- `prototype/affiliator-leaderboard.html`
- `prototype/affiliator-profile.html`
- `prototype/admin.html`

### Halaman Terkait Alur Referral -> Pembelian
- `prototype/product-detail.html`
- `prototype/cart.html`
- `prototype/checkout.html`
- `prototype/checkout-success.html`

---

## 3) Prinsip UX dan Konsistensi Global

- Gunakan layout shell yang sama di halaman login-protected:
  - Sidebar kiri (navigasi utama)
  - Topbar (judul halaman + user info)
  - Main content (container Bootstrap)
- Gunakan urutan menu seragam:
  1. Dashboard
  2. Link Referral
  3. Komisi
  4. Withdrawal
  5. Materi Marketing
  6. Leaderboard
  7. Profil
- Gunakan pola status seragam di seluruh tabel:
  - Pending = warning
  - Approved/Cair = success
  - Rejected/Refunded = danger
- Semua CTA primer gunakan `.btn-danger` yang di-custom ke `#d42427`.
- Semua elemen navigasi dan active state gunakan skema `#283d8f`.

---

## 4) Design System Bootstrap 5 (Token & Komponen)

## 4.1 CSS Variables

```css
:root {
  --aff-primary: #283d8f;
  --aff-primary-dark: #1e2f73;
  --aff-danger: #d42427;
  --aff-danger-dark: #b11e21;
  --aff-white: #fff;
  --aff-bg-soft: #f5f7ff;
  --aff-border: #dbe2ff;
  --aff-text: #1f2a44;
  --aff-muted: #6b7899;
}
```

## 4.2 Mapping Komponen

- Navbar/Sidebar: `navbar`, `nav`, `offcanvas`, `list-group`
- Card KPI: `card`, `card-body`, `badge`
- Form: `form-control`, `form-select`, `input-group`, `form-text`
- Tabel data: `table`, `table-hover`, `table-responsive`
- Alert/notif: `alert`
- Aksi utama: `btn btn-danger` (override ke `#d42427`)
- Aksi sekunder: `btn btn-outline-primary` (primary `#283d8f`)
- Modal konfirmasi: `modal`
- Bubble tips: custom popover-style (Bootstrap-compatible)

---

## 5) Arsitektur Bubble Tips (Guide Tur Prototype)

## 5.1 Tujuan
- Menjelaskan alur kerja end-to-end secara bertahap untuk presentasi.
- Tur bisa dijalankan per halaman atau berkelanjutan lintas halaman.

## 5.2 Spesifikasi Teknis
- Gunakan 1 modul JS global: `prototype/assets/js/affiliate-tour.js`.
- Setiap target diberi atribut:
  - `data-tour-id="..."`
  - `data-tour-order="..."` (opsional jika pakai config object)
- Kontrol tur:
  - Tombol floating `Mulai Tur` (kanan bawah)
  - Tombol `Lanjut`, `Kembali`, `Lewati`
  - Progress: `Langkah X/Y`
- State tur lintas halaman disimpan di `localStorage`:
  - `affiliateTour.currentFlow`
  - `affiliateTour.currentStep`
- Jika step berikutnya di halaman lain, tombol `Lanjut` melakukan redirect otomatis.

## 5.3 Pola Naskah Bubble Tip
- Judul: nama fitur
- Isi: 1-2 kalimat, fokus manfaat dan aksi user
- Contoh format:
  - Judul: `Dashboard Ringkas`
  - Isi: `Di sini Anda melihat performa klik, penjualan, dan komisi secara real-time. Mulai analisis dari card ini setiap hari.`

---

## 6) Alur Step-by-Step Bubble Tips (Semua Fitur Affiliate)

## 6.1 Flow A - Registrasi & Aktivasi

### Halaman: `prototype/affiliate.html`
1. Hero Program Affiliate: nilai utama program.
2. Blok Cara Kerja: ringkasan 3 langkah.
3. Skema Komisi: perbedaan non-peserta vs alumni.
4. CTA Daftar: masuk ke form registrasi.

### Halaman: `prototype/register.html`
1. Pilih tipe affiliator: non-peserta/alumni.
2. Isi data personal: validasi identitas.
3. Upload dokumen: syarat verifikasi.
4. Isi rekening dan kredensial login.
5. Submit pendaftaran.

### Halaman: `prototype/register-pending.html`
1. Status review admin.
2. Timeline verifikasi.
3. Notifikasi email approval/reject.

## 6.2 Flow B - Login & Masuk Dashboard

### Halaman: `prototype/login.html`
1. Input email.
2. Input password.
3. Opsi ingat saya.
4. Tombol login ke dashboard.

### Halaman: `prototype/dashboard-nonpeserta.html`
1. Banner upgrade alumni.
2. KPI utama (klik, sales, komisi).
3. Link referral default + copy.
4. Penjelasan fitur terkunci.

### Halaman: `prototype/dashboard-peserta.html`
1. KPI lengkap + total pemasukan.
2. Grafik performa 30 hari.
3. Custom link + QR.
4. Preview leaderboard.
5. Histori transaksi komisi.

## 6.3 Flow C - Referral Link Management

### Halaman: `prototype/affiliator-links.html`
1. Penjelasan privilege custom slug.
2. Form generate custom link.
3. Link default + parameter tambahan.
4. Tabel performa link (klik/sales).
5. Aksi copy, QR, hapus link.

## 6.4 Flow D - Statistik Komisi

### Halaman: `prototype/affiliator-commissions.html`
1. Ringkasan saldo: total, pending, aktif.
2. Penjelasan cooling period 7 hari.
3. Filter tab status komisi.
4. Tabel transaksi komisi per invoice.
5. CTA ke halaman withdrawal.

## 6.5 Flow E - Penarikan Dana

### Halaman: `prototype/affiliator-withdrawals.html`
1. Saldo aktif siap tarik.
2. Rekening tujuan pencairan.
3. Tombol buka modal tarik dana.
4. Input nominal + validasi minimum.
5. Histori withdrawal dan status proses.

## 6.6 Flow F - Materi Marketing

### Halaman: `prototype/affiliator-marketing.html`
1. Ringkasan manfaat materi promosi.
2. Filter jenis materi (gambar/video/copy).
3. Card materi + preview.
4. Aksi download/copy untuk distribusi.

## 6.7 Flow G - Leaderboard & Gamification

### Halaman: `prototype/affiliator-leaderboard.html`
1. Banner periode kompetisi.
2. Posisi pengguna saat ini.
3. Tabel ranking top affiliator.
4. Info bonus estimasi per peringkat.

## 6.8 Flow H - Profil & Keamanan

### Halaman: `prototype/affiliator-profile.html`
1. Status akun dan verifikasi.
2. Form data personal.
3. Form rekening pencairan.
4. Form ubah password.

## 6.9 Flow I - Admin Review

### Halaman: `prototype/admin.html`
1. Alert item yang perlu aksi cepat.
2. KPI operasional affiliate system.
3. Tabel pendaftaran pending.
4. Aksi approve/reject affiliator.

## 6.10 Flow J - Pembeli dari Referral (Halaman Terkait)

### Halaman: `prototype/product-detail.html`
1. Banner referral terdeteksi.
2. Detail produk dan harga.
3. Add to cart.

### Halaman: `prototype/cart.html`
1. Konfirmasi referral masih aktif.
2. Ringkasan item.
3. CTA checkout.

### Halaman: `prototype/checkout.html`
1. Input data pelanggan.
2. Ringkasan total tagihan.
3. Proses pembayaran.

### Halaman: `prototype/checkout-success.html`
1. Pembayaran sukses.
2. Invoice transaksi.
3. Catatan komisi affiliate (webhook).

---

## 7) Roadmap Implementasi Revamp

## Phase 1 - Foundation UI (Bootstrap 5)
- Hapus dependency Tailwind dari semua halaman affiliate prototype.
- Tambahkan dependency global:
  - Bootstrap 5 CSS/JS
  - Bootstrap Icons
  - `prototype/assets/css/affiliate-bootstrap-theme.css`
- Samakan komponen base: topbar, sidebar, card, form, table.

## Phase 2 - Layout Seragam per Grup Halaman
- Grup Auth: `affiliate.html`, `register.html`, `register-pending.html`, `login.html`
- Grup Dashboard: `dashboard-nonpeserta.html`, `dashboard-peserta.html`
- Grup Operasional: links, commissions, withdrawals, marketing, leaderboard, profile
- Grup Admin: `admin.html`
- Grup Store-related referral journey: `product-detail.html`, `cart.html`, `checkout.html`, `checkout-success.html`

## Phase 3 - Integrasi Bubble Tips
- Implement `affiliate-tour.js` + style bubble.
- Pasang target `data-tour-id` pada elemen kunci tiap halaman.
- Tambahkan CTA `Mulai Tur` pada semua halaman prototype.
- Uji lintas halaman dengan localStorage resume.

## Phase 4 - Final QA Presentasi
- Uji responsive: mobile, tablet, desktop.
- Uji kontras warna utama sesuai ketentuan.
- Uji kelengkapan semua flow bubble tips.
- Uji konsistensi label, istilah, dan status.

---

## 8) Acceptance Criteria

- Semua halaman affiliate dan halaman terkait referral menggunakan Bootstrap 5 + Bootstrap Icons.
- Tidak ada dependency Tailwind di halaman scope revamp.
- Semua halaman mematuhi skema warna utama:
  - Navigasi/utama `#283d8f`
  - CTA penting/notifikasi penting `#d42427`
  - Background dominan `#fff`
- Tersedia bubble tips step-by-step untuk:
  - Registrasi
  - Login
  - Dashboard
  - Link referral
  - Statistik komisi
  - Penarikan dana
  - Halaman terkait alur affiliate lainnya
- Tur bisa dipakai saat presentasi tanpa perlu menjelaskan manual dari nol.

---

## 9) Catatan Implementasi Singkat (Direkomendasikan)

- Pertahankan struktur konten yang sudah ada, fokus pada:
  1. Standardisasi framework UI
  2. Konsistensi visual
  3. Narasi bubble tips
- Gunakan 1 sumber data JSON untuk script bubble tips agar mudah update naskah presentasi.
- Pastikan semua tombol CTA utama konsisten posisi dan label (contoh: `Daftar`, `Masuk`, `Copy Link`, `Tarik Dana`).
