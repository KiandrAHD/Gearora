# GEARORA UI/UX REVAMP — AUDIT (PHASE 0)

Tanggal audit: 27 November 2026
Repository: https://github.com/KiandrAHD/Gearora (branch `main`)

## 1. Kondisi awal (baseline)

| Item | Nilai |
|---|---|
| Branch | `main` |
| HEAD | `fbe2850` Merge pull request #3 from KiandrAHD/arbany |
| Working tree | Bersih, kecuali untracked `deployment/` dan `infinityfree-package/` (pekerjaan operator — **tidak disentuh**) |
| Remote origin | `https://github.com/KiandrAHD/Gearora.git` (fetch+push) |
| Baseline test | `vendor/bin/phpunit` → **336 tests, 1048 assertions, OK** (SQLite `:memory:`, PHP 8.5.9) |
| Baseline build | `npm run build` → OK (62 modules, warning non-fatal untuk URL `/images/editorial/*` di CSS — resolved runtime) |
| Dev dependencies | Awalnya belum ter-install (tidak ada `vendor/bin/phpunit`); dieksekusi `composer install --no-interaction` sehingga `phpunit/phpunit 11.5.58` + `laravel/pint` tersedia. Tidak ada perubahan `composer.lock` akibatnya (versi sudah sesuai lock). |
| Catatan command | `php artisan test` tidak tersedia di environment ini (command `test` tidak terdaftar), sehingga suite dijalankan langsung via `vendor/bin/phpunit`. |

## 2. Arsitektur existing (hasil audit source code)

- **Stack**: Laravel 12.x, PHP 8.2+, Blade, Tailwind CSS 4 (via `@import 'tailwindcss'` + design tokens manual di `resources/css/app.css`), Alpine.js 3, Vite 6, MySQL 8 (produksi) / SQLite (test), PHPUnit 11, Midtrans PHP SDK.
- **Pola**: Modular Monolith + MVC + Service Layer. Business logic ada di `app/Services/*` (30 service), bukan di controller/frontend.
- **Layout publik**: `resources/views/components/layouts/public.blade.php` + alias `x-layout` (`components/layout.blade.php`).
- **Layout customer**: `x-layouts.customer` (`components/layouts/customer.blade.php`) — nav akun hanya 3 item (dashboard, orders, profile).
- **Layout admin**: `x-layouts.admin` (`components/layouts/admin.blade.php`) — sidebar grouped via `App\Support\Navigation\AdminNavigation`, badge pending via `PendingIndicators`.
- **Navbar publik**: `components/partials/site-header.blade.php` — floating pill dengan Alpine `mobileNav` + logika collapse ke pill (`expanded`/`scrolled`).
- **Design tokens**: `:root` di `resources/css/app.css` (forest/navy/trail/sage/sand/cloud/earth/line). Font: `Plus Jakarta Sans` (heading) + `Inter` (body).
- **UI kit Blade**: `x-ui.button|input|badge|card|table|modal|alert|pagination|empty-state` + partial `x-partials.product-card|status-badge|payment-badge|site-footer|site-header`.
- **Route**: `routes/web.php` — publik (home, katalog sewa/beli, detail, panduan, trip planner, keranjang, booking summary), customer (`/akun/*`), admin (`/admin/*` dengan RBAC `staff` + `permission:`).
- **Payments**: `PaymentService` (transfer manual + tunai + settlement idempotent `markSettled`), `MidtransPaymentService` (Snap Redirect, attempt idempotent), `Api\MidtransNotificationController` (webhook), `SalesPaymentService` untuk retail. `PaymentMethod` enum: `midtrans|transfer|cash`.
- **Guides**: `EquipmentGuide` + `GuideCategory` + `GuideSource`, renderer `App\Support\GuideContentRenderer` (escape-first, markdown aman), Admin Guide Studio (`admin/guides*`).
- **Trip Planner**: wizard 5 langkah Alpine (`tripWizard`), `TripPlannerRequest`, `TripRecommendationService` (aturan DB), `TripPackageController` + checkout.
- **Tests**: 336 test (49 Feature, 2 Unit, 3 MySQL terpisah). Playwright e2e tersedia (`tests/e2e`, `playwright.config.js`).

### Perbedaan dokumentasi vs source code

