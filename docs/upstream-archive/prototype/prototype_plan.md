# 📋 Prototype Plan — MasFirmanPratama.com

## Tujuan Prototype

Prototype ini dibuat untuk **mempresentasikan konsep dan desain** ekosistem bisnis online MasFirmanPratama.com kepada klien (Mas Firman Pratama). Prototype bersifat **visual & navigable** — semua halaman dapat diklik dan dijelajahi, namun belum terhubung ke backend/database.

---

## 🎯 Target Audience Presentasi

| Audience | Peran |
|----------|-------|
| **Mas Firman Pratama** | Klien / Owner bisnis Alpha Mind Control |
| **Tim Mas Firman** | Admin yang akan mengelola sistem |

---

## 📦 Scope Prototype

### ✅ Yang Ada di Prototype

| # | Halaman | File | Deskripsi |
|---|---------|------|-----------|
| 1 | **Store Homepage** | `index.html` | Halaman utama toko online — hero, benefit, katalog buku, pricing kelas, testimoni, CTA |
| 2 | **Affiliate Landing Page** | `affiliate.html` | Halaman promosi program afiliasi — kalkulator penghasilan, perbandingan tipe, cara kerja, FAQ |
| 3 | **Dashboard Non-Peserta** | `dashboard-nonpeserta.html` | Dashboard affiliator yang belum ikut kelas AMC — stat cards, chart, referral links, transaksi |
| 4 | **Dashboard Peserta** | `dashboard-peserta.html` | Dashboard affiliator alumni — verified badge, 5 stats, dual chart, leaderboard, materi marketing |
| 5 | **Admin Dashboard** | `admin.html` | Panel admin afiliasi — overview, pending actions, revenue chart, approval queue, withdrawal |

### ❌ Yang Belum Ada di Prototype (Akan Dibangun Saat Development)

| Fitur | Keterangan |
|-------|------------|
| Form Registrasi Affiliator | Halaman isi data lengkap + upload KTP |
| Login & Autentikasi | Sistem login nyata dengan session |
| Detail Produk (Store) | Halaman individual per produk |
| Cart & Checkout (Store) | Keranjang belanja + form checkout |
| Integrasi Midtrans | Payment gateway sungguhan |
| Webhook System | Komunikasi Store ↔ Affiliate |
| Email Notifikasi | Kirim email otomatis |
| CRUD Admin | Create/Edit/Delete produk, affiliator, dll |
| Withdrawal Flow | Proses approval + upload bukti transfer |
| Responsive Mobile | Sudah partial, akan di-polish saat development |

---

## 🗺️ Peta Navigasi Prototype

```
┌─────────────────────────────────────────────────────────────┐
│                    STORE (Main Domain)                       │
│                                                             │
│  ┌──────────────────────┐                                   │
│  │   Store Homepage     │──── Link "Program Afiliasi" ────►│
│  │   (index.html)       │                                   │
│  │                      │                                   │
│  │  • Hero Section      │                                   │
│  │  • Benefit Cards     │                                   │
│  │  • Katalog Buku 📖   │                                   │
│  │  • Pricing Kelas 🎓  │                                   │
│  │  • Testimoni         │                                   │
│  │  • CTA               │                                   │
│  └──────────────────────┘                                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  AFFILIATE (Subdomain)                       │
│                                                             │
│  ┌──────────────────────┐    ┌──────────────────────────┐   │
│  │ Affiliate Landing    │──► │ Dashboard Non-Peserta    │   │
│  │ (affiliate.html)     │    │ (dashboard-nonpeserta)   │   │
│  │                      │    │                          │   │
│  │ • Kalkulator 💰      │    │ • 4 Stat Cards           │   │
│  │ • Benefit Cards      │    │ • Line Chart             │   │
│  │ • Non-Peserta vs     │    │ • Referral Links         │   │
│  │   Peserta            │    │ • Tabel Transaksi        │   │
│  │ • Produk & Komisi    │    │ • Withdrawal Widget      │   │
│  │ • 4 Langkah          │    │ • Tier Progress          │   │
│  │ • FAQ                │    │ • Banner Upgrade ⚡       │   │
│  │ • CTA Daftar         │    └──────────────────────────┘   │
│  └──────────────────────┘                                   │
│           │                  ┌──────────────────────────┐   │
│           └────────────────► │ Dashboard Peserta ⭐      │   │
│                              │ (dashboard-peserta)      │   │
│                              │                          │   │
│                              │ • Verified Alumni Badge  │   │
│                              │ • 5 Stat Cards           │   │
│                              │ • Line + Doughnut Chart  │   │
│                              │ • Custom Slug + QR Code  │   │
│                              │ • Leaderboard 🏆         │   │
│                              │ • Materi Marketing       │   │
│                              │ • Tabel Komisi Detail    │   │
│                              │ • Export CSV 📥          │   │
│                              │ • Withdrawal History     │   │
│                              └──────────────────────────┘   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Admin Dashboard ⚙️                       │   │
│  │              (admin.html)                             │   │
│  │                                                      │   │
│  │  • 4 Overview Stats (Affiliator, Sales, Komisi, Rev) │   │
│  │  • Alert: Pending Approvals, Withdrawals, Webhooks   │   │
│  │  • Bar Chart Revenue 6 Bulan                         │   │
│  │  • Doughnut Chart Distribusi Produk                  │   │
│  │  • Tabel Pendaftaran Baru (Approve/Reject)           │   │
│  │  • Tabel Withdrawal Requests                         │   │
│  │  • Top 5 Leaderboard                                 │   │
│  │  • Tabel Transaksi Webhook                           │   │
│  │  • System Settings Preview                           │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎨 Design System

### Warna

| Token | Hex | Penggunaan |
|-------|-----|------------|
| Primary | `#7C3AED` | Tombol utama, accent, sidebar aktif |
| Primary Light | `#A78BFA` | Link, label, text highlight |
| Secondary / Gold | `#F5A623` | Harga, badge gold, CTA sekunder |
| Accent / Cyan | `#06B6D4` | Chart, icon, variasi |
| Success | `#10B981` | Komisi, approved, growth |
| Warning | `#F59E0B` | Pending, tier badge |
| Danger | `#EF4444` | Rejected, failed, alert |
| Background Dark | `#0B0713` | Body background |
| Card Background | `#130D20` | Semua card & container |
| Sidebar | `#0F0920` | Sidebar dashboard |

