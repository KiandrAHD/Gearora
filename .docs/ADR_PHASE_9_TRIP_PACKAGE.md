# ADR — Paket Trip Planner (Sewa + Beli)

> Tanggal: 20 November 2026
> Status: Diterima
> Konteks: Phase 9 — Trip Planner Gunung Prau, Milestone 6–7

## Konteks

Trip Planner memungkinkan pelanggan memilih beberapa gear sekaligus, sebagian
disewa dan sebagian dibeli, lalu checkout. Sistem existing memiliki dua alur
order terpisah dengan state machine dan sumber kebenaran masing-masing:

- **RentalOrder** (`RentalOrderService`): alokasi unit fisik, ketersediaan via
  `RentalAvailabilityService`, deposit via `RentalPricingService`.
- **SalesOrder** (`SalesOrderService`): reservasi stok via `SalesStockService`,
  harga jual per unit.

Gateway pembayaran (Midtrans Snap) dan bukti transfer saat ini dibuat **per
order** (`rental_orders.payments`, `sales_orders.payments`).

## Keputusan

1. **Satu paket → dua order tertaut.** Tabel baru `trip_packages` menautkan
   `rental_order_id` dan `sales_order_id` (keduanya nullable) serta menyimpan
   snapshot rencana (`plan_snapshot`). Paket **tidak** menyimpan nominal atau
   status pembayaran.

2. **Checkout atomik lewat `TripPackageCheckoutService`.** Service membuka satu
   `DB::transaction`, lalu memanggil `RentalOrderService::create` dan/atau
   `SalesOrderService::checkout` (nested transaction = savepoint). Bila salah
   satu gagal, seluruh paket di-rollback. Tidak ada mutasi stok/harga di luar
   service domain tersebut.

3. **Harga & ketersediaan server-side.** Draft paket hanya menyimpan pilihan
   (`variant_id`, `quantity`, `fulfillment`). Harga, deposit, dan stok dihitung
   ulang oleh service domain saat pratinjau dan saat checkout. Nominal tidak
   pernah diterima dari klien.

4. **Pembayaran per order.** Paket **tidak** menggabungkan pembayaran menjadi
   satu tagihan gateway. Setiap order (sewa/beli) dibayar lewat alur existing
   (Midtrans/transfer). Status paket (`TripPackageStatus`) adalah agregasi
   status kedua order. Alasan: menghindari order-merging di gateway yang
   berisiko dan tidak kompatibel dengan webhook per-order yang sudah teruji.

5. **Mixed fulfillment divalidasi domain.** `fulfillment` (`sewa`/`beli`) harus
   didukung produk (`supportsRental()`/`supportsSale()`) dan memiliki harga
   valid. Nilai liar dari klien ditolak server-side.

## Konsekuensi

- Positif: nol perubahan pada alur pembayaran/keamanan Phase 8; reuse service
  domain; rollback menyeluruh pada kegagalan.
- Negatif: pelanggan melakukan dua pembayaran untuk paket mixed; perlu UX yang
  jelas (halaman paket menautkan kedua order).
- Risiko: nested transaction bergantung pada dukungan savepoint (MySQL/InnoDB,
  SQLite) — sudah tersedia di kedua target.

## Alternatif yang ditolak

- **Satu tagihan gateway gabungan**: memerlukan order-merging dan rekonsiliasi
  webhook baru → risiko tinggi, di luar cakupan.
- **Menyalin logika pemesanan ke service paket**: melanggar "single source of
  truth" stok/harga dan menambah jalur mutasi baru.
