# File Structure — MasFirmanPratama Ecosystem

> **Purpose:** Peta navigasi cepat untuk agent. Sebelum eksplorasi codebase, baca file ini dulu.
> **Path:** `D:\laragon\www\masfirmanpratama\`
> **Updated:** 26 Juni 2026

---

## Root Layout

```
masfirmanpratama/
├── store/                  → App 1: Online Store (port 8000)
├── affiliate/              → App 2: Affiliate System (port 8001)
├── docs/                   → Dokumentasi + QC artifacts
├── docs_dev/               → Dev docs: plans, runbook, reference
├── product-development/    → PRD, plan, JTBD, templates
├── prototype/              → Prototype HTML (referensi UI)
├── AGENTS.md               → Project context + decision log (auto-loaded)
├── .env.example            → Root env template
└── .gitignore
```

---

## Store App (`store/`)

Laravel 11 — Online Store + Admin Panel terintegrasi.

### app/

```
app/
├── Console/
│   └── (no custom commands — uses default Laravel)
├── Events/
│   ├── OrderShipped.php              → Fire saat admin input resi
│   ├── PaymentRejected.php           → Fire saat admin reject bukti bayar
│   ├── PaymentSubmitted.php          → Fire saat customer upload bukti
│   └── PaymentVerified.php           → Fire saat admin approve bayar
├── Exceptions/
│   └── ShippingRateException.php     → Custom exception untuk shipping API error
├── Helpers/
│   └── MenuHelper.php                → Sidebar nav config untuk TailAdmin
├── Http/
│   ├── Controllers/
│   │   ├── (Public)
│   │   │   ├── CheckoutController.php       → Checkout buku (lunas/cicilan)
│   │   │   ├── CourseCheckoutController.php → Checkout kelas (lunas/cicilan)
│   │   │   ├── CourseController.php         → Detail kelas (/kelas/{slug})
│   │   │   ├── HomeController.php           → Homepage
│   │   │   ├── PageController.php           → Static pages (tentang, kontak)
│   │   │   ├── ProductController.php        → Detail buku (/buku/{slug})
│   │   │   ├── ShippingRateController.php   → API hitung ongkir (AJAX)
│   │   │   ├── TrackController.php          → Tracking order tanpa login
│   │   │   └── UploadController.php         → Upload bukti pembayaran
│   │   ├── Admin/
│   │   │   ├── AuthController.php           → Login/logout admin
│   │   │   ├── CourseController.php         → CRUD kelas (full resource)
│   │   │   ├── DashboardController.php      → Dashboard + chart data
│   │   │   ├── InstallmentSchemeController.php → CRUD skema cicilan
│   │   │   ├── OrderController.php          → List/detail/verify/ship orders
│   │   │   ├── ProductController.php        → CRUD produk (full resource)
│   │   │   ├── SettingsController.php       → Settings: bank, shipping, WA, store info
│   │   │   └── WaNotificationController.php → WA notification log viewer
│   │   └── Webhooks/
│   │       └── AwbCallbackController.php    → Receive AWB callback dari Agenwebsite
│   ├── Middleware/
│   │   └── (uses Laravel default — auth:admin guard via bootstrap/app.php)
│   └── Requests/
│       └── Admin/
│           ├── StoreCourseRequest.php       → Validation create course
│           ├── StoreProductRequest.php      → Validation create product
│           ├── UpdateCourseRequest.php      → Validation update course
│           └── UpdateProductRequest.php     → Validation update product
├── Listeners/
│   ├── DispatchAffiliateOrderPaid.php       → Webhook ke affiliate saat payment verified
│   ├── SendAdminPaymentReviewAlert.php      → WA alert ke admin saat upload bukti
│   ├── SendCustomerOrderShippedNotification.php → WA ke customer saat resi input
│   ├── SendCustomerPaymentRejectedNotification.php → WA ke customer saat bayar ditolak
│   ├── SendCustomerPaymentVerifiedNotification.php → WA ke customer saat bayar verified
│   └── SendOrderShippedEmail.php            → Email resi ke customer
├── Mail/
│   └── OrderShippedMail.php                 → Mailable untuk order shipped email
├── Models/
│   ├── Admin.php                            → Admin user (auth:admin guard)
│   ├── Course.php                           → Kelas (AMC Reguler, Platinum, dll)
│   ├── InstallmentScheme.php                → Skema cicilan (DP%, N installment, interval)
│   ├── Order.php                            → Order header (status, customer, shipping)
│   ├── OrderItem.php                        → Order line item (product_id OR course_id)
│   ├── OrderPayment.php                     → Payment per cicilan (proof, status, verified_by)
│   ├── Product.php                          → Buku fisik (weight, dimensions, specs)
│   ├── Setting.php                          → Key-value settings store
│   ├── User.php                             → Default Laravel user (unused di store)
│   ├── WaNotification.php                   → WhatsApp notification log
│   └── WebhookLog.php                       → Webhook send/receive log
├── Providers/
│   └── AppServiceProvider.php               → Boot + register
└── Services/
    ├── Settings.php                         → Settings service (cached key-value)
    ├── WhatsappNotifier.php                 → WA notification dispatcher
    ├── XSenderService.php                   → XSender WA Gateway API client
    ├── Shipping/
    │   ├── AgenwebsiteClient.php            → HTTP client ke Agenwebsite API (ongkir + AWB)
    │   ├── FulfillmentService.php           → AWB generation + fulfillment status
    │   └── ShippingRateService.php          → Rate calculation (weight, dimensi, merge custom)
    └── Webhook/
        └── AffiliateWebhookClient.php       → HMAC-SHA256 webhook ke Affiliate app
