# Project Plan — MasFirmanPratama.com
## 30-Day Development Timeline (13 Mei – 11 Juni 2026)

---

## Executive Summary

Membangun ekosistem bisnis online Mas Firman Pratama dalam 30 hari kerja:
- **Online Store** (`masfirmanpratama.com`) — etalase produk, checkout manual, upload bukti bayar
- **Admin Panel** (unified) — kontrol store + affiliate management dalam 1 dashboard
- **Affiliate System** (`affiliate.masfirmanpratama.com`) — landing, dashboard affiliator, komisi, withdrawal

**Prioritas Build Order:**
1. Online Store + Admin Panel (Week 1–3)
2. Affiliate System (Week 3–4)
3. Integrasi webhook Store ↔ Affiliate + QA (Week 4)

---

## Tech Stack (Final)

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11 (2 app terpisah, shared hosting-friendly) |
| Frontend Store | Blade + Tailwind CSS v3 + Alpine.js |
| Frontend Admin | Blade + Tailwind + Alpine.js (unified panel) |
| Database | MySQL (2 DB: `store_db`, `affiliate_db`) |
| Payment | Manual transfer + upload bukti bayar (lunas/cicilan) |
| Ongkir | Agenwebsite.com API (buku fisik) |
| Notifikasi | WhatsApp API (Fonnte/WA Gateway) ke admin |
| Webhook | HMAC-SHA256 (Store → Affiliate) |
| Hosting | Shared/VPS (Laragon lokal dev) |

---

## Arsitektur Admin Panel (Unified)

Admin Panel mengelola **KEDUA sistem** dari 1 login:

```
Admin Panel (admin.masfirmanpratama.com ATAU masfirmanpratama.com/admin)
├── Store Management
│   ├── Dashboard (revenue, orders today, pending confirmations)
│   ├── Produk (CRUD kelas & buku)
│   ├── Pesanan (list, detail, verifikasi bukti bayar, track cicilan)
│   ├── Pengiriman (input resi buku fisik)
│   └── Laporan Penjualan
├── Affiliate Management
│   ├── Dashboard Afiliasi (total affiliator, komisi, revenue via referral)
│   ├── Affiliator (list, approve/reject, verifikasi alumni, suspend)
│   ├── Komisi (list, approve, cancel)
│   ├── Withdrawal (list, process, complete, reject + upload bukti TF)
│   ├── Produk Afiliasi (set persentase komisi per produk)
│   ├── Materi Marketing (CRUD banner/video/copy)
│   ├── Leaderboard
│   └── Event Gamifikasi (CRUD event, set periode/target/reward)
├── Settings
│   ├── Info Toko & Rekening
│   ├── WhatsApp Gateway Config
│   ├── Webhook Secret
│   └── Ongkir API Config
└── Webhook Logs
```

---

## Payment Flow (Manual — Lunas & Cicilan)

### Flow Lunas:
```
Customer checkout → Pilih "Bayar Lunas" → Dapat info rekening + nominal
→ Transfer → Upload bukti bayar → WA notif ke admin
→ Admin verifikasi → Order PAID → Proses kirim (buku) / akses (kelas)
```

### Flow Cicilan:
```
Customer checkout → Pilih "Cicilan" → Sistem generate jadwal cicilan
→ Bayar DP (cicilan 1) → Upload bukti → Admin verifikasi → Order PARTIAL_PAID
→ Sistem reminder cicilan berikutnya (WA ke customer)
→ Setiap cicilan: upload bukti → admin verifikasi
→ Lunas semua → Order PAID → Proses kirim/akses
```

### Status Order:
`unpaid` → `waiting_confirmation` → `paid` / `partial_paid` → `processing` → `completed` / `refunded`

---

## Database Tambahan (vs spec lama)

### Tabel baru di `store_db`:

```sql
-- Track pembayaran per cicilan
order_payments:
  id, order_id, payment_number (cicilan ke-N), amount, 
  payment_proof (file upload), status (pending/confirmed/rejected),
  confirmed_by (admin_id), confirmed_at, due_date, notes, timestamps

-- Skema cicilan per produk/order
installment_schemes:
  id, name (e.g. "3x Cicilan"), total_installments, 
  down_payment_pct, is_active, timestamps

-- WhatsApp notification log
wa_notifications:
  id, phone, message, type (admin_alert/customer_reminder), 
  status (sent/failed), sent_at, timestamps
```

