# PHASE 9 — Trip Package Test Report

Tanggal: 20 November 2026

## Scope

Wizard Trip Planner Gunung Prau, draft paket, checkout campuran sewa+beli,
idempotensi, kepemilikan dokumen/PDF, dan pemantauan staf.

## Hasil otomatis

| Berkas | Hasil |
|---|---|
| `tests/Feature/TripPackageTest.php` | 9 passed (37 assertions) |
| `tests/Feature/TripPlannerWizardTest.php` | 6 passed (19 assertions) |
| `tests/Feature/AdminTripPackageTest.php` | 3 passed (10 assertions) |
| `tests/Feature/TripRecommendationRuleTest.php` | termasuk suite penuh |

Gabungan berkas trip terkait: **31 passed (101 assertions)**.
Suite penuh SQLite: **331 passed, 1020 assertions**.

## Cakupan kasus

- Tamu menyimpan gear terpilih sebagai pratinjau paket.
- Checkout paket sewa membuat pesanan rental; paket beli membuat pesanan
  penjualan + reservasi stok jual.
- **Idempotensi:** permintaan checkout ganda dengan `checkout_key` sama
  menghasilkan tepat satu `TripPackage` dan satu pesanan rental
  (`test_duplicate_checkout_with_same_session_key_creates_one_package`).
- Harga & stok dihitung ulang server; nilai klien diabaikan.
- Kepemilikan: pelanggan non-pemilik ditolak mengakses paket/dokumen/PDF.
- Login return ke halaman paket setelah autentikasi.
- Admin: daftar + filter status, detail paket; pelanggan ditolak (403).
- Dokumen HTML dan PDF paket ter-render.

## Bukti browser (lihat `PHASE_9_BROWSER_E2E_RESULTS.md`)

- Checkout transfer → redirect `/akun/paket/GRP-20261120-VGIL`.
- `GET .../pdf` → `200 application/pdf` (879722 byte).
- Admin `/admin/trip-packages` → HTTP 200.

## MySQL (opt-in)

`vendor/bin/phpunit -c phpunit.mysql.xml tests/MySql` → 7 passed (71 assertions),
target dipaksa `gearora_test`. Lihat `.docs/PHASE_9_MYSQL_INTEGRATION.md`.

## Catatan

Gateway pembayaran otomatis (Midtrans) tidak dipanggil pada uji ini. Jalur
transaksional campuran diuji pada MySQL memakai savepoint.