```

### database/

```
database/
├── factories/
│   ├── CourseFactory.php
│   ├── InstallmentSchemeFactory.php
│   ├── OrderFactory.php
│   ├── OrderItemFactory.php
│   ├── OrderPaymentFactory.php
│   ├── ProductFactory.php
│   └── UserFactory.php
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php         → Default Laravel
│   ├── 0001_01_01_000001_create_cache_table.php         → Default Laravel
│   ├── 0001_01_01_000002_create_jobs_table.php          → Default Laravel
│   ├── 2026_05_19_092901_create_admins_table.php        → Admin users
│   ├── 2026_05_19_092902_create_settings_table.php      → Key-value settings
│   ├── 2026_05_19_092903_create_products_table.php      → Buku (weight, specs, stock)
│   ├── 2026_05_19_092903b_create_courses_table.php      → Kelas (syllabus, schedule JSON)
│   ├── 2026_05_19_092904_create_orders_table.php        → Orders (ref_code, shipping, fulfillment)
│   ├── 2026_05_19_092905_create_order_items_table.php   → Order items (product_id OR course_id)
│   ├── 2026_05_19_092906_create_order_payments_table.php → Payments (proof, status, verified_by)
│   ├── 2026_05_19_092907_create_installment_schemes_table.php → Cicilan schemes
│   ├── 2026_05_19_092908_create_wa_notifications_table.php → WA log
│   ├── 2026_05_19_092909_create_webhook_logs_table.php  → Webhook log
│   ├── 2026_05_19_150000_add_rejection_reason_to_order_payments.php
│   ├── 2026_05_20_120000_add_shipping_fields_to_orders.php → courier, resi, shipped_at
│   ├── 2026_05_31_100000_add_shipping_fields_to_products.php → weight_kg, dimensions
│   ├── 2026_05_31_200000a_add_shipping_meta_to_orders.php → service, cost, etd
│   ├── 2026_05_31_200000b_add_fulfillment_columns_to_orders.php → fulfillment_status, AWB
│   ├── 2026_06_01_200000_add_card_fields_to_courses_table.php → rating, student_count, badge
│   ├── 2026_06_02_142000_fix_uploaded_image_paths.php   → Data fix: prepend storage/
│   ├── 2026_06_05_024910_add_specs_to_products_table.php → specs JSON
│   └── 2026_06_18_070000_add_order_meta_to_orders_table.php → order_meta JSON (ref_code abuse fix)
└── seeders/
    ├── AdminSeeder.php                     → admin@masfirmanpratama.com / admin123
    ├── CourseSeeder.php                    → 3 kelas AMC (Reguler, Platinum, Private)
    ├── DatabaseSeeder.php                  → Orchestrator (urutan penting!)
    ├── InstallmentSchemeSeeder.php         → Skema 3x, 6x, 12x cicilan
    ├── OrderSeeder.php                     → 10 sample orders (lunas + cicilan)
    ├── ProductionSeeder.php                → Minimal seed untuk production
    ├── ProductSeeder.php                   → 4 buku
    └── SettingsSeeder.php                  → Bank accounts, store info, shipping config
