# PHASE 9 — Trip Planner and Package

Tanggal: 20 November 2026

## Wizard

Lima langkah (`resources/views/trip-planner/index.blade.php` + `tripWizard` Alpine):

1. Aktivitas, destinasi, lokasi, catatan.
2. Durasi, peserta, level pengalaman.
3. Mode sewa/beli, tanggal gear sewa, kebutuhan khusus.
4. Ringkasan.
5. Pilih gear dari hasil rekomendasi.

Progress wizard client-side; hasil rekomendasi dan harga selalu dihitung ulang server.

## Rule engine

- `TripRecommendationRule.required_experience_levels` (JSON, nullable) memfilter `experience` dari `TripPlanData`.
- Null/empty berarti cocok untuk semua level.
- Hasil membawa `supports_rental`, `supports_sale`, `rental_price`, `sale_price`.

## Package

- Draft session `trip_package_draft` menyimpan pilihan: `variant_id`, `quantity`, `fulfillment` (`sewa`/`beli`).
- Pratinjau (`trip-planner/package-review`) menghitung ulang harga sewa (`RentalPricingService`) dan harga jual server-side.
- Checkout (`TripPackageCheckoutService`) membuka satu `DB::transaction` dan membuat RentalOrder dan/atau SalesOrder tertaut via service domain existing.
- Pembayaran per order (Midtrans/transfer) tetap pada alur Phase 8; paket tidak menggabungkan tagihan gateway.
- Halaman paket (`akun/paket/{package}`) menautkan kedua order; dokumen HTML dan PDF (`dompdf`) untuk pemilik paket.
- Ownership: hanya pemilik paket yang dapat melihat paket, dokumen, atau PDF.

## Test

- `TripPlannerWizardTest`, `TripPackageTest` (SQLite), `MySqlTripPackageIntegrationTest` (MySQL savepoint).
- Keputusan arsitektur: `.docs/ADR_PHASE_9_TRIP_PACKAGE.md`.