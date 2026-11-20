# PHASE 9 — Trip Package Architecture

Tanggal: 20 November 2026
Lihat juga: `.docs/ADR_PHASE_9_TRIP_PACKAGE.md`

## Tujuan

Trip Planner Gunung Prau mengubah kombinasi gear sewa dan gear beli menjadi
**satu paket** (`trip_packages`) yang menghasilkan **dua pesanan domain
terpisah**: pesanan rental dan pesanan penjualan. Tidak ada penggabungan
charge pembayaran lintas domain.

## Alur data

```
TripPlannerController (GET /rekomendasi-trip)
  → POST /rekomendasi-trip         (TripPlannerRequest) → rekomendasi
  → POST /rekomendasi-trip/paket   (TripPackageRequest)  → draft di session
  → GET  /rekomendasi-trip/paket   → halaman pratinjau (review)
  → POST /rekomendasi-trip/paket/checkout → TripPackageCheckoutService
        → RentalOrder (sewa)  + SalesOrder (beli)
        → TripPackage menautkan kedua pesanan
  → GET  /akun/paket/{code}         → ringkasan paket (ownership)
  → GET  /akun/paket/{code}/dokumen → dokumen HTML cetak
  → GET  /akun/paket/{code}/pdf     → PDF
```

## Komponen kunci

- `app/Data/TripPlanData.php` — input planner tervalidasi.
- `app/Data/TripPackageData.php` — item paket (variant, qty, fulfillment).
- `app/Models/TripPackage.php` — status + `checkout_key` unik.
- `app/Services/TripPackageCheckoutService.php` — orkestrasi checkout.
- `app/Policies/TripPackagePolicy.php` — akses pemilik & staf.
- `app/Http/Controllers/PublicSite/TripPackageController.php` — draft, review,
  checkout, dokumen, PDF.
- `app/Http/Controllers/Admin/TripPackageController.php` — pemantauan staf.

## Aturan keamanan & integritas

- **Harga & stok server-side.** Checkout menghitung ulang harga, ketersediaan,
  dan stok lewat service domain; input harga dari klien diabaikan.
- **Tanpa mutasi stok langsung.** Alokasi rental hanya lewat service rental;
  reservasi jual hanya lewat `SalesStockService`. Stok fisik jual berkurang
  hanya saat pembayaran terverifikasi.
- **Idempotensi.** `checkout_key` (UUID) disimpan di session, dikirim sebagai
  field tersembunyi di form review, dan dilindungi `Cache::lock`. Permintaan
  ganda (double click/retry) dengan key sama mengembalikan paket yang sudah ada,
  bukan membuat pesanan baru.
- **Ownership.** Halaman paket/dokumen/PDF hanya untuk pemilik paket atau staf;
  pelanggan lain ditolak.
- **Login return.** Guest yang checkout diarahkan ke login dengan
  `url.intended` lokal-aman menuju halaman paket.

## Status paket

Status paket mengikuti pesanan tertaut: `draft`, `awaiting_payment`,
`partially_confirmed`, `confirmed`, `completed`, `cancelled`. Tidak ada state
machine duplikat — nilai diturunkan dari status pesanan rental/penjualan.

## Dokumen & PDF

- `resources/views/trip-planner/package-document.blade.php` — dokumen HTML
  siap cetak (tombol Cetak memakai `window.print()`).
- PDF dihasilkan server-side; smoke browser: `GET .../pdf` →
  `200 application/pdf`.

## Bukti uji

| Berkas | Hasil |
|---|---|
| `tests/Feature/TripPackageTest.php` | 9 passed (37 assertions) |
| `tests/Feature/TripPlannerWizardTest.php` | 6 passed (19 assertions) |
| `tests/Feature/AdminTripPackageTest.php` | 3 passed (10 assertions) |
| `tests/Feature/TripPlannerTest.php` | termasuk dalam 31 passed gabungan |

Bukti detail di `.docs/PHASE_9_TRIP_PACKAGE_TEST_REPORT.md`.