```

### resources/views/

```
resources/views/
├── admin/
│   ├── auth/login.blade.php                → Admin login page
│   ├── dashboard.blade.php                 → Dashboard + ApexCharts
│   ├── placeholder.blade.php               → (Legacy M1 placeholder — unused)
│   ├── courses/                            → Course CRUD views
│   │   ├── _form.blade.php                 → Shared form partial
│   │   ├── create.blade.php
│   │   ├── edit.blade.php
│   │   └── index.blade.php
│   ├── installment-schemes/                → Cicilan scheme CRUD
│   │   ├── _form, create, edit, index
│   ├── orders/
│   │   ├── index.blade.php                 → Order list + filter + pagination
│   │   └── show.blade.php                  → Order detail + verify + ship
│   ├── products/                           → Product CRUD views
│   │   ├── _form, create, edit, index
│   ├── settings/
│   │   ├── index.blade.php                 → Settings tabs container
│   │   ├── _bank_accounts.blade.php        → Bank accounts tab
│   │   ├── _shipping.blade.php             → Shipping config tab
│   │   ├── _store_info.blade.php           → Store info tab
│   │   └── _whatsapp.blade.php             → WA gateway config tab
│   └── wa-notifications/
│       └── index.blade.php                 → WA notification log
├── components/
│   ├── admin/                              → TailAdmin component library
│   │   ├── _nav-links, alert, breadcrumb, button, card, form-group,
│   │   ├── icon, logo, metric-card, navbar, page-header,
│   │   ├── sidebar, stat-card, status-badge, table
│   │   └── (15 components total)
│   ├── (Public)
│   │   ├── badge, benefit-card, button, footer, navbar,
│   │   ├── media-coverage, page-placeholder, product-card
│   │   └── components-gallery
│   └── layouts/
│       └── store.blade.php                 → Store public layout
├── emails/
│   └── order-shipped.blade.php             → Email template order shipped
├── layouts/
│   ├── admin.blade.php                     → Admin layout (TailAdmin shell)
│   └── partials/
│       ├── admin-backdrop, admin-header, admin-sidebar
├── pages/
│   ├── home.blade.php                      → Homepage
│   ├── cart.blade.php                      → Cart page
│   ├── track.blade.php                     → Order tracking
│   ├── upload.blade.php                    → Upload bukti bayar
│   ├── tentang.blade.php                   → About page
│   ├── kontak.blade.php                    → Contact page
│   ├── signed-url-error.blade.php          → 403 signed URL error
│   ├── checkout/
│   │   ├── index.blade.php                 → Checkout buku
│   │   └── success.blade.php               → Checkout success
│   ├── courses/
│   │   ├── checkout.blade.php              → Checkout kelas
│   │   └── checkout-success.blade.php
│   └── products/
│       ├── index.blade.php                 → Katalog produk
│       ├── show.blade.php                  → Detail buku
│       ├── book.blade.php                  → (Alternative book detail)
│       └── course.blade.php                → (Alternative course detail)
```

### routes/

```
routes/
├── web.php                                 → All routes (public + admin + webhooks)
└── console.php                             → Console commands (scheduler)
```

### config/

```
config/
├── admin.php                               → Admin panel config (logo, name)
├── admin-nav.php                           → Sidebar nav menu config
├── app.php, auth.php, cache.php, ...       → Laravel default
├── checkout.php                            → Checkout config
├── products.php                            → Product config
├── services.php                            → Service config (Agenwebsite, WA)
├── shipping.php                            → Shipping config (origin, couriers, cache TTL)
├── store.php                               → Store config (shipping_methods, bank accounts)
└── webhook.php                             → Webhook config (affiliate_url, secret, timeout, retry)
```

### tests/

```
tests/
├── Feature/
│   ├── Admin/                              → 12 test files
│   │   ├── AuthTest, CourseManagementTest, InstallmentSchemeCrudTest,
│   │   ├── OrderIndexTest, OrderPaymentVerifyTest, OrderShipTest,
│   │   ├── OrderShowTest, ProductCrudTest, ProductSoftDeleteTest,
│   │   ├── SettingsTest, SidebarIconWhitelistTest, SidebarMobileDrawerTest
│   ├── Shipping/                           → 12 test files (28 failures — API license expired)
│   │   ├── AdminGenerateShipmentTest, AdminShippingSettingsTest,
│   │   ├── AgenwebsiteClientTest, AgenwebsiteFulfillmentTest,
│   │   ├── AgenwebsiteMasterDataTest, AgenwebsitePriceTest,
│   │   ├── AwbCallbackTest, FulfillmentServiceTest,
│   │   ├── OrderShippedMailTest, OrderShippingMetaTest,
│   │   ├── ProductShippingFieldsTest, ShippingErrorHandlingTest,
│   │   ├── ShippingRateEndpointTest, ShippingRatesTest,
│   │   ├── ShippingWeightCalcTest, TrackingTest
│   ├── (Public)                            → 20 test files
│   │   ├── AffiliateWebhookTest, BookDetailPageTest, BookRelatedKelasTest,
│   │   ├── CartPageTest, CheckoutCourseTest, CheckoutPageTest,
│   │   ├── CheckoutShippingIntegrationTest, CheckoutStoreTest,
│   │   ├── CourseAddToCartTest, CourseDetailLighthouseGuardTest,
│   │   ├── CourseSeederTest, CourseStorefrontTest, ExampleTest,
│   │   ├── ProductCardComponentTest, SignedUrlGuardTest,
│   │   ├── StorefrontRoutesTest, TrackOrderPageTest,
│   │   ├── UploadProofPageTest, UploadStoreDbTest, WaNotificationStubTest
│   └── TestCase.php
└── Unit/
    └── ExampleTest.php
