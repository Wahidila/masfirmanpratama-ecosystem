# Task Plan — t_2dce058d · Produk CRUD form

> Source kanban: `t_2dce058d` — Produk CRUD · Create/edit form + image upload + validation
> Branch: `feat/t_2dce058d-products-crud-form`
> Sprint: M2 (Admin Panel + Wire FE→BE)
> Started: 2026-05-19

## Goals (acceptance dari kanban)

- [ ] Bisa CRUD produk full (list, create, show, edit, delete)
- [ ] Image upload + preview di form
- [ ] Validation message Bahasa Indonesia
- [ ] Redirect ke index dengan flash message
- [ ] Soft delete aware (kolom `deleted_at` di-respect)
- [ ] Auth `admin` guard middleware

## Field spec

| Field        | Tipe / rule                                                                  |
|--------------|------------------------------------------------------------------------------|
| `title`      | string, required, max 200                                                    |
| `slug`       | string, required, kebab-case, unique (kecuali edit current). Auto dari title |
| `type`       | enum `book` / `course`, required                                             |
| `price`      | numeric ≥ 0, required                                                        |
| `stock`      | integer ≥ 0, required                                                        |
| `status`     | enum `draft` / `active` / `archived`, required                               |
| `image`      | file, image jpg/png/webp, max 2MB, dimensi min 800x800                       |
| `description`| string nullable, plain text/markdown (Tiny opsional, M2 simple textarea dulu)|
| `meta_seo`   | array — keys `title`, `description`, optional                                |

## Architecture

```
routes/web.php
  ├── admin.products.index   GET    /admin/products
  ├── admin.products.create  GET    /admin/products/create
  ├── admin.products.store   POST   /admin/products
  ├── admin.products.show    GET    /admin/products/{product}      [optional]
  ├── admin.products.edit    GET    /admin/products/{product}/edit
  ├── admin.products.update  PUT    /admin/products/{product}
  └── admin.products.destroy DELETE /admin/products/{product}

app/Http/Controllers/Admin/ProductController.php  (resource)
app/Http/Requests/Admin/StoreProductRequest.php
app/Http/Requests/Admin/UpdateProductRequest.php

resources/views/admin/products/
  ├── index.blade.php
  ├── create.blade.php
  ├── edit.blade.php
  └── _form.blade.php  (partial DRY)
```

## Decisions

| # | Decision | Rationale |
|---|----------|-----------|
| 1 | Pakai `getRouteKeyName() = 'slug'` yang sudah ada di Product model | Konsisten sama route public `/produk/{slug}`. Admin pun pakai slug supaya URL human-readable. |
| 2 | Image storage `public` disk, path `products/{slug}/{filename}` | Public disk udah default Laravel 11, `php artisan storage:link` bikin akses via `/storage/...`. Slug-based folder biar gampang di-trace per produk. |
| 3 | Soft delete tetep ON (model udah pakai `SoftDeletes`) | DELETE action soft-delete only — restore + bulk delete handle di task next (`t_e51df9e5`). Form CRUD ini cuma trigger soft delete. |
| 4 | Description: plain textarea dulu, no TinyMCE M2 | Sesuai brief task ("rich text minimal — TinyMCE atau plain textarea"). TinyMCE bisa drop later kalau klien minta. |
| 5 | Slug auto-fill via Alpine.js `kebabCase(title)` di create form, editable di edit | UX standar admin panel modern. |
| 6 | Validation message via `messages()` di FormRequest, semua Bahasa Indonesia | Sesuai konvensi project (`store.blade.php` + admin existing pakai Indonesian). |
| 7 | Image dimension validation: pakai `dimensions:min_width=800,min_height=800` Laravel rule | Built-in, ngga perlu Intervention/Image untuk validate aja. |
| 8 | Sidebar: pindah "Produk" dari coming-soon ke primary nav, route `admin.products.index` | Pertama feature M2 yang siap, jadi unlock sidebar item. |

## Errors Encountered

| # | Symptom | Root cause | Fix |
|---|---------|-----------|-----|
| 1 | `LogicException: GD extension is not installed` saat `UploadedFile::fake()->image()` | PHP 8.2 di VPS belum install `php8.2-gd` — fresh Ubuntu instal default tanpa GD | `apt-get install -y php8.2-gd`. Documented sebagai prerequisite di laravel-11 pitfall reference. |
| 2 | `assertDatabaseHas` fail di `test_store_creates_product_with_image` — slug yang ke-save `buku-test`, bukan `buku-mind-power-101` | `validPayload()` default override slug, jadi `prepareForValidation` ngga regen dari title | Test override slug eksplisit di payload. (Logic controller bener — dokumentasi `prepareForValidation` cuma fallback ke title kalau slug kosong.) |

## Phase

- [x] Phase 0: orientasi workspace + read existing scaffold
- [x] Phase 1: branch + plan doc
- [x] Phase 2: routes + controller skeleton
- [x] Phase 3: FormRequest pair + validation message Indonesian
- [x] Phase 4: Blade form partial + create/edit/index views
- [x] Phase 5: Sidebar nav update
- [x] Phase 6: storage:link + manual smoke test (skipped — covered by feature test)
- [x] Phase 7: Feature test (PHPUnit) — 22/22 pass
- [x] Phase 8: self-review checklist + handoff QC

## Self-Review Checklist

- [x] Build pass tanpa warning (Vite + Pint clean)
- [x] Test pass (104/104, 22 baru di ProductCrudTest)
- [x] Lint pass (Laravel Pint — 1 fix auto-applied di ProductFactory)
- [x] No hardcoded secret / URL prod
- [x] Image upload validation (size 2MB, mime jpg/png/webp, dimension 800x800 min)
- [x] CSRF enabled (Blade `@csrf`, Laravel default middleware)
- [x] Slug uniqueness aware soft-delete (`whereNull('deleted_at')`)
- [x] Old image dihapus dari disk saat di-replace
- [x] Auth guard `admin` enforced via middleware group
- [x] Validation message full Bahasa Indonesia
- [x] Responsive (sm/lg breakpoints di form + table)
- [x] Accessibility basics — label-for, required indicator, focus-visible (inherit dari shell)
- [x] Acceptance criteria task ke-meet (CRUD full, image preview, message Indonesian, redirect + flash)