### Tabel baru di `affiliate_db`:

```sql
-- Event gamifikasi
gamification_events:
  id, title, description, start_date, end_date, 
  target_type (omset/sales_count), target_value,
  reward_title, reward_description, reward_image,
  is_active, timestamps

-- Peserta event
event_participants:
  id, event_id, user_id, current_progress, 
  is_achieved, achieved_at, timestamps
```

---

## 30-Day Timeline

### ═══ WEEK 1 (13–18 Mei) — Foundation & Store Frontend ═══

#### Day 1–2 (Sel–Rab, 13–14 Mei): Project Setup
- [ ] Scaffold Laravel 11 project Store (`d:\laravel\store`)
- [ ] Scaffold Laravel 11 project Affiliate (`d:\laravel\affiliate`)
- [ ] Setup database migrations Store (products, orders, order_items, order_payments, installment_schemes, admins, settings, wa_notifications, webhook_logs)
- [ ] Setup Tailwind CSS + Alpine.js build pipeline
- [ ] Create base Blade layouts (store public + admin)
- [ ] Seed data: produk kelas & buku, admin user, installment schemes
- [ ] Setup Git repo + branching strategy

#### Day 3–4 (Kam–Jum, 15–16 Mei): Store Public Pages
- [ ] Homepage (hero, benefit AMC, katalog produk, pricing kelas, testimoni, CTA)
- [ ] Katalog produk (grid view, filter kelas/buku)
- [ ] Detail produk — Kelas (deskripsi, materi 20 poin, jadwal, benefit, testimoni)
- [ ] Detail produk — Buku (deskripsi, preview, harga)
- [ ] Responsive mobile polish semua halaman

#### Day 5–6 (Sab–Min, 17–18 Mei): Cart & Checkout
- [ ] Cart (session-based, tanpa login, add/update/remove)
- [ ] Checkout page (form data diri: nama, email, HP, alamat untuk buku)
- [ ] Integrasi Agenwebsite.com API — hitung ongkir otomatis (buku fisik)
- [ ] Pilihan metode bayar: Lunas vs Cicilan (pilih skema)
- [ ] Halaman konfirmasi order + info rekening tujuan
- [ ] Upload bukti pembayaran (halaman terpisah via link/order number)
- [ ] Halaman status order (tracking tanpa login via order number)

---

### ═══ WEEK 2 (19–25 Mei) — Admin Panel Store ═══

#### Day 7–8 (Sen–Sel, 19–20 Mei): Admin Auth & Dashboard
- [ ] Admin login/logout (session-based)
- [ ] Admin dashboard: total revenue, orders hari ini, pending confirmations, chart penjualan 30 hari
- [ ] Widget: pesanan butuh konfirmasi, cicilan jatuh tempo hari ini

#### Day 9–10 (Rab–Kam, 21–22 Mei): Admin Produk & Pesanan
- [ ] CRUD Produk (kelas & buku) — nama, slug, harga, harga coret, gambar, gallery, stok, kategori, deskripsi, status
- [ ] List pesanan (filter status, search, pagination)
- [ ] Detail pesanan (info customer, items, payment history, timeline status)
- [ ] Verifikasi bukti bayar (approve/reject per payment)
- [ ] Track cicilan per order (progress bar, reminder button)
- [ ] Update status order manual

#### Day 11–12 (Jum–Sab, 23–24 Mei): Admin Pengiriman & Notifikasi
- [ ] Input resi pengiriman (buku fisik)
- [ ] Integrasi WhatsApp Gateway (Fonnte/WA API):
  - Notif ke admin: "Pesanan baru masuk!" / "Bukti bayar baru diupload!"
  - Notif ke customer: "Pembayaran dikonfirmasi" / "Resi pengiriman: XXX"
  - Reminder cicilan: "Cicilan ke-N jatuh tempo tanggal X"