```

**Test stats:** 438 tests, 1544 assertions — 28 failures (all shipping API), 4 errors

### Blog module (`store/`, feat/blog — 2026-07-03)

```
app/
├── Models/                      Post, BlogCategory, BlogTag, BlogMedia
├── Http/Controllers/
│   ├── BlogController.php        → /blog (index+filter+search), /blog/{slug}
│   ├── BlogFeedController.php     → /sitemap-blog.xml, /blog/feed (RSS)
│   ├── LegacyRedirectController.php → 301 old root /{slug}/ → /blog/{slug}
│   └── Admin/
│       ├── PostController.php     → CRUD + bulk + restore + WXR import
│       └── BlogCategoryController.php
├── Http/Requests/Admin/          StorePostRequest, UpdatePostRequest
├── Services/Blog/WxrImporter.php → WordPress WXR parser (idempotent)
├── Console/Commands/             ImportWordpressBlog, PublishScheduledPosts
└── Support/HtmlSanitizer.php     → DOM allowlist (anti-XSS) for post bodies

database/migrations/2026_07_02_1000*  → posts, blog_categories, blog_tags,
                                         category_post, tag_post, blog_media, post_product
database/seeders/BlogSeeder.php        (+ Post/BlogCategory/BlogTag factories)
resources/views/admin/posts/           index, _form, create, edit, import
resources/views/admin/blog-categories/ index
resources/views/pages/blog/            index, show
tests/Feature/{Admin/PostCrudTest, BlogPageTest, BlogSeoTest,
              Blog/WxrImporterTest, Blog/LegacyRedirectTest}  → 50 tests
