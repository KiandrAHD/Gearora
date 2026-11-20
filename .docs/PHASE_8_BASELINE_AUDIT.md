# PHASE 8 — Baseline Audit (GEARORA)

> Fase: **PHASE 8 — Release Readiness, Security & QA**
> Tanggal: 20 November 2026
> Basis commit sebelum fase: `8c3aaa5` (`main` == `origin/main`, working tree bersih saat mulai)
> Lingkungan audit: Windows, Laravel 12.72.0, PHP 8.5.9, MySQL 8.4.3

---

## 1. Tujuan

Mengukur kondisi aplikasi sebelum hardening Phase 8: stack, migrasi, suite test, kualitas statis,
dependency, dan utang keamanan. Semua angka di bawah diambil dengan menjalankan perintah nyata
(bukan asumsi). Yang tidak dapat dijalankan ditandai **BLOCKED**.

## 2. Ringkasan Lingkungan

| Komponen | Versi / Nilai | Status |
|---|---|---|
| PHP | 8.5.9 (minimum proyek 8.2) | OK |
| Composer | 2.9.7 | OK |
| Node / npm | 26.13.0 / 11.20.0 | OK |
| Laravel | 12.72.0 | OK |
| MySQL | 8.4.3 (Laragon, `127.0.0.1:3306`) | OK |
| Database dev | `gearora` (`APP_ENV=local`, `APP_DEBUG=true`) | OK |
| Database test | `gearora_test` (disposable) | OK |
| Route terdaftar | 136 | OK |

Ekstensi PHP terverifikasi: `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`,
`openssl`, `curl`, `dom`, `xml`, `tokenizer`, `bcmath`, `intl`, `gd`, `zip`.

## 3. Baseline Otomatis (sebelum perubahan Phase 8)

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| Unit + Feature (SQLite memory) | `php artisan test --compact` | 206 lulus / 624 assertion |
| Integrasi MySQL | `php artisan test --configuration=phpunit.mysql.xml` | 4 lulus / 56 assertion |
| Gaya kode | `vendor/bin/pint --test` | lulus |
| Manifest Composer | `composer validate --strict` | valid |
| Audit Composer | `composer audit` | tidak ada advisory |
| Audit npm | `npm audit` | 0 kerentanan |
| Build aset | `npm run build` | sukses |

## 4. Peta Modul & Titik Risiko yang Ditinjau

| Modul | Titik tinjauan | Risiko awal |
|---|---|---|
| Webhook Midtrans | `PaymentNotificationService::handle` | Urutan validasi signature; dedup & retry event |
| Gateway pembayaran | `AppServiceProvider` singleton | Provider tiruan dapat aktif di produksi |
| Reservasi stok retail | `SalesStockReservation::scopeActive`, `SalesStockService` | Reservasi kedaluwarsa masih menahan stok |
| Settlement retail | `SalesPaymentService::markSettled` | Late settlement mengonfirmasi order yang sudah dibatalkan/kedaluwarsa |
| Provisioning Owner | `ProvisionOwnerCommand` | Password dapat lewat argumen command (riwayat shell) |
| Endpoint media publik | `PublicSite\MediaController` | Ekstensi `svg`/`gif` dapat menyimpan script (stored XSS) |
| Bukti pembayaran retail | `Customer\SalesPaymentProofController` | Parameter route tidak cocok → error 500 (IDOR/route binding) |
| Konfigurasi produksi | boot aplikasi | Tidak ada gate otomatis untuk debug/session/https/gateway |
| Halaman error | `resources/views/errors` | Respons 403 dirender memakai view 500 (salah status & pesan) |

## 5. Temuan Awal (masuk ke Bug Register)

Ringkasan temuan yang dikonfirmasi pada audit baseline (detail: `PHASE_8_BUG_REGISTER.md`):

- **B1 (P0)** Webhook mendaftarkan `event_hash` sebelum memverifikasi signature → *event poisoning*.
- **B2 (P0)** Event berstatus `Failed` tidak dapat dicoba ulang; payload cacat dapat mengunci fingerprint valid.
- **B3 (P0)** Provider pembayaran tiruan dapat aktif di produksi bila flag salah konfigurasi.
- **B4 (P0)** Reservasi stok retail yang lewat `expires_at` tetap menahan stok (tidak ada expiry).
- **B5 (P0)** Late settlement retail mengonfirmasi order yang sudah dibatalkan/kedaluwarsa.
- **B6 (P1)** Opsi `--password` pada `gearora:provision-owner` membocorkan rahasia via argumen proses.
- **B7 (P1)** `MediaController` memperbolehkan `.svg`/`.gif` → potensi stored XSS pada origin aplikasi.
- **B8 (P1)** `SalesPaymentProofController::store` tidak menerima parameter `{salesOrder}` → HTTP 500 (bukan 403) dan tidak memvalidasi kepemilikan.
- **B9 (P2)** Tidak ada gate konfigurasi produksi otomatis (debug/session/https/gateway).
- **B10 (P2)** Respons 403/419 dirender memakai view `errors/500` (status benar, tampilan/pesan salah).

## 6. Yang Tidak Dapat Diverifikasi (BLOCKED)

| Item | Alasan | Tindakan lanjutan |
|---|---|---|
| Hosting AeonFree (PHP/cPanel/MySQL) | Tidak ada akses cPanel | Checklist di `.docs/PHASE_0_HOSTING_VALIDATION.md` |
| Midtrans Sandbox end-to-end nyata | Tidak ada URL webhook HTTPS publik di lingkungan audit | Verifikasi manual operator |
| Paralelisme multi-proses (pcntl) | Windows tanpa pcntl/parallel test | Digantikan uji dua-koneksi MySQL (lihat `PHASE_8_MYSQL_INTEGRATION_REPORT.md`) |

## 7. Kesimpulan Baseline

Aplikasi hijau secara fungsional pada SQLite dan MySQL, tetapi memiliki **5 bug P0** pada alur
pembayaran dan stok retail yang harus diperbaiki sebelum rilis. Rencana perbaikan, pengujian,
dan gate rilis ada di dokumen Phase 8 lainnya. Tidak ada operasi destruktif dijalankan terhadap
database `gearora`; pengujian MySQL hanya memakai `gearora_test`.
