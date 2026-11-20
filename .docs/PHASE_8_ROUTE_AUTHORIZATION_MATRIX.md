# PHASE 8 — Route Authorization Matrix

> Tanggal: 20 November 2026 · Sumber: `routes/web.php`, `routes/api.php`, `bootstrap/app.php`,
> `app/Support/Rbac/RbacDefaults.php`, policy di `app/Policies`.

Total route: **136**. Ringkasan 62 route GET.

## 1. Aktor

| Aktor | Guard | Keterangan |
|---|---|---|
| Tamu | — | Halaman publik & form login/daftar |
| Customer | `auth` (web) | Hanya data milik sendiri |
| Admin | `auth` + `staff` | Operasional sesuai permission |
| Petugas Gudang | `auth` + `staff` | Inventaris + operasional rental |
| Owner | `auth` + `staff` | Semua permission (`Gate::before`) |

## 2. Mekanisme Penegakan (berlapis)

1. **Middleware grup** — `auth`, `staff`, `guest`, `throttle:*`.
2. **Middleware `permission:<name>`** pada route tertentu (mis. dashboard).
3. **Controller `$this->authorize('<ability>', <model>)`** → Policy (`app/Policies/*`).
4. **Pemeriksaan eksplisit** `hasPermission()` untuk keputusan granular (mis. laporan, operasional).
5. **Scope kepemilikan** di query/policy untuk customer.

Menu Blade yang disembunyikan **bukan** kontrol akses.

## 3. Matriks per Grup

### 3.1 Publik (tanpa guard)

| Route (nama) | Method | Middleware | Akses |
|---|---|---|---|
| `home` | GET | web | Publik |
| `catalog.index`, `catalog.show` | GET | web | Publik |
| `guides.index`, `guides.show` | GET | web | Publik |
| `trip-planner.index` | GET | web | Publik |
| `trip-planner.recommend` | POST | web, `throttle:30,1` | Publik (dibatasi) |
| `media.show` | GET | web | Publik (hanya raster aman, anti-traversal) |
| `availability.check` | POST | web | Publik |
| `retail.cart.*` | GET/POST/PUT/DELETE | web (session) | Publik (keranjang berbasis session) |
| `booking.store/summary/destroy` | POST/GET/DELETE | web | Publik (draft booking berbasis session) |

### 3.2 Autentikasi

| Route (nama) | Middleware | Akses |
|---|---|---|
| `login`, `login.store`, `register`, `register.store` | `guest` | Tamu saja |
| `logout` | `auth` | Customer terautentikasi |
| `admin.login`, `admin.login.store` | `guest` | Tamu saja |
| `admin.logout` | `auth`,`staff` | Staf |

`login.store`/`admin.login.store` dilindungi `RateLimiter` (per email+IP).

### 3.3 Area Customer (`/akun`, prefix `customer.`, middleware `auth`)

| Route (nama) | Ability/Policy | Akses |
|---|---|---|
| `customer.dashboard` | — | Customer sendiri |
| `customer.orders.index/store` | — | Customer sendiri |
| `customer.orders.show/cancel` | `view`/`cancel` order (policy) | **Hanya order milik sendiri** |
| `customer.orders.payment-proof.store` | `view` order | Milik sendiri |
| `customer.orders.midtrans.start/finish` | `view` order | Milik sendiri |
| `customer.sales.checkout` | — | Customer sendiri |
| `customer.sales.index` | — | Customer sendiri |
| `customer.sales.show/cancel` | policy order | Milik sendiri |
| `customer.sales.proof.store` | `view` order + cek `payment.sales_order_id == order.id` | Milik sendiri (anti-IDOR) |
| `customer.sales.midtrans.start` | `view` order | Milik sendiri |
| `customer.profile.edit/update` | — | Customer sendiri |

### 3.4 Area Admin (`/admin`, prefix `admin.`, middleware `auth`+`staff`)

Semua route di bawah ini hanya untuk staf aktif. Enforcement utama = policy/`permission` di
controller.