tests/Fixtures/wxr-sample.xml
```

Migrasi WordPress: `php artisan blog:import-wordpress export.xml --media` (jalankan
`--media` SEBELUM cutover DNS). Detail: `product-development/features/blog/PRD.md §10`.

---

## Affiliate App (`affiliate/`)

Laravel 11 — Affiliate System + Admin Panel terintegrasi.

### app/

```
app/
├── Console/Commands/
│   ├── FinalizeEvents.php                  → Daily: finalize ended events + grant rewards
│   └── ReleaseCommissions.php              → Daily: flip cooling → available (7-day window)
├── Http/
│   ├── Controllers/
│   │   ├── (Public Affiliator)
│   │   │   ├── CommissionController.php    → List komisi (filter status)
│   │   │   ├── DashboardController.php     → Dashboard stats + recent activity
│   │   │   ├── EventController.php         → Event list, detail, leaderboard, claim reward
│   │   │   ├── LandingController.php       → Public landing page (/)
│   │   │   ├── MaterialController.php      → Marketing materials download
│   │   │   ├── NotificationController.php  → In-app notifications
│   │   │   ├── ProfileController.php       → Edit profile (bank info, avatar)
│   │   │   ├── ReferralController.php      → Referral link CRUD + /ref/{code} tracking
│   │   │   └── WithdrawalController.php    → Withdrawal request + history
│   │   ├── Admin/
│   │   │   ├── AdminAffiliatorController.php → Approve/reject/suspend affiliator
│   │   │   ├── AdminCommissionController.php → Commission settings + review
│   │   │   ├── AdminDashboardController.php  → Admin dashboard stats
│   │   │   ├── AdminEventController.php      → Event CRUD + finalization
│   │   │   ├── AdminLoginController.php      → Admin login
│   │   │   ├── AdminMaterialController.php   → Material CRUD + toggle
│   │   │   └── AdminWithdrawalController.php → Withdrawal approve/reject
│   │   ├── Auth/
│   │   │   ├── EmailVerificationController.php
│   │   │   ├── LoginController.php
│   │   │   └── RegisterController.php       → 2 tipe: alumni, non-alumni
│   │   └── Webhooks/
│   │       └── StoreWebhookController.php   → HMAC verify + order-paid/refunded handler
│   └── Middleware/
│       ├── AdminAuthenticate.php            → Admin guard middleware
│       └── EnsureAffiliatorIsActive.php     → Block suspended affiliator
├── Models/
│   ├── ActivityLog.php                      → Log aktivitas affiliator
│   ├── AffiliateEvent.php                   → Event gamifikasi (target, periode, reward)
│   ├── AffiliateEventParticipant.php        → Peserta event (score, rank, progress)
│   ├── AffiliateEventReward.php             → Reward per event (title, description, image)
│   ├── Affiliator.php                       → User affiliator (Authenticatable, SoftDeletes)
│   ├── AffiliatorType.php                   → 2 tipe: alumni, non-alumni
│   ├── Commission.php                       → Komisi (cooling → available → withdrawn)
│   ├── CommissionSetting.php                → Rate per type × product_type (book/course)
│   ├── Material.php                         → Materi marketing (banner, video, copy)
│   ├── MaterialDownload.php                 → Log download materi
│   ├── Notification.php                     → In-app notif untuk affiliator
│   ├── ReferralClick.php                    → Log klik referral link
│   ├── ReferralCode.php                     → Unique code per affiliator (8 char)
│   ├── ReferralOrder.php                    → Order dari store via referral
│   ├── WebhookLog.php                       → Webhook receive log
│   ├── Withdrawal.php                       → Pengajuan tarik komisi
│   └── WithdrawalMethod.php                 → Metode pencairan (bank transfer)
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    └── Gamification/
        └── EventScoringService.php          → Score calc (omset/sales_count, refund deduction)
```

### database/

```
database/
├── factories/
│   ├── AffiliateEventFactory.php
│   ├── AffiliateEventParticipantFactory.php
│   ├── AffiliateEventRewardFactory.php
│   ├── AffiliatorFactory.php
│   ├── AffiliatorTypeFactory.php
│   ├── CommissionSettingFactory.php
│   ├── ReferralCodeFactory.php
│   ├── ReferralOrderFactory.php
│   └── UserFactory.php
├── migrations/
│   ├── 2026_06_17_010000_create_affiliator_types_table.php
│   ├── 2026_06_17_010100_create_affiliators_table.php
│   ├── 2026_06_17_010200_create_commission_settings_table.php
│   ├── 2026_06_17_010300_create_referral_codes_table.php
│   ├── 2026_06_17_010400_create_referral_clicks_table.php
│   ├── 2026_06_17_010500_create_referral_orders_table.php
│   ├── 2026_06_17_010600_create_commissions_table.php
│   ├── 2026_06_17_010700_create_withdrawal_methods_table.php
│   ├── 2026_06_17_010800_create_withdrawals_table.php
│   ├── 2026_06_17_010900_create_materials_table.php
│   ├── 2026_06_17_011000_create_material_downloads_table.php
│   ├── 2026_06_17_011100_create_affiliate_events_table.php
│   ├── 2026_06_17_011200_create_affiliate_event_participants_table.php
│   ├── 2026_06_17_011300_create_affiliate_event_rewards_table.php
│   ├── 2026_06_17_011400_create_webhook_logs_table.php
│   ├── 2026_06_17_011500_create_notifications_table.php
│   ├── 2026_06_17_011600_create_activity_logs_table.php
│   ├── 2026_06_17_011700_create_cache_table.php
│   ├── 2026_06_17_011800_create_sessions_table.php
│   ├── 2026_06_17_011900_create_password_reset_tokens_table.php
│   └── 2026_06_18_120000_make_referral_order_id_nullable_on_commissions_table.php
└── seeders/
    ├── AffiliatorTypeSeeder.php             → 2 tipe: alumni (15%), non-alumni (10%)
    ├── CommissionSettingSeeder.php          → Rate per type × product_type
    ├── DatabaseSeeder.php                   → Orchestrator
    └── WithdrawalMethodSeeder.php           → BCA, Mandiri, BRI