- [ ] Laporan penjualan (filter tanggal, produk, status) + export CSV

#### Day 13 (Min, 25 Mei): Admin Settings & Polish
- [ ] Settings: info toko, rekening bank (untuk ditampilkan ke customer), WhatsApp API config
- [ ] Installment scheme management (CRUD skema cicilan)
- [ ] Bug fixes & UI polish admin panel

---

### ═══ WEEK 3 (26 Mei – 1 Jun) — Affiliate System ═══

#### Day 14–15 (Sen–Sel, 26–27 Mei): Affiliate DB & Auth
- [ ] Setup database migrations Affiliate (users, products, referral_links, referral_clicks, transactions, commissions, withdrawals, marketing_materials, notifications, settings, webhook_logs, activity_logs, gamification_events, event_participants)
- [ ] Seed data: produk afiliasi (mirror dari store + set komisi %), admin user
- [ ] Auth: register affiliator (form lengkap + upload KTP + pilih tipe)
- [ ] Register pending page (status review)
- [ ] Login/logout affiliator & admin

#### Day 16–17 (Rab–Kam, 28–29 Mei): Affiliate Landing & Dashboard
- [ ] Landing page program afiliasi (hero, benefit, kalkulator penghasilan, perbandingan tipe, cara kerja, FAQ, CTA)
- [ ] Dashboard Non-Peserta (4 stat cards, chart 30 hari, referral links, tabel transaksi, banner upgrade)
- [ ] Dashboard Peserta/Alumni (5 stats, dual chart, custom slug, QR code, leaderboard preview, materi marketing)

#### Day 18–19 (Jum–Sab, 30–31 Mei): Affiliator Features
- [ ] Referral Link Manager (generate link per produk, copy, QR code, performa per link)
- [ ] Halaman Komisi (saldo total/pending/aktif, cooling period 7 hari, filter status, tabel per invoice)
- [ ] Withdrawal (saldo siap tarik, form pengajuan, histori + status)
- [ ] Materi Marketing (list banner/video/copy, filter, download)
- [ ] Leaderboard (ranking, posisi user, info bonus per tier)
- [ ] Profile (edit data personal, rekening, password)

#### Day 20 (Min, 1 Jun): Admin Affiliate Panel
- [ ] Admin dashboard afiliasi (total affiliator, pending approval, total komisi, revenue via referral)
- [ ] Manage affiliator (list, detail, approve/reject/suspend, change type, verifikasi alumni)
- [ ] Manage komisi (list, approve batch, cancel)
- [ ] Manage withdrawal (list, process, complete + upload bukti TF, reject)
- [ ] Manage produk afiliasi (set komisi % per produk per tipe)
- [ ] Manage materi marketing (CRUD)

---

### ═══ WEEK 4 (2–11 Jun) — Integration, Gamifikasi & QA ═══

#### Day 21–22 (Sen–Sel, 2–3 Jun): Webhook Integration
- [ ] Store: WebhookSenderService — kirim `order-paid` & `order-refunded` ke Affiliate
- [ ] Affiliate: WebhookController — terima, validasi HMAC, proses komisi
- [ ] Referral tracking: `/ref/{code}` → catat klik → redirect ke Store + `?ref=CODE`
- [ ] Store: baca `?ref=` → set cookie 30 hari → attach ke order saat checkout
- [ ] API validate referral: Store panggil Affiliate untuk tampilkan "Direferensikan oleh: X"
- [ ] Webhook logs (kedua sisi) + retry mechanism

#### Day 23–24 (Rab–Kam, 4–5 Jun): Gamifikasi & Event
- [ ] Admin: CRUD Event Gamifikasi (judul, periode, target omset, reward Umroh/Mobil/dll)
- [ ] Sistem tracking progress affiliator per event (otomatis dari komisi)
- [ ] Leaderboard per event (real-time ranking)
- [ ] Notifikasi achievement (target tercapai)
- [ ] Display event aktif di dashboard affiliator

