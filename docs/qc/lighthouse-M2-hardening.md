# Lighthouse Re-Audit — M2-hardening (Production-Like)

**Task:** `t_ab94a31f` — M2-hardening QC-2: re-Lighthouse production-like (verify ≥90 + course detail unblock)
**Reviewer:** mc-review-qc
**Date:** 2026-05-27
**Branch reviewed:** `main` @ `863e6f0`
**Method:** Production-like nginx + php-fpm via Docker compose (port 3003), Lighthouse CLI mobile form-factor.
**Stack:** `docs/qc/M2-hardening/lighthouse/` (cloned dari M2 dengan rename container `mfp-m2-*` → `mfp-m2h-*`, port 3002 → 3003, network `m2net` → `m2hnet`).

---

## Scoreboard

### Mobile (production-like nginx, gzip + cache)

| Route | Perf | A11y | BP | SEO | LCP | CLS | TBT | Δ Perf vs M2 | Δ CLS vs M2 |
|---|---|---|---|---|---|---|---|---|---|
| home          | **87** | 96  | 100 | 100 | 3.62s | 0.037 | 194ms | **+22** ✅ | +0.005 ⚠️ |
| produk-list   | 98 | 95  | 100 | 100 | 1.83s | 0.039 | 135ms | -1 | flat |
| produk-buku   | **99** | 96  | 100 | 100 | 1.62s | 0.000 | 108ms | flat ✅ | flat ✅ |
| produk-kelas  | **74** | 100 | 100 | 100 | 3.60s | **0.228** | 254ms | **unblocked** ✅ | NEW ⚠️ |
| cart          | 92 | 100 | 100 | 100 | 1.41s | **0.000** | 344ms | -2 | **fixed** ✅ |
| checkout      | **78** | 100 | 100 | 100 | 1.40s | **0.197** | 497ms | **-15** ❌ | **regressed** ❌ |

### M2 baseline (untuk comparison)

| Route | Perf | CLS | LCP | TBT |
|---|---|---|---|---|
| home          | 65 | 0.032 | 5.18s | 620ms |
| produk-list   | 99 | 0.033 | 2.02s | 66ms  |
| produk-buku   | 99 | 0.000 | 1.76s | 60ms  |
| produk-kelas  | timeout | — | — | — |
| cart          | 94 | 0.129 | 1.40s | 132ms |
| checkout      | 93 | 0.131 | 1.41s | 162ms |

---

## Highlights

### ✅ Major wins

1. **home perf 65 → 87 (+22)** — H3 fix `<picture>` + webp untuk founder photo bener-bener landed. LCP 5.18s → 3.62s (-30%), TBT 620ms → 194ms (-69%). Founder JPEG 385KB sebelumnya = LCP killer.
2. **produk-kelas unblocked** — di M2 ini Lighthouse timeout total (lucide CDN sourced wrong + alpine loop bug). PR #7 (yang udah merged sebelum M2-hardening) fix-nya. Sekarang measurable: P 74, A11y 100, BP+SEO 100. Tinggal CLS yang bermasalah.
3. **cart CLS 0.129 → 0.000** — M4 `min-h-[420px]` cart state container reservation = textbook CLS fix.

### ⚠️ Regressions / Blockers

1. **checkout CLS 0.131 → 0.197** — M4 fix nge-reservasi error message inline (`min-h-[1.25rem]`), tapi dynamic shipping-method options + installment-scheme dropdown above footer **masih shift** saat Alpine hydrate. Single shift element: `<footer ...>` (full-page push-down 0.197). Score gak naik dari M2 — malah turun 15 poin dari 93 ke 78.
2. **produk-kelas CLS 0.228 (NEW)** — sebelumnya gak ke-measure (timeout). Shift element: `<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">` — main content grid populate by Alpine, no skeleton.
3. **home perf 87 < 90 target** — improvement masif tapi belum lewat acceptance threshold. `unused-javascript` audit nunjukin ~21KB unused, kemungkinan unlock 90.

---

## Acceptance Criteria Check (from `t_ab94a31f`)

| Criteria | Status | Note |
|---|---|---|
| All public routes Perf ≥ 90 mobile | ❌ | 4/6 pass (produk-list 98, produk-buku 99, cart 92, but home 87, produk-kelas 74, checkout 78) |
| Accessibility ≥ 95 | ✅ | All routes 95-100 |
| Best Practices ≥ 95 | ✅ | All 100 |
| SEO ≥ 95 | ✅ | All 100 |
| Course detail (`produk-kelas`) audit-able (no timeout) | ✅ | Measured 74/100 — unblocked from M2 timeout |
| CLS < 0.1 across routes | ❌ | checkout 0.197, produk-kelas 0.228 fail |

---

## Root Cause — Checkout CLS Regression

Single `<footer>` shift dengan score 0.197:

```html
<footer class="bg-slate-950 text-slate-300 pt-16 pb-10 border-t border-slate-800 mt-20">
```

Footer di-push down karena content above grow setelah Alpine hydrate. Suspect:

1. **`shipping-method options` block** — di-render lewat `<template x-for>` setelah cart items load via Alpine. Awalnya 0 height (no items), expand jadi N × ~80px per option.
2. **`installment-scheme block`** — `<div x-show="installment_required">` toggled saat scheme dipilih. Initial state `display:none`, jadi 0 height. Saat user (atau auto-select first scheme) trigger, expand jadi ~120-200px.
3. **Order-summary live-update** — `<aside x-data="cartSummary">` re-renders saat cart items hydrate dari sessionStorage. Initial render mungkin punya skeleton tapi items grow vertikal.

