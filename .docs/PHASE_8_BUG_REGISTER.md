# PHASE 8 — Bug Register

> Tanggal: 20 November 2026 · Basis: `8c3aaa5` · Status akhir semua bug di bawah: **FIXED & teruji**.

Skala prioritas: **P0** (blokir rilis: keamanan uang/stok atau pembayaran tak sah),
**P1** (keamanan/robustness penting), **P2** (perbaikan kualitas/UX).

Setiap entri memuat: deskripsi, dampak, akar masalah, perbaikan, dan bukti uji.

---

## B1 — Webhook mendaftarkan event sebelum verifikasi signature (P0)

- **Modul:** `app/Services/PaymentNotificationService.php`
- **Dampak:** penyerang yang mengirim notifikasi palsu (signature salah) dengan `order_id`
  tertentu dapat lebih dulu mengklaim `event_hash` notifikasi itu. Notifikasi **valid** berikutnya
  dari Midtrans dianggap `duplicate` dan diabaikan → pembayaran sah tidak pernah ter-settle
  (denial-of-settlement).
- **Akar masalah:** `handle()` memanggil `firstOrCreate` berdasarkan `event_hash` (dihitung dari
  payload mentah) **sebelum** `MidtransSignature::isValid()`.
- **Perbaikan:** verifikasi signature dilakukan **sebelum** event didaftarkan. Penolakan signature
  dicatat pada namespace hash terpisah (`sig:<sha256>`) sehingga tidak pernah mengklaim fingerprint
  notifikasi valid. Attempt tak dikenal juga tidak mendaftarkan event (aman untuk race).
- **Bukti:** `tests/Feature/WebhookSecurityTest.php::test_invalid_signature_does_not_poison_later_valid_notification`,
  `::test_unknown_attempt_does_not_register_blocking_event`.

## B2 — Event `Failed` tidak dapat diretry / payload cacat mengunci fingerprint (P0)

- **Modul:** `app/Services/PaymentNotificationService.php`
- **Dampak:** kegagalan sementara (mis. DB sibuk) menandai event `Failed`, lalu seluruh retry
  dari Midtrans selamanya dianggap duplikat → pembayaran hilang. Payload cacat (tanpa field wajib)
  juga bisa mengunci hash.
- **Akar masalah:** `firstOrCreate` memperlakukan semua event lama sebagai duplikat; tidak ada
  jalur retry untuk status `Failed`; validasi struktur payload absen.
- **Perbaikan:**
  - Validasi struktur payload lebih dulu (`invalid_payload`).
  - `reserveEvent()`: `Processed`/`Duplicate`/`Received`/`Rejected` → dilewati; **hanya `Failed`
    yang boleh dicoba ulang**, ditandai ulang `Received` di bawah `lockForUpdate` (aman dari retry
    paralel). Bentrokan insert (race) diperlakukan sebagai duplikat.
- **Bukti:** `WebhookSecurityTest::test_failed_event_can_be_retried_and_settles`,
  `::test_processed_event_is_not_reprocessed`, `::test_amount_mismatch_then_correct_amount_is_processed`.

## B3 — Provider pembayaran tiruan dapat aktif di produksi (P0)

- **Modul:** `app/Providers/AppServiceProvider.php`
- **Dampak:** bila `MIDTRANS_FAKE=true` di produksi, transaksi dianggap "berhasil" tanpa uang
  nyata → kerugian langsung.
- **Akar masalah:** binding gateway mengembalikan `FakePaymentGateway` bila
  `environment('testing') || config('gearora.midtrans.fake')` tanpa pengecualian produksi.
- **Perbaikan:** produksi **dilarang** memakai provider tiruan; flag `MIDTRANS_FAKE=true` di
  produksi melempar `LogicException`. Lingkungan `local` hanya memakai fake bila opt-in eksplisit;
  `testing` selalu fake.
- **Bukti:** `tests/Feature/PaymentGatewayGuardTest.php` (3 kasus).

## B4 — Reservasi stok retail kedaluwarsa tetap menahan stok (P0)

- **Modul:** `app/Models/SalesStockReservation.php`, `app/Services/SalesStockService.php`,
  `app/Services/SalesOrderExpiryService.php` (baru), `app/Console/Commands/ExpireSalesOrdersCommand.php` (baru)
- **Dampak:** order `PendingPayment` yang tak dibayar mengunci stok selamanya → stok tidak
  terjual meski tak ada pembeli.
- **Akar masalah:** `scopeActive()` hanya memeriksa `status = reserved`; `expires_at` tidak pernah
  dipakai untuk pelepasan, dan tidak ada proses expiry.
- **Perbaikan:**
  - `scopeActive()` menjadi sadar waktu: `status = reserved` **dan** (`expires_at` null atau > now()).
  - `SalesOrderExpiryService::expireStale()`/`expireOrder()` mengubah order `PendingPayment`/
    `PaymentReview` yang lewat `expires_at` menjadi `Expired`, menandai attempt/payment pending
    menjadi `Expired`, dan melepas reservasi (tanpa memotong stok). Semua di bawah lock order.
    Jalur pembayaran yang sudah sukses (attempt settlement/capture atau payment `Verified`)
    **diabaikan** agar tidak balapan dengan settlement.
  - `releaseForOrder()` memakai `status = reserved` (bukan `active()`) agar reservasi yang lewat
    waktu namun belum dimutasi tetap dilepas saat pembatalan.
  - Command: `php artisan gearora:expire-sales-orders [--limit=]`.
- **Bukti:** `tests/Feature/SalesReservationExpiryTest.php` (6 kasus), termasuk idempotensi dan
  pelepasan stok tanpa konsumsi.

## B5 — Late settlement mengonfirmasi order yang sudah final (P0)

