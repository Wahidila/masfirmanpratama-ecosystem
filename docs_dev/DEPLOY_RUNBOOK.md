# Deploy Runbook — MasFirmanPratama Ecosystem (M5 Production Launch)

> 2 app Laravel 11: **store** (masfirmanpratama.com) + **affiliate** (affiliate.masfirmanpratama.com).
> Manual deploy via SSH/FTP ke shared/VPS. DB MySQL 2 schema terpisah.

## ⚠️ Keputusan yang HARUS dikonfirmasi klien SEBELUM go-live

1. **Domain final** — `masfirmanpratama.com` + `affiliate.masfirmanpratama.com`? (template pakai ini)
2. **Hosting target** — shared hosting (cPanel) atau VPS? Menentukan langkah deploy.
3. **Kredensial DB MySQL produksi** — host, 2 database (`store_db`, `affiliate_db`), user, password.
4. **SMTP mail** — provider + kredensial (buat verifikasi email affiliator + notifikasi).
5. **XSender** — API key + sender number produksi.
6. **Rate komisi final** — sudah di-seed (alumni 15/12, non-alumni 10/8, global 8%). Konfirmasi angka ke klien; ubah via admin atau re-seed kalau beda.

## 🔒 Security hard-gates (WAJIB sebelum launch)

- [ ] **`APP_DEBUG=false`** di kedua app (template sudah set; verifikasi di server).
- [ ] **`APP_KEY` di-generate** per app (`php artisan key:generate`).
- [ ] **Admin password BUKAN default `admin123`** — saat ini admin login compare plaintext `config('admin.password')`. Set `ADMIN_PASSWORD` kuat di `.env`. (Catatan teknis: ini plaintext-in-env, bukan hash; acceptable untuk single-admin tapi pastikan `.env` permission 600 + tidak ke-commit.)
- [ ] **Webhook secret**: `AFFILIATE_WEBHOOK_SECRET` (store) === `STORE_WEBHOOK_SECRET` (affiliate), string random ≥32 byte. Generate: `php -r "echo bin2hex(random_bytes(32));"`. Kalau beda/kosong → webhook fail-closed 503.
- [ ] **`SESSION_SECURE_COOKIE=true`** (template set; butuh HTTPS aktif).
- [ ] **HTTPS** aktif di kedua domain (SSL cert). Laravel `TrustProxies` sudah `at: '*'` (per AGENTS.md) untuk handle proxy/tunnel.
- [ ] **File `.env` permission 600**, owner web user, di luar webroot atau di-protect.

## 📦 Langkah deploy per app (ulang untuk store & affiliate)

```bash
# 1. Upload kode (git clone / rsync / FTP) ke direktori app di server
#    Pastikan webroot mengarah ke <app>/public

# 2. Dependency produksi (tanpa dev)
composer install --no-dev --optimize-autoloader

# 3. Env
cp .env.production.example .env
# EDIT .env — isi semua CHANGE_ME (DB, mail, secret, domain)
php artisan key:generate

# 4. Migrasi DB (DB harus sudah dibuat + user punya akses)
php artisan migrate --force

# 5. Seed data awal
#    - affiliate: AffiliatorType, WithdrawalMethod, CommissionSetting
php artisan db:seed --force
#    - store: seeder produk/setting kalau ada (cek database/seeders/)

# 6. Build asset frontend
npm ci && npm run build

# 7. Optimisasi cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Storage symlink (upload bukti bayar, avatar, materi)
php artisan storage:link

# 9. Permission
chmod -R 775 storage bootstrap/cache
# chown ke web user (www-data / nobody / sesuai hosting)

# 10. Scheduler (cron) — WAJIB untuk commissions:release + events:finalize
#    Tambah ke crontab web user:
#    * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## 🔗 Urutan deploy (PENTING)

Deploy **affiliate dulu** (receiver), baru **store** (emitter). Supaya saat store mulai kirim webhook, endpoint affiliate sudah hidup.

## ✅ Smoke test pasca-deploy (2 domain live)

### Store
- [ ] `curl -sI https://masfirmanpratama.com/` → 200, header `Strict-Transport` / HTTPS OK
- [ ] Homepage render (katalog buku + kelas tampil)
- [ ] `/produk` list buku, `/kelas/{slug}` detail course
- [ ] Checkout flow: tambah ke cart → checkout → upload bukti bayar (image ≤2MB)
- [ ] Admin login (`/admin`) dengan kredensial produksi (BUKAN admin123)
- [ ] Admin verifikasi bayar → trigger `PaymentVerified` event

### Affiliate
- [ ] `curl -sI https://affiliate.masfirmanpratama.com/` → 200
- [ ] Landing program render
- [ ] Register affiliator → email verifikasi terkirim (cek SMTP)
- [ ] Login → dashboard, referral link, leaderboard, /rewards
- [ ] Admin event CRUD (`/admin/events`)

### Integrasi webhook (end-to-end)
- [ ] Buat order di store dengan ref_code valid affiliator → admin verifikasi bayar
- [ ] Cek `webhook_logs` di affiliate_db: status `processed`
- [ ] Cek `referral_orders` + `commissions` (status cooling) terbuat
- [ ] Signature mismatch → 401 (test dengan secret salah)
- [ ] `php artisan commissions:release` → cooling habis jadi available
- [ ] `php artisan events:finalize` → event lewat deadline jadi ended + reward granted

## 🔄 Rollback

- DB: backup sebelum `migrate --force` (`mysqldump store_db > backup.sql`).
- Kode: tag rilis sebelum deploy, `git checkout <prev-tag>` + re-run cache commands.
- `php artisan down` saat deploy, `php artisan up` setelah smoke test pass.

## 📋 Post-launch monitoring

- Log: `storage/logs/laravel-*.log` (LOG_LEVEL=warning) kedua app.
- Webhook health: query `webhook_logs` status `failed`/`invalid_signature`.
- Scheduler jalan: cek `commissions` flip cooling→available tiap hari.
