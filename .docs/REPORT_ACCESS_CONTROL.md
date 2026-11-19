# Report Access Control

> **Produk:** GEARORA — Outdoor Supply
> **Fase:** Phase 7.5
> **Tanggal:** 19 November 2026

Dokumen ini adalah otoritas tunggal untuk siapa boleh melihat dan mengekspor laporan apa.

---

## 1. Aturan Inti

1. Setiap jenis laporan punya permission sendiri.
2. Halaman Ringkasan (`summary`) boleh dibuka bila pengguna punya **salah satu** permission laporan.
3. Ekspor wajib memenuhi **dua** syarat: izin lihat laporan terkait **dan** `reports.export`.
4. Owner selalu bypass (semua permission).
5. Pengguna tanpa permission laporan apa pun tidak bisa membuka halaman laporan maupun mengekspor.

Tidak ada jalur "ekspor saja". `reports.export` adalah pengali, bukan pengganti izin lihat.

---

## 2. Matriks Permission

| Laporan | Permission lihat |
| --- | --- |
| `summary` | salah satu dari seluruh daftar di bawah |
| `rental` | `reports.rentals.view` |
| `sales` | `reports.sales.view` |
| `payments` | `reports.payments.view` |
| `deposits` | `reports.deposits.view` |
| `charges` | `reports.charges.view` |
| `inventory` | `reports.inventory.view` |
| `operations` | `reports.rental_operations.view` |
| Ekspor (semua format) | permission lihat laporan sesuai + `reports.export` |

`reports.charges.view` adalah permission baru pada Phase 7.5.

---

## 3. Matriks Peran (default seeder)

| Peran | Lihat laporan | Ekspor |
| --- | --- | --- |
| Owner | semua | ya |
| Admin | semua laporan bisnis (rental, sales, payments, deposits, charges, inventory, operations) | ya |
| Warehouse Staff | tidak ada | tidak |
| Customer | tidak ada | tidak |

Catatan: `RbacDefaults` memberi Admin seluruh permission `reports.*` termasuk `reports.export`. Warehouse tetap hanya operasional/inventaris, tanpa akses laporan bisnis. Customer hanya halaman publik dan pesanan retail miliknya.

---

## 4. Perilaku Ringkasan

`summary` memanggil `ReportService::dashboard($filters, $allowedGroups)`. `$allowedGroups` adalah daftar grup yang permission-nya dimiliki pengguna. Akibatnya:

- Pengguna dengan hanya `reports.sales.view` melihat kartu Penjualan Retail saja.
- Grup Pembayaran/Deposit/Biaya/Inventaris/Operasional tidak muncul.
- Ekspor ringkasan (CSV/XLSX/PDF) juga hanya memuat grup yang sama.

Tidak ada kebocoran angka lintas grup lewat ringkasan maupun ekspor.

---

## 5. Titik Penegakan

| Titik | Berkas | Perilaku |
| --- | --- | --- |
| Halaman laporan | `ReportController@index` → `authorizeReportView()` | 403 bila tidak berhak |
| Ekspor | `ReportController@export` → `authorizeReportView()` lalu `authorizeExport()` | 403 bila salah satu gagal |
| Report tak dikenal | `ReportController@export` | 404 (bukan fallback diam-diam) |
| Format tak dikenal | route constraint + `abort_unless` | 404 |
| Menu laporan | `AdminNavigation::forUser()` | child tanpa izin disembunyikan |
| Aksi cepat dashboard | `DashboardController::quickActions()` | hanya route yang boleh diakses |

---

## 6. Contoh Skenario

**A. Staf dengan `reports.sales.view` saja**

- `GET /admin/reports?report=sales` → 200.
- `GET /admin/reports?report=deposits` → 403.
- `GET /admin/reports?report=sales&...&format=csv` (tanpa `reports.export`) → 403.
- Ringkasan hanya memuat grup sales.

**B. Staf dengan `reports.sales.view` + `reports.export`**

- Ekspor sales (csv/pdf/xlsx) → 200.
- Ekspor deposits → 403 (tidak punya izin lihat deposit).

**C. Staf dengan `reports.export` saja**

- Semua ekspor → 403.

**D. Owner**

- Semua laporan & format → 200.

---

## 7. Verifikasi Otomatis

`tests/Feature/ReportAuthorizationTest.php` mengunci aturan di atas:

- Owner semua jenis laporan.
- Sales-only tidak bisa laporan lain.
- Ringkasan menyembunyikan grup terlarang.
- Laporan charges butuh `reports.charges.view`.
- Ekspor butuh izin lihat + `reports.export`.
- `reports.export` saja tidak cukup.
- Owner bisa semua format.
- Customer ditolak.