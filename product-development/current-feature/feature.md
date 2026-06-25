# Feature: Sales Reports + Refund Webhook Integration

> **Source:** Audit Report `docs/audit/project-plan-validation-M1-M4.md`
> **Status:** Proposed — 2 gaps identified from M1-M4 compliance audit
> **Priority:** P1 (Medium)

## Problem

Audit menyeluruh terhadap project plan M1-M4 menemukan **2 gap** yang harus ditutup:

### Gap 1: Laporan Penjualan (Sales Reports) — M2 MISSING

Plan M2 (`project-plan-30days.md` line 169-174) menyebutkan admin harus bisa:
- Melihat laporan penjualan (revenue, orders, charts)
- Filter by date range, product, status
- Export data

**Realitas:** Tidak ada `ReportController`, tidak ada `reports/` views, tidak ada route. Dashboard admin hanya menampilkan stats ringkas (total revenue, orders today, pending), bukan laporan penjualan yang bisa difilter dan di-export.

**Impact:** Admin tidak bisa melihat tren penjualan, tidak bisa identify produk terlaris, tidak bisa export data untuk accounting/pajak.

### Gap 2: order-refunded Webhook Dispatcher — M4 MISSING

Plan M4 (`docs_dev/plans/2026-06-18-m4-webhook-referral.md` Phase 4) menyebutkan:
- Store harus emit `order-refunded` webhook ke Affiliate saat order di-refund
- Affiliate receiver `StoreWebhookController::handleOrderRefunded()` sudah ada & tested (7/7 pass)
- Tapi Store **tidak pernah mengirim** event ini

**Realitas:** `AdminOrderController.php` (324 lines) tidak ada method refund. `AffiliateWebhookClient` mendukung `order-refunded` (line 19 docblock), tapi tidak ada `OrderRefunded` event class dan tidak ada `DispatchAffiliateOrderRefunded` listener.

**Impact:** Saat order di-refund, komisi affiliator tidak otomatis ter-cancel. Harus manual intervention di Affiliate admin panel. Ini bisa cause payout ke affiliator untuk order yang sudah di-refund (financial loss).

## Proposed Solution

### Feature 1: Sales Reports Module

Tambahkan module Laporan Penjualan di admin panel Store:

- **Route:** `/admin/reports` (index, revenue, products, export)
- **Controller:** `Admin/ReportController.php`
- **Views:** `admin/reports/index.blade.php`, `admin/reports/revenue.blade.php`, `admin/reports/products.blade.php`
- **Charts:** ApexCharts (sudah ada di admin) — daily/monthly revenue trend, top products
- **Filter:** Date range, product type (book/course), order status
- **Export:** CSV download

### Feature 2: Refund Webhook Integration

Tutup glue M4 antara Store → Affiliate:

- **Event:** `App\Events\OrderRefunded` — fired saat admin refund order
- **Listener:** `App\Listeners\DispatchAffiliateOrderRefunded` — calls `AffiliateWebhookClient->dispatch('order-refunded', $payload)`
- **Admin Action:** `AdminOrderController@refund()` — transition order ke `refunded` + fire event
- **Payload:** store_order_id, ref_code, buyer_name, order_total, refunded_at, reason

## Scope

### In Scope
- Sales Reports: 3 pages (overview, revenue detail, product detail) + CSV export
- Refund Webhook: event + listener + admin refund action + view button

### Out of Scope
- PDF export (CSV dulu)
- Scheduled email reports (defer)
- Automatic refund via payment gateway (manual admin decision)
- Refund partial (full refund only untuk M4 closure)

## Success Criteria

1. Admin bisa akses `/admin/reports` dan melihat revenue chart 30 hari terakhir
2. Admin bisa filter laporan by date range dan product type
3. Admin bisa export laporan ke CSV
4. Admin bisa refund order dari order detail page
5. Saat order di-refund, webhook `order-refunded` terkirim ke Affiliate
6. Affiliate receiver cancel commission (cooling/available) — already tested
7. All new tests pass, no regression in existing 410 store tests (non-shipping)

## Dependencies

- ApexCharts (sudah terinstall di admin)
- `AffiliateWebhookClient` (sudah ada, commit `be48955`)
- `StoreWebhookController::handleOrderRefunded()` (sudah ada, commit `f19ae22`)
- Laravel Excel/CSV (native `fputcsv` — no new dependency)
