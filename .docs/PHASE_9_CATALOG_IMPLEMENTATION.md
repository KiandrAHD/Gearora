# PHASE 9 — Catalog Implementation

Tanggal: 20 November 2026

## Ringkasan

Katalog publik mendukung dua mode — **sewa** dan **beli** — dengan filter,
urutan, dan kartu produk yang sadar-mode. Logika tetap di
`Controller → Form Request → Service → Model`, tanpa mutasi stok langsung.

## Komponen

- `app/Http/Controllers/PublicSite/CatalogController.php`
  - Menentukan mode aktif (`sewa`/`beli`) dari query string.
  - Menyusun filter: kategori, harga min/max, ketersediaan.
  - Urutan: unggulan/terbaru, terbaru, nama A–Z, harga terendah, harga tertinggi.
  - Menyaring produk status aktif saja.
- `resources/views/catalog/index.blade.php`
  - Toolbar mode + urutan + pencarian.
  - Sidebar filter (`#catalog-filters`); pada ≤900px menjadi drawer
    (`.catalog-sidebar.is-open`) dengan tombol tutup; fokus dipindah ke tombol
    tutup saat dibuka; tombol `Esc` menutup drawer.
- Kartu produk: `resources/views/components/...` menampilkan harga sesuai mode.
  Mode sewa tidak menampilkan harga jual, dan sebaliknya.

## Aturan bisnis

- **Mode-aware price.** Harga sewa memakai tarif harian aset; harga beli memakai
  harga varian jual. Nilai mode sewa tidak pernah bocor ke tampilan beli.
- **Ketersediaan.** Stok sewa berasal dari alokasi aset rental; stok beli dari
  stok fisik dikurangi reservasi aktif. Perhitungan memakai service domain,
  bukan logika view.
- **Gambar produk.** Bila foto belum ada, dipakai placeholder aman; unggahan
  disajikan lewat endpoint media tervalidasi.

## Bukti uji

| Berkas | Hasil |
|---|---|
| `tests/Feature/RentalCatalogFilterTest.php` | 10 passed (37 assertions) |
| `tests/Feature/RetailCatalogFilterTest.php` | 7 passed (22 assertions) |
| `tests/Feature/ProductCardModePriceTest.php` | 4 passed (13 assertions) |
| `tests/Feature/ProductDetailTest.php` | 7 passed (23 assertions) |

Smoke browser: `GET /katalog?mode=sewa` dan `?mode=beli` → HTTP 200, kartu
produk dan kontrol filter ter-render (lihat `PHASE_9_BROWSER_E2E_RESULTS.md`).

## Catatan

- Drawer filter tidak menulis apa pun ke server; filter hanya query-string.
- Tidak ada perubahan skema database pada modul katalog.