```

### resources/views/

```
resources/views/
├── admin/
│   ├── auth/login.blade.php
│   ├── dashboard.blade.php
│   ├── affiliators/ (index, show)
│   ├── commissions/ (index, settings)
│   ├── events/ (index, create, edit)
│   ├── materials/ (index, create)
│   ├── withdrawals/ (index)
│   ├── components/sidebar.blade.php
│   └── layouts/admin.blade.php
├── auth/ (login, register, pending-approval, verify-email)
├── commissions/index.blade.php
├── components/sidebar-nav.blade.php
├── dashboard.blade.php
├── events/ (index, show, leaderboard, rewards)
├── landing.blade.php
├── layouts/ (app, dashboard)
├── materials/index.blade.php
├── notifications/index.blade.php
├── profile/edit.blade.php
├── referrals/ (index, create, edit)
├── withdrawals/ (index, create)
└── welcome.blade.php
```

### routes/

```
routes/
├── web.php                                 → All routes (public + auth + admin + webhook)
└── console.php                             → Scheduler (FinalizeEvents, ReleaseCommissions daily)
```

### tests/

```
tests/
├── Feature/
│   ├── Admin/AdminEventTest.php            → Event CRUD admin (176 lines)
│   ├── AdminTest.php                       → Admin panel tests
│   ├── AuthTest.php                        → Register + login + 2 tipe
│   ├── CommissionSettingSeederTest.php
│   ├── DashboardTest.php
│   ├── EventRewardTest.php                 → Reward granting + claiming (241 lines)
│   ├── EventScoringTest.php                → Score engine (235 lines)
│   ├── ExampleTest.php
│   ├── ReferralTest.php                    → CRUD + tracking + cookie (8 tests)
│   └── StoreWebhookTest.php                → HMAC + idempotency + refund + release (7 tests)
└── Unit/
    └── ExampleTest.php
```

**Test stats:** 68 tests, 191 assertions — 0 failures, 0 errors ✅

---

## docs/

```
docs/
├── audit/
│   └── project-plan-validation-M1-M4.md    → Audit report (348 lines)
├── PR-CHAIN-M2.md                          → 5 PR chain untuk M2
├── qc/
│   ├── integration-tests-M2.md
│   ├── lighthouse-M1.md                    → Lighthouse audit M1
│   ├── lighthouse-M2.md                    → Lighthouse audit M2
│   ├── lighthouse-M2-hardening.md
│   ├── visual-review-M1.md
│   ├── visual-review-M2-admin.md
│   ├── visual-review-M2-hardening.md
│   ├── M1/                                 → Screenshots + Lighthouse reports (desktop/mobile/tablet × chromium/firefox)
│   ├── M2/                                 → Screenshots + Lighthouse reports
│   ├── M2-hardening/                       → Screenshots + Lighthouse reports
│   └── upstream-archive/                   → Read-only archive (prototype HTML, DESIGN.md, plan)
```

## docs_dev/

```
docs_dev/
├── DEPLOY_RUNBOOK.md                       → Production deploy guide (M5)
├── plans/
│   ├── 2026-05-31-m-shipping.md            → M-Shipping plan (Agenwebsite integration)
│   └── 2026-06-18-m4-webhook-referral.md   → M4 webhook integration plan
├── reference/
│   └── M_SHIPPING_REFERENCE.md             → Shipping API reference
└── task_plan.md                            → M2 task: Produk CRUD form
```

## product-development/

```
product-development/
├── resources/
│   ├── product.md                          → Product context
│   ├── PRD-template.md                     → 8-section PRD template
│   └── file-structure.md                   → THIS FILE
└── current-feature/
    ├── feature.md                          → Feature: Sales Reports + Refund Dispatcher
    ├── JTBD.md                             → Jobs to be Done (5 Job Stories)
    ├── PRD.md                              → PRD (8-section, 12 KB)
    └── plan.md                             → Implementation plan (7 tasks, 3 waves)