#### Day 25–26 (Jum–Sab, 6–7 Jun): Email & Notifikasi Lengkap
- [ ] Email templates: konfirmasi order, pembayaran diterima, resi pengiriman
- [ ] WhatsApp notif lengkap (admin + customer + affiliator)
- [ ] Notification center affiliator (in-app)
- [ ] Reminder otomatis cicilan (scheduled command)

#### Day 27–28 (Min–Sen, 8–9 Jun): Testing & Bug Fixes
- [ ] End-to-end test: customer journey (browse → checkout → bayar → konfirmasi)
- [ ] End-to-end test: affiliator journey (daftar → approve → share link → dapat komisi → withdraw)
- [ ] End-to-end test: admin journey (kelola produk → verifikasi bayar → approve affiliator → approve withdrawal)
- [ ] Webhook test: order paid → komisi muncul di affiliate
- [ ] Security audit: HMAC validation, CSRF, input sanitization, file upload validation
- [ ] Performance: lazy loading images, pagination, query optimization

#### Day 29–30 (Sel–Rab, 10–11 Jun): Deployment & Launch Prep
- [ ] Setup hosting/VPS (2 domain: main + subdomain affiliate)
- [ ] Configure production environment (.env, DB, queue, cron)
- [ ] SSL certificates (both domains)
- [ ] Deploy Store app
- [ ] Deploy Affiliate app
- [ ] Smoke test production
- [ ] Seed production data (produk real, admin account)
- [ ] Handover documentation ke tim Mas Firman
- [ ] Training session: cara pakai admin panel

---

## Milestones & Deliverables

| Milestone | Target Date | Deliverable |
|-----------|-------------|-------------|
| M1: Store Live (local) | 18 Mei (Day 6) | Customer bisa browse + checkout + upload bukti bayar |
| M2: Admin Panel Store | 25 Mei (Day 13) | Admin bisa kelola produk, verifikasi bayar, input resi |
| M3: Affiliate System | 1 Jun (Day 20) | Affiliator bisa daftar, login, generate link, lihat komisi |
| M4: Integration Done | 5 Jun (Day 24) | Webhook jalan, referral tracking works, gamifikasi aktif |
| M5: Production Launch | 11 Jun (Day 30) | Kedua app live di production |

---

## Risiko & Mitigasi

| Risiko | Impact | Mitigasi |
|--------|--------|----------|
| Agenwebsite.com API tidak stabil/dokumentasi kurang | Ongkir tidak jalan | Fallback: admin input ongkir manual per order |
| WhatsApp Gateway rate limit / banned | Notif tidak sampai | Fallback: email + in-app notification |
| Scope creep fitur gamifikasi | Timeline molor | MVP: 1 event type dulu, extend later |
| Harga buku belum final | Seed data salah | Pakai placeholder, admin bisa edit kapan saja |
| Design conflict (Tailwind vs Bootstrap) | Inkonsistensi UI | Keputusan: pakai Tailwind v3 (sesuai DESIGN.md) untuk semua |

---

## Keputusan yang Perlu Dikonfirmasi

1. **Domain admin**: `admin.masfirmanpratama.com` (subdomain) ATAU `masfirmanpratama.com/admin` (prefix route)?
2. **WhatsApp Gateway**: Fonnte / Wablas / lainnya? (butuh API key)
3. **Agenwebsite.com**: sudah punya akun + API key?
4. **Harga buku final**: kapan bisa dikonfirmasi?
5. **Skema cicilan**: berapa kali cicil? (2x, 3x, 6x?) — atau admin set bebas per produk?
6. **Rekening tujuan**: BCA/Mandiri/BRI? (untuk ditampilkan di checkout)
7. **Hosting**: shared hosting / VPS? Spek?
8. **Design final**: Tailwind dark glassmorphism (DESIGN.md) untuk Store, lalu Affiliate pakai style yang sama atau beda?

---

## Notes

- Midtrans **DIHAPUS** dari seluruh spec. Diganti sistem manual.
- Privacy First: affiliator hanya lihat nama pembeli di histori konversi.
- Admin Panel **UNIFIED** — 1 login untuk kontrol Store + Affiliate.
- Gamifikasi event bisa di-CRUD admin tanpa coding ulang.
- Cooling period komisi: 7 hari sebelum status `approved` (anti-fraud).
