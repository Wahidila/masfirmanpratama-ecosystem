# Implementation Plan — Audit Gap Remediation

> **Sprint:** Post-Audit Remediation · Started: 2026-06-26
> **Base branch:** `main`
> **Working branch:** `feat/audit-remediation`
> **Source:** `product-development/current-feature/PRD.md`
> **Audit report:** `docs/audit/project-plan-validation-M1-M4.md`

---

## Overview

4 work streams dari audit M1-M4, dikelompokkan berdasarkan prioritas dan dependensi.

```
Stream A: Laporan Penjualan (M2 gap)        ──→ independent
Stream B: Order-Refunded Dispatcher (M4 gap) ──→ independent
Stream C: Migration Bug Fixes (setup gap)    ──→ independent, quick
Stream D: Shipping Test Environment (M2 env) ──→ independent, environmental
```

**Total: 4 streams, 20 tasks, 0 blocking dependencies antar stream**

---

## Stream A — Laporan Penjualan (Medium, ~6h)

> **Goal:** Admin bisa lihat revenue summary, chart harian/bulanan, top produk, dan export CSV
> **Gap source:** Audit M2 #14 — plan menyebutkan "Laporan Penjualan" tapi tidak diimplementasikan

### Architecture

```
store/app/Http/Controllers/Admin/ReportController.php
store/app/Services/Reporting/SalesReportService.php
store/resources/views/admin/reports/
  ├── index.blade.php          (dashboard with date range picker)
  ├── _revenue_chart.blade.php (ApexCharts line chart)
  ├── _top_products.blade.php  (table top 10 produk)
  ├── _order_status.blade.php  (pie chart status breakdown)
  └── _payment_summary.blade.php (verified vs pending totals)
store/routes/web.php           (add 2 routes: reports.index, reports.export)
store/app/Http/Requests/ReportFilterRequest.php (date range validation)
```

### Task Breakdown

#### A1: ReportFilterRequest — date range validation
- **File:** `store/app/Http/Requests/ReportFilterRequest.php`
- **Action:** Create form request with `date_from`, `date_to` (nullable, date format, date_to >= date_from)
- **Verify:** Unit test `ReportFilterRequestTest` — valid range pass, invalid range 422

#### A2: SalesReportService — business logic
- **File:** `store/app/Services/Reporting/SalesReportService.php`
- **Action:** Create service class with methods:
  - `revenueSummary(Carbon $from, Carbon $to): array` — total revenue, avg order value, order count
  - `dailyRevenue(Carbon $from, Carbon $to): Collection` — date + revenue pairs for chart
  - `monthlyRevenue(Carbon $from, Carbon $to): Collection` — grouped by month
  - `topProducts(Carbon $from, Carbon $to, int $limit = 10): Collection` — by revenue
  - `statusBreakdown(Carbon $from, Carbon $to): array` — count per status
  - `paymentSummary(Carbon $from, Carbon $to): array` — verified vs pending vs rejected totals
- **Verify:** Unit test `SalesReportServiceTest` — 6 test methods dengan factory orders

#### A3: ReportController — controller
- **File:** `store/app/Http/Controllers/Admin/ReportController.php`
- **Action:** Create controller with:
  - `index(ReportFilterRequest $request): View` — render dashboard with data
  - `export(ReportFilterRequest $request): StreamedResponse` — CSV export
- **Verify:** Feature test `AdminReportTest` — auth required, date range filter, CSV download

#### A4: Routes — register 2 routes
- **File:** `store/routes/web.php`
- **Action:** Add inside `admin` middleware group:
  ```php
  Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
  Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
  ```
- **Verify:** `php artisan route:list --name=reports` shows 2 routes

#### A5: Views — 5 Blade files
- **Files:** `store/resources/views/admin/reports/*.blade.php`
- **Action:**
  - `index.blade.php` — date range picker + 4 widget sections
  - `_revenue_chart.blade.php` — ApexCharts line chart (daily revenue)
  - `_top_products.blade.php` — table top 10 produk by revenue
  - `_order_status.blade.php` — ApexCharts pie chart status breakdown
  - `_payment_summary.blade.php` — verified/pending/rejected totals
- **Verify:** Manual QA — buka `http://localhost:8000/admin/reports`, verify chart render

#### A6: Sidebar nav link
- **File:** `store/app/Helpers/MenuHelper.php` (or sidebar blade component)
- **Action:** Add "Laporan" menu item with chart icon, link to `admin.reports.index`
- **Verify:** Manual QA — sidebar shows "Laporan" link, click navigates to reports page

#### A7: Feature tests
- **File:** `store/tests/Feature/AdminReportTest.php`
- **Action:** Write tests:
  - `reports_index_requires_auth`
  - `reports_index_loads_with_default_date_range`
  - `reports_index_filters_by_date_range`
  - `reports_export_downloads_csv`
  - `reports_export_respects_date_range`
  - `reports_shows_zero_state_when_no_orders`
