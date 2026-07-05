# Product: MasFirmanPratama.com Ecosystem

## Overview

Ekosistem bisnis online Mas Firman Pratama (Mind Power & Life Mastery / AMC) yang terdiri dari 3 sub-sistem terintegrasi:

1. **Online Store** (`masfirmanpratama.com`) — Etalase produk (kelas + buku), checkout manual, upload bukti bayar (lunas/cicilan), tracking order tanpa login, integrasi ongkir Agenwebsite.com untuk buku fisik.
2. **Admin Panel Unified** (`/admin`) — 1 login kontrol Store + Affiliate: produk, pesanan, verifikasi bayar, resi, affiliator, komisi, withdrawal, materi marketing, event gamifikasi.
3. **Affiliate System** (`affiliate.masfirmanpratama.com`) — Landing program, register affiliator (2 tipe: alumni/non-alumni), dashboard, referral link manager, komisi (cooling 7 hari), withdrawal, leaderboard, gamifikasi event.

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11 (2 app terpisah — `store` + `affiliate`) |
| Frontend | Blade + Tailwind CSS v3 + Alpine.js |
| Database | MySQL — 2 schema: `store_db`, `affiliate_db` |
| Auth | Session-based (admin + affiliator), Laravel built-in |
| Payment | Manual transfer + upload bukti bayar (lunas/cicilan) |
| Ongkir | Agenwebsite.com API (buku fisik) — fallback admin manual |
| Notifikasi | WhatsApp Gateway (XSender integration) |
| Webhook | HMAC-SHA256 (Store → Affiliate untuk order-paid/order-refunded) |

## Key Stakeholders

| Role | Name | Responsibility |
|------|------|----------------|
| Klien | Firman Pratama (AMC) | Product owner, content provider |
| Lead MC | Rezvi | Project coordination, client liaison |
| Developer | Naufalix | Full-stack implementation |
| Auditor | OpenCode Agent | Quality assurance, audit, documentation |

## Current Status (Post-Audit M1-M4)

- **M1 (Store Frontend)**: ✅ 100% compliant
- **M2 (Admin Panel Store)**: ⚠️ 93% — missing Laporan Penjualan
- **M3 (Affiliate System)**: ✅ 100% compliant
- **M4 (Integration Done)**: ⚠️ 90% — missing order-refunded dispatcher
- **M5 (Production Launch)**: ✅ Production prep done

## Architecture

```
┌─────────────────┐     Webhook HMAC     ┌──────────────────────┐
│   STORE APP     │ ──────────────────→  │   AFFILIATE APP      │
│ masfirmanpratama │   (order-paid/       │ affiliate.masfirman-  │
│   .com           │    order-refunded)   │  pratama.com          │
│                  │                      │                       │
│ - Etalase produk │                      │ - Landing program     │
│ - Checkout manual│   /ref/{code}        │ - Register affiliator │
│ - Upload bukti   │ ←──────────────────  │ - Dashboard           │
│ - Tracking order │    cookie 30 hari    │ - Referral links      │
│ - Admin panel    │                      │ - Komisi (cooling 7d) │
│   (produk,       │                      │ - Withdrawal          │
│    pesanan,      │                      │ - Leaderboard         │
│    shipping,     │                      │ - Gamifikasi event    │
│    settings)     │                      │ - Admin panel         │
└──────────────────┘                      └───────────────────────┘
```

## Key Business Rules

1. **Payment Flow**: Customer checkout → transfer → upload bukti → admin verify → order paid
2. **Cicilan**: DP + N installments, each with upload bukti + admin verify
3. **Referral Tracking**: `/ref/{code}` → cookie 30 hari → attached ke order → webhook ke affiliate
4. **Commission Lifecycle**: cooling (7 hari) → available → withdrawn
5. **2 Tipe Affiliator**: alumni, non-alumni (beda commission rate)
6. **Gamifikasi**: Event scoring → finalization → reward granting → claiming
