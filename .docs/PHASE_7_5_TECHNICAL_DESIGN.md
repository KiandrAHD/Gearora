# Phase 7.5 — Technical Design

> **Produk:** GEARORA — Outdoor Supply
> **Fase:** Phase 7.5 (Admin Dashboard Optimization & Reporting Enhancement)
> **Tanggal:** 19 November 2026
> **Bahasa:** Indonesia
> **Basis stack:** Laravel 12, PHP 8.2+, Blade, Tailwind 4, Alpine.js 3, Vite, MySQL/InnoDB

---

## 1. Tujuan

Phase 7.5 memoles area admin tanpa mengubah arsitektur yang sudah ada dan tanpa menambah modul duplikat:

1. Sidebar admin bertingkat (grouped navigation) yang sadar permission dan menampilkan badge pending.
2. Dashboard admin adaptif per role: KPI, ringkasan keuangan, aksi cepat, dan daftar pending.
3. Perbaikan otorisasi laporan: setiap jenis laporan punya permission sendiri; ekspor butuh permission lihat laporan + `reports.export`.
4. Filter laporan yang dinamis dan konsisten antara layar dan ekspor.
5. Ekspor multi-format: PDF, XLSX, dan CSV (CSV tetap backward compatible).
6. Dokumentasi, test, dan commit bergrup.

Prinsip prioritas: security > data accuracy > authorization > consistency > usability > visuals > performance.

---

## 2. Batasan yang Dipegang

- Tidak menulis ulang Blade menjadi React dan tidak menginisialisasi ulang proyek.
- Tidak menghapus dependency yang masih dipakai.
- Fitur/test Phase 0–7 tetap hijau; tidak ada modul duplikat.
- Deposit **bukan** revenue.
- Tidak ada angka demo palsu dan tidak ada pemotongan data diam-diam (silent truncation).
- Tidak ada tombol dekoratif tanpa fungsi.
- Setiap ekspor memakai permission lihat laporan yang sesuai + `reports.export` (Owner bypass).
- Target hosting AeonFree tanpa asumsi queue/Redis/SSH; unduhan memakai streaming dengan `Cache-Control: no-store`.

---

## 3. Navigasi Admin

### 3.1 Sumber tunggal konfigurasi

`App\Support\Navigation\AdminNavigation` memegang definisi grup menu (struktur, route, `match`, dan permission). Sidebar Blade hanya me-render hasil filter, bukan daftar datar hardcoded.

- `groups()` — konfigurasi lengkap sebelum filter permission.
- `forUser(?User)` — menu yang sudah difilter; grup tanpa child yang boleh diakses otomatis disembunyikan.
- `allows()` / `allowsChild()` — cek permission; child dapat memakai `any` (mis. Report "Ringkasan" butuh salah satu permission laporan).
- `isActive()` / `isGroupActive()` — penentuan item aktif; laporan memakai parameter `report` agar anak yang aktif tepat.
- `reportPermissions()` / `reportPermissionsByName()` — pemetaan laporan ke permission.

### 3.2 Badge pending

`App\Support\Navigation\PendingIndicators` menghitung angka pending untuk badge sidebar, di-memoize per request dan hanya untuk item yang boleh diakses pengguna:

| Badge | Sumber | Permission |
| --- | --- | --- |
| `payments_waiting` | `Payment` status `waiting_verification` | `payments.view` |
| `charges_pending` | `AdditionalCharge` status `pending_approval` | `charges.view` |
| `inspections_pending` | `RentalAsset` status `inspection_pending` | `inspections.view` |
| `pickups_today` | `RentalOrder` siap serah hari ini | `rentals.handover` |
| `returns_today` | `RentalOrder` jatuh tempo kembali hari ini | `rentals.return` |
| `overdue` | `RentalOrder` lewat tanggal kembali | `rentals.view` |

### 3.3 Layout & aksesibilitas

`resources/views/components/layouts/admin.blade.php` menulis ulang sidebar menjadi nav bertingkat Alpine (`x-data`, `openGroups`). Atribut yang dijaga:

- `aria-expanded` pada toggle grup, `aria-controls` menunjuk panel `id="admin-nav-<group>"`.
- `aria-current="page"` pada item aktif.
- `@keydown.escape.window` menutup sidebar mobile; `document.body.classList.toggle('nav-open', ...)` mengunci scroll.
- Tombol buka/tutup mobile (`aria-controls="admin-sidebar"`) dan `.admin-overlay`.
- Fokus dipindahkan ke close button / toggle saat buka/tutup.

Ikon memakai komponen inline `resources/views/components/admin/nav-icon.blade.php`.

---

## 4. Dashboard Adaptif

`Admin\Http\Controllers\Admin\DashboardController@index` menghitung data secara kondisional berdasarkan `AdminNavigation::allows($user, ...)`:

- KPI operasional (rental aktif, jatuh tempo, pickup, pengembalian, inspeksi, overdue, pembayaran menunggu, pesanan retail aktif, unit perawatan/rusak) — hanya dimunculkan bila permission terkait dimiliki.
- Ringkasan keuangan periode berjalan (bulan berjalan) via `ReportService::dashboard($filters, $allowed)`; grup difilter ke permission `reports.*.view` yang dimiliki. Deposit ditampilkan terpisah dan **tidak** dihitung sebagai pendapatan.
- Aksi cepat hanya bila route-nya memang bisa diakses (owner atau permission terkait).
- Daftar "Pesanan terbaru", "Pembayaran menunggu verifikasi", "Stok jual menipis", "Ringkasan data", dan "Aktivitas terbaru" ikut difilter permission.

