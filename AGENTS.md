# MasFirmanPratama.com Ecosystem — AGENTS.md

> File ini auto-loaded oleh Hermes setiap session yang berjalan dari folder project ini.
> Update kapanpun ada perubahan stack, konvensi, atau decision penting.

## 📌 Identitas project

- **Nama:** MasFirmanPratama.com Ecosystem
- **Slug:** masfirmanpratama
- **Kanban board:** `masfirmanpratama`
- **Discord forum post:** https://discord.com/channels/1504923923525927012/1505035685931913376
- **Repo upstream:** https://github.com/naufalix/affiliate (existing — prototype HTML + plan + design)
- **Mulai:** 2026-05-13 (plan) / 2026-05-16 (intake MC)
- **Target launch:** 2026-06-11 (Day 30)
- **Status:** 🟢 active · 🧭 planning

## 🎯 Brief singkat

Ekosistem bisnis online Mas Firman Pratama (Mind Power & Life Mastery / AMC):

1. **Online Store** (`masfirmanpratama.com`) — etalase produk (kelas + buku),
   checkout manual, upload bukti bayar (lunas atau cicilan), tracking order
   tanpa login, integrasi ongkir Agenwebsite.com untuk buku fisik.
   Plus **Blog/Artikel** (`/blog`) — CMS di `/admin/posts`, SEO (sitemap, OG,
   canonical), migrasi dari WordPress lama via importer WXR (`blog:import-wordpress`).
2. **Admin Panel Unified** (`/admin` atau `admin.masfirmanpratama.com`) —
   1 login kontrol Store + Affiliate: produk, pesanan, verifikasi bayar,
   resi, affiliator, komisi, withdrawal, materi marketing, event gamifikasi.
3. **Affiliate System** (`affiliate.masfirmanpratama.com`) — landing program,
   register affiliator (3 tipe: alumni/non-alumni/peserta), dashboard,
   referral link manager, komisi (cooling 7 hari), withdrawal,
   leaderboard, gamifikasi event.

Webhook **HMAC-SHA256** Store → Affiliate untuk `order-paid` / `order-refunded`.
Referral tracking via `/ref/{code}` → cookie 30 hari attached ke order.

## 👥 Stakeholder

- **Klien:** Firman Pratama (AMC — Mind Power & Life Mastery)
- **Lead MC:** Rezvi (`rezvi`, Discord `1413174374529372313`)
- **Developer assigned:** Naufalix (`naufalix`, Discord `281181009314185220`)
- **Contact klien:** TBD (perlu konfirmasi PIC + WhatsApp)

## 🛠️ Tech stack

- **Backend:** Laravel 11 (2 app terpisah — `store` + `affiliate`, shared-hosting friendly)
- **Frontend:** Blade + Tailwind CSS v3 + Alpine.js (utility-first, no separate JS framework)
- **Database:** MySQL — 2 schema: `store_db`, `affiliate_db`
- **Auth:** Session-based (admin + affiliator), Laravel built-in
- **Payment:** **Manual transfer + upload bukti bayar** (lunas/cicilan) — Midtrans **DIHAPUS** dari spec
- **Ongkir:** Agenwebsite.com API (buku fisik) — fallback admin manual
- **Notifikasi:** WhatsApp Gateway (Fonnte / Wablas — TBD) — admin alert + customer reminder
- **Webhook:** HMAC-SHA256, retry mechanism, log kedua sisi
- **Hosting:** Shared/VPS (Laragon untuk dev lokal di `d:\laravel\store` & `d:\laravel\affiliate`)
- **Ikonografi:** Lucide Icons (script tag)
- **Tipografi:** Google Fonts — Inter, sans-serif

## 🌐 Preview server (development)