| Temuan | Detail |
|---|---|
| Font | Beberapa `.docs` menyebut Poppins untuk heading; implementasi aktual memakai `Plus Jakarta Sans`. Dipertahankan yang ada di source (Plus Jakarta Sans) agar tidak ada perubahan font besar yang belum disepakati; konsistensi dipertahankan situs-wide. |
| Navbar test | `PublicNavigationLayoutTest` masih mengasumsikan layout header lama (`.site-header-inner` grid 3 kolom) — CSS tersebut masih ada di `app.css` meski tidak dipakai template. Tidak mempengaruhi kebenaran navbar floating saat ini. |
| Seeder panduan | `DemoGuidesSeeder` hanya membuat 2 artikel demo pendek. Konten editorial 8 artikel belum ada. |

## 3. Root cause permasalahan yang dilaporkan pengguna

1. **Navbar Sewa/Beli/Panduan terlihat "putih polos"** — `is-hero-overlay` hanya aktif di `home`; halaman lain memakai background translucent putih sehingga terasa berbeda dari homepage. Root cause: state visual navbar tidak diikat ke warna hero per-halaman.
2. **Perilaku scroll navbar** — collapse menjadi pill saat scroll ke bawah, bukan sembunyi-halus (hide/show) sesuai ekspektasi. Root cause: logika `expanded` melakukan shrink, bukan translate hide.
3. **Halaman Sewa/Beli kurang premium** — hero tipis tanpa visual, product card minimal, tidak ada label ketersediaan/ Gearora Select, mobile 1 kolom di ≤600px. Root cause: styling katalog generik dan tidak setara homepage.
4. **Trip Planner** — basecamp masih free text (tidak tervalidasi), tanggal keberangkatan hanya divalidasi client-side (`required` HTML) tanpa rule backend "tidak boleh sebelum hari ini". Root cause: `TripPlannerRequest::rules()` belum punya constraint `after_or_equal:today` dan field `location` bebas.
5. **Panduan terasa kosong** — hanya 2 artikel demo pendek, tidak ada artikel unggulan kaya, TOC, callout. Root cause: belum ada konten + styling editorial.
6. **Akun pelanggan sederhana** — dashboard hanya 3 stat + tabel; nav akun tidak lengkap. Root cause: `customer.blade.php` layout minimal dan `DashboardController` belum menghitung status pesanan per kategori.
7. **Admin console "elemen melayang"** — spacing/alignment antarhalaman belum distandardisasi (header/topbar/toolbar/table wrapper).
8. **Payment** — pemilihan metode belum eksplisit sebelum checkout (draft booking selalu default `transfer`), QRIS hanya teks di halaman order, tunai belum punya naming "Bayar Tunai Saat Pengambilan" yang eksplisit, dan pencatatan tunai admin belum mencatat nominal diterima secara terstruktur.

## 4. File yang akan diperbaiki (rencana)

| Fase | File |
|---|---|
| 1 | `resources/css/app.css`, `resources/views/components/partials/site-header.blade.php`, `resources/js/app.js` |
| 2 | `resources/views/catalog/index.blade.php`, `components/partials/product-card.blade.php`, `app/Http/Controllers/PublicSite/CatalogController.php` (jika perlu), CSS |
| 3 | `resources/views/trip-planner/index.blade.php`, `app/Http/Requests/PublicSite/TripPlannerRequest.php`, `config/gearora.php` (basecamp), CSS |
| 4 | `resources/views/guides/index.blade.php`, `guides/show.blade.php`, `database/seeders/DemoGuidesSeeder.php`, CSS |
| 5 | `resources/views/components/layouts/customer.blade.php`, `customer/dashboard.blade.php`, `customer/orders/index.blade.php`, `customer/profile.blade.php`, `app/Http/Controllers/Customer/DashboardController.php` |
| 6 | `resources/views/components/layouts/admin.blade.php`, `admin/dashboard.blade.php`, CSS admin, wrapper tabel |
| 7 | `rental/summary.blade.php`, `catalog/show.blade.php`, `customer/orders/show.blade.php`, `app/Http/Controllers/PublicSite/BookingController.php`, `app/Services/PaymentService.php` (catat nominal tunai), admin payments view |
| 8–9 | Tests + docs |

## 5. Fitur yang sudah tersedia (tidak perlu dibangun ulang)

- Katalog sewa/beli dengan filter server-side lengkap (keyword, kategori, harga min/max, stok, tanggal rental via `RentalAvailabilityService`), sorting, pagination dengan query string.
- Trip Planner 5 langkah + paket mixed sewa/beli + checkout + dokumen/PDF.
- Panduan CMS (draft/publish/archive, kategori, cover, inline image, preview, sumber) + `GuideContentRenderer` aman.
- Pembayaran: transfer manual + bukti + verifikasi admin, tunai, Midtrans Snap (QRIS termasuk pilihan channel di Snap), webhook dengan verifikasi signature, attempt idempotent.
- RBAC + permission granular, activity log, deposit terpisah dari revenue, anti-overselling retail.

