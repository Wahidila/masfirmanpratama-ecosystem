# PRD — Blog / Artikel masfirmanpratama.com

> **Status:** Draft · **Author:** AI Agent (session paralel `feat/blog`) · **Created:** 2 Juli 2026
> **Branch kerja:** `worktree-feat+blog` (worktree terisolasi, merge ke `main` saat selesai)
> **App target:** `store/` (Laravel 11 — `masfirmanpratama.com`)

---

## 1. Summary

Menambahkan modul **Blog/Artikel** ke Store masfirmanpratama.com: admin bisa menulis & mengelola artikel (draft/publish, kategori, tag, featured image, SEO), dan pengunjung bisa membaca daftar artikel + halaman detail. Tujuannya jadi kanal konten SEO + edukasi (Mind Power & Life Mastery) yang menarik trafik organik dan menggiring pembaca ke produk (kelas + buku).

**Konteks migrasi:** situs lama `masfirmanpratama.com` saat ini **berbasis WordPress** (tema *Strategist Pro*, SEO plugin *Yoast*) dengan **~74 artikel published** di 6 kategori aktif. Requirement klien: migrasi konten dilakukan cukup dengan **export dari WordPress (format WXR/XML) lalu import** ke site baru — tanpa copy-paste manual. Karena itu modul ini mencakup **WordPress WXR Importer** + strategi 301 redirect agar SEO artikel lama tidak hilang. Lihat §10.

---

## 2. Contacts

| Nama | Role | Komentar |
|------|------|----------|
| Firman Pratama | Klien / Product Owner | Decision maker final + penulis konten |
| Rezvi | Lead MC | Coordinator, requirement gathering |
| Naufalix | Developer | Review implementasi |
| AI Agent (`feat/blog`) | Engineer | Eksekusi kode + testing |
| [TBD] | Tester | QC verification |

---

## 3. Background

### Konteks
Store saat ini punya etalase produk (kelas + buku), checkout manual, tracking order, dan admin panel unified. **Belum ada kanal konten.** Semua trafik masuk lewat iklan/link langsung ke halaman produk — tidak ada aset SEO yang menarik pengunjung baru dari pencarian organik.

### Mengapa Sekarang?
- M1–M5 core commerce sudah 100% compliance (audit terakhir). Fondasi Store stabil → aman menambah modul konten.
- Bisnis AMC (Mind Power & Life Mastery) sangat "content-heavy": banyak materi edukasi yang cocok jadi artikel dan bisa jadi funnel ke kelas/buku.
- SEO organik = akuisisi berbiaya rendah dibanding iklan.

### Sudah Mungkinkah?
Ya. Stack sudah ada semua: Laravel 11 + Blade + Tailwind + Alpine, pola slug-routing & `meta_seo` JSON sudah dipakai `Product`/`Course`, admin panel + `auth:admin` sudah jalan. Blog = modul CRUD tambahan yang meniru pola `Product` yang sudah ada. **Tidak ada dependency eksternal baru.**

---

## 4. Objective

### Tujuan
Menyediakan sistem publikasi artikel yang dikelola admin sendiri (tanpa dev), teroptimasi SEO, dan terintegrasi visual dengan Store existing — sebagai mesin trafik organik + funnel edukasi ke produk.

### Benefit
- **Bisnis:** akuisisi organik murah, aset SEO jangka panjang, funnel artikel → produk, otoritas brand di niche Mind Power.
- **Customer/pembaca:** akses materi edukasi gratis, konteks sebelum membeli kelas/buku.
- **Admin:** kelola konten mandiri (draft, jadwal, edit) tanpa minta dev.

### Key Results (SMART OKR)
| KR | Metric | Target | Measurement |
|----|--------|--------|-------------|
| KR1 | Admin bisa publish artikel end-to-end tanpa dev | 100% alur (buat→draft→publish→tampil) | Manual QC + test |
| KR2 | Halaman publik SEO-ready | Meta title/description, OG tags, slug, sitemap entry per artikel | View source + validator |
| KR3 | Performa listing artikel | < 500ms render, pagination | Query log / benchmark |
| KR4 | Test coverage modul blog | ≥ 15 test (feature + unit) PASS | `php artisan test` |
| KR5 | Funnel artikel → produk | CTA/related-product tampil di ≥ 1 slot per artikel | Manual QC |

