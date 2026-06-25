# PRD — Gap Closure: Laporan Penjualan & Order-Refunded Webhook

> **Status:** Draft · **Author:** Ultrawork Audit Engine · **Created:** 26 Juni 2026
> **Source:** Audit Report `docs/audit/project-plan-validation-M1-M4.md`

---

## 1. Summary

PRD ini menutup 2 gap yang ditemukan saat audit compliance M1-M4: (1) **Laporan Penjualan** untuk admin Store, dan (2) **order-refunded webhook dispatcher** agar refund order otomatis membatalkan komisi affiliator. Kedua fitur ini melengkapi deliverable plan M2 dan M4 yang belum terimplementasi.

---

## 2. Contacts

| Nama | Role | Komentar |
|------|------|----------|
| Firman Pratama | Klien / Product Owner | Penyetor requirement akhir |
| Rezvi | Lead MC | Coordinator antara dev & klien |
| Naufalix | Developer | Implementasi teknis |
| AI Agent (OpenCode) | Engineer | Eksekusi kode + testing |

---

## 3. Background

Audit menyeluruh terhadap project plan 30-day MasFirmanPratama menemukan **94% compliance** (49/52 deliverable PASS). Dua gap tersisa:

1. **Laporan Penjualan (M2)** — Plan menyebutkan admin bisa melihat "Laporan Penjualan" (revenue summary, chart 30 hari, export). Dashboard admin saat ini hanya menampilkan widget statis (revenue hari ini, orders pending). Tidak ada halaman laporan terpisah dengan filter tanggal, breakdown per produk, atau export.

2. **order-refunded dispatcher (M4)** — Saat admin refund order di Store, Affiliate system **tidak diberi tahu**. `AffiliateWebhookClient` sudah support event `order-refunded` (docblock line 19), receiver `StoreWebhookController::handleOrderRefunded()` sudah ada & tested (7/7 pass), tapi Store tidak pernah emit event tersebut. Akibatnya komisi affiliator yang seharusnya dibatalkan tetap aktif.

**Why now?** Kedua gap ini adalah deliverable plan yang missed. Menutupnya membawa project ke 100% compliance dan mencegah masalah finansial (komisi dibayar untuk order yang di-refund).

---

## 4. Objective

### Objective 1: Laporan Penjualan
Memberi admin kemampuan melihat tren penjualan, breakdown per produk/kelas, dan export data untuk accounting.

**Key Results (SMART):**
- KR1: Admin dapat melihat revenue summary dengan filter rentang tanggal (dari-to)
- KR2: Chart penjualan harian/mingguan/bulalan tampil dalam 1 detik (<1000ms)
- KR3: Breakdown top 5 produk/kelas by revenue dan quantity terjual
- KR4: Export laporan ke CSV dengan 1 klik
- KR5: 100% test coverage untuk ReportController (target 15+ test)

### Objective 2: Order-Refunded Webhook
Membuat Store otomatis mengirim webhook `order-refunded` ke Affiliate saat admin refund order, sehingga komisi affiliator otomatis dibatalkan.

**Key Results (SMART):**
- KR1: `OrderRefunded` event class dibuat dan di-fire saat admin set status order ke `refunded`
- KR2: `DispatchAffiliateOrderRefunded` listener mengirim webhook via `AffiliateWebhookClient`
- KR3: Affiliate receiver `handleOrderRefunded()` sudah tested — verifikasi end-to-end pass
- KR4: 0 komisi aktif untuk order yang di-refund (integration test proof)
- KR5: Webhook log tercatat di tabel `webhook_logs` Store

---

## 5. Market Segment(s)

### Segment 1: Admin Store (Internal)
- **Job:** Memantau performa penjualan dan membuat laporan untuk klien
- **Pain saat ini:** Harus export manual dari database atau hitung dari order list
- **Constraint:** Harus usable di mobile (admin akses via HP)