View: `resources/views/admin/dashboard.blade.php`. Kelas CSS pendukung (`.stat-grid`, `.stat-card`, `.admin-grid-2`, `.plain-list`, `.summary-list`) sudah ada di `resources/css/app.css`.

---

## 5. Otorisasi Laporan

### 5.1 Masalah lama

`ReportController` lama hanya memeriksa `reports.rentals.view` / `reports.sales.view`, dan `authorizeExport()` hanya memeriksa `reports.export`. Akibatnya pengguna dengan `reports.export` bisa mengekspor laporan yang tidak boleh ia lihat.

### 5.2 Perbaikan

- `permissionFor($report)` memetakan tiap jenis laporan ke permission granular.
- `authorizeReportView()`: Owner bypass; `summary` butuh salah satu permission laporan; jenis lain butuh permission spesifik.
- `authorizeExport()`: butuh permission lihat laporan (lewat `authorizeReportView`) **dan** `reports.export` (Owner bypass).
- Permission baru `reports.charges.view` ditambahkan ke `PermissionName` dan `RbacDefaults` role Admin; Owner otomatis semua.

Pemetaan granular:

| Jenis laporan | Permission |
| --- | --- |
| `summary` | salah satu dari daftar `reportPermissions()` |
| `rental` | `reports.rentals.view` |
| `sales` | `reports.sales.view` |
| `payments` | `reports.payments.view` |
| `deposits` | `reports.deposits.view` |
| `charges` | `reports.charges.view` |
| `inventory` | `reports.inventory.view` |
| `operations` | `reports.rental_operations.view` |

Detail lengkap ada di `REPORT_ACCESS_CONTROL.md`.

---

## 6. Filter Laporan

`App\Http\Requests\Admin\ReportFilterRequest` menyatukan validasi filter untuk layar dan ekspor:

- `start_date`, `end_date` (default: awal bulan s/d hari ini).
- `status`, `type`, `method`, `product_id`, `category_id`, `transaction_type`.

`options()` mengembalikan opsi yang sudah lolos validasi dan dipakai seragam oleh layar dan ekspor, sehingga filter di layar identik dengan isi berkas yang diunduh. Filter `transaction_type` (rental/sales) khusus laporan pembayaran; `product_id`/`category_id` khusus laporan inventaris.

---

## 7. Ekspor Multi-Format

### 7.1 Rute

```
GET /admin/reports/{report}/export/{format?}   admin.reports.export
```

- `{format}` opsional dengan constraint `csv|pdf|xlsx`; default `csv`.
- Report di luar daftar `TYPES` atau format di luar daftar → `404`.
- Nama rute lama `admin.reports.export` dipertahankan agar pemanggilan `route('admin.reports.export', ['report' => 'sales'])` tetap menghasilkan CSV.

### 7.2 Sumber data tunggal

`ReportService` tetap satu-satunya sumber angka. Tiap metode laporan menerima parameter `bool $export`:

- Layar: limit 500 baris (tampilan).
- Ekspor: seluruh dataset dalam rentang filter, dijaga `EXPORT_ROW_LIMIT = 50000` lewat `guardRows()` (`abort_if` → HTTP 422, bukan pemotongan diam-diam).

### 7.3 Renderer

`App\Services\ReportExportService` hanya memformat; tidak menghitung ulang angka bisnis.

- **PDF** (dompdf, `barryvdh/laravel-dompdf ^3.1.2`): `PDF_MAX_ROWS = 1000`; A4 landscape untuk laporan berkolom banyak, portrait untuk ringkasan/operasional. View: `resources/views/admin/reports/pdf.blade.php` (header/footer brand, nomor halaman, tabel metrik & detail).
- **XLSX** (PhpSpreadsheet, `phpoffice/phpspreadsheet ^5.11`): `XLSX_MAX_ROWS = 10000`; laporan ringkasan menghasilkan satu sheet overview + satu sheet per grup; laporan detail menjadi satu sheet dengan autofilter dan freeze pane. Kolom uang ditulis sebagai cell numerik dengan format `"Rp"#,##0`.
- **CSV** (`App\Support\CsvExporter`, tidak berubah): UTF-8 BOM, header lama, dan formula-injection guard dipertahankan (backward compatible).

### 7.4 Keamanan

- Nama file: `gearora-{report}-{start}_{end}.{ext}`.
- Sel string dinetralkan dari formula injection (prefix `'` bila diawali `= + - @ tab CR`).
- XLSX dikirim via `streamDownload` dengan `Cache-Control: no-store, no-cache`.
- Tidak ada asumsi queue/Redis — unduhan sinkron dan streaming.

---

## 8. Dependency Baru

| Paket | Versi | Alasan |
| --- | --- | --- |
| `barryvdh/laravel-dompdf` | `^3.1.2` | Render PDF server-side tanpa headless browser (cocok AeonFree). |
| `phpoffice/phpspreadsheet` | `^5.11` | Menulis XLSX asli (angka tetap numerik, formula aman). |

Keduanya mendukung PHP 8.2. Ekstensi PHP `zip` dibutuhkan PhpSpreadsheet untuk menulis XLSX; sudah diaktifkan di lingkungan pengembangan.

---

## 9. Dampak & Kompatibilitas

- Tidak ada migrasi destructive.
- CSV tetap format lama; test lama tetap lulus.
- Route laporan lama tetap; penambahan hanya parameter `{format?}` opsional.
- Tidak menyentuh `.env` rahasia, dump, proof, log, `vendor`, atau `node_modules`.