---

## 5. Market Segment(s)

### Untuk Siapa?
- **Pencari solusi** yang meng-Google topik Mind Power / self-development / life mastery → belum kenal brand.
- **Calon pembeli yang masih ragu** → butuh edukasi/konteks sebelum beli kelas/buku.
- **Alumni/peserta** → konten lanjutan & retensi.

### Constraints
- **Tech:** shared-hosting friendly (Laravel 11, MySQL), tanpa framework JS terpisah.
- **Konten:** ditulis admin non-teknis → editor harus sederhana, tidak butuh tahu HTML.
- **Timeline:** v1 fokus core; fitur berat (komentar, newsletter) ditunda.
- **Konsistensi:** harus reuse layout/komponen Store existing (jangan bikin desain baru dari nol).

---

## 6. Value Proposition(s)

### Customer Jobs
Pembaca ingin **memahami topik & menilai kredibilitas Firman** sebelum memutuskan ikut kelas / beli buku.

### Gains
- Konten edukatif gratis, mudah dibaca, mobile-friendly.
- Jalur alami dari "baca artikel" ke "produk terkait".

### Pains (yang dihilangkan)
- Tidak ada tempat menemukan brand secara organik (saat ini nol konten).
- Admin tidak punya cara publish konten tanpa developer.

### Competitive Advantage
Terintegrasi langsung dengan store & funnel produk (artikel bisa nempel CTA ke kelas/buku spesifik) — bukan blog terpisah yang putus dari komersial.

---

## 7. Solution

### 7.1 UX / User Flow

**Publik:**
- `/blog` — daftar artikel (card: thumbnail, judul, excerpt, kategori, tanggal), filter kategori, pagination, search sederhana. Struktur mengikuti blog lama (list + sidebar kategori/artikel-terbaru), tapi **visual mengikuti design-system Store baru** (Tailwind + Inter + komponen existing), **bukan** kloning tema WordPress lama.
- `/blog/{slug}` — detail artikel: judul di atas featured image (bukan hero overlay), meta (tanggal, kategori, penulis), isi (rich text), CTA produk terkait, artikel terkait, tombol share.
- Entry point: link "Blog" / "Artikel" di navbar + section "Artikel Terbaru" opsional di homepage.

> **Catatan brand:** blog lama pakai hijau `#60C19F`/`#7fc242` + font Poppins + layout dua kolom (konten + sidebar). Referensi ini dipakai untuk **struktur & taksonomi**, bukan warna/font — site baru punya design-system sendiri (Inter). Konsistensi = ikut Store baru, bukan meniru WordPress lama.

**Admin (`/admin/posts`):**
- Index: tabel artikel (judul, status, kategori, tanggal, aksi), filter status/kategori, bulk action, restore (soft delete) — persis pola `products`.
- Create/Edit: form judul, slug (auto dari judul, editable), kategori, excerpt, body (rich text editor), featured image upload, status (draft/published/scheduled), tanggal publish, SEO meta (title, description), pilih produk/kelas terkait untuk CTA.
- Preview draft sebelum publish.

### 7.2 Key Features
| # | Feature | Priority | Description |
|---|---------|----------|-------------|
| F1 | Post CRUD (admin) | Must | Create/read/update/delete artikel, soft delete + restore, bulk action |
| F2 | Draft / Published / Scheduled | Must | Status artikel; scheduled tampil otomatis saat `published_at` tercapai |
| F3 | Kategori | Must | Klasifikasi artikel (1 post → 1..n kategori), kelola kategori di admin |
| F4 | Featured image | Must | Upload gambar hero (reuse `UploadController`/storage existing) |
| F5 | Slug + SEO meta | Must | Slug unik dari judul, `meta_seo` JSON (title/description), OG tags |
| F6 | Public listing + detail | Must | `/blog` + `/blog/{slug}`, pagination, filter kategori |
| F7 | Rich text editor | Must | Editor WYSIWYG ringan (mis. Trix/Quill/TipTap) untuk admin non-teknis |
| F8 | Related product CTA | Should | Nempel kelas/buku terkait di dalam/akhir artikel (funnel) |
| F9 | Artikel terkait | Should | Rekomendasi artikel se-kategori di halaman detail |
| F10 | Sitemap + RSS | Must | Sitemap blog (lastmod dari modified date) + 301 redirect — wajib untuk SEO migrasi |
| F11 | **WordPress WXR Import** | **Must** | Import ~74 artikel + kategori + tag + gambar dari file export WordPress. **Inti requirement migrasi.** Lihat §10 |
| F12 | **Tags** | **Must** | Tagging many-to-many. Naik dari Could → Must karena artikel lama punya tag (rata-rata 3/artikel) yang harus terbawa |
| F13 | 301 Redirect legacy URL | Must | Redirect `/{slug}/` lama → `/blog/{slug}` baru untuk pertahankan ranking |
| F14 | Reading time / view count | Could | Estimasi waktu baca (Yoast lama punya), hitung view |
| F15 | Komentar pembaca | Won't (v2) | Butuh moderasi + anti-spam — ditunda |
| F16 | Newsletter subscribe | Won't (v2) | Ditunda |

