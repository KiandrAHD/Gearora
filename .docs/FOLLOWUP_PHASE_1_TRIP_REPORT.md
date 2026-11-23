# Follow-up Phase 1 — Trip Planner Gunung Prau

Status: selesai.

## Perubahan

- Wizard Trip Planner 5 tahap dengan layout visual 2 kolom.
- Aset Gunung Prau lokal, kredit CC0 tersimpan di `public/images/trips/CREDITS.md`.
- `departure_time` tersimpan pada `trip_packages` dan snapshot.
- `end_date` dihitung server dari `start_date + duration_days`.
- Mode `Fleksibel` menerima rule sewa/beli; fulfillment tetap divalidasi server saat checkout.
- Availability rental hanya diperiksa untuk produk yang mendukung rental.
- Detail paket, dokumen HTML, dan PDF menampilkan data rencana serta status pembayaran per order.

## Verifikasi

- `php artisan test --compact`: 336 passed, 1048 assertions.
- `vendor/bin/phpunit -c phpunit.mysql.xml tests/MySql`: 7 passed, 71 assertions.
- `npm run build`: pass.
- Playwright desktop: 20 passed, 1 skipped.
- Playwright mobile: 21 passed.

Commit: `54c434a fix(trip): align Gunung Prau planner with Figma`.
