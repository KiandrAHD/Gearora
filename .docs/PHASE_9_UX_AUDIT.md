# PHASE 9 — UX AUDIT

> Tanggal: 20 November 2026
> Branch kerja: `feat/phase-9-gearora-experience`
> HEAD awal: `1a26c76` (`feat: complete phase 8 release readiness audit`)
> Baseline: Phase 8 — 249 SQLite tests (747 assertions), 5 MySQL integration tests, Pint/Composer/NPM hijau.

## 0. Ringkasan Eksekutif

Phase 9 menyentuh empat area publik utama: navbar global, katalog (sewa & beli), Trip Planner Gunung Prau
sampai transaksi, dan Panduan (editorial + studio Admin). Audit berikut memetakan implementasi existing,
gap visual/fungsional/business-logic, batasan keamanan, dan rencana implementasi.

## 1. Existing Behavior

### 1.1 Navbar (`resources/views/components/partials/site-header.blade.php`, `resources/css/app.css`)
- Header sticky, tinggi 78px (68px mobile), lebar `min(1180px, 100% - 40px)`.
- `.site-header-inner` memakai `display:flex; justify-content:space-between`.
- `.primary-nav` memakai `margin-left:35px; margin-right:auto` → navigasi **tidak** benar-benar di tengah.
- Underline aktif memakai `::after` dengan `bottom:-28px` (hardcoded, rapuh).
- Mobile: Alpine `open` state, `.mobile-nav` + backdrop, tombol hamburger, `Escape` menutup,
  `nav-open` mengunci scroll. Router nav tetap berfungsi.
- Cart badge `.cart-count` absolute di kanan atas ikon.

### 1.2 Katalog (`CatalogController`, `resources/views/catalog/index.blade.php`)
- Satu form filter horizontal (`.catalog-filter`) berisi `q`, `kategori`, `mode`, `start_date`, `end_date`.
- Grid produk `.product-grid` 4 kolom desktop, 2 kolom ≤900px, carousel horizontal ≤600px.
- **Tidak ada** sidebar filter, **tidak ada** sticky, **tidak ada** search toolbar terpisah,
  **tidak ada** sorting, **tidak ada** filter harga, **tidak ada** filter stok (retail).
- Availability rental dihitung benar via `RentalAvailabilityService::availableVariantIds`. Tanggal retail
  tidak dipisahkan dari rental (field tanggal selalu tampil).
- `ProductController::show` menampilkan detail + quote rental (benar).

### 1.3 Product Card (`components/partials/product-card.blade.php`)
- Memakai `background-image` inline, bukan `<img>` → **tidak** ada `alt`, `loading`, `width/height`,
  tidak ada fallback; bila `displayImagePath()` null → `product-photo` kosong berwarna `#DCE5DD`.
- Harga: memilih **rental lebih dulu** (`supportsRental() && rentalPrice > 0`) → di halaman Beli produk
  `both` menampilkan harga **sewa** (salah).

### 1.4 Trip Planner (`TripPlannerController`, `resources/views/trip-planner/index.blade.php`)
- Satu form POST sederhana (`activity`, `destination` bebas, `duration_days`, `participants`, `mode`,
  opsional `start_date`/`end_date`). Bukan wizard.
- Hasil rekomendasi berupa `info-card` polos tanpa pemilihan gear, tanpa harga, tanpa checkout.
- `TripRecommendationService` deterministic, data-driven dari `TripRecommendationRule`; mendukung
  activity, duration, participants, special needs, service mode, quantity, priority, reason template.
- **Belum ada** experience level, **belum ada** Trip Package, checkout, pembayaran, atau dokumen paket.

### 1.5 Panduan (`GuideController`, `GuideService`, `GuideContentRenderer`)
- Index: grid kartu `info-card` + filter q/kategori. Belum editorial, belum featured.
- Detail: `GuideContentRenderer` meng-escape HTML lalu mengubah subset Markdown (`#`,`##`,`###`,
  `-`, `1.`, `**tebal**`, `` `kode` ``). Aman terhadap XSS.
- **Belum ada**: italic, blockquote, link, divider, gambar inline, embed YouTube, TOC, related rapi,
  reading time, atau `is_featured`.
- Admin editor: `<textarea>` Markdown polos, tanpa toolbar/live preview.
- Publikasi/arsip/unpublish lewat `GuideService` + policy (sudah benar).

## 2. Visual Gaps

