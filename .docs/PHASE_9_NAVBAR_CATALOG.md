# PHASE 9 — Navbar and Catalog

Tanggal: 20 November 2026

## Navbar

- `.site-header-inner` memakai grid tiga kolom: brand kiri, navigasi tengah, aksi kanan.
- Underline aktif memakai posisi relatif elemen, bukan offset hardcoded `bottom:-28px`.
- Mobile: drawer Alpine, backdrop, Escape, dan scroll lock dipertahankan.
- Test: `NavigationAndMobileTest`, `PublicNavigationLayoutTest`, `RouteSmokeTest`.

## Catalog

- Layout: heading, search toolbar, sidebar filter sticky, grid responsif.
- Filter rental: kategori, harga per hari, tanggal, ketersediaan via `RentalAvailabilityService`.
- Filter retail: harga per unit, stok tersedia via `SalesStockService`; field tanggal rental disembunyikan.
- Product card: `<img>` dengan `alt`, `loading`, `decoding`, ukuran eksplisit, dan fallback `public/images/product-placeholder.svg`.
- Harga mode-aware: sewa menampilkan harga sewa; beli tidak pernah menampilkan harga sewa.
- Add-to-cart retail aktif pada halaman detail saat mode beli; catatan lama "Pembelian online belum tersedia" dihapus.
- Test: `RentalCatalogFilterTest`, `RetailCatalogFilterTest`, `ProductCardModePriceTest`, `ProductDetailTest`, `AdminProductImageLifecycleTest`.

## Safety

Semua harga dan ketersediaan dihitung ulang server-side. Controller tidak memutasi stok langsung.