# Upstream archive — naufalix/affiliate (pre-MC intake)

Snapshot lengkap branch `main` upstream `naufalix/affiliate` per commit
`c8e166e2e361372121ec2f429166f72d7ecc87d8` (2026-05-16, "Merge pull
request #1 from naufalix/project-plan").

## Kenapa ada di sini

Saat Malang Creative intake project `masfirmanpratama` (16 Mei 2026),
folder lokal di-bootstrap dari intake brief — bukan `git clone` upstream.
Akibatnya history MC dan history upstream punya root commit terpisah
(unrelated histories), tidak punya merge base.

Saat recovery push M1 store FE (17 Mei 2026), keputusan: **force push**
local sebagai source of truth baru, sambil **archive** seluruh tree
upstream lama di sini supaya tidak hilang.

## Isi

- `prototype/` — HTML prototype affiliate + admin + dashboard (44 file)
  - `admin.html`, `affiliate.html`, `affiliator-*.html`, `dashboard-*.html`,
    `register*.html`, `login.html`, `book-detail.html`, `cart.html`,
    `checkout*.html`, `course-detail.html`, `index.html`,
    `product-detail.html`
  - `REVAMP_PLAN_AFFILIATE.md`, `prototype_plan.md`, `revamp.py`
  - `assets/css/`, `assets/js/`, `assets/images/`
- `Firman Pratama - Expert in Mind Power & Life Mastery.html` +
  `_files/` — snapshot landing klien existing (96 asset Elementor /
  WordPress)
- `DESIGN.md` — design tokens versi upstream
- `implementation.md`, `implementation_plan.md.resolved` — dokumen plan
  versi upstream
- `.sisyphus/plans/project-plan-30days.md` +
  `.sisyphus/plans/project-plan-timeline.html` — 30-day timeline klien
- Image assets root-level (logo, foto klien, media coverage)

## Status

⛔ **READ-ONLY archive.** Jangan edit konten di sini.

✅ Working copy aktif:
- `prototype/` (root) — subset prototype yang relevan untuk Store M1,
  dipakai sebagai referensi saat porting ke Blade.
- `store/` — Laravel 11 implementation M1 store FE.
- `docs/qc/M1/` — visual + Lighthouse audit M1.

## Recovery

Kalau perlu tarik file balik dari archive:

```bash
cp "docs/upstream-archive/prototype/affiliate.html" prototype/
cp "docs/upstream-archive/implementation.md" docs/specs/
```

History upstream lama (5 commit pre-MC) masih ada di GitHub di tag
`upstream-pre-mc` (akan ditambahkan saat force push).
