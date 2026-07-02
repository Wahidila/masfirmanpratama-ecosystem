# Plan — Blog / Artikel (Store) · 2 Juli 2026

> **PRD:** `product-development/features/blog/PRD.md`
> **Branch:** `worktree-feat+blog` (worktree paralel; merge ke `main` saat selesai)
> **App:** `store/` (Laravel 11) · **Scope v1:** Core + **WordPress migration** (F1–F13 must, F8–F9 should)
> **Prinsip:** meniru pola `Product`/`Admin\ProductController` yang sudah ada — jangan reinvent.
> **Konteks migrasi:** situs lama WordPress (~74 artikel, 6 kategori, Yoast SEO). Requirement: export WXR → import. Lihat PRD §10 + Fase 7–8 di bawah.

---

## 0. Konteks arsitektur (yang sudah ada, jadi acuan)

- `Product` model: `SoftDeletes`, slug, `meta_seo` array cast, `status`, `image_path`. → `Post` meniru ini.
- Admin CRUD: `Route::resource('products', ...)` + `products/bulk` + `products/{product}/restore`. → replikasi untuk `posts`.
- Public slug routing: `/produk/{slug}` → `ProductController@show`. → `/blog/{slug}` → `BlogController@show`.
- Views: `resources/views/pages/*` (publik) + `resources/views/admin/*` (admin), pakai `layouts/*`.
- Upload gambar: mekanisme `UploadController`/storage existing → reuse untuk featured image.
- Middleware: group `admin.` + `auth:admin` sudah ada di `routes/web.php`.

---

## Fase 1 — Data layer (model + migration + factory + seeder)

**Migrations** (`store/database/migrations/`) — schema sudah disiapkan untuk WP import:
- `create_posts_table`: `id, slug (unique), title, excerpt (text nullable), content (longText), image_path (nullable), status (enum: draft/published/scheduled, default draft), published_at (nullable timestamp), meta_seo (json nullable), reading_minutes (nullable), views (unsigned default 0), timestamps, softDeletes`.
  - **Kolom kompat WP:** `wp_post_id (unsignedBigInt nullable, UNIQUE)`, `wp_author_login (nullable)`, `wp_guid (nullable)`, `canonical_url (nullable)`, `legacy_url (nullable, index)`, `primary_category_id (unsignedBigInt nullable, FK blog_categories)`.
- `create_blog_categories_table`: `id, slug (unique), name, description (nullable), parent_id (nullable self-FK, hierarki WP), wp_term_id (nullable unique), timestamps, softDeletes`.
- `create_blog_tags_table`: `id, name, slug (unique), wp_term_id (nullable unique), timestamps`.
- `create_category_post_table`: pivot `post_id, blog_category_id` (+ unique pasangan).
- `create_tag_post_table`: pivot `post_id, blog_tag_id` (+ unique pasangan).
- `create_blog_media_table`: `id, wp_post_id (attachment id, nullable unique), disk_path, original_url, original_path, mime_type, width (nullable), height (nullable), timestamps` — lacak gambar rehosted (download-once, idempotent).
- (F8) `create_post_product_table`: pivot `post_id, product_id` untuk CTA produk terkait.

**Models** (`store/app/Models/`):
- `Post`: `HasFactory, SoftDeletes`; fillable + kolom wp_*; casts `meta_seo=>array`, `published_at=>datetime`, `views=>integer`; relasi `categories()`, `tags()`, `products()` (belongsToMany), `primaryCategory()` (belongsTo); scope `published()` = `status=published AND published_at<=now()`; scope `search()`; auto-slug via booted/observer.
- `BlogCategory`: `HasFactory, SoftDeletes`; relasi `posts()`, `parent()`/`children()` (hierarki).
- `BlogTag`: relasi `posts()`.
- `BlogMedia`: helper resolve `wp_post_id`/`original_path` → `disk_path`.

**Factory + Seeder:** `PostFactory`, `BlogCategoryFactory`, `BlogTagFactory`, `BlogSeeder` (3–5 artikel + 6 kategori nyata dari situs lama + tag, minimal 1 published/1 draft/1 scheduled).

**Checkpoint:** `php artisan migrate:fresh --seed` sukses; `Post::published()->count()` benar.

---

## Fase 2 — Admin CRUD (`/admin/posts`)

**Controller:** `Admin\PostController` (meniru `Admin\ProductController`):
- `index` (filter status/kategori, search, paginate, withTrashed toggle), `create`, `store`, `edit`, `update`, `destroy` (soft delete), `restore`, `bulk`.
- Validasi: title required, slug unique (auto jika kosong), body required, image opsional, status in enum, published_at required jika scheduled, category_ids exists.
- Handle upload featured image (reuse pola produk), simpan `meta_seo` sebagai array.
- Hitung `reading_minutes` dari word count body saat save.