### Tipografi

| Font | Penggunaan |
|------|------------|
| **Inter** (Google Fonts) | Body text, UI elements |
| **Playfair Display** (Google Fonts) | Heading besar, hero title |

### Visual Style

- **Dark Mode** premium — sesuai tema "kekuatan pikiran" / spiritual
- **Glassmorphism** subtle — backdrop-blur pada card tertentu
- **Gradient accents** — ungu ke emas pada teks penting
- **Glow effects** — box-shadow purple pada hover
- **Micro-animations** — fade-in-up on scroll, hover lift, floating cards

---

## 📊 Data Dummy yang Digunakan

### Stats & Angka

| Data | Nilai | Catatan |
|------|-------|---------|
| Alumni aktif | 2,500+ | Angka ilustrasi |
| Buku terjual | 10,000+ | Angka ilustrasi |
| Affiliator aktif | 500+ | Angka ilustrasi |
| Total komisi dibayar | Rp 2.1M | Angka ilustrasi |
| Revenue via afiliasi | Rp 245Jt | Angka ilustrasi |

### Harga Produk (Aktual dari Website)

| Produk | Harga |
|--------|-------|
| Kelas Reguler AMC | Rp 4.500.000 |
| Kelas Privat AMC | Rp 7.500.000 |
| Kelas Platinum AMC | Rp 22.500.000 |
| Buku Alpha Telepati | Rp 299.000 *(placeholder)* |
| Kitab KPR | Rp 350.000 *(placeholder)* |
| 10 Keajaiban Pikiran | Rp 250.000 *(placeholder)* |
| 101 Sugesti Ajaib | Rp 199.000 *(placeholder)* |

> ⚠️ Harga buku adalah **placeholder** — perlu dikonfirmasi ke Mas Firman.

### Persentase Komisi

| Tipe Affiliator | Komisi Kelas | Komisi Buku |
|-----------------|-------------|-------------|
| Non-Peserta | 5% | 10% |
| Peserta / Alumni | 10% | 15% |

### Affiliator Dummy

| Nama | Tipe | Tier |
|------|------|------|
| Ahmad Setiawan | Peserta | 💎 Diamond |
| Siti Rahmawati | Peserta | 🥇 Gold |
| Hendra Wijaya | Peserta | 🥇 Gold |
| Dewi Lestari | Peserta | 🥈 Silver |
| Rizki Amanah | Non-Peserta | 🥈 Silver |
| Rina Susanti | Non-Peserta | 🥉 Bronze |

---

## 🎤 Panduan Presentasi ke Klien

### Urutan Presentasi yang Disarankan