## 6. Fitur yang belum tersedia (gap)

- Navbar: smart reveal (hide saat scroll down / muncul saat scroll up + pointer near top), overlay konsisten di semua halaman publik.
- Katalog: premium hero + product card (badge ketersediaan, label Gearora Select, tombol lihat detail), mobile 2 kolom.
- Trip Planner: dropdown basecamp tervalidasi + validasi tanggal backend (min hari ini, Asia/Jakarta).
- Panduan: 8 artikel editorial lengkap + TOC + callout + hero landing yang lebih kaya.
- Akun: dashboard ringkas per status (menunggu pembayaran, menunggu pengambilan, aktif, selesai) + nav akun lengkap.
- Admin: konsistensi layout + KPI lengkap (total produk, total pesanan, revenue, stok menipis) + tabel responsive.
- Payment: pemilihan metode eksplisit (QRIS/Transfer/Tunai saat pengambilan) sebelum order dibuat + pencatatan nominal tunai oleh admin.

## 7. Risiko regresi & mitigasi

| Risiko | Mitigasi |
|---|---|
| Navbar test `PublicNavigationLayoutTest` + `NavigationAndMobileTest` | Pertahankan selector yang dites: `id="mobile-navigation"`, `mobile-nav-backdrop`, `mobile-nav-close`, `@keydown.tab="trap($event)"`, `@click="toggle()"`, `x-effect="document.body.classList.toggle('nav-open', open)"`, `@keydown.escape.window="close()"`, `x-ref="drawerClose"`, dan `aria-current="page"` hanya pada link aktif per mode. |
| Harga sewa vs jual tertukar | `product-card` tetap memakai `effectiveRentalPrice()`/`effectiveSalePrice()` per mode; test `ProductCardModePriceTest` dijalankan ulang. |
| Booking flow | Perubahan hanya pada sumber pilihan metode (form → session draft). `AvailabilityCheckRequest` ditambah rule `method` (in: transfer,cash). `PaymentMethod::values()` tidak boleh menerima `midtrans` lewat draft ini (gateway dibuat eksplisit dari halaman order). |
| Trip planner | Menambah rule `start_date` tidak boleh lampau + daftar basecamp dari config; perubahan tidak menyentuh `TripRecommendationService`. |
| Admin layout | Hanya CSS/wrapper, tidak mengubah route/permission. |
| Payment | Tidak menambah pipeline baru; memakai `PaymentService` existing. Tidak ada perubahan status dari frontend. |

## 8. Strategi testing

- PHPUnit full suite setelah setiap fase (SQLite `:memory:`).
- `vendor/bin/pint --test`, `composer validate --strict`, `composer audit`, `npm audit`, `npm run build`.
- Test baru: validasi tanggal trip planner, pilihan metode booking, catat tunai admin (amount mismatch, double settlement).
- Playwright e2e tersedia bila environment mendukung (opsional, tidak menggantikan PHPUnit).
- Browser visual: menggunakan agent-browser bila tersedia; kalau tidak, dilaporkan sebagai bagian yang belum terverifikasi.

## 9. Keputusan implementasi (aman/terdokumentasi)

1. **Basecamp**: daftar basecamp disimpan di `config/gearora.php` (`trip.basecamps`) + rule `in:` di FormRequest; nilai custom "Lainnya" ditolak (hanya pilihan tervalidasi) agar ringkasan paket selalu konsisten. Tidak ada tabel baru (shared hosting, perubahan minimal).
2. **Tanggal keberangkatan**: validasi `after_or_equal:today` di backend dengan zona `Asia/Jakarta` (config `app.timezone`), plus atribut `min` di HTML sebagai bantuan.
3. **Panduan demo**: artikel testing ditambahkan ke `DemoGuidesSeeder` (hanya `local`/`testing`, idempotent via `updateOrCreate` by slug, tidak menimpa artikel existing karena slug demo berbeda).
4. **Pembayaran**: QRIS disajikan melalui Midtrans Snap (channel e-wallet/QRIS di dalam Snap). Tidak ada pipeline baru. Tunai diberi label "Bayar Tunai Saat Pengambilan (COD pickup)". Admin mencatat nominal diterima + waktu + petugas melalui `PaymentService::recordCash` yang diperluas (kolom baru `cash_received_amount` + `cash_officer_note` bila migration diperlukan — dievaluasi di fase 7; jika tidak perlu kolom baru, note terstruktur disimpan di `verification_note`).
5. **Mobile catalog**: 2 kolom mulai ≥ 420px; 1 kolom hanya di layar sangat sempit.