**Controller:** `Admin\BlogCategoryController` (resource ringkas: index/store/update/destroy).

**Routes** (`routes/web.php`, di dalam group `auth:admin` existing):
```
Route::post('posts/bulk', [AdminPostController::class,'bulk'])->name('posts.bulk');
Route::post('posts/{post}/restore', [AdminPostController::class,'restore'])->name('posts.restore')->withTrashed();
Route::resource('posts', AdminPostController::class);
Route::resource('blog-categories', AdminBlogCategoryController::class)->except(['show','create','edit']);
```

**Views** (`resources/views/admin/posts/`): `index`, `create`, `edit`, `_form` partial. Kategori: kelola inline atau halaman ringkas. Tambah link "Blog" di sidebar/nav admin.

**Editor:** integrasikan WYSIWYG ringan (Trix/TipTap — **butuh keputusan Q1**) di `_form`. Sanitasi HTML server-side saat store/update (allowlist tag; cegah XSS).

**Checkpoint:** admin bisa CRUD + draft/publish + restore lewat browser (preview server).

---

## Fase 3 — Halaman publik (`/blog`)

**Controller:** `BlogController`:
- `index`: `Post::published()` + eager load categories, filter `?category=`, search `?q=`, paginate(9/12). Sort `published_at desc`.
- `show`: bind by slug, hanya published (404 kalau draft/scheduled), increment `views`, ambil related posts (se-kategori) + related products (CTA).

**Routes** (publik, dekat route `/produk`):
```
Route::get('/blog', [BlogController::class,'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class,'show'])->name('blog.show');
```

**Views** (`resources/views/pages/blog/`):
- `index`: grid card (thumbnail/judul/excerpt/kategori/tanggal), filter kategori, pagination — reuse komponen card existing.
- `show`: hero image, judul, meta (tanggal/kategori/penulis/reading time), body rendered, CTA produk terkait, related posts, tombol share. Set `<title>`, meta description, OG tags dari `meta_seo`.

**Nav:** tambah link "Blog"/"Artikel" di navbar layout publik.

**Checkpoint:** `/blog` + `/blog/{slug}` tampil rapi (desktop + mobile via preview_resize), draft/scheduled tidak bocor.

---

## Fase 4 — SEO & funnel (should)

- **OG/meta tags** per artikel (title, description, image) di `show`.
- **Sitemap:** tambahkan artikel published ke sitemap (integrasi setup SEO existing / route `/sitemap.xml`).
- **RSS feed:** `/blog/feed` (opsional).
- **Related product CTA:** render produk/kelas terkait di dalam artikel (F8).
- **Scheduled publish:** command `posts:publish-scheduled` (flip scheduled→published saat waktunya) + jadwalkan di `routes/console.php`/scheduler. Alternatif: scope `published()` sudah cukup jika hanya andalkan `published_at<=now`.

**Checkpoint:** view-source artikel punya meta+OG; artikel muncul di sitemap.

---

## Fase 7 — WordPress WXR Importer (INTI MIGRASI)

> Referensi mapping lengkap: PRD §10.2–§10.3.

**Service** `store/app/Services/Blog/WxrImporter.php`:
- Parse **channel** dulu (`XMLReader`/`SimpleXML`): `wp:category`→`BlogCategory` (resolve `parent_id` dari `category_parent` slug, 2-pass), `wp:tag`→`BlogTag`, `wp:author`. `firstOrCreate` keyed `wp_term_id`.
- Stream **`<item>`**: filter `wp:post_type=post` (skip page/nav/revision/auto-draft). `updateOrCreate(['wp_post_id'=>…])`.
- Map field: title/slug/content/excerpt/status/tanggal (guard `0000-00-00`), attach `categories()`/`tags()` via `nicename`→slug (switch pada atribut `domain`).
- **CDATA-safe:** jangan `html_entity_decode` body; jangan utak-atik postmeta serialized.
- **2-pass remap:** build map `old_id→new_id` untuk semua item, lalu resolve `_thumbnail_id`, `primary_category`, dan rewrite URL gambar inline.

**Media rehost** (`--media`): download attachment `wp:attachment_url` (fallback `guid`) → disk `public` di `blog/uploads/{_wp_attached_file}` → row `blog_media` (skip jika ada, kecuali `--force`). Rewrite `<img src>`/`srcset` + featured image dari `wp-content/uploads/...` → `/storage/blog/uploads/...` (strip suffix `-WxH` untuk cari original).