```
1️⃣  Store Homepage (index.html)
    → Tunjukkan tampilan toko online
    → Scroll ke benefit, produk, pricing
    → Highlight: "Ini yang dilihat customer"

2️⃣  Affiliate Landing (affiliate.html)  
    → Tunjukkan halaman program afiliasi
    → Demo kalkulator penghasilan (ubah produk & jumlah)
    → Jelaskan perbedaan Non-Peserta vs Peserta
    → Highlight: "Ini yang dilihat calon affiliator"

3️⃣  Dashboard Non-Peserta (dashboard-nonpeserta.html)
    → Tunjukkan dashboard sederhana
    → Highlight banner upgrade ke Peserta
    → "Ini mendorong affiliator untuk ikut kelas AMC"

4️⃣  Dashboard Peserta (dashboard-peserta.html)
    → Tunjukkan verified badge & fitur premium
    → Demo referral links dengan custom slug
    → Tunjukkan leaderboard & materi marketing
    → "Alumni mendapat pengalaman lebih lengkap"

5️⃣  Admin Dashboard (admin.html)
    → Tunjukkan overview sistem
    → Scroll ke approval queue
    → Tunjukkan withdrawal management
    → "Mas Firman/tim bisa kelola semua dari sini"
```

### Poin Penting untuk Disampaikan

1. **Dua website terpisah tapi terintegrasi**
   - Store: untuk customer beli kelas & buku
   - Affiliate: untuk affiliator promosi & dapat komisi
   - Terhubung via webhook otomatis

2. **Affiliator didorong jadi peserta kelas**
   - Banner upgrade di dashboard Non-Peserta
   - Komisi lebih besar untuk alumni (2x lipat)
   - Fitur lebih lengkap (custom link, QR, materi eksklusif)
   - *Win-win: lebih banyak peserta kelas + affiliator lebih loyal*

3. **Gamification mendorong performa**
   - Sistem tier: Bronze → Silver → Gold → Diamond
   - Bonus komisi per tier (+1% s/d +3%)
   - Leaderboard kompetisi bulanan

4. **Keamanan & transparansi**
   - Webhook dengan HMAC-SHA256 signature
   - Cooling period 7 hari sebelum komisi approved
   - Self-referral detection
   - Semua transaksi tercatat di webhook log

---

## 🛠️ Teknologi Prototype

| Komponen | Teknologi |
|----------|-----------|
| Structure | HTML5 |
| Styling | Vanilla CSS (custom design system) |
| Interaksi | Vanilla JavaScript |
| Chart | Chart.js 4.4.0 (CDN) |
| Font | Google Fonts (Inter, Playfair Display) |
| Icon | Emoji (native) |
| Server | http-server (npx) untuk preview lokal |

---

## ✅ Checklist Sebelum Presentasi

- [ ] Jalankan `npx -y http-server prototype -p 8080` di `d:\laravel\affiliate`
- [ ] Buka `http://localhost:8080` di browser
- [ ] Test navigasi antar semua 5 halaman
- [ ] Test kalkulator penghasilan di Affiliate Landing
- [ ] Test scrolling di semua halaman
- [ ] Siapkan device (laptop/monitor) untuk presentasi
- [ ] Opsional: buka di HP untuk demo responsive

---

## 📝 Feedback yang Perlu Ditanyakan ke Klien

Setelah presentasi, konfirmasi hal-hal berikut:

### Wajib Dikonfirmasi

| # | Pertanyaan | Status |
|---|------------|--------|
| 1 | Apakah design & warna sudah sesuai branding? | ⬜ |
| 2 | Harga buku masing-masing berapa? | ⬜ |
| 3 | Persentase komisi 5%/10% (non-peserta) dan 10%/15% (peserta) ok? | ⬜ |
| 4 | Buku fisik atau digital? Perlu integrasi ongkir? | ⬜ |
| 5 | Minimum withdrawal Rp 100.000 ok? | ⬜ |
| 6 | Sudah punya akun Midtrans? | ⬜ |
| 7 | Admin Store & Admin Affiliate orang yang sama? | ⬜ |
| 8 | Setelah pembeli bayar kelas, proses selanjutnya apa? (WA/email/Zoom link?) | ⬜ |

### Opsional

| # | Pertanyaan | Status |
|---|------------|--------|
| 9 | Ada preferensi warna / branding lain? | ⬜ |
| 10 | Ada data customer/peserta lama yang perlu di-import? | ⬜ |
| 11 | Semua affiliator perlu di-approve admin, atau non-peserta bisa langsung aktif? | ⬜ |
| 12 | Perlu multi-level (downline) di versi pertama? | ⬜ |

---

## 📅 Timeline Setelah Approval

Jika Mas Firman approve prototype ini, berikut estimasi development:

| Fase | Durasi | Deskripsi |
|------|--------|-----------|
| **Fase 1–5** | 2–3 minggu | Store App (Laravel): Homepage, katalog, cart, checkout, Midtrans, admin, webhook sender |
| **Fase 6–12** | 3–4 minggu | Affiliate App (Laravel): Landing, registrasi, admin, dashboard, referral, webhook receiver, withdrawal |
| **Fase 13–14** | 1 minggu | Email notifikasi, polish, responsive, testing |
| **Total** | ~6–8 minggu | Full development kedua aplikasi |