**Lighthouse hanya report 1 shift = 0.197** karena Lighthouse aggregate semua shift jadi single CLS metric. Real CLS = 0.197 dari kombinasi shift di atas yang push footer.

**Fix strategy (recommended):**

```blade
{{-- shipping options wrapper --}}
<div class="min-h-[180px]" x-show="!loading">
  <template x-for="...">...</template>
</div>

{{-- installment block wrapper --}}
<div class="min-h-[160px]" x-show="installment_required">
  ...
</div>

{{-- order summary skeleton --}}
<aside class="min-h-[400px]" x-data="...">
  ...
</aside>
```

Atau pakai `x-cloak` + CSS reservation:

```css
[x-cloak] { display: none !important; }
.checkout-section { contain: layout; min-block-size: 200px; }
```

---

## Root Cause — Produk-Kelas CLS

Single shift di main content grid:

```html
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
```

CLS 0.228 — grid populate dari Blade SSR data tapi inner cards (image + meta + CTA) hydrate via Alpine setelah Lucide icons load. Image lazy-load tanpa `width`/`height` attribute → image dimensions unknown → reflow.

**Fix:**
1. Kasih `min-h-[600px]` atau `min-h-screen` ke `<div class="grid ...">` parent.
2. Image tags tambah `width` + `height` attribute eksplisit (atau pakai `aspect-ratio` Tailwind utility `aspect-[4/3]`).
3. Lucide icons via `<i data-lucide="...">` → wrap dengan fixed-size span: `<span class="inline-block h-5 w-5">`.

---

## Verdict

**⚠️ REQUEST CHANGES** — `t_ab94a31f`

Production-like setup ✅ proven reusable, course detail unblock ✅, home perf significant win ✅, cart CLS fix ✅. **Tapi 2 CLS regression baru blocking sign-off (`t_de99d26b` QC-3)** dan 3 perf score di bawah 90 threshold.

### Action items

**`mc-fullstack`** (urut prioritas):

1. **H1-new** — checkout CLS root cause: wrap dynamic Alpine blocks dengan `min-h-*` reservation. Target: CLS < 0.05 (back to baseline lebih baik dari M2's 0.131).
2. **H2-new** — produk-kelas CLS root cause: kasih `min-h-*` ke grid container + dimension hints ke images. Target: CLS < 0.1.
3. **L-perf** — home unused JS 21KB cleanup (optional, kalau Lead mau push perf 87 → 90+).
4. Re-deploy preview, ping `mc-review-qc` untuk re-verify Lighthouse.

**`mc-planning`** — Lead decision required:

- 4 PRs masih open belum merged di main: M1 emerald→secondary (#17), L1 dead code (#15), L2 logo (#16), M3 Larastan (#18). Lead pilih: (a) merge semua sebagai trailing M2-hardening sebelum sign-off, atau (b) defer ke M3 sprint. Saran: merge L1+L2 (trivial, no QC needed), merge M1 dengan re-Lighthouse spot-check (palette only, gak ngubah perf), defer M3 Larastan ke M3 sprint task tersendiri.

### Re-review trigger

Setelah H1-new + H2-new fixed dan optional L-perf:

```bash
cd /root/malang-creative/_active/masfirmanpratama/docs/qc/M2-hardening/lighthouse
docker compose up -d
sleep 90  # wait extension compile (first run only)
docker exec mfp-m2h-php sh -c \
  'chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
   chmod -R u+rwX,g+rwX /var/www/html/storage /var/www/html/bootstrap/cache'
BASE_URL=http://127.0.0.1:3003 bash run-audit.sh m2h-v2
docker compose down
```

---

## Notes — Production-like setup as M3+ Reusable Pattern

Stack di-clone dari `docs/qc/M2/lighthouse/` ke `docs/qc/M2-hardening/lighthouse/` dengan:

- Container rename: `mfp-m2-{php,nginx}` → `mfp-m2h-{php,nginx}` (avoid clash kalau M2 stack masih ada)
- Network rename: `m2net` → `m2hnet`
- Port: 3002 → 3003 (M2 stack might still be bound to 3002)
- nginx.conf bind mount path: `M2/lighthouse` → `M2-hardening/lighthouse`

Pattern stable. Untuk M3+ tinggal copy folder, sed-replace 4 token (container name, network, port, bind path), boot. Estimate first-run ~3 min (extension compile), subsequent <30s.

**Permission step is needed once per `docker compose up`** — bind-mounted `storage/` owned by host root, php-fpm runs as www-data; tanpa chown saat boot pertama, Laravel error 500 di route yang nge-write log/cache.

---

## Decisions (append ke `docs_dev/task_plan.md::Decisions`)

> 2026-05-27 — `t_ab94a31f` REQUEST CHANGES (M2-hardening Lighthouse re-audit). 6 routes audited via production-like nginx+php-fpm stack (port 3003). Wins: home perf 65→87 (H3 webp), produk-kelas unblocked from timeout (M2 carryover fix), cart CLS 0.129→0.000 (M4). Regressions: checkout CLS 0.131→0.197 (footer shift dari dynamic Alpine blocks), produk-kelas CLS 0.228 NEW (grid container shift). 3/6 routes < perf 90 threshold (home 87, checkout 78, produk-kelas 74). M2 sign-off blocked sampai H1-new + H2-new addressed. Production-like stack reusable pattern stable, 4-token sed-replace untuk milestone clone.