```

---

## Quick Reference — Key Files

### Store App

| Need to... | File |
|------------|------|
| Add admin route | `store/routes/web.php` (line ~190+) |
| Add admin sidebar menu | `store/config/admin-nav.php` + `store/app/Helpers/MenuHelper.php` |
| Add admin page | `store/resources/views/admin/{module}/index.blade.php` |
| Add event | `store/app/Events/{Name}.php` |
| Add listener | `store/app/Listeners/{Name}.php` (register in `bootstrap/app.php` or auto-discovered) |
| Add migration | `store/database/migrations/{timestamp}_{name}.php` |
| Add seeder | `store/database/seeders/{Name}Seeder.php` (register in `DatabaseSeeder.php`) |
| Change settings tabs | `store/resources/views/admin/settings/_*.blade.php` |
| Shipping config | `store/config/shipping.php` |
| Webhook config | `store/config/webhook.php` |
| Admin auth guard | `store/bootstrap/app.php` (line 27-34) |
| Admin login creds | `store/.env` → `ADMIN_SEED_PASSWORD=admin123` |

### Affiliate App

| Need to... | File |
|------------|------|
| Add admin route | `affiliate/routes/web.php` (line ~120+) |
| Add affiliator route | `affiliate/routes/web.php` (line ~53+) |
| Add event scoring | `affiliate/app/Services/Gamification/EventScoringService.php` |
| Add cron command | `affiliate/app/Console/Commands/{Name}.php` + register in `routes/console.php` |
| Add webhook handler | `affiliate/app/Http/Controllers/Webhooks/StoreWebhookController.php` |
| Admin login creds | `affiliate/.env` → `ADMIN_EMAIL` + `ADMIN_PASSWORD` |
| Commission rates | `affiliate/database/seeders/CommissionSettingSeeder.php` |

---

## Conventions

### Naming
- **Migration:** `{YYYY}_{MM}_{DD}_{HHMMSS}{suffix}_{description}.php` — suffix `a`, `b` untuk same-timestamp ordering
- **Controller (public):** `{Name}Controller.php` di `app/Http/Controllers/`
- **Controller (admin):** `Admin{Name}Controller.php` di `app/Http/Controllers/Admin/` (store) atau sama (affiliate)
- **View (admin store):** `admin/{module}/{action}.blade.php`
- **View (admin affiliate):** `admin/{module}/{action}.blade.php`
- **Test:** `tests/Feature/{Module}/{TestName}Test.php`

### Guard
- **Store admin:** `auth:admin` guard → `admins` table → redirect ke `/admin/login`
- **Affiliate admin:** `AdminAuthenticate` middleware → config-based (`ADMIN_EMAIL` + `ADMIN_PASSWORD`)
- **Affiliator:** `auth:affiliator` guard → `affiliators` table

### Webhook Flow
```
Store (PaymentVerified event)
  → DispatchAffiliateOrderPaid listener
  → AffiliateWebhookClient::dispatch('order-paid', $payload)
  → HMAC-SHA256 sign + POST to affiliate
  → StoreWebhookController::handleOrderPaid()
  → hash_equals verify → create ReferralOrder + Commission (cooling 7d)
```

### Commission Lifecycle
```
cooling (7 hari, available_at = created_at + 7d)
  → ReleaseCommissions command (daily cron) → available
  → Withdrawal request → admin approve → withdrawn
  → Order refunded → cancel (cooling/available only, preserve withdrawn)
```
