# Visual Review — M2-hardening (Re-screenshot + Re-audit)

**Task:** `t_5bab0bd3` — M2-hardening QC-1: re-screenshot + re-review visual admin (33 view × 3 viewport)
**Reviewer:** mc-review-qc
**Date:** 2026-05-27
**Method:** Static visual audit fallback (vision tool partially flaky — first scan returned wrong image, second unavailable) + Playwright re-shoot 3 viewport baseline.
**Branch reviewed:** `main` @ `863e6f0` (post 6 hardening PRs #9-14 merged)
**Predecessor report:** `docs/qc/visual-review-M2-admin.md` (verdict REQUEST CHANGES)

---

## Scope

Verifikasi 6 fix M2-hardening yang sudah merged ke `main`:

| Tag | Severity | PR | Fix |
|---|---|---|---|
| C1 | 🔴 Critical | #13 | Mobile nav drawer (Alpine inline) + extract nav config |
| H1 | 🟠 High | #9 | Unify destructive palette `red-*` → `rose-*` (admin) |
| H2 | 🟠 High | #10 | `text-orange-600` → `text-accent-600` di home.blade.php |
| H3 | 🟠 High | #11 | firman-foto ke `<picture>` webp + jpeg fallback |
| M2 | 🟡 Medium | #12 | Pint clean (unary, FQCN, array indent) |
| M4 | 🟡 Medium | #14 | Cart + checkout CLS guard |

Out-of-scope tetap **open di branch** (4 PRs unmerged): M1 emerald→secondary (#17), L1 sidebar dead code (#15), L2 logo component (#16), M3 Larastan (#18) — tracked sebagai backlog M3 carryover.

---

## Re-screenshot Results

- 33 PNG (11 routes × 3 viewports) di `docs/qc/M2-hardening/screenshots/`
- Helper script: `docs/qc/M2-hardening/playwright-shoot-desktop.mjs` (patched dari M2 — fix double-prefix BASE+http URL bug di FIRST_ORDER resolver)
- Tooling fix kecil: `playwright-shoot.mjs` mode all-viewport punya bug session loss di desktop iteration setelah `submitEmptyForm`. Solusi: gabungin mobile+tablet dari shoot-all + desktop dari shoot-desktop (with re-login on auth-loss). Hashes per viewport sekarang 11 unique = 0 duplicate.

---

## Static Visual Audit (per fix)

### C1 — Mobile drawer ✅

```
resources/views/layouts/admin.blade.php:26  <div x-data="{ open: false }" class="lg:hidden">
                                       29  @click="open = true"
                                       31  aria-controls="admin-mobile-drawer"
                                       56  id="admin-mobile-drawer"
                                       63  <div @click="open = false" (backdrop)
                                       83  @click="open = false" (close button)
                                       93  'linkClickHandler' => '@click="open = false"' (auto-close on nav)

resources/views/components/admin/sidebar.blade.php:9
   <aside class="hidden lg:flex lg:w-64 ...">  ← desktop sidebar

resources/views/components/admin/_nav-links.blade.php
   ← shared nav config, accepts $linkClickHandler injection
```

✅ Hamburger button visible at `lg:hidden`, drawer overlay + backdrop, close-on-click-link, ARIA controls wiring. Pattern sound.

### H1 — Destructive palette unified ✅

```
grep -rEnho 'text|bg|border|ring)-red-[0-9]+' resources/views  → 0 hits
grep -rEho '...rose-[0-9]+' resources/views                    → 10+ hits (rose-50/100/200/300/400/500/600)
```

✅ Zero `red-*` residue di views. Canonical destructive = `rose-*`.

### H2 — Home palette accent-* ✅

```
grep -nE 'orange' resources/views/pages/home.blade.php  → blank (0 hits)
grep -rEho 'accent-[0-9]+' resources/views              → bg-accent-{50,100,300,500,600}, text-accent-{500,600,700}
```

✅ `text-orange-600` residue eliminated, replaced by canonical `accent-*` tokens.

### H3 — Founder photo `<picture>` + webp ✅

```
resources/views/pages/home.blade.php:243  <picture>
                                     244    <source srcset="...firman-foto.webp" type="image/webp">
                                     246    src="...firman-foto.jpeg"  ← fallback
```

✅ Modern format with progressive fallback. Lighthouse home perf `+22` (M2 65 → M2-h 87) confirmed by data.

### M2 — Pint clean ✅

```
$ ./vendor/bin/pint --test
PASS  103 files
```

✅ All previously-flagged files pass strict mode.

### M4 — Cart + checkout CLS guard ⚠️

```
cart.blade.php:39    <div class="mt-10 min-h-[420px]">         ← cart state container
            :260   button: min-h-[44px]                          ← touch target
            :238   {{-- /min-h-[420px] cart state container (CLS guard) --}}

checkout/index.blade.php:161,181,201,238,259,280,319,432
            min-h-[1.25rem] text-xs ... text-rose-600 (per-error reservation, opacity-0/100 toggle)
```

✅ Cart fix worked: CLS 0.129 → 0.000 (Lighthouse data).
❌ **Checkout CLS regressed**: 0.131 → 0.197. Per-error `min-h-[1.25rem]` reservation works for inline error text, tapi `<footer>` masih shift karena dynamic sections (shipping-method options, installment-scheme block) populate after Alpine hydration tanpa `min-h-*` reservation. Lihat section "Findings Baru" di bawah.

---

## Acceptance Criteria Check (from `t_5bab0bd3` body)

| Criteria | Status | Note |
|---|---|---|
| C1: mobile drawer accessible, link work, active state | ✅ | Static analysis confirms drawer + ARIA + backdrop + auto-close |
| H1: tidak ada red-* untuk destructive (semua rose) | ✅ | 0 `red-*` hits di views |
| H2: home iconColor accent (bukan orange) | ✅ | 0 `orange-*` hits di `pages/home.blade.php` |
| M1: success state secondary (bukan emerald) | ⚠️ N/A | PR #17 belum merged, 70 emerald-* hits masih ada di main. Backlog. |
| L1: sidebar no dead $comingSoon block | ⚠️ N/A | PR #15 belum merged. Backlog. |
| L2: logo via component | ⚠️ N/A | PR #16 belum merged. Backlog. |

✅ **3/3 hardening fixes yang merged ke main verified.** M1/L1/L2 explicitly out-of-scope task ini (carryover ke M3).

---

## Findings Baru (post-hardening)

### 🟠 H1-new — Checkout CLS regression: footer shift karena dynamic block

**File:** `resources/views/pages/checkout/index.blade.php`
**Lighthouse data:** CLS 0.131 (M2) → 0.197 (M2-h), worsening past 0.1 threshold.
**Shift element:** `<footer class="bg-slate-950 text-slate-300 pt-16 pb-10 border-t border-slate-800 mt-20">` (single shift, score 0.197)

**Root cause:** M4 fix nge-reservasi `min-h-[1.25rem]` untuk per-input error text — itu fix CLS dari error label expansion. Tapi footer shift datang dari **above** footer, yaitu:

- Shipping-method options block (Alpine `x-show` saat shipping_method dipilih)
- Installment-scheme dropdown block (Alpine `x-show` saat scheme tertentu dipilih + dependent description block)
- Order-summary live-update saat cart items hydrate

Dynamic content above footer expands → footer pushed down → 0.197 shift.

**Fix:** wrap dynamic shipping/installment regions dengan `min-h-*` skeleton, atau swap ke `visibility: hidden` + reserved space pattern (Alpine `x-cloak` + initial layout reservation).

**Severity:** 🟠 H1 — CLS > 0.1 = a Core Web Vitals fail = production launch blocker.
**Est:** 1-2h.

### 🟠 H2-new — produk-kelas CLS new visible (was timeout in M2)

**File:** `resources/views/pages/products/course.blade.php` (kemungkinan)
**Lighthouse data:** P 74, CLS 0.228, LCP 3.60s (di M2 ini timeout total — M1 unblocked it via lucide CDN fix).
**Shift element:** `<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">` (single shift, score 0.228)

**Root cause:** Main content grid populate via Alpine after first paint — gak ada skeleton/min-height. CLS triggered saat grid items render dan grid grow vertically.

**Fix:** kasih `min-h-screen` atau `min-h-[600px]` ke grid container, atau pre-render placeholder via Blade kalau data tersedia di server-side.

**Severity:** 🟠 H2 — CLS > 0.1, blocking M2 sign-off (target Perf ≥90).
**Est:** 1h.

### 🟢 L1-new — Home perf improvement opportunity (unused JS 21KB)

**File:** unknown — Lighthouse `unused-javascript` audit shows ~21KB unused.
**Severity:** 🟢 Low — perf 87, target 90. Dengan dropping 21KB unused, kemungkinan break ≥90.
**Fix:** `npm run build` audit + tree-shake review, atau split Alpine bundle dari hero animation libs.
**Est:** 1-2h.

---

## Verdict

**⚠️ REQUEST CHANGES** — `t_5bab0bd3`

3 hardening fixes yang scope task ini (C1, H1, H2 — plus H3, M2, M4 sebagai bonus verify) **APPROVED** dari sisi static analysis. Tapi re-Lighthouse buka 2 finding baru CLS regression yang harus di-address sebelum M2 sign-off (`t_de99d26b` QC-3) bisa PASS clean:

1. **H1-new** Checkout CLS 0.197 (footer shift dari dynamic blocks)
2. **H2-new** Produk-kelas CLS 0.228 (grid container shift)

L1-new (home perf 87 → 90) optional kalau Lead OK terima 87.

### Action items

- `mc-fullstack`: address H1-new + H2-new — wrap dynamic Alpine `x-show` blocks dengan `min-h-*` reservation atau pre-render skeleton. Re-deploy preview, ping `mc-review-qc` untuk re-verify Lighthouse.
- `mc-planning`: 4 open PRs (M1, L1, L2 hardening + M3 Larastan) — Lead decision: merge sebagai trailing M2-hardening atau punt ke M3 backlog. Saran: M1+L1+L2 layak merge biar palette truly canonical sebelum sign-off; M3 (Larastan) memang M3 backlog.

### Re-review trigger

Setelah H1-new + H2-new fixed:

```bash
cd docs/qc/M2-hardening/lighthouse
docker compose up -d
sleep 90  # wait extension compile
docker exec mfp-m2h-php sh -c 'chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && chmod -R u+rwX,g+rwX /var/www/html/storage /var/www/html/bootstrap/cache'
BASE_URL=http://127.0.0.1:3003 bash run-audit.sh m2h-v2
docker compose down
```

Then re-run `mc-review-qc` di thread ini untuk close-out QC-2 + QC-3.

---

## Decisions (append ke `docs_dev/task_plan.md::Decisions`)

> 2026-05-27 — `t_5bab0bd3` REQUEST CHANGES (M2-hardening visual + Lighthouse re-audit). 3 hardening fix dari scope task ini (C1, H1, H2) verified PASS via static analysis. Re-Lighthouse buka 2 CLS regression baru: checkout 0.197 (footer shift dari dynamic Alpine blocks above), produk-kelas 0.228 (grid container shift, was M2 timeout). Carryover unmerged: M1+L1+L2 PRs (3 unmerged @ origin), Lead decide merge vs punt. Production-like stack di-clone ke `docs/qc/M2-hardening/lighthouse/` (port 3003, container `mfp-m2h-*`) — pattern reusable untuk M3+ re-audits per milestone.