- **URL preview store:** http://43.133.128.222:3001
- **Port store:** `3001` (alokasi dari `/root/malang-creative/_ports.json`, key `masfirmanpratama-store`)
- **Bind address:** `0.0.0.0:<port>` (jangan `127.0.0.1`, biar bisa diakses dari IP publik)
- **Multi-app**: project ini punya `store/` + `affiliate/` (TBD) — tiap app dapet port sendiri
- **Cara start dev server:**
  ```bash
  mc-preview start masfirmanpratama --app store       # auto-detect Laravel, bind 0.0.0.0:3001
  mc-preview start masfirmanpratama --app affiliate   # auto-allocate port baru pas affiliate dibuat
  mc-preview status                                    # cek semua project running
  mc-preview logs masfirmanpratama --app store        # tail server log
  mc-preview restart masfirmanpratama --app store     # reload setelah config change
  mc-preview stop masfirmanpratama --app store        # matiin preview
  ```
- **Custom command (kalau butuh full Laravel 11 dev concurrent — vite + queue + pail):**
  ```bash
  # Hati-hati: composer run dev default bind 127.0.0.1, jadi modify composer.json scripts.dev
  # untuk pass --host=0.0.0.0 ke artisan serve, baru pake:
  mc-preview start masfirmanpratama --app store --cmd "composer run dev"
  ```

## 🎨 Brand & design tokens

Sumber: `DESIGN.md` di repo upstream. Filosofi: modern SaaS bersih + sentuhan
magis/futuristik (gradients, blur, glassmorphism).

- **Primary (Indigo):** `#6366f1` (500), `#4f46e5` (600 — CTA utama), `#4338ca` (700)
- **Secondary (Teal):** `#14b8a6` (500), `#0d9488` (600), `#134e4a` (900)
- **Accent (Amber):** `#f59e0b` (500), `#d97706` (600)
- **Neutral:** Slate (Tailwind default) + custom `slate-850: #1e293b`
- **Gradient:** `.text-gradient` linear `#4f46e5 → #0d9488`
- **Glass:** `.glass` (white 85% + blur 12px), `.glass-dark` untuk slate-900
- **Cards:** `rounded-2xl` / `rounded-3xl` / `rounded-[2.5rem]`, border slate-100, shadow-sm, hover-lift (translateY -8px)
- **Buttons:** primary `bg-primary-600 rounded-full` + `shadow-primary-500/30`, ripple effect
- **Container:** `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`, section padding `py-20`–`py-24`
- **Animasi custom:** `.animate-blob`, `.animate-float`, `.animate-fade-in-up`

## 📐 Konvensi project ini

- **Branch:** `feat/<task-id>-<slug>`, `fix/<task-id>-<slug>`
- **Commit:** `type(scope): subject` — scope `store|affiliate|admin|webhook|infra`
- **PR:** ke `develop` dulu, baru `main` setelah QC
- **Test runner:** PHPUnit (Laravel default) + Pest opsional
- **Build assets:** `npm run dev` (dev) / `npm run build` (prod) — Vite + Tailwind
- **Lint:** Laravel Pint + ESLint (untuk Alpine bila ada custom JS)
- **Deploy:** Manual deploy via SSH/FTP ke shared/VPS, env per stage
- **Repo dual:** monorepo `naufalix/affiliate` upstream (prototype + plan +
  design) — implementasi Laravel di-init terpisah per app (`store/` & `affiliate/`),
  bisa subfolder di repo yang sama atau split repo (decision pending)

### Hash anchor canonical names

Decision (task `t_719570cc`, audit M1 tail): adopt `#kelas` + `#katalog` sebagai
nama canonical karena lebih spesifik dari prototype generic. Prototype lama pakai
`#kategori`/`#buku` (silent-404 di Blade home — section ID-nya `#kelas`/`#katalog`).

| Konteks                    | Anchor canonical | Section ID di `home.blade.php` |
|----------------------------|------------------|--------------------------------|
| Daftar kelas / kursus      | `#kelas`         | `<section id="kelas">`         |
| Katalog buku / produk fisik| `#katalog`       | `<section id="katalog">`       |

Mapping untuk migrasi konten dari prototype / WA copy template:

| Prototype lama  | Canonical (Blade) |
|-----------------|-------------------|
| `#kategori`     | `#kelas`          |
| `#buku`         | `#katalog`        |

Aturan:

- Semua Blade di `store/`, AGENTS.md, dan dokumentasi internal **wajib** pakai
  `#kelas` / `#katalog`.