### Segment 2: Admin Affiliate (Internal)
- **Job:** Memastikan komisi hanya dibayar untuk order yang tidak di-refund
- **Pain saat ini:** Harus manual cek setiap refund lalu batalkan komisi satu-satu
- **Constraint:** Proses harus otomatis, tidak ada lag >5 menit

### Segment 3: Affiliator
- **Job:** Menerima komisi yang fair dan akurat
- **Pain saat ini:** Risk of overpayment jika order di-refund tapi komisi tetap aktif
- **Constraint:** Tidak boleh melihat info buyer berlebihan (privacy)

---

## 6. Value Proposition(s)

| Job to be Done | Pain Eliminated | Gain Created |
|----------------|-----------------|--------------|
| "Saya ingin melihat tren penjualan" | Tidak perlu export manual / query DB | Chart interaktif + filter tanggal + export CSV |
| "Saya ingin pastikan komisi akurat" | Tidak perlu cek manual refund vs komisi | Otomatis cancel komisi saat order refund |
| "Saya ingin laporan untuk accounting" | Tidak ada data terstruktur siap pakai | CSV export dengan breakdown per produk |

**Competitive advantage:** Sistem affiliate dengan webhook real-time dan cooling period 7 hari — lebih sophisticated dari platform affiliate umum.

---

## 7. Solution

### 7.1 UX / Wireframes

#### Laporan Penjualan
```
┌─────────────────────────────────────────────────┐
│  Admin → Laporan Penjualan                       │
├─────────────────────────────────────────────────┤
│  [Dari: 2026-06-01] [Sampai: 2026-06-30] [Filter]│
│                                                   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐         │
│  │ Revenue  │ │ Orders   │ │ Avg Order│         │
│  │ Rp 12.5M │ │ 87       │ │ Rp 143K  │         │
│  └──────────┘ └──────────┘ └──────────┘         │
│                                                   │
│  📈 Chart Penjualan Harian (ApexCharts line)     │
│  ┌───────────────────────────────────────┐       │
│  │     /\      /\                        │       │
│  │   /    \  /    \    /\                │       │
│  │  /      \/      \__/  \___            │       │
│  └───────────────────────────────────────┘       │
│                                                   │
│  🏆 Top 5 Produk by Revenue                      │
│  ┌────────────────────┬──────┬─────────┬──────┐  │
│  │ Produk             │ Qty  │ Revenue │ %    │  │
│  │ Kelas AMC Reguler  │ 12   │ Rp 6M   │ 48%  │  │
│  │ Buku Mind Power    │ 25   │ Rp 3.5M │ 28%  │  │
│  │ ...                │      │         │      │  │
│  └────────────────────┴──────┴─────────┴──────┘  │
│                                                   │
│  [Export CSV]                                     │
└─────────────────────────────────────────────────┘
```

#### Order-Refunded Flow
```
Admin Order Detail → Set status: "refunded"
  → Fire OrderRefunded($order) event
    → Listener: DispatchAffiliateOrderRefunded
      → AffiliateWebhookClient->dispatch('order-refunded', $payload)
        → POST to Affiliate /webhook/store
          → StoreWebhookController::handleOrderRefunded()
            → Cancel commission (cooling/available → cancelled)
            → Log to webhook_logs
```

### 7.2 Key Features

#### Feature A: Laporan Penjualan (Store Admin)

| # | Feature | Priority | Description |
|---|---------|----------|-------------|
| A1 | ReportController | P0 | Controller dengan method `index()` — filter tanggal, summary stats, chart data, top products |
| A2 | Date range filter | P0 | Input dari/sampai tanggal, default 30 hari terakhir |
| A3 | Summary cards | P0 | Total revenue, total orders, average order value, total qty sold |
| A4 | Chart penjualan | P0 | ApexCharts line chart, revenue per hari |
| A5 | Top 5 produk | P1 | Breakdown by revenue + qty, sortable |
| A6 | Export CSV | P1 | Download laporan sebagai CSV (produk, qty, revenue, date range) |
| A7 | Breakdown per status | P2 | Filter by order status (paid, completed, refunded) |
| A8 | Sidebar nav link | P0 | Tambah menu "Laporan" di admin sidebar |