**Command** `store/app/Console/Commands/ImportWordpressBlog.php`:
`blog:import-wordpress {file} {--dry-run} {--media} {--force}`. `--dry-run` = parse penuh + print tabel preview (create/update, kategori, tag, media, slug collision) tanpa tulis DB/disk.

**UI admin:** `GET/POST /admin/posts/import` (`PostController@importForm/@import`) — upload `.xml` (`mimes:xml,txt`, max size), simpan temp, jalankan dry-run → preview → tombol "Confirm import" → dispatch queued job. Tombol "Import WordPress" di toolbar `/admin/posts`.

**Checkpoint:** import file WXR nyata (atau fixture) → ~74 artikel + 6 kategori + tag + gambar masuk, status & tanggal benar, **re-run tidak duplikat**, tak ada URL gambar nunjuk domain lama.

---

## Fase 8 — Legacy URL 301 & SEO cutover

> ✅ Keputusan **Q5** (2 Jul 2026): **`/blog/{slug}` + 301** dari root lama.

- **Catch-all 301:** `GET /{slug}` (registered TERAKHIR di `web.php`, constraint exclude `blog|produk|kelas|admin|storage|checkout|cart|track|upload`) → lookup `Post` by slug → `redirect()->route('blog.show',$slug,301)`. Terima `/{slug}` & `/{slug}/`.
- Redirect `/category/{slug}`, `/tag/{slug}`, feed lama → ekuivalen baru / `/blog`.
- **Canonical** self-referential ke `/blog/{slug}` di `show`.
- **Sitemap blog** (`sitemap-blog.xml`, `lastmod`=`updated_at`) + daftarkan di sitemap index + `robots.txt`.
- **Verifikasi:** cek status 301 (bukan 302), canonical baru, submit sitemap ke Google Search Console.

**Checkpoint:** akses URL lama `/{slug}/` → 301 ke `/blog/{slug}`; sitemap valid.

---

## Fase 5 — Testing & QC

- **Feature tests:** admin CRUD (create/update/publish/soft-delete/restore/bulk), auth guard (`auth:admin`), publik index (hanya published), show (404 untuk draft), filter kategori & tag, slug unik, related CTA.
- **Importer tests (fixture WXR kecil):** parse kategori hierarkis + tag, status mapping (publish→published, future→scheduled, trash→soft-delete), idempotency (re-run tak duplikat), remap `_thumbnail_id`, rewrite URL gambar, guard `0000-00-00`.
- **Redirect tests:** `/{slug}/` → 301 `/blog/{slug}`; reserved prefix tidak ke-redirect.
- **Unit:** scope `published()`, auto-slug, reading time, sanitasi HTML.
- **Regresi:** `php artisan test` full suite tetap hijau. Target total ≥ 20 test.
- **QC manual:** preview server — alur admin + import + tampilan publik desktop/mobile.

**Checkpoint:** semua test PASS, acceptance criteria PRD §9 tercentang.

---

## Fase 6 — Merge & deploy

- Update `AGENTS.md` (tambah modul Blog + importer di ringkasan Store).
- Commit rapi per fase di branch `worktree-feat+blog`.
- **Merge ke `main`** (setelah session paralel lain juga aman) → riwayat blog "jadi satu" dengan main.
- Migration & seeder masuk runbook deploy. **Runbook migrasi:** export WXR dari WP → jalankan import (`--media`) **sebelum** cutover DNS → verifikasi 301 → submit sitemap.

---

## Urutan eksekusi & dependency

```
Fase 1 (data) → Fase 2 (admin) → Fase 3 (publik) → Fase 4 (SEO/funnel)
                                          ↓
                            Fase 7 (WXR importer) → Fase 8 (301/SEO cutover)
                                          ↘  Fase 5 (test) jalan paralel tiap fase
Fase 6 (merge) paling akhir.
```
Fase 7 butuh Fase 1 (schema) minimal; idealnya setelah Fase 3 agar hasil import bisa langsung dilihat di `/blog`.

## Keputusan
- ✅ **Q5 (2 Jul 2026):** URL artikel = **`/blog/{slug}` + 301** dari root lama. **FIXED.**
- **Q1:** editor rich text — rekomendasi **Trix** (belum di-lock).
- **Q2:** single vs multi-author — rekomendasi **single (Firman)** (belum di-lock).
- **Q6 (operasional):** jadwalkan import gambar **sebelum** cutover DNS.

## Out of scope v1 (→ v2)
Komentar (F15), newsletter (F16), multi-author roles, like/reaction.
*(Tags & WordPress import NAIK jadi in-scope v1.)*
