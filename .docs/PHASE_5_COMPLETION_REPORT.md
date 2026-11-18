# Laporan Penyelesaian GEARORA Phase 5 — Payments & Rental Operations

**Tanggal:** 18 November 2026  
**Branch:** `main`  
**Status:** implementasi lokal selesai, sandbox Midtrans nyata BLOCKED karena kredensial sandbox belum tersedia; laporan rental terpisah ditunda ke Phase 7.

## 1. Ruang Lingkup Selesai

- Integrasi provider Midtrans Snap melalui `PaymentGateway` + `MidtransPaymentGateway` dan `FakePaymentGateway`.
- Checkout Snap Redirect, payment attempts, idempotency, expiry tanpa asumsi cron.
- Webhook Midtrans: signature SHA512 + `hash_equals`, validasi nominal/mata uang, deduplikasi event, idempotensi settlement.
- Refund policy dan refund records; manual evidence dan gateway API path.
- Deposit sebagai liabilitas terpisah, ledger `deposit_transactions`, collect/deduct/refund/forfeit.
- Pickup/handover, partial return, inspeksi awal/akhir, foto inspeksi, pelepasan unit per unit.
- Overdue calculation berdasarkan deadline 20:00 WIB + grace 60 menit + tarif dari snapshot.
- Additional charges, persetujuan, potong deposit lebih dulu, outstanding balance.
- RBAC permissions dan admin UI untuk pickup/return/inspection/overdue, refund, deposit, charges.
- Customer UI untuk memulai pembayaran Midtrans.
- Dokumen integrasi, workflow, deposit, refund, operasi, biaya tambahan, inspeksi, test plan.

## 2. File Dokumen

- `.docs/PHASE_5_TECHNICAL_DESIGN.md`
- `.docs/MIDTRANS_INTEGRATION.md`
- `.docs/PAYMENT_WORKFLOW.md`
- `.docs/REFUND_POLICY_IMPLEMENTATION.md`
- `.docs/DEPOSIT_MANAGEMENT.md`
- `.docs/RENTAL_OPERATIONS.md`
- `.docs/HANDOVER_RETURN_INSPECTION.md`
- `.docs/ADDITIONAL_CHARGES.md`
- `.docs/PHASE_5_TEST_PLAN.md`
- `.docs/RENTAL_POLICY.md` diperbarui untuk Midtrans.

## 3. Database

- 17 migrasi Phase 5 (`2026_11_18_000100`–`001600`), incremental dan reversible.
- Tabel utama: `payment_attempts`, `payment_webhook_events`, `payment_proofs`, `refunds`, `deposits`, `deposit_transactions`, `rental_handovers`, `rental_handover_items`, `rental_returns`, `rental_return_items`, `inspections`, `inspection_photos`, `additional_charges`.
- Kolom uang baru integer Rupiah; kolom decimal lama dipertahankan untuk kompatibilitas.
- Status/metode memakai `string`, bukan MySQL ENUM, agar kompatibel dengan SQLite test.
- Tidak menjalankan `migrate:fresh`, `db:wipe`, atau reset terhadap database dev.

## 4. Hasil Pengujian Aktual

### Unit + Feature (SQLite)

```text
php artisan test --testsuite=Unit,Feature
Tests: 83 passed (271 assertions)
```

Tambahan Phase 5: 11 tests, 43 assertions mencakup webhook sah/tidak sah, amount mismatch, duplicate, larangan verifikasi manual gateway, transition workflow-only, refund tiers, deposit ledger, overdue grace, handover precondition, pelepasan unit per unit.

### MySQL

```text
php artisan test -c phpunit.mysql.xml
Tests: 4 passed (56 assertions)
```

MySQL test guard menolak database selain `gearora_test`. Uji mencakup semua tabel Phase 5 sebagai InnoDB, tipe kolom status/money, unique index webhook, dan persist payment Midtrans.

### Lainnya

- `php -l` seluruh file PHP terkait: tidak ada syntax error.
- `php artisan route:list`: rute API webhook dan admin/customer berhasil diregistrasi.
- `RolePermissionSeeder` berhasil dijalankan.
- `vendor/bin/pint` diterapkan pada file yang berubah; file yang tidak terkait dikembalikan.

## 5. Sandbox Midtrans — BLOCKED

Tidak tersedia `MIDTRANS_SERVER_KEY` / `MIDTRANS_CLIENT_KEY` sandbox nyata. Maka:

- Tidak mengklaim HTTP call Snap nyata sudah diuji.
- Fake provider dan test signature/webhook teruji lokal.
- Set kredensial sandbox dengan aman di `.env` server; jangan commit.
- Ikuti `.docs/MIDTRANS_INTEGRATION.md` §8 untuk URL notifikasi dan transaksi end-to-end.
- Production tetap off sampai sandbox verified dan owner mengaktifkannya secara sadar.

## 6. Pekerjaan Ditunda / Batasan

- Laporan rental dasar dan laporan finansial gabungan ditunda ke Phase 7.
- Belum ada kredensial nyata untuk menguji Snap API, refund API, ataupun notifikasi Midtrans asli.
- Belum ada cron/queue dependency; expiry/overdue perlu dipanggil opportunistically dari aplikasi atau command manual.
- UI admin saat ini mengutamakan operasi inti; audit detail rute dan test UI tambahan direkomendasikan sebelum deploy.

## 7. Commit Phase 5

1. `29b11f5` — `feat(payment): add Midtrans Snap gateway foundation, payments and rental operations schema`
2. `64ee94c` — `feat(admin): add rental operations, refund, deposit and charge management UI`
3. `e0018bc` — `test(payment): cover webhook signature, deposit ledger, refund, overdue and per-unit release`
4. `d5326f3` — `docs(phase5): add integration guides, workflow docs, test plan and update rental policy`

Push ke `origin/main` belum dijalankan pada saat laporan ini ditulis.
