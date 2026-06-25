# Jobs to be Done (JTBD)

## Framework

Jobs to be Done membantu kita fokus pada **apa yang user coba capai**, bukan siapa user tersebut.

> "People don't want to buy a quarter-inch drill. They want a quarter-inch hole."
> — Theodore Levitt

---

## Job Stories

### Job 1: Sales Report Visibility

**When** admin perlu mengevaluasi performa penjualan,
**I want to** melihat ringkasan revenue, order count, dan tren dalam periode tertentu,
**So that** saya bisa membuat keputusan bisnis berdasarkan data, bukan tebakan.

---

### Job 2: Refund → Commission Sync

**When** admin membatalkan/refund order yang sudah dibayar,
**I want to** sistem otomatis membatalkan komisi affiliator yang terkait,
**So that** affiliator tidak menerima komisi untuk transaksi yang dibatalkan dan saya tidak perlu intervensi manual.

---

### Job 3: Shipping Test Reliability

**When** developer menjalankan test suite secara lokal atau di CI,
**I want to** test shipping berjalan tanpa membutuhkan API key live Agenwebsite,
**So that** test suite selalu hijau dan tidak tergantung pada status license API pihak ketiga.

---

## Job Mapping

```
┌─────────────────────────────────────────────────────┐
│                    JOB 1: Sales Report              │
├─────────────────────────────────────────────────────┤
│ BEFORE: Admin buta data, hanya lihat dashboard     │
│         hari ini tanpa konteks historis             │
│                                                     │
│ DURING: Admin buka /admin/reports → pilih periode   │
│         → lihat chart + tabel → filter produk       │
│                                                     │
│ AFTER:  Admin punya data untuk decision-making,     │
│         bisa export untuk laporan ke klien          │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│              JOB 2: Refund → Commission Sync        │
├─────────────────────────────────────────────────────┤
│ BEFORE: Admin refund order → komisi affiliator      │
│         tetap active → harus cancel manual di        │
│         affiliate panel                             │
│                                                     │
│ DURING: Admin klik "Refund" → event fire → webhook  │
│         → affiliate receiver cancel commission       │
│                                                     │
│ AFTER:  Komisi auto-cancelled, affiliator diberi    │
│         notifikasi, audit trail tersimpan           │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│           JOB 3: Shipping Test Reliability           │
├─────────────────────────────────────────────────────┤
│ BEFORE: 28 test failures karena API license expired │
│         → CI merah → developer ignore test suite     │
│                                                     │
│ DURING: Developer run php artisan test → semua      │
│         shipping tests mock HTTP → 438/438 pass      │
│                                                     │
│ AFTER:  Test suite hijau, CI reliable, developer    │
│         trust test suite lagi                        │
└─────────────────────────────────────────────────────┘
```

---

## Priority Matrix

| Job | Impact | Effort | Priority |
|-----|--------|--------|----------|
| Job 1: Sales Report | HIGH — admin buta data saat ini | MEDIUM — 4-6h | 🔴 P0 |
| Job 2: Refund Sync | HIGH — financial integrity | LOW — 2-3h | 🔴 P0 |
| Job 3: Test Reliability | MEDIUM — developer experience | LOW — 1h | 🟡 P1 |

---

## Success Criteria per Job

### Job 1: Sales Report
- ✅ Admin bisa lihat revenue harian/mingguan/bulanan
- ✅ Filter by produk (kelas vs buku)
- ✅ Export CSV/Excel
- ✅ Chart tren 30/90/365 hari

### Job 2: Refund Sync
- ✅ Admin bisa mark order sebagai refunded
- ✅ Webhook `order-refunded` terkirim ke affiliate
- ✅ Commission cooling/available di-cancel otomatis
- ✅ Commission yang sudah withdrawn TIDAK di-cancel (preserve)
- ✅ Audit log: webhook log + activity log

### Job 3: Test Reliability
- ✅ `php artisan test` → 438/438 pass (0 failures)
- ✅ Shipping tests menggunakan Http::fake() atau mock
- ✅ Tidak ada dependency ke live API Agenwebsite
- ✅ CI-ready (bisa run di GitHub Actions tanpa secrets)