- **Verify:** `php artisan test --filter=AdminReportTest` — 6/6 pass

---

## Stream B — Order-Refunded Dispatcher (Medium, ~3h)

> **Goal:** Saat admin refund order, Store emit webhook `order-refunded` ke Affiliate, commission auto-cancelled
> **Gap source:** Audit M4 #17 — receiver ready, emitter missing

### Architecture

```
store/app/Events/OrderRefunded.php                    (new event)
store/app/Listeners/DispatchAffiliateOrderRefunded.php (new listener)
store/app/Http/Controllers/Admin/OrderController.php   (add refund method)
store/routes/web.php                                   (add refund route)
store/tests/Feature/OrderRefundTest.php                (new test)
```

### Task Breakdown

#### B1: OrderRefunded event
- **File:** `store/app/Events/OrderRefunded.php`
- **Action:** Create event class (similar to `PaymentVerified`), takes `Order $order`
- **Verify:** Event class exists, `php artisan event:list` shows it registered

#### B2: DispatchAffiliateOrderRefunded listener
- **File:** `store/app/Listeners/DispatchAffiliateOrderRefunded.php`
- **Action:** Create listener that:
  - Checks order has `ref_code` (skip if no referral)
  - Calls `AffiliateWebhookClient->dispatch('order-refunded', payload)`
  - Payload: `store_order_id`, `ref_code`, `order_total`, `refunded_at`
  - Graceful skip if webhook URL/secret empty (same pattern as order-paid)
- **Verify:** Unit test — listener calls client with correct payload

#### B3: Wire event-listener binding
- **File:** `store/app/Listeners/` auto-discovery or manual registration
- **Action:** Laravel 11 auto-discovers listeners. Ensure `OrderRefunded` event triggers `DispatchAffiliateOrderRefunded`
- **Verify:** `php artisan event:list` shows `OrderRefunded → DispatchAffiliateOrderRefunded`

#### B4: AdminOrderController@refund method
- **File:** `store/app/Http/Controllers/Admin/OrderController.php`
- **Action:** Add method:
  ```php
  public function refund(Request $request, Order $order): RedirectResponse
  {
      // Validate: only 'paid' or 'shipped' can be refunded
      // Transition status → 'refunded'
      // Fire OrderRefunded::dispatch($order->fresh())
      // Flash success message
  }
  ```
- **Verify:** Feature test — refund paid order → status refunded → event fired

#### B5: Route + UI button
- **File:** `store/routes/web.php` + `store/resources/views/admin/orders/show.blade.php`
- **Action:**
  - Add route: `Route::post('orders/{order}/refund', ...)->name('orders.refund')`
  - Add "Refund" button in order detail (only show when status is 'paid' or 'shipped')
  - Confirm dialog before submit
- **Verify:** Manual QA — order detail shows refund button, click → order refunded

#### B6: Feature tests
- **File:** `store/tests/Feature/OrderRefundTest.php`
- **Action:** Write tests:
  - `refund_paid_order_transitions_to_refunded`
  - `refund_shipped_order_transitions_to_refunded`
  - `refund_non_refundable_status_fails` (pending/partial_paid should 422)
  - `refund_fires_order_refunded_event`
  - `refund_dispatches_webhook_to_affiliate` (use Http::fake)
  - `refund_without_ref_code_skips_webhook`
  - `refund_requires_admin_auth`
- **Verify:** `php artisan test --filter=OrderRefundTest` — 7/7 pass

#### B7: Integration smoke test
- **Action:** Manual end-to-end test with both apps running:
  1. Store: admin approve payment (triggers order-paid webhook → commission created)
  2. Store: admin refund order (triggers order-refunded webhook)
  3. Affiliate: check commission status → should be `cancelled`
- **Verify:** Affiliate dashboard shows cancelled commission, webhook log shows 2 entries

---

## Stream C — Migration Bug Fixes (Quick, ~15min)

> **Goal:** Fix 3 migration bugs yang ditemukan saat setup lokal
> **Gap source:** Setup phase — migrations gagal di MySQL (works di SQLite)

### Task Breakdown

#### C1: Fix courses migration ordering
- **File:** Rename `store/database/migrations/2026_06_01_100000_create_courses_table.php`
  → `store/database/migrations/2026_05_19_092903b_create_courses_table.php`
- **Reason:** `order_items` (timestamp `092905`) FK references `courses`, but `courses` created at `100000` (later)
- **Verify:** `php artisan migrate:fresh --seed` pass without FK error

#### C2: Fix shipping meta timestamp collision
- **Files:**
  - Rename `2026_05_31_200000_add_shipping_meta_to_orders.php` → `2026_05_31_200000a_*.php`
  - Rename `2026_05_31_200000_add_fulfillment_columns_to_orders.php` → `2026_05_31_200000b_*.php`
- **Reason:** Same timestamp → non-deterministic execution order; `fulfillment_columns` references `shipping_etd` from `shipping_meta`
- **Verify:** `php artisan migrate:fresh --seed` pass without "Unknown column" error

