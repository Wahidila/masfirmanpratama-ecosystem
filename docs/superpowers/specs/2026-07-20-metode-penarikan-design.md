# Metode Penarikan — Desain

**Tanggal:** 2026-07-20
**Aplikasi:** `affiliate` (affiliate.masfirmanpratama.id)
**Status:** disetujui, siap dikerjakan

## Masalah

Admin tidak punya cara mengatur metode penarikan. Tabel `withdrawal_methods` sudah ada dan
sudah dipakai — 9 metode terisi (BCA, BNI, BRI, Mandiri, BSI, Dana, OVO, GoPay, ShopeePay) dan
sudah menjadi sumber dropdown di form penarikan affiliator — tapi mengubah minimum atau
menonaktifkan satu metode hanya bisa lewat edit database langsung.

Di sisi affiliator, tujuan transfer masih diketik ulang tiap kali menarik, dan data bank di
profil (`bank_name`, `bank_account_number`, `bank_account_name`) berupa teks bebas yang tidak
terhubung sama sekali dengan daftar metode itu.

## Keputusan

| Hal | Pilihan |
|---|---|
| Cakupan | Panel admin **dan** rombak sisi affiliator |
| Biaya admin | Nominal tetap per metode, **memotong** yang diterima affiliator |
| Rekening tersimpan | Banyak per affiliator, satu ditandai utama |
| Basis minimum | Nominal yang diminta (bruto) |
| Hapus metode | Hanya yang belum pernah dipakai; sisanya cukup dinonaktifkan |

Sengaja **tidak** dikerjakan: logo metode, urutan tampil manual, teks instruksi per metode,
biaya persentase, minimum global. Tiap kolom yang ditulis tapi tak pernah dibaca jadi jebakan.

## Bentuk

### 1. Admin — Metode Penarikan (`/admin/withdrawal-methods`)

CRUD penuh: nama, tipe, minimum penarikan, biaya admin, aktif/nonaktif.

`type` berubah dari teks bebas menjadi pilihan tertutup lewat `WithdrawalMethod::TYPES`
(`bank_transfer` => Bank, `e_wallet` => E-Wallet). Hari ini label di form affiliator ditentukan
ternary yang menganggap apa pun selain `bank_transfer` sebagai E-Wallet — begitu admin bisa
mengetik `type` sendiri, ternary itu jadi bohong.

Hapus hanya untuk metode yang belum dipakai penarikan maupun rekening tersimpan. Foreign key
`withdrawals.withdrawal_method_id` tanpa cascade — hapus paksa berujung error 500 dan riwayat
penarikan yang tidak bisa dirender.

**Aturan pengaman: biaya admin harus lebih kecil dari minimum metode itu.** Tanpa ini admin bisa
memasang biaya Rp50.000 pada metode berminimum Rp25.000, dan affiliator menerima angka negatif.

### 2. Affiliator — Rekening Tujuan (di halaman profil)

Tabel baru `affiliator_payout_accounts`: `affiliator_id`, `withdrawal_method_id`,
`account_number`, `account_name`, `is_primary`. Affiliator menambah/menghapus rekening dan
menandai satu sebagai utama.

Data lama dipindah lewat migrasi: affiliator yang `bank_name`-nya cocok dengan nama metode
(**pencocokan case-insensitive** — satu-satunya baris di produksi berbunyi `"DANA"` sedangkan
metodenya bernama `Dana`) dibuatkan rekening tersimpan dan ditandai utama. Yang tidak cocok
dibiarkan; affiliator menambahkan sendiri.

Kolom `bank_*` lama **tidak dihapus** pada rilis ini — jaring pengaman kalau pencocokan meleset.

### 3. Penarikan

Form berubah dari mengetik nomor rekening menjadi memilih rekening tersimpan. Nomor dan nama
tetap di-snapshot ke baris penarikan seperti sekarang, jadi mengganti rekening tidak mengubah
riwayat.

Perhitungan — saldo Rp120.000, minta Rp100.000 lewat BCA (biaya Rp2.500):

| | |
|---|---|
| Saldo terpotong | Rp100.000 (bruto) |
| Diterima affiliator | Rp97.500 (neto) |
| Sisa saldo | Rp20.000 |

Minimum dibandingkan dengan bruto, sesuai perilaku yang sudah berjalan.

`withdrawals` dapat tiga kolom: `fee`, `net_amount`, dan `method_name` (snapshot). Yang ketiga
menambal risiko rename: tanpa itu, admin mengganti nama metode ikut mengubah bunyi seluruh
riwayat lama, karena tampilan membaca `$wd->method->name` lewat relasi hidup. Nomor dan nama
rekening sudah di-snapshot sejak awal, jadi absennya snapshot metode tampak kelupaan.

Produksi punya **0 baris penarikan**, jadi kolom-kolom ini masuk ke tabel kosong dan tidak ada
janji lama yang dilanggar. Backfill tetap ditulis untuk database lokal/dev: `fee`=0,
`net_amount`=`amount`, `method_name` dari relasi.

## Bug yang ikut ditambal

1. **Nonaktif tidak memblokir.** `WithdrawalController::store()` memvalidasi
   `exists:withdrawal_methods,id` tanpa cek `is_active`. Metode yang dimatikan hilang dari
   dropdown tapi POST-nya tetap diterima. Tanpa perbaikan ini tombol aktif/nonaktif yang baru
   hanya kosmetik.
2. **`db:seed` menimpa editan admin.** `WithdrawalMethodSeeder` memakai `updateOrCreate` pada
   `name`, dan `name` tidak punya unique index. Admin menurunkan minimum BCA, lalu seed dijalankan
   setelah migrasi — kembali ke nilai semula tanpa jejak. Jadi `firstOrCreate` + unique index.
3. **Rekening milik orang lain.** Risiko baru dari desain ini: begitu penarikan merujuk ID
   rekening tersimpan, ID milik affiliator lain harus ditolak. Divalidasi terikat pemilik.

## Di luar cakupan

**Penarikan sebagian memusnahkan sisa komisi** — `WithdrawalController::store()` menandai komisi
`withdrawn` secara utuh, jadi menarik Rp50.000 dari komisi Rp200.000 menghanguskan Rp150.000
sisanya. Belum menggigit karena penarikan produksi masih nol. Perbaikannya butuh keputusan
tersendiri (memecah komisi / kolom jumlah terpakai / pivot penarikan-komisi) dan tidak boleh
menumpang di PR ini. Dicatat sebagai tugas terpisah.

## Verifikasi

- Test admin CRUD: index, create, store, edit, update, toggle, hapus-terjaga, tamu ditolak.
- Test aturan biaya < minimum.
- Regresi: metode nonaktif menolak POST penarikan.
- Regresi: rekening milik affiliator lain ditolak.
- Perhitungan: `net_amount` benar, saldo terpotong bruto.
- Migrasi backfill: `"DANA"` cocok ke metode `Dana`.
- Factory `WithdrawalMethodFactory`, `WithdrawalFactory`, `AffiliatorPayoutAccountFactory` —
  ketiganya belum ada padahal modelnya memakai `HasFactory`.