| Area | Target (Figma/deskripsi) | Kondisi now |
|---|---|---|
| Navbar | Logo kiri · nav tengah · aksi kanan, balanced | Nav bergeser, tidak center |
| Katalog | Heading kiri + deskripsi kanan · search toolbar · sidebar kiri sticky · grid 3 kolom | Form horizontal, 4 kolom, no sidebar, no sticky |
| Trip Planner | Navy gelap · hero kiri · form cream kanan · step progress | Halaman putih polos, satu form |
| Panduan | Hero editorial centered · featured · panel sand · grid 3 kolom | Grid kartu polos |

## 3. Functional Gaps

- Sorting katalog (backend) tidak ada.
- Filter harga (sewa/hari & jual/unit) tidak ada.
- Filter stok retail (fisik − reservation) tidak ada.
- Filter tanggal hanya untuk rental; di mode beli harus disembunyikan.
- Product card tanpa `<img>`/`alt`/lazy/fallback.
- Trip Planner: tidak ada experience, tidak ada pemilihan gear/quantity/mode, tidak ada order.
- Panduan: tidak ada featured, reading time, gambar inline, video aman, TOC, editor rich.

## 4. Business Logic Gaps

- **Trip Package** belum ada: tidak ada parent order yang menautkan Rental Order + Sales Order,
  tidak ada snapshot, tidak ada pembayaran gabungan, tidak ada PDF paket.
- **Experience level** belum ada di `TripPlanData`/rule engine.
- Katalog retail belum memakai `SalesStockService` untuk availability.
- `TripRecommendationRule` belum punya kolom `required_experience_levels`.

## 5. Security Constraints (Wajib Dipertahankan)

- Webhook signature + dedup + retry; FakePaymentGateway guard; CSRF; rate limit; production checks.
- Payment status **hanya** lewat `PaymentService`/`SalesPaymentService`/`MidtransPaymentService`/
  `PaymentNotificationService`.
- Stok: rental via `RentalAvailabilityService`; retail via `SalesStockService` (reservasi, bukan potong langsung).
- Harga server-side; **tidak** menerima nominal dari client.
- Upload privat (`local` disk) untuk bukti bayar/inspection; publik hanya untuk gambar produk/sampul.
- Guide: escape-then-render; **tidak** ada raw HTML/iframe arbitrer.
- Authz via Policy/middleware/gate existing.

## 6. Files Involved

- Views: `site-header`, `product-card`, `catalog/index`, `catalog/show`, `trip-planner/index`,
  `guides/index`, `guides/show`, `admin/guides/form`, `admin/guides/index`, `admin/trip-rules/*`.
- CSS: `resources/css/app.css`. JS: `resources/js/app.js`.
- Controllers: `PublicSite\{CatalogController,ProductController,TripPlannerController,GuideController}`,
  `Admin\{GuideController,ProductImageController,TripRecommendationRuleController}`.
- Services: `TripRecommendationService`, `GuideService`, `ProductMediaService`, `SalesStockService`,
  `SalesOrderService`, `RentalOrderService`, `PaymentService`, `SalesPaymentService`.
- Models: `Product`, `ProductVariant`, `ProductImage`, `TripRecommendationRule`, `EquipmentGuide`.
- Support: `GuideContentRenderer`, `RentalPeriod`, `Money`, `helpers`.
- Migrations baru (backwards compatible) untuk experience level & guide `is_featured`.

## 7. Implementation Plan (Stage B–I)

1. **B**: Navbar grid 3 kolom + perbaikan mobile drawer + underline; test `PublicNavigationLayoutTest`.
2. **C**: Layout katalog (sidebar sticky + search toolbar + grid 3 kolom) + mobile drawer filter.
3. **D**: Filter rental (kategori, harga/hari, tanggal, availability) + card mode-aware + image fallback.
4. **E**: Filter retail (harga/unit, stok via `SalesStockService`) + sembunyikan tanggal rental.
5. **F**: Wizard 5 langkah Gunung Prau + experience + pemilihan gear + checkout + pembayaran + PDF paket.
6. **G**: Guide editorial landing + detail + rich editor + gambar inline + YouTube aman + lifecycle.
7. **H**: PHPUnit/MySQL/browser/responsive/a11y/performance regression.
8. **I**: Dokumentasi + completion report + Git push.

## 8. Database Safety

- Dev DB `gearora` (MySQL): **read-only** untuk browsing manual. Dilarang `fresh`/`wipe`/`reset`.
- Test otomatis: SQLite `:memory:` (phpunit.xml). MySQL test: `gearora_test`.
- Browser E2E: DB SQLite disposable `storage/phase9-e2e.sqlite` (bukan `gearora`).
- Tidak menampilkan `.env`, password, atau API key pada log/laporan.