#### C3: Fix affiliate index name length
- **File:** `affiliate/database/migrations/2026_06_17_011200_create_affiliate_event_participants_table.php`
- **Action:** Change `$table->unique(['affiliate_event_id', 'affiliator_id'])` to `$table->unique(['affiliate_event_id', 'affiliator_id'], 'event_participant_unique')`
- **Reason:** Auto-generated name `affiliate_event_participants_affiliate_event_id_affiliator_id_unique` exceeds MySQL 64-char limit
- **Verify:** `php artisan migrate:fresh --seed` pass without "Identifier name too long" error

#### C4: Commit all 3 fixes
- **Action:** `git add -A && git commit -m "fix(migrations): ordering, timestamp collision, index name length"`
- **Verify:** `git log --oneline -1` shows commit, `php artisan migrate:fresh --seed` clean on both apps

---

## Stream D — Shipping Test Environment (Environmental, ~1h)

> **Goal:** 28 shipping test failures → 0 dengan mock AgenwebsiteClient
> **Gap source:** Audit M2 — tests hit live API yang license-nya expired

### Task Breakdown

#### D1: Create test mock for AgenwebsiteClient
- **File:** `store/tests/TestCase.php` or `store/tests/Feature/Shipping/ShippingTestCase.php`
- **Action:** In test setup, bind a mock `AgenwebsiteClient` that returns canned responses:
  - `getRates()` → return fixture JSON (3 couriers: JNE, JNT, SiCepat)
  - `createShipment()` → return fixture AWB response
  - `getMasterData()` → return fixture couriers/services
- **Verify:** Shipping tests run without hitting live API

#### D2: Create fixture JSON files
- **Files:** `store/tests/Fixtures/agenwebsite_*.json`
  - `agenwebsite_rates_response.json` — sample /shipping/price response
  - `agenwebsite_awb_response.json` — sample create AWB response
  - `agenwebsite_couriers_response.json` — sample master data
  - `agenwebsite_tracking_response.json` — sample tracking response
- **Action:** Capture real API responses (from docs/dev/plans/2026-05-31-m-shipping.md) and save as fixtures
- **Verify:** Fixtures exist, valid JSON, match API response structure

#### D3: Update shipping tests to use mock
- **Files:** All `store/tests/Feature/Shipping/*Test.php` (12 files)
- **Action:** Replace `Http::fake()` calls with mock client binding, or ensure `Http::fake()` covers all API endpoints
- **Verify:** `php artisan test --filter=Shipping` — 0 failures, 0 errors

#### D4: Verify full test suite
- **Action:** Run `php artisan test` on both apps
- **Target:** Store: 438/438 pass · Affiliate: 68/68 pass
- **Verify:** Zero failures, zero errors across both apps

---

## Execution Order

```
Wave 1 (parallel, independent):
  ├── Stream C: Migration fixes (15min)     ──→ blocks nothing, unblocks fresh seed
  ├── Stream D: Shipping test mock (1h)     ──→ blocks nothing, fixes 28 test failures
  ├── Stream A: Laporan Penjualan (6h)      ──→ blocks nothing, adds new feature
  └── Stream B: Order-Refunded (3h)         ──→ blocks nothing, fixes M4 gap

Wave 2 (after Wave 1):
  └── Final verification: full test suite + manual QA both apps
```

**Semua 4 stream bisa berjalan paralel** — tidak ada dependensi antar stream.

---

## Verification Matrix

| Scenario | Stream | Verify By | Pass Condition |
|----------|--------|-----------|----------------|
| Admin buka /admin/reports | A | Playwright | 200 OK, chart render, data dari DB |
| Admin export CSV laporan | A | curl/Playwright | CSV download, date range respected |
| Admin refund paid order | B | Playwright | Status → refunded, commission cancelled |
| Webhook order-refunded | B | curl + DB check | Affiliate webhook_log entry, commission cancelled |
| migrate:fresh --seed store | C | bash | 0 errors, all 22 migrations + 6 seeders |
| migrate:fresh --seed affiliate | C | bash | 0 errors, all 21 migrations + 3 seeders |
| php artisan test (store) | D | bash | 438/438 pass, 0 failures |
| php artisan test (affiliate) | D | bash | 68/68 pass, 0 failures |

---

## Definition of Done

- [ ] Stream A: Laporan Penjualan — 7 tasks, 6 feature tests pass, manual QA
- [ ] Stream B: Order-Refunded — 7 tasks, 7 feature tests pass, integration smoke test
- [ ] Stream C: Migration fixes — 3 fixes committed, `migrate:fresh --seed` clean
- [ ] Stream D: Shipping tests — 0 failures, mock fixtures in place
- [ ] Full test suite: Store 438/438 + Affiliate 68/68 = 506/506 pass
- [ ] Audit compliance: 94% → 100%
- [ ] Update `docs/audit/project-plan-validation-M1-M4.md` dengan status FIXED
- [ ] Commit all changes with conventional commit messages
