# Rencana Pengujian Phase 5 — GEARORA

## 1. Ringkasan

| Suite | Command | Cakupan |
|---|---|---|
| Unit + Feature | `php artisan test --testsuite=Unit,Feature` | SQLite in-memory |
| MySQL | `php artisan test -c phpunit.mysql.xml` | `gearora_test` (MySQL 8) |

Baseline sebelum Phase 5: 72 tes / 228 asersi.
Setelah Phase 5: **83 tes / 271 asersi** (Unit+Feature) + **4 tes / 56 asersi** (MySQL).

## 2. File Tes Baru

### `tests/Feature/Phase5PaymentOperationsTest.php` (11 tes / 43 asersi)

| Tes | Membuktikan |
|---|---|
| `valid webhook settles payment confirms order and collects deposit` | Webhook sah → pembayaran `verified`, order `confirmed`, deposit terkumpul, attempt `settlement` |
| `webhook with invalid signature is rejected and changes nothing` | Tanda tangan salah → HTTP 401, tidak ada perubahan |
| `webhook with wrong amount is rejected` | Nominal beda → HTTP 422, pembayaran tetap `unpaid` |
| `duplicate webhook is idempotent` | Notifikasi diulang → `duplicate`, tidak menggandakan deposit |
| `gateway payment cannot be manually verified` | Verifikasi manual gateway ditolak |
| `workflow only status cannot be reached by manual transition` | `picked_up` ditolak via transisi manual |
| `refund calculation follows policy tiers` | Refund 100% / 50% / 0% sesuai jam sebelum mulai + deposit penuh |
| `deposit ledger collect deduct and refund keep balance consistent` | Ledger deposit konsisten, saldo akhir 0, 3 transaksi |
| `overdue late fee uses grace period and daily rate snapshot` | Grace 60 menit; 1 hari → denda 50.000 (harian 50.000) |
| `handover is blocked without verified payment` | Handover ditolak tanpa pembayaran terverifikasi |
| `inspection releases only the inspected unit` | Pelepasan per unit; unit lain tetap `inspection_pending`; order tetap `picked_up` |

### `tests/MySql/MySqlCrudIntegrationTest.php` (tambahan 1 tes)

| Tes | Membuktikan |
|---|---|
| `mysql phase5 payment and operations schema` | 14 tabel baru ada & InnoDB; kolom `method` = varchar (bukan ENUM); uang = int/bigint; unique index `event_hash`; persist `payments` (midtrans, integer) |

## 3. Yang Teruji Tanpa Kredensial Midtrans

Semua alur gateway diuji memakai `FakePaymentGateway` (binding otomatis saat `APP_ENV=testing`).
Verifikasi tanda tangan, validasi nominal, deduplikasi, idempotensi, dan pemetaan status **tidak** memerlukan jaringan.

## 4. Yang BELUM Teruji (BLOCKED)

- Panggilan HTTP nyata ke Midtrans Snap API.
- Notifikasi asli dari Midtrans (format respons produksi/sandbox).
- Refund nyata via API gateway.

Alasan: tidak tersedia `MIDTRANS_SERVER_KEY`/`MIDTRANS_CLIENT_KEY` asli.
Langkah verifikasi setelah kredensial tersedia: lihat `MIDTRANS_INTEGRATION.md` §8.

## 5. Cara Menjalankan

```bash
# SQLite (cepat, default)
php artisan test --testsuite=Unit,Feature

# MySQL nyata (butuh database gearora_test dan kredensial di .env.testing/phpunit.mysql.xml)
php artisan test -c phpunit.mysql.xml

# Skema baru spesifik
php artisan test --filter=Phase5PaymentOperationsTest
```

> MySQL suite **menolak** berjalan bila database bukan `gearora_test` (guard di `beforeRefreshingDatabase()`), mencegah penghapusan data produksi.

## 6. Verifikasi Migrasi

- `php artisan migrate` (MySQL dev `gearora`) — 16 migrasi baru berstatus DONE.
- Semua migrasi memiliki `down()` (reversible).
- Kompatibilitas MySQL + SQLite diverifikasi (ENUM → string, integer money).