| Kelompok | Nama route (contoh) | Ability/Policy | Owner | Admin | Gudang |
|---|---|---|---|---|---|
| Dashboard | `admin.dashboard` (`permission:dashboard.view`) | dashboard.view | ✅ | ✅ | ✅ |
| Kategori | `admin.categories.*` | `viewAny/create/update/delete` Category | ✅ | ✅ | lihat saja |
| Produk | `admin.products.*`, `products.archive`, `products.destroy`, varian/gambar | `viewAny/create/update/archive/delete` Product | ✅ | ✅ | lihat saja |
| Inventaris | `admin.inventory.*`, `inventory.stock.adjust` | `viewAny/create/update` RentalAsset + `inventory.adjust-stock` | ✅ | ✅ | ✅ |
| Rental order | `admin.rentals.index/show/transition` | `viewAny/view/update` RentalOrder | ✅ | ✅ | update (no cancel) |
| Operasional | `admin.operations.*` (pickups/returns/inspections/overdue) | permission per aksi (handover/return/inspection/charge) | ✅ | ✅ | ✅ |
| Pembayaran | `admin.payments.*` | `viewAny/verify/reject/view` Payment | ✅ | ✅ | ❌ |
| Refund | `admin.refunds.*` | `viewAny/create/approve/process` Refund | ✅ | ✅ | ❌ |
| Deposit | `admin.deposits.*` | `viewAny/view/refund/deduct/manage` Deposit | ✅ | ✅ | ❌ |
| Biaya tambahan | `admin.charges.*` | `viewAny/create/approve/reject` AdditionalCharge | ✅ | ✅ | view/create |
| Pelanggan | `admin.customers.*` | permission `customers.view` | ✅ | ✅ | ❌ |
| Aturan trip | `admin.trip-recommendations.*` | `viewAny/create/update/delete` | ✅ | ✅ | ❌ |
| Panduan | `admin.guides.*`, `guide-categories.*` | `viewAny/create/update/publish/archive/delete` | ✅ | ✅ | ❌ |
| Sales | `admin.sales.*` | `viewAny/view/update/cancel/complete` SalesOrder | ✅ | ✅ | ❌ |
| Laporan | `admin.reports.index/export` | permission per grup + `reports.export` | ✅ | ✅ | ❌ |
| Log aktivitas | `admin.activity.index` | `dashboard.view` | ✅ | ✅ | ✅ |

Catatan: kolom Gudang bersifat ringkas; penegakan final tetap pada kombinasi middleware+policy
(diverifikasi lewat `AdminRouteAuthorizationTest`).

### 3.5 API / Webhook

| Route (nama) | Method | Middleware | Akses |
|---|---|---|---|
| `payments.midtrans.notification` | POST | `api`, `throttle:midtrans-webhook` | Provider (tanpa CSRF; diamankan signature) |

## 4. Verifikasi Uji

| Aspek | Test |
|---|---|
| Guest → redirect login admin | `AdminRouteAuthorizationTest::test_anonymous_is_redirected_from_admin` |
| Customer → 403 di area admin | `AdminRouteAuthorizationTest::test_customer_is_forbidden_from_admin_area` |
| Gudang boleh operasional, ditolak finance/laporan | `AdminRouteAuthorizationTest::test_warehouse_staff_can_access_operations_but_not_finance_or_reports` |
| Staf non-aktif → 403 | `AdminRouteAuthorizationTest::test_inactive_staff_is_forbidden` |
| Gudang tak bisa POST verifikasi pembayaran | `AdminRouteAuthorizationTest::test_warehouse_staff_cannot_verify_payment_via_direct_post` |
| IDOR order pembelian/rental | `CustomerOwnershipSecurityTest` |
| Guest → dashboard customer dialihkan | `RouteSmokeTest` |

## 5. Kesimpulan

Setiap route non-publik memiliki penjagaan di server. Tidak ditemukan route sensitif yang hanya
bergantung pada penyembunyian menu. Dua celah yang semula ada (route bukti retail B8 dan
ketiadaan cek kepemilikan eksplisit) sudah ditutup dan diuji.