- Folder `prototype/` (top-level + `docs/upstream-archive/prototype/`)
  dipertahankan as-is sebagai baseline visual reference — **tidak** ikut di-fix.
- Cek pre-commit untuk milestone berikutnya:
  `grep -rnE 'href="#(kategori|buku)"' store/ AGENTS.md` → harus `0 hits`.

## 🚦 Acceptance criteria umum

- [ ] Lighthouse Performance ≥ 90 (mobile)
- [ ] Lighthouse Accessibility ≥ 95
- [ ] Tidak ada `dd()` / `dump()` / `console.log` debugging tertinggal
- [ ] Tidak ada secret hardcoded — semua via `.env`
- [ ] PHPUnit/Pest pass + Pint clean sebelum merge
- [ ] CSRF + input sanitization + file upload validation (bukti bayar = image, max 2MB)
- [ ] HMAC-SHA256 validation di webhook receiver (Affiliate side)
- [ ] Manual QA per milestone (M1–M5) sebelum lanjut

## 🧱 Arsitektur tinggi

```
┌────────────────────────────────────────────────────────┐
│  masfirmanpratama.com (Store + Admin Panel unified)    │
│  Laravel 11 / store_db                                  │
│  - public: katalog, cart, checkout, upload bukti        │
│  - admin: produk, pesanan, verifikasi bayar, resi      │
│           + affiliate management (proxied)              │
└─────────────────┬──────────────────────────────────────┘
                  │ Webhook HMAC-SHA256
                  │ (order-paid, order-refunded)
                  ▼
┌────────────────────────────────────────────────────────┐
│  affiliate.masfirmanpratama.com                         │
│  Laravel 11 / affiliate_db                              │
│  - public: landing, register affiliator                 │
│  - affiliator: dashboard, referral, komisi, withdrawal  │
│  - /ref/{code} → cookie 30 hari → redirect ke Store     │
└────────────────────────────────────────────────────────┘
```

## 🗃️ Database tabel kunci

**store_db:** `products`, `orders`, `order_items`, `order_payments` (cicilan tracking),
`installment_schemes`, `admins`, `settings`, `wa_notifications`, `webhook_logs`.

**affiliate_db:** `users` (affiliator), `products` (mirror + komisi%),
`referral_links`, `referral_clicks`, `transactions`, `commissions` (cooling 7d),
`withdrawals`, `marketing_materials`, `notifications`, `gamification_events`,
`event_participants`, `webhook_logs`, `activity_logs`.

## 🗓️ Milestones (30-day plan dari `.sisyphus/plans/project-plan-30days.md`)

| # | Target Date | Deliverable |
|---|-------------|-------------|
| **M1** | 2026-05-18 (Day 6) | Store live lokal — customer bisa browse + checkout + upload bukti bayar |
| **M2** | 2026-05-25 (Day 13) | Admin Panel Store — kelola produk, verifikasi bayar, input resi |
| **M3** | 2026-06-01 (Day 20) | Affiliate System — affiliator daftar, login, generate link, lihat komisi |
| **M4** | 2026-06-05 (Day 24) | Integration — webhook jalan, referral tracking works, gamifikasi aktif |
| **M5** | 2026-06-11 (Day 30) | Production launch — 2 domain live, smoke test pass |

## ⚠️ Catatan khusus / pitfall

- **Tailwind vs Bootstrap conflict** di prototype existing — keputusan: pakai
  Tailwind v3 untuk semua implementasi final (DESIGN.md). Prototype Bootstrap
  hanya jadi reference visual.
- **Cicilan kompleks** — order bisa `partial_paid`, butuh tracking per
  pembayaran (`order_payments` table), reminder otomatis WA, dan rekonsiliasi
  manual admin per cicilan.
- **Webhook reliability** — HMAC + retry mechanism + log wajib. Kalau Affiliate
  down, Store harus retry tanpa double-credit komisi.
- **WA Gateway rate limit** — fallback ke email + in-app notification.
- **Privacy first** — affiliator hanya lihat **nama** pembeli, bukan kontak/alamat.
- **Admin panel UNIFIED** — 1 login control 2 DB. Hati-hati cross-DB query
  performance, pertimbangkan caching di dashboard.