### 7.3 Technology
- **Model:** `Post` (mirip `Product`: `HasFactory`, `SoftDeletes`, slug, `meta_seo` array cast, `status`), `BlogCategory` (hierarkis, `parent_id`), `BlogTag`, `BlogMedia`. Pivot `category_post`, `tag_post`. Relasi opsional `Post` ↔ `Product`/`Course` untuk CTA.
- **Migration:** `posts`, `blog_categories`, `blog_tags`, `category_post`, `tag_post`, `blog_media`, (opsional) `post_product`. Kolom tambahan di `posts` untuk kompatibilitas WP: `wp_post_id` (unique, idempotency), `excerpt`, `content` (longText), `canonical_url`, `wp_author_login`, `wp_guid`, `primary_category_id`, `legacy_url`. SEO Yoast di-nest ke `meta_seo` JSON existing. Detail: §10.2.
- **Controllers:** `Admin\PostController` (Route::resource + bulk/restore + `importForm`/`import`), `Admin\BlogCategoryController`, publik `BlogController` (`index`, `show`), `LegacyRedirectController` (301 root URL lama).
- **Routes:** publik `/blog`, `/blog/{slug}`; admin `admin.posts.*` (+ `admin.posts.import`), `admin.blog-categories.*` di dalam group `auth:admin` existing; catch-all 301 root `/{slug}` (registered terakhir, exclude prefix reserved).
- **Importer:** artisan `blog:import-wordpress {file} --dry-run --media` → service `App\Services\Blog\WxrImporter` (stream `XMLReader`, idempotent via `wp_post_id`). Dipakai juga oleh layar `/admin/posts/import` (queued job). Detail: §10.3.
- **Views:** `resources/views/pages/blog/{index,show}.blade.php` + `resources/views/admin/posts/*` (+ `import.blade.php`), reuse layout & komponen existing.
- **Editor:** WYSIWYG ringan via script tag (Trix — rekomendasi, konsisten pola "Lucide/Alpine via script tag"). Sanitasi HTML output server-side (body import WP disimpan raw, sanitasi saat render).
- **Upload:** reuse disk `public` (`storage/app/public` → `/storage`) spt gambar produk. Import: rehost `wp-content/uploads` ke `blog/uploads/YYYY/MM/` + rewrite URL di body.
- **Scheduled publish:** scope `published()` (`status = published AND published_at <= now`); command/cron flip `scheduled`→`published`.

### 7.4 Non-Goals (v1)
Komentar, newsletter, multi-author roles, like/reaction, AMP. Ditunda ke v2.
*(Catatan: Tags NAIK jadi in-scope v1 karena dibutuhkan untuk migrasi konten lama.)*

---

## 8. Risks & Open Questions