#### Feature B: Order-Refunded Webhook Dispatcher (Store → Affiliate)

| # | Feature | Priority | Description |
|---|---------|----------|-------------|
| B1 | OrderRefunded event | P0 | Event class `App\Events\OrderRefunded` dengan `$order` payload |
| B2 | Refund action di AdminOrderController | P0 | Method `refund(Request, Order)` — set status `refunded`, fire event |
| B3 | DispatchAffiliateOrderRefunded listener | P0 | Listen `OrderRefunded`, call `AffiliateWebhookClient->dispatch('order-refunded', ...)` |
| B4 | Webhook payload | P0 | `{store_order_id, ref_code, order_total, refunded_at, reason}` |
| B5 | Refund button UI | P0 | Button di order detail (hanya muncul jika status `paid`/`completed`) |
| B6 | Confirmation modal | P1 | Alpine modal "Yakin refund order ini? Komisi affiliator akan dibatalkan." |
| B7 | Webhook log | P1 | Log ke `webhook_logs` table dengan payload + response status |

### 7.3 Technology (high-level)

| Layer | Technology | Notes |
|-------|------------|-------|
| Backend | Laravel 11 (Store app) | `ReportController`, `OrderRefunded` event, listener |
| Frontend | Blade + Tailwind + Alpine.js + ApexCharts | Chart pakai ApexCharts (sudah ada di admin) |
| Database | MySQL `store_db` | Query aggregation di ReportController (no new tables) |
| Webhook | HMAC-SHA256 via `AffiliateWebhookClient` | Sudah ada, tinggal wire event baru |
| Testing | PHPUnit Feature Tests | TDD: RED → GREEN untuk setiap feature |

---

## 8. Release

### Phase 1 — MVP (Sprint 1)
- A1: ReportController + route
- A2: Date range filter
- A3: Summary cards
- A4: Chart penjualan harian
- A8: Sidebar nav link
- B1: OrderRefunded event
- B2: Refund action di AdminOrderController
- B3: DispatchAffiliateOrderRefunded listener
- B5: Refund button UI

### Phase 2 — Enhancement (Sprint 2)
- A5: Top 5 produk breakdown
- A6: Export CSV
- B6: Confirmation modal
- B7: Webhook log table view

### Phase 3 — Future (Defer)
- A7: Breakdown per status
- Monthly recurring report email
- Affiliate commission dispute system

---

## Assumptions & Risks

| # | Assumption / Risk | Mitigation |
|---|-------------------|------------|
| 1 | ApexCharts sudah terinstall di admin | Verify di `admin.js` pipeline — confirmed existing |
| 2 | AffiliateWebhookClient menerima event string apapun | Confirmed: `dispatch(string $event, array $payload)` |
| 3 | Admin ada akses refund order | Tambah policy check `auth:admin` |
| 4 | Refund hanya untuk order `paid`/`completed` | Validasi di controller + UI guard |

---

## Acceptance Criteria

### Laporan Penjualan
- [ ] Admin dapat akses `/admin/reports` dari sidebar
- [ ] Filter tanggal dari-sampai berfungsi
- [ ] Summary cards menampilkan angka benar (verified vs DB)
- [ ] Chart render within 1 detik
- [ ] 15+ feature tests pass (ReportControllerTest)

### Order-Refunded Webhook
- [ ] Admin bisa set order status ke `refunded` dari order detail
- [ ] `OrderRefunded` event di-fire
- [ ] Webhook `order-refunded` terkirim ke Affiliate
- [ ] Affiliate receiver cancel commission (integration test)
- [ ] Webhook log tercatat di Store
- [ ] 10+ feature tests pass (OrderRefundTest + WebhookDispatchTest)

---

*PRD created using create-prd skill (phuryn/pm-skills) — 8-section template.*
