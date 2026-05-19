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

## 🚀 Sprint aktif — M2 Admin Panel Store + Wire FE→BE (2026-05-18 → 2026-05-25)

**Target milestone:** M2 (Day 13, 2026-05-25)
**Scope:** Admin authentication + dashboard, CRUD produk, pesanan + verifikasi bayar + input resi, settings (bank accounts + store info), installment schemes CRUD, wire Store FE checkout/upload ke backend real, WA notification **stub** (write only, gateway integration ditunda M3+).
**Out of scope:** WA gateway provider integration, Agenwebsite ongkir API, affiliate system, public→admin role separation (admin = single role di M2).
**Owner agent split:** mc-fullstack (16 task), mc-ui (2 task), mc-qc (3 task), mc-debug (1 task)
**ETA:** ~58h (45h raw + 30% buffer) → 6-7 hari kerja 1 dev

**Sprint blocks:**
- **M1 tail** (3 task) — fix tailwind palette H1, anchor audit H2, debug Lighthouse timeout `/kelas-amc-reguler`
- **Foundation** (4 task) — admin auth Breeze, admin layout + sidebar, migrations bundle, seeders
- **Produk CRUD** (3 task) — index/filter, create/edit + image upload, soft delete
- **Pesanan + verifikasi** (4 task) — index, detail, verifikasi bayar, input resi
- **Settings** (2 task) — bank accounts + store info, installment schemes
- **Wire FE→BE** (3 task) — POST /checkout, POST /upload, token-protect /upload + /track
- **WA stub** (1 task) — write event ke `wa_notifications`, gateway later
- **QC** (3 task) — admin visual review, backend integration tests, M2 sign-off + nginx Lighthouse re-audit

**Decisions klien yang masih perlu konfirmasi sebelum task tertentu:**
- WA gateway provider (Fonnte / Wablas / lainnya) — bukan blocker M2, tapi blocker M3 (customer reminder)
- Order status workflow final — usulan: `pending → awaiting_payment → payment_review → verified → packed → shipped → delivered → cancelled` — minta confirm via Naufalix
- Image upload storage — M2 default ke local disk `storage/app/public/products/`, switchable via `.env` filesystem driver

**Carry over ke M3 (Affiliate System):**
- Replace WA stub dengan provider integration (after gateway dipilih)
- Replace ongkir manual input → Agenwebsite.com API
- Wire affiliate.* domain + register flow
- Webhook HMAC-SHA256 Store→Affiliate untuk `order-paid` / `order-refunded`

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