| # | Risiko / Pertanyaan | Catatan |
|---|---------------------|---------|
| Q1 | Editor rich text pilihan mana? | Rekomendasi: **Trix** (ringan, cukup untuk konten WP lama: p/h2/h3/ul/img/link). **Perlu keputusan.** |
| Q2 | Perlu multi-author? | v1 single-author "Firman Pratama" (WP author = `firmanp`, disimpan di `wp_author_login`). |
| Q3 | Sanitasi HTML editor + import | Wajib — cegah XSS. Body import disimpan raw HTML, sanitasi (allowlist tag) saat render. |
| ~~Q5~~ | **URL artikel: root vs `/blog/{slug}`** | ✅ **DIPUTUSKAN (2 Jul 2026): `/blog/{slug}` + 301 redirect** dari root lama. Lihat §10.4 |
| **Q6** | **Import gambar sebelum/sesudah cutover DNS?** | **HARUS sebelum cutover** — importer download gambar dari `wp-content/uploads` situs lama yang masih online. Kalau DNS sudah pindah, gambar 404. |
| Q4 | Seed vs import langsung | Import WP jadi sumber utama konten. Seed dummy cukup untuk dev/test. |
| R1 | Konsistensi desain dengan Store | Mitigasi: reuse `layouts/*` + komponen existing, ikut design-system baru (Inter/Tailwind), bukan kloning WP. |
| R2 | SEO regression saat migrasi | Mitigasi: 301 (bukan 302), canonical self-referential ke URL baru, sitemap baru submit ke Search Console. Lihat §10.4 |

---

## 9. Acceptance Criteria (v1 Done)

- [ ] Admin bisa buat, edit, draft, publish, hapus (soft delete), restore artikel dari `/admin/posts`.
- [ ] Kategori bisa dikelola & di-assign ke artikel.
- [ ] Featured image ter-upload & tampil.
- [ ] `/blog` menampilkan artikel published, paginated, filter kategori.
- [ ] `/blog/{slug}` menampilkan artikel lengkap + meta SEO + OG tags + CTA produk terkait.
- [ ] Artikel `draft`/`scheduled` (belum waktunya) TIDAK tampil di publik.
- [ ] Link Blog muncul di navbar.
- [ ] **Import 1 file WXR WordPress berhasil**: ~74 artikel + 6 kategori + tag + gambar ter-import, status ter-map benar (publish→published), tanggal asli terjaga.
- [ ] **Import idempotent**: re-run file yang sama tidak menghasilkan duplikat.
- [ ] **Gambar featured + inline ter-rehost** ke `/storage`, tidak ada URL yang masih menunjuk `wp-content/uploads` domain lama.
- [ ] **301 redirect**: URL root lama `/{slug}/` → `/blog/{slug}` (status 301, bukan 302).
- [ ] Canonical tiap artikel self-referential ke URL baru; artikel published masuk sitemap blog.
- [ ] ≥ 20 test PASS (`php artisan test`) — termasuk test importer & redirect.
- [ ] Tidak ada regresi pada modul existing (test suite lama tetap hijau).

---

## 10. Migrasi dari WordPress (WXR Import)

> **Sumber:** analisis situs lama (WordPress + tema Strategist Pro + Yoast SEO) & riset format WXR — lihat `docs_dev/plans/2026-07-02-blog-feature.md` §7–§10.

### 10.1 Kondisi situs lama (fakta terverifikasi)
- Platform **WordPress**, REST API `/wp-json/wp/v2/` masih aktif, SEO oleh **Yoast**.
- **~74 artikel published**, permalink di **root**: `https://masfirmanpratama.com/{slug}/` (trailing slash).
- **6 kategori aktif:** Mindset dan Spiritualitas (24), Kekuatan Pikiran (21), Pengembangan Diri (slug `kualitas-diri`, 19), Kekayaan (15), Alpha Mind Control (4), Keluarga Bahagia (3).
- **Tag** dipakai (rata-rata ~3 tag/artikel).
- Konten body: HTML Gutenberg (`<p> <h2> <h3> <ul> <a> <strong>`), gambar inline dari `wp-content/uploads/YYYY/MM/` (jpg/png/webp) + featured image terpisah.
- Author tunggal `firmanp` (Firman Pratama).

### 10.2 Format export & mapping (WXR → Laravel)
Klien export via **WordPress admin → Tools → Export → "All content"** → file **WXR** (XML, RSS 2.0 + namespace `wp/content/excerpt/dc`). Ringkasan mapping (lengkap di plan §8):