- **Modul:** `app/Services/SalesPaymentService.php` (`markSettled`)
- **Dampak:** pembayaran retail yang masuk setelah reservasi kedaluwarsa/dilepas dapat
  mengonfirmasi order dan **memotong stok** untuk order yang seharusnya batal → stok negatif/logika salah.
- **Akar masalah:** kondisi `late` hanya memeriksa status order `Cancelled/Expired` dan status
  payment `Expired/Failed`, tidak memeriksa tidak adanya reservasi aktif.
- **Perbaikan:** `late` juga bernilai true bila order masih `PendingPayment`/`PaymentReview`
  tetapi jumlah reservasi aktif = 0. Pada jalur late, payment ditandai `Processing` (bukan
  `Verified`), stok tidak dipotong, order tidak dikonfirmasi, dan dicatat untuk rekonsiliasi.
- **Bukti:** `SalesReservationExpiryTest::test_late_settlement_after_reservation_expiry_does_not_consume_or_confirm`,
  `::test_settlement_before_expiry_consumes_stock_and_confirms`.

## B6 — Password Owner dapat lewat argumen command (P1)

- **Modul:** `app/Console/Commands/ProvisionOwnerCommand.php`
- **Dampak:** rahasia terekspos di riwayat shell dan daftar proses OS.
- **Perbaikan:** opsi `--password` dihapus. Password hanya diminta via prompt rahasia ganda
  (`secret()`), divalidasi kekuatannya (`min:8` + huruf + angka) dan harus dikonfirmasi.

## B7 — Endpoint media publik memperbolehkan SVG/GIF (P1)

- **Modul:** `app/Http/Controllers/PublicSite/MediaController.php`
- **Dampak:** berkas `.svg` dapat memuat script dan dieksekusi pada origin aplikasi (stored XSS).
- **Perbaikan:** hanya raster `jpg/jpeg/png/webp`. Ditambah penolakan path traversal
  (`..`, `\`, null byte, ekstensi berbahaya) dan header `X-Content-Type-Options: nosniff` serta
  `Content-Security-Policy: default-src 'none'; img-src 'self'; sandbox`.
- **Bukti:** `tests/Feature/PrivateUploadSecurityTest::test_public_media_serves_images_but_blocks_dangerous_paths`.

## B8 — Bug binding route bukti pembayaran retail (P1)

- **Modul:** `app/Http/Controllers/Customer/SalesPaymentProofController.php`
- **Dampak:** route `customer.sales.proof.store` mendeklarasikan `{salesOrder}` + `{payment}`,
  tetapi controller hanya menerima satu parameter bertipe `SalesPayment` → **HTTP 500** saat
  dipakai, dan tidak ada pemeriksaan kepemilikan eksplisit. Bug laten karena belum ada test.
- **Perbaikan:** signature `store(Request, SalesOrder $salesOrder, SalesPayment $payment)`,
  otorisasi `view` pada order, dan `abort_unless($payment->sales_order_id === $salesOrder->id, 404)`
  (anti-IDOR).
- **Bukti:** `tests/Feature/CustomerOwnershipSecurityTest::test_customer_cannot_submit_sales_payment_proof_for_other_order`.

## B9 — Tidak ada gate konfigurasi produksi (P2)

- **Modul:** `app/Support/ProductionReadiness.php` (baru), `app/Console/Commands/CheckProductionReadinessCommand.php` (baru),
  `app/Providers/AppServiceProvider.php`
- **Perbaikan:** gate otomatis memeriksa `APP_DEBUG`, `APP_URL` https, `APP_KEY`, `SESSION_SECURE_COOKIE`,
  `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, `MIDTRANS_FAKE`. Dipakai sebagai asersi saat boot
  produksi dan command `php artisan gearora:production-check` (keluar non-nol bila melanggar).
- **Bukti:** `tests/Feature/ProductionConfigurationTest.php` (5 kasus).

## B10 — Respons 403 dirender memakai view 500 (P2)

- **Modul:** `resources/views/errors/403.blade.php` (baru), `resources/views/errors/4xx.blade.php` (baru)
- **Temuan:** QA browser menemukan respons berstatus 403 menampilkan halaman "500 — Terjadi
  kesalahan" (karena hanya ada view `404`, `500`, `minimal`; fallback 4xx memakai view 500).
- **Perbaikan:** view `403` spesifik + fallback generik `4xx` yang membaca kode HTTP asli dari
  `$exception`. Status HTTP tetap benar; tampilan/pesan kini sesuai.
- **Bukti:** `tests/Feature/ErrorPageRenderingTest.php`, dan verifikasi browser nyata (lihat
  `PHASE_8_E2E_TEST_RESULTS.md`).

---

## Matriks Ringkas

| ID | Prioritas | Area | Status | Bukti utama |
|---|---|---|---|---|
| B1 | P0 | Webhook signature-first | FIXED | WebhookSecurityTest |
| B2 | P0 | Retry event Failed | FIXED | WebhookSecurityTest |
| B3 | P0 | Guard gateway produksi | FIXED | PaymentGatewayGuardTest |
| B4 | P0 | Expiry reservasi retail | FIXED | SalesReservationExpiryTest |
| B5 | P0 | Late settlement guard | FIXED | SalesReservationExpiryTest |
| B6 | P1 | Provisioning Owner | FIXED | Tinjauan kode |
| B7 | P1 | Media publik SVG/XSS | FIXED | PrivateUploadSecurityTest |
| B8 | P1 | Binding route bukti retail | FIXED | CustomerOwnershipSecurityTest |
| B9 | P2 | Gate konfigurasi produksi | FIXED | ProductionConfigurationTest |
| B10 | P2 | Halaman 403 salah view | FIXED | ErrorPageRenderingTest + browser |