## 🏁 Sprint selesai — M1 Frontend Online Store (closed 2026-05-16)

QC approved (`docs/qc/visual-review-M1.md`, `docs/qc/lighthouse-M1.md`):
- ✅ 7 route ported, semua HTTP 200
- ✅ A11y ≥95 di 6/7 route
- ⚠ Perf <90 — artefak `php artisan serve` (single-thread, no gzip), bukan code issue, re-audit di nginx prod
- ⚠ 2 high-sev finding (#H1 palette + #H2 anchor) → carryover ke M2 tail
- ⚠ `/produk/kelas-amc-reguler` Lighthouse timeout (curl OK 26ms 76kB) → carryover ke M2 tail untuk root-cause
- ⚠ Kanban task IDs M1 (t_8779b460 ... t_9cf308a8) hilang setelah VPS migration 2026-05-18 — DB pindah kosong, tapi git history + QC report cukup sebagai audit trail

## 🏁 Sprint selesai — M2 Admin Panel Store + Wire FE→BE (closed 2026-05-22, sign-off PASS-WITH-NOTES)

QC delivered (`docs/qc/lighthouse-M2.md`, `docs/qc/visual-review-M2-admin.md`, `docs/qc/integration-tests-M2.md`):
- ✅ Admin foundation + Produk CRUD + Pesanan + Verifikasi bayar + Input resi + Settings + Installment schemes + WA notification stub — semua functional
- ✅ Backend integration tests 270/270 pass, 1019 assertions, 6.62s
- ✅ Lighthouse re-audit production-like nginx: 4/5 route ≥90 perf mobile (cart 94, checkout 93, produk-list 99, produk-buku 99), A11y 95-100, BP+SEO 100
- ✅ Wire FE→BE complete — checkout, upload bukti bayar, token-protect /upload + /track via signed URL
- ⚠ Sign-off PASS-WITH-NOTES: 1 Critical (mobile admin nav drawer absent) + 2 High (palette red/rose split, home perf 65 due to 385KB founder JPEG + Lucide CDN) + 3 Medium (Pint clean 5 file, Larastan ngga install, CLS cart/checkout) + 2 Low (sidebar dead code, logo extract) → 7 carryover di-handle di M2-hardening sprint
- ✅ Lucide CDN pin + alpine loop fix landed (PR #7 t_5e6b03f1) — `/produk/kelas-amc-reguler` no more Lighthouse PROTOCOL_TIMEOUT
- ✅ message-square icon whitelist + dev-time guard landed (PR #8) — admin sidebar WA Notifikasi render proper

## 🏁 Sprint selesai — M2-hardening (closed 2026-05-22, sign-off PASS clean)

QC outcome: PASS clean (Lead anggap QC oke setelah verify 10/10 PR ship + tests + lint + phpstan all green).

**Delivery (10 PR shipped, all merged ke main):**
- PR #9 H1 — destructive palette `red-* → rose-*` canonical (7 file, 13 occurrences)
- PR #10 H2 — `text-orange-600 → text-accent-600` di home.blade.php (single line, prevent Tailwind safelist scan emit unused class)
- PR #11 H3 — `firman-foto.webp` re-encode 246KB→51KB + `<picture>` fallback di hero (estimated LCP -1.5s)
- PR #12 M2 — pint auto-fix 5 file (concat_space, unary_operator_spaces, fully_qualified_strict_types, array_indentation)
- PR #13 C1 — mobile admin nav drawer (Alpine inline) + extract nav config ke `config/admin-nav.php` + 7 regression test (`SidebarMobileDrawerTest`)
- PR #14 M4 — CLS guard `/cart` + `/checkout` (8 error slot reserve `min-h-[1.25rem]` + opacity toggle, cart container `min-h-[420px]`, plus `aria-live="polite"` bonus a11y)
- PR #15 L1 — hapus dead code `coming_soon` di nav config (post-C1 cleanup, +tree-shake duplicate Tailwind utility)
- PR #16 L2 — extract logo F ke `<x-admin.logo />` component + config `admin.logo_initial` (DRY × 3 tempat: desktop sidebar, mobile header, drawer panel; -0.76 kB CSS tree-shake)
- PR #17 M1 — `emerald-* → secondary-*` canonical (10 file, 34 occurrences + critical fix di `wa-notifications/index.blade.php` toneMap dynamic class)
- PR #18 M3 — Larastan level 6 install + `composer ci` gauntlet (10 initial errors fixed, root cause via `Order::payments()` generic typing `HasMany<OrderPayment, $this>`)

**Day-end metrics:**
- Build: 75.38 kB CSS gzip (vs M2 75.03 = +0.35 kB net, dominated by drawer Tailwind utility)
- Tests: **283 passed** (1053 assertions, 6.49s) — +13 dari baseline M2 (270)
- PHPStan: **0 errors** (level 6, app/ scope)
- Pint: 101 files PASS
- All 6 acceptance criteria umum AGENTS.md ✅

**Carryover ke M3 (Affiliate System):**
- `AdminNavComposer` extract — refactor `config/admin-nav.php` jadi view composer kalau mau merge dengan affiliate admin nav
- Refactor destructive palette ke `tailwind.config.js::colors.danger` semantic token (cosmetic DRY, defer karena scope kecil)
- CI gauntlet pre-commit hook (`composer ci`) — task baru di M3, integrate ke git hook atau GitHub Actions
- Bump PHPStan level 6 → 7-8 setelah codebase mature (M3+ task)

**Decisions M2-hardening:**
- Option A (Alpine inline drawer) menang vs Option B (AdminNavComposer) — Option A 4-6h actual ~25 menit, Option B di-defer
- Bulk find-replace per-file untuk palette consolidation (red→rose, emerald→secondary) — manual review per match, lebih cepat dari refactor ke semantic token
- PHPStan level 6 default — strict enough untuk catch real bugs, tapi ngga bikin friction tinggi (level 7-8 perlu full template type yang ngga semua pas untuk Laravel magic)
- Larastan errors fix via root-cause typing (`Order::payments()` generic) bukan suppress per-line — 6 errors disappear dengan 1 docblock fix

## 🚀 Sprint aktif — M3 Affiliate System (2026-05-22 → 2026-06-01)

**Target:** M3 deliverable Day 20 (2026-06-01) — affiliate.masfirmanpratama.com landing + register/login affiliator + dashboard 3 tipe (alumni/non-alumni/peserta) + admin affiliate panel (proxied dari unified admin).

**Scope:**
- Foundation DB: 14 tabel (`affiliators`, `affiliator_types`, `referral_codes`, `referral_clicks`, `referral_orders`, `commissions`, `commission_settings`, `withdrawals`, `withdrawal_methods`, `affiliate_events`, `affiliate_event_participants`, `affiliate_event_rewards`, `materials`, `material_downloads`)
- Auth affiliator: register flow (3 tipe) + login + email verification
- Public landing: `affiliate.masfirmanpratama.com` program landing + benefit explainer + register CTA
- Dashboard non-peserta: referral link manager + earnings overview + withdraw trigger
- Dashboard peserta/alumni: extra leaderboard + event card + materi marketing download
- Admin affiliate panel: affiliator CRUD + komisi review + withdrawal approve + materi upload + event setup (basic, gamifikasi penuh M4)

**Out of scope (defer ke M4):**
- Webhook integration HMAC-SHA256 Store→Affiliate (`order-paid` / `order-refunded`)
- Gamifikasi event lengkap (leaderboard real-time, reward auto-claim, level system)
- Agenwebsite.com ongkir API integration
- WA gateway provider integration (still stub via `wa_notifications` table)

**Owner agent split (akan di-decompose di kanban M3 setelah DOC-1 land):**
- `mc-planning` — decompose 14 tabel + auth + landing + 3 dashboard + admin panel jadi task atomic (estimasi ~25-30 task)
- `mc-fullstack` — bulk implementation (Foundation + Auth + Dashboard + Admin)
- `mc-ui` — landing page distinctive (klien minta high-impact program landing) + 3 dashboard layout
- `mc-qc` — review per sub-block + final M3 sign-off

**ETA: 9 days (Day 12 → Day 20).**

**Sprint blocks:**
- **Foundation DB+auth** (Day 12-13) — 14 migration + Affiliator model + auth flow + email verification
- **Public landing + register** (Day 14-15) — landing page distinctive + register form 3 tipe + onboarding email
- **Dashboard non-peserta** (Day 16) — referral link manager + komisi tracking + withdraw form
- **Dashboard peserta/alumni** (Day 17-18) — extra leaderboard + event card + materi download
- **Admin affiliate panel** (Day 19) — affiliator CRUD + komisi approve + withdrawal review (proxied di unified admin)
- **QC + sign-off** (Day 20) — visual review + integration tests + final sign-off

**Open decisions (perlu konfirmasi klien):**
- WA gateway provider final (Fonnte / Wablas / lainnya) — blocker M4 reminder, NOT M3
- Affiliate design system: ikut Store DESIGN.md (Indigo/Teal/Amber) atau distinct palette buat differentiate `affiliate.*` subdomain dari `masfirmanpratama.com`?
- Komisi structure: percentage flat, tier-based, or product-based custom? Belum locked
- Referral cookie window: 30 hari (initial spec) tetap, atau bump ke 90 hari kompetitif?
- Email provider: SMTP shared hosting cukup, atau pakai SES/Mailgun untuk delivery rate?

## 🔓 Open decisions (perlu konfirmasi klien)

1. ~~Domain admin: subdomain vs prefix?~~ → **prefix `/admin`** (2026-05-16)
2. WA Gateway provider: Fonnte / Wablas / lainnya? (butuh API key)
3. Agenwebsite.com — sudah punya akun + API key?
4. Harga buku final?
5. ~~Skema cicilan?~~ → **admin set bebas per skema** (2026-05-16)
6. ~~Rekening tujuan transfer manual?~~ → **dummy dulu untuk M1** (2026-05-16)
7. Hosting: shared / VPS? Spek?
8. Affiliate system pakai design system yang sama dengan Store, atau distinct?

## 📚 Decisions log

> Format: `YYYY-MM-DD | siapa | apa | kenapa`

- 2026-05-13 | Klien | Midtrans **OUT**, manual transfer + upload bukti **IN** | Simplifikasi + kontrol manual
- 2026-05-13 | Klien | Admin panel **UNIFIED** (1 login Store + Affiliate) | UX admin, hindari double login
- 2026-05-13 | Klien | Cicilan support per order (DP + N tahapan) | Kebutuhan harga kelas premium
- 2026-05-13 | Plan | Tailwind v3 menang vs Bootstrap | Konsistensi DESIGN.md
- 2026-05-16 | Lead MC | Project intake & bootstrap di MC workspace | Mulai delivery via agent flow
- 2026-05-16 | Lead MC | Naufalix assigned sebagai developer | Sesuai existing repo ownership
- 2026-05-16 | Klien (via Naufalix) | Skema cicilan bebas diatur admin (bukan fixed N) | Fleksibilitas per produk
- 2026-05-16 | Klien (via Naufalix) | Rekening tujuan dummy untuk M1 | Belum ada rekening final
- 2026-05-16 | Klien (via Naufalix) | Domain admin = prefix `/admin` (bukan subdomain) | Setup hosting lebih sederhana
- 2026-05-16 | Klien (via Naufalix) | Theme Store ikut DESIGN.md (light Indigo/Teal/Amber) | Konsisten brand utama
- 2026-05-16 | mc-planning | Sprint M1 = FE Store only, 17 task di kanban | Decoupling FE/BE biar paralel
- 2026-05-17 | Naufalix + MCAIAgent | Force push local M1 work ke `naufalix/affiliate` main, archive seluruh upstream tree ke `docs/upstream-archive/`, tag `upstream-pre-mc` di SHA `c8e166e` | Local & upstream punya unrelated histories (bootstrap fresh vs git clone), butuh single source of truth tanpa kehilangan konten klien existing
- 2026-05-17 | MCAIAgent | `.kanban-task-ids.json` di-untrack dari git (sudah di .gitignore tapi committed sebelum ignored) | Hindari MC infra state leak ke repo upstream
- 2026-05-18 | Lead MC + MCAIAgent | VPS migration recovery — VPS lama off, full restore dari `naufalix/affiliate` (main + feat/m1-store-fe + project-plan + tag upstream-pre-mc) sukses, working tree clean | Code + AGENTS.md + QC report + upstream archive intact via git, satu-satunya state yang hilang = kanban task IDs M1 (di-record di sprint closed section, ngga di-rebuild)
- 2026-05-18 | mc-planning | Sprint M1 closed (QC approved 2026-05-16), kick off M2 Admin Panel Store + Wire FE→BE | Day 6/30, on-track ke target M2 (Day 13, 2026-05-25)
- 2026-05-18 | mc-planning | M1 tail (H1 palette, H2 anchor, /kelas-amc-reguler timeout) digabung ke M2 sprint, bukan sprint terpisah | 3 task ringan, ngga worth standalone sprint, parallel dengan M2 foundation
- 2026-05-22 | mc-debug | Root-cause `/produk/kelas-amc-reguler` Lighthouse PROTOCOL_TIMEOUT — 3 stacked causes (lucide CDN broken, alpine:morphed loop, x-for x-init multiplier), pin `lucide@0.469.0` + drop morphed listener + drop per-tab x-init | M1 tail debug task `t_5e6b03f1` resolved, regression test `CourseDetailLighthouseGuardTest` ditambah
- 2026-05-22 | mc-review-qc | M2 sign-off PASS-WITH-NOTES (4/5 route ≥90 perf, 270/270 backend tests, A11y 95-100) — 7 carryover (1 Critical mobile drawer + 2 High palette+perf + 3 Medium tooling+CLS + 2 Low cleanup) | M2 deliverable functional, polish gap → handle di M2-hardening sprint sebelum M3 kick off
- 2026-05-22 | Lead MC + mc-planning | Kick off M2-hardening sprint (14 task, ~17-22h, 2-3 hari) instead of langsung lompat ke M3 | Pastikan M2 PASS clean dulu sebelum tambah scope affiliate (1 Critical blocker `/admin` mobile, 2 High palette+perf langsung impact UX)
- 2026-05-22 | mc-planning | Destructive palette canonical = `rose-*` (M2-hardening H1 decision) | Konsistensi visual + kontras lebih baik dengan primary indigo. Refactor ke `tailwind.config.js::colors.danger` semantic token defer ke M3 backlog (hindari PR besar di hardening)
- 2026-05-22 | mc-planning | C1 mobile drawer pakai Option A (Alpine inline) bukan Option B (AdminNavComposer extract) | Option A 4-6h vs Option B 6-8h, Option B refactor reusable di-defer ke M3 backlog karena akan dipakai pas affiliate admin dibikin
- 2026-05-22 | Lead MC + mc-planning | PR #7 (lucide fix `t_5e6b03f1`) + PR #8 (admin icon whitelist `message-square`) merged ke main sebagai M2-hardening unblocker | Course detail no more Lighthouse timeout di main, sidebar WA Notifikasi render proper
- 2026-05-22 | mc-fullstack | M2-hardening sprint closed clean — 10 PR shipped (PR #9-#18) all merged, 283/283 tests pass, phpstan 0 errors level 6, pint clean | Lead anggap QC oke (manual sign-off via thread), seluruh 14 task M2-hardening selesai dalam 1 sprint hari
- 2026-05-22 | mc-fullstack | M2-hardening Option A drawer + Larastan level 6 + composer ci gauntlet sebagai standar pre-M3 | Foundation static analysis + DRY nav config + ci script siap reused di M3 affiliate
- 2026-05-22 | mc-fullstack | Larastan errors fix via root-cause typing (`Order::payments()` `@return HasMany<OrderPayment, $this>`) bukan suppress per-line | 6 dari 10 errors hilang dengan 1 docblock fix, lebih clean dari ignore-baseline
- 2026-05-22 | mc-fullstack | `coming_soon` placeholder dihapus permanen dari nav config | M2 selesai semua menu ready, dead code cleanup post-C1
- 2026-05-22 | mc-fullstack | Logo extract jadi `<x-admin.logo />` + `config('admin.logo_initial')` env-overridable | DRY × 3 tempat (sidebar + mobile header + drawer panel) + future-proof klien lain bisa swap initial via env
- 2026-05-22 | mc-fullstack | Success state palette canonical = `secondary-*` (Teal, design token) bukan `emerald-*` (default Tailwind) | Konsistensi DESIGN.md, plus penting karena `wa-notifications/index.blade.php` toneMap pakai dynamic class `bg-{{ $tone }}-50` yang silent-broken kalau emerald-* purged
- 2026-05-22 | mc-planning | M2-hardening closed PASS clean (Lead anggap), kick off M3 Affiliate System sprint | Day 12/30, on-track ke target M3 Day 20 (2026-06-01). Decompose ~25-30 task kanban M3 sebagai langkah berikutnya
- 2026-06-18 | orchestrator | Merge `feat/m3-affiliate-system` → main (`b2d9491`), nol konflik | M3 di-branch dari main lama, paralel dengan shipping+kelas/buku split. m3 cuma nambah `affiliate/`, jadi clean merge. Affiliate app verified: 14 tabel scope + auth + dashboard + admin panel (51 route), 36 test pass, pint clean
- 2026-06-18 | orchestrator | M3 verdict SELESAI | Audit: 14 tabel migration lengkap, controllers + route penuh (auth, referral, commission, withdrawal, material, event, leaderboard, admin), landing pakai view beneran, no stub/dd. Lanjut M4
- 2026-06-18 | engineer (opencode) | M4 batch 1 store emitter (`be48955`) | AffiliateWebhookClient HMAC-SHA256 + retry, DispatchAffiliateOrderPaid listener di PaymentVerified, cookie referral fallback. BONUS FIX: ref_code abuse (occupation/motivation JSON) dipindah ke kolom `order_meta` baru. 438 test pass
- 2026-06-18 | engineer (opencode) | M4 batch 2 affiliate receiver (`f19ae22`) | StoreWebhookController verify HMAC (hash_equals timing-safe, fail-closed kalau secret kosong → 503), idempotency by store_order_id, order-paid → referral_order+commission cooling, commission_settings match w/ fallback global, min_amount guard, order-refunded → cancel cooling/available (preserve withdrawn), commissions:release command daily. 43 test pass
- 2026-06-18 | orchestrator | M4 integration smoke PASS end-to-end | Store sign → affiliate verify cocok (200), bad sig 401, referral_order paid + commission 10% (Rp50k) cooling +7d, idempotent (kirim 2x → 1 commission). Secret store/affiliate HARUS sama (AFFILIATE_WEBHOOK_SECRET = STORE_WEBHOOK_SECRET). PENDING: seed commission_settings nilai final dari klien, push ke origin (PAT invalid)
- 2026-07-03 | engineer (feat/blog) | Modul **Blog** ditambah ke Store (branch `worktree-feat+blog`, paralel) | 8 fase: data layer (posts/blog_categories/blog_tags/pivots/blog_media + kolom kompat WP), admin CRUD `/admin/posts` + Trix + HtmlSanitizer, publik `/blog` + `/blog/{slug}`, SEO (sitemap/RSS/OG/JSON-LD/scheduled-publish), **WXR importer** (`blog:import-wordpress`, idempotent via `wp_post_id`), legacy 301 root→`/blog/{slug}`. 50 test blog pass. Keputusan URL: `/blog/{slug}` + 301 (bukan root, hindari tabrakan route). Detail: `product-development/features/blog/PRD.md`
- 2026-07-03 | engineer (feat/blog) | Migrasi konten WP lama = export WXR (Tools→Export) → `/admin/posts/import` (checklist: `--media` SEBELUM cutover DNS selagi `wp-content/uploads` masih online) | Situs lama WordPress+Yoast, ~74 artikel/6 kategori. Import idempotent, slug dipertahankan → 301 mulus
