# PHASE 8 — Test Plan

> Tanggal: 20 November 2026 · Runtime uji: PHP 8.5.9 · Laravel 12.72.0

## 1. Strategi

Tiga lapisan uji, semuanya deterministik dan tanpa data nyata:

1. **Unit + Feature (SQLite `:memory:`)** — logika lintas modul, cepat, default `php artisan test`.
2. **Integrasi MySQL 8 (`gearora_test`)** — parity skema/transaksi DB nyata (opt-in).
3. **E2E browser nyata** — verifikasi HTTP+CSRF+render pada server dev (lihat `PHASE_8_E2E_TEST_RESULTS.md`).

Aturan keselamatan uji: SQLite terpisah; MySQL hanya `gearora_test` (diproteksi `beforeRefreshingDatabase`).
Tidak ada `migrate:fresh`/`db:wipe` pada `gearora`.

## 2. Perintah

```bash
# Suite utama
php artisan test --compact

# Integrasi MySQL (butuh database test)
vendor/bin/phpunit --configuration=phpunit.mysql.xml

# Gaya kode & audit
vendor/bin/pint --test
composer validate --strict
composer audit
npm audit
npm run build
```

## 3. Hasil Suite Utama

| Metrik | Baseline (pra-Phase 8) | Setelah Phase 8 |
|---|---|---|
| Tests | 206 | **249** |
| Assertions | 624 | **747** |
| Durasi | ~89s | ~35–37s |
| Status | lulus | **lulus** |

## 4. Cakupan Baru (berkas test Phase 8)

| Berkas | Fokus | Kasus |
|---|---|---|
| `tests/Feature/WebhookSecurityTest.php` | Signature-first, anti event-poisoning, retry `Failed`, dedup | 5 |
| `tests/Feature/SalesReservationExpiryTest.php` | Expiry reservasi, konsumsi, late settlement, idempotensi | 6 |
| `tests/Feature/PaymentGatewayGuardTest.php` | Guard provider tiruan vs produksi | 3 |
| `tests/Feature/ProductionConfigurationTest.php` | Gate konfigurasi produksi + command | 5 |
| `tests/Feature/CustomerOwnershipSecurityTest.php` | IDOR rental & retail, bukti pembayaran | 6 |
| `tests/Feature/PrivateUploadSecurityTest.php` | Disk privat, mime/ukuran, traversal, SVG | 4 |
| `tests/Feature/CsrfSecurityTest.php` | Grup web/api, POST tanpa token → 419 | 4 |
| `tests/Feature/RateLimitSecurityTest.php` | Throttle login customer/staf, reset pada sukses | 3 |
| `tests/Feature/AdminRouteAuthorizationTest.php` | RBAC area admin per role | 5 |
| `tests/Feature/ErrorPageRenderingTest.php` | 403/404 view dan kode benar | 2 |
| `tests/MySql/MySqlConcurrencyTest.php` | Row lock InnoDB dua koneksi | 1 |

Total kasus baru: **44** (43 Feature/Unit + 1 MySQL).

## 5. Matriks Risiko → Uji

| Risiko | Uji penutup |
|---|---|
| Event poisoning webhook | WebhookSecurityTest (B1) |
| Pembayaran hilang karena retry gagal | WebhookSecurityTest (B2) |
| Gateway palsu di produksi | PaymentGatewayGuardTest (B3) |
| Stok terkunci selamanya | SalesReservationExpiryTest (B4) |
| Stok negatif karena late settlement | SalesReservationExpiryTest (B5) |
| Rahasia Owner bocor | Tinjauan kode `ProvisionOwnerCommand` (B6) |
| Stored XSS via media publik | PrivateUploadSecurityTest (B7) |
| IDOR/binding bukti retail | CustomerOwnershipSecurityTest (B8) |
| Config produksi tak aman | ProductionConfigurationTest, AppServiceProvider (B9) |
| Status error membingungkan | ErrorPageRenderingTest (B10) |
| Overselling konkuren | MySqlConcurrencyTest |
| CSRF dimatikan global | CsrfSecurityTest |
| Brute force login | RateLimitSecurityTest |

## 6. Kriteria Lulus (Gate Fase)

- Suite utama **hijau** tanpa skip.
- Integrasi MySQL **hijau**.
- `pint --test`, `composer validate`, `composer audit`, `npm audit`, `npm run build` **lulus**.
- Setiap bug P0/P1 pada register punya uji regresi.

## 7. Evaluasi Akhir

Semua kriteria **TERPENUHI** (lihat `PHASE_8_RELEASE_CHECKLIST.md`). Seluruh kasus baru dan
lama lulus; tidak ada test yang di-skip atau dibiarkan gagal.