| WXR | → | Laravel |
|-----|---|---------|
| `wp:post_id` | → | `posts.wp_post_id` (idempotency + remap id) |
| `title` / `wp:post_name` | → | `posts.title` / `posts.slug` |
| `content:encoded` | → | `posts.content` (raw HTML, rewrite URL gambar) |
| `excerpt:encoded` | → | `posts.excerpt` |
| `wp:status` | → | `posts.status` (publish→published, future→scheduled, trash→soft-delete) |
| `wp:post_date_gmt` | → | `posts.published_at` (guard `0000-00-00`) |
| `wp:post_modified_gmt` | → | `posts.updated_at` (buat sitemap lastmod) |
| `dc:creator` | → | `posts.wp_author_login` |
| `category domain="category"` | → | pivot `category_post` (match `nicename`→slug) |
| `category domain="post_tag"` | → | pivot `tag_post` |
| `_thumbnail_id` (postmeta) | → | `posts.image_path` (remap attachment, 2-pass) |
| `_yoast_wpseo_title/metadesc/canonical/og-*` | → | `posts.meta_seo` JSON |
| `_yoast_wpseo_primary_category` | → | `posts.primary_category_id` (remap term id) |
| item `attachment` + `wp:attachment_url` | → | download → `blog_media` + disk `public` |

**Gotcha kunci:** semua nilai CDATA (jangan `html_entity_decode` body); `_thumbnail_id`/`primary_category`/`post_parent` menunjuk **ID lama** → wajib remap 2-pass (build map old→new dulu, baru resolve); `post_date_gmt` sering `0000-00-00` untuk draft → null-guard; postmeta serialized (`_wp_attachment_metadata`) jangan diutak-atik (byte-length prefix).

### 10.3 Importer
- **CLI:** `php artisan blog:import-wordpress path/to/export.xml --media --dry-run`
- **Service:** `WxrImporter` — stream `<item>` via `XMLReader` (aman untuk file besar / split), phase-1 ingest kategori+tag+author dari `<channel>`, phase-2 upsert post + attach taksonomi + rehost gambar, lalu remap featured/primary/inline-URL.
- **Idempotent:** `updateOrCreate(['wp_post_id'=>…])`, media `firstOrCreate` — re-run aman.
- **`--dry-run`:** parse penuh + tampilkan tabel preview (post to create/update, kategori, tag, gambar, slug collision) **tanpa** tulis DB/disk.
- **UI admin:** `/admin/posts/import` (upload `.xml`) → tampilkan preview dry-run → tombol "Confirm import" → queued job. Tombol "Import" ada di toolbar `/admin/posts` (sebelah "Add Post").

### 10.4 Strategi URL & SEO (KRUSIAL) — ✅ DIPUTUSKAN: `/blog/{slug}` + 301
1. **URL baru:** artikel di **`/blog/{slug}`** (slug dipertahankan persis dari WP).
2. **301 redirect:** satu catch-all `GET /{slug}` (registered terakhir, exclude prefix `blog|produk|admin|storage|checkout|kelas`) → lookup `posts.slug` → `redirect()->route('blog.show',$slug, 301)`. Terima `/{slug}` dan `/{slug}/`. Redirect juga `/category/*`, `/tag/*`, feed lama → ekuivalen baru / `/blog`.
3. **Canonical:** tiap artikel render `<link rel="canonical">` ke URL **baru** `/blog/{slug}` (Yoast canonical lama disimpan di `meta_seo` untuk audit saja).
4. **Sitemap:** generate `sitemap-blog.xml` (semua published, `lastmod` = `updated_at`), submit ke Google Search Console; sitemap lama 301 ke baru. `robots.txt` expose sitemap baru.

> **Keputusan (2 Jul 2026):** dipilih `/blog/{slug}` + 301. Alasan: root dimiliki Store (produk, halaman, checkout) — hosting ~74 slug artikel di root rawan tabrakan route & fragile. `/blog/{slug}` + 301 = aman secara teknis, SEO tetap 100% terjaga (Google transfer ranking lewat 301).

### 10.5 Risiko migrasi
- **Timing gambar (utama):** rehost gambar **sebelum** cutover DNS — importer download dari `wp-content/uploads` situs lama yang masih online. Alternatif: backup folder `uploads` dulu.
- **Suffix ukuran responsif** (`-1024x585`) di URL inline ≠ file "full" → importer strip suffix `-WxH` untuk cari original.
- **SEO regression window:** salah konfigurasi 301/canonical bisa turunkan ranking → verifikasi 301 (bukan 302) + canonical baru + submit sitemap.
- **Re-import authoritative:** field dari WP menimpa edit lokal saat re-import; dokumentasikan / guard "jangan timpa post yang sudah diedit lokal".
