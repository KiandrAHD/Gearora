# Phase 7.5 — Test Plan

> **Produk:** GEARORA — Outdoor Supply
> **Fase:** Phase 7.5 (Admin Dashboard Optimization & Reporting Enhancement)
> **Tanggal:** 19 November 2026
> **Basis:** Laravel 12, PHPUnit, SQLite in-memory (`phpunit.xml`)

---

## 1. Tujuan Pengujian

Membuktikan:

1. Navigasi admin sadar permission dan stabil secara struktural.
2. Dashboard hanya menampilkan data yang boleh diakses role.
3. Otorisasi laporan granular benar, termasuk kombinasi izin ekspor.
4. Filter konsisten antara layar dan ekspor.
5. PDF/XLSX/CSV menghasilkan berkas valid tanpa merusak CSV lama.
6. Tidak ada regresi pada suite Phase 0–7.

---

## 2. Perintah

```bash
php artisan test --compact
php artisan test --filter=AdminNavigationTest
php artisan test --filter=ReportAuthorizationTest
php artisan test --filter=PdfReportExportTest
php artisan test --filter=XlsxReportExportTest
php artisan test --filter=CsvReportExportTest
vendor/bin/pint --test
npm run build
php artisan route:list --path=admin/reports
```

> Catatan shell Windows: warning PHPUnit di stderr dapat membuat exit code non-nol walau suite lulus. Baca baris ringkasan `Tests: N passed`.

---

## 3. Cakupan per Berkas

### 3.1 `tests/Feature/AdminNavigationTest.php` (7 test)

| Test | Yang dibuktikan |
| --- | --- |
| `test_owner_sees_all_navigation_groups` | Owner melihat seluruh grup (termasuk label ber-`&` yang ter-escape). |
| `test_admin_sees_report_and_finance_groups` | Admin melihat Keuangan, Pembayaran, Laporan. |
| `test_warehouse_sees_only_permitted_groups` | Warehouse hanya Katalog/Inventaris, Pickup, Biaya Tambahan, Log Aktivitas; tidak melihat grup bisnis. |
| `test_customer_cannot_open_admin_dashboard` | Customer 403. |
| `test_active_parent_opens_and_child_is_marked` | Pada halaman inventaris: `data-group-active="true"`, `aria-current="page"`, panel `admin-nav-catalog` dan `aria-controls`. |
| `test_sidebar_mobile_controls_and_escape_handler_present` | `admin-sidebar`, `aria-controls`, handler Escape, scroll lock. |
| `test_every_navigation_route_exists` | Semua route di konfigurasi nav benar-benar terdaftar. |

### 3.2 `tests/Feature/ReportAuthorizationTest.php` (8 test)

| Test | Yang dibuktikan |
| --- | --- |
| `test_owner_can_open_every_report_type` | Owner 200 untuk 8 jenis laporan. |
| `test_sales_only_permission_cannot_open_other_reports` | Sales-only boleh sales, 403 untuk lainnya. |
| `test_summary_hides_unauthorized_groups` | Ringkasan menyembunyikan grup yang tidak diizinkan. |
| `test_charges_report_requires_charges_permission` | Charges butuh izin baru. |
| `test_export_requires_view_and_export_permission` | Butuh izin lihat + `reports.export`. |
| `test_export_permission_alone_is_not_enough` | `reports.export` saja → 403. |
| `test_owner_can_export_all_formats` | Owner csv/pdf/xlsx 200. |
| `test_customer_cannot_access_reports` | Customer 403 halaman & ekspor. |

### 3.3 `tests/Feature/PdfReportExportTest.php` (6 test)

| Test | Yang dibuktikan |
| --- | --- |
| `test_owner_downloads_pdf_with_branding_and_filename` | `application/pdf`, prefix `%PDF`, nama file `gearora-sales…pdf`. |
| `test_summary_pdf_renders_empty_state` | PDF ringkasan tanpa data tetap valid. |
| `test_filters_do_not_break_pdf_export` | Filter tanggal tidak memecahkan render. |
| `test_pdf_export_respects_row_limit` | Melebihi `PDF_MAX_ROWS` → 422. |
| `test_warehouse_cannot_export_pdf` | Warehouse 403. |
| `test_unknown_format_returns_not_found` | Format tak dikenal 404. |

### 3.4 `tests/Feature/XlsxReportExportTest.php` (5 test)

| Test | Yang dibuktikan |
| --- | --- |
| `test_owner_downloads_valid_xlsx` | Content-type XLSX, prefix `PK`, judul sheet, header benar. |
| `test_currency_column_is_numeric_cell` | Kolom Total = `TYPE_NUMERIC` bernilai angka. |
| `test_formula_injection_is_stored_as_text` | `=SUM(...)` disimpan sebagai teks berprefix `'`. |
| `test_summary_workbook_respects_role_sheets` | Sheet ringkasan hanya memuat grup yang diizinkan. |
| `test_export_permission_required_for_xlsx` | Tanpa `reports.export` → 403. |

### 3.5 `tests/Feature/CsvReportExportTest.php` (diperluas)

| Test tambahan | Yang dibuktikan |
| --- | --- |
| `test_csv_export_includes_all_filtered_rows_beyond_page_limit` | 520 baris terekspor penuh (bukan 500) — tidak ada silent truncation. |
| `test_csv_export_name_includes_report_and_period` | Nama file `gearora-sales-2026-11-01_2026-11-30.csv`. |

Test lama dipertahankan: CSV content-type, BOM, formula-injection, unknown report 404, warehouse 403.

### 3.6 `tests/Feature/ExpandedReportsTest.php` (diperluas)

| Test tambahan | Yang dibuktikan |
| --- | --- |
| `test_deposit_is_not_counted_as_rental_revenue` | `net_rental_revenue` hanya `rental_amount`; deposit terpisah. |
| `test_pending_payment_is_not_collected` | `collected` hanya pembayaran `verified`/settled. |
| `test_retail_refund_metric_is_not_fabricated` | Refund retail `null` + flag `refunded_sales_available=false`. |
| `test_inventory_report_respects_product_filter` | Filter produk membatasi baris inventaris. |

---

## 4. Kriteria Lulus

- Semua test di atas PASS.
- Suite penuh PASS tanpa regresi (baseline Phase 7: 174 test → target ≥ 200).
- Pint bersih pada berkas yang diubah.
- `npm run build` sukses.
- Route `admin.reports.export` menerima `{format?}`.
- Tidak ada berkas rahasia yang ikut ter-stage.

---

## 5. Hasil Eksekusi

| Tahap | Perintah | Hasil |
| --- | --- | --- |
| Baseline Phase 7 | `php artisan test --compact` | 174 passed, 495 assertions |
| Phase 7.5 | `php artisan test --compact` | **206 passed, 624 assertions** |
| Navigation | `AdminNavigationTest` | 7 passed, 64 assertions |
| Authorization | `ReportAuthorizationTest` | 8 passed |
| PDF | `PdfReportExportTest` | 6 passed |
| XLSX | `XlsxReportExportTest` | 5 passed, 15 assertions |
| Build | `npm run build` | sukses (62 modul) |
| Route | `php artisan route:list --path=admin/reports` | 2 route terdaftar |

---

## 6. Risiko Pengujian

- Suite memakai SQLite in-memory; concurrency MySQL belum diuji pada fase ini (tidak ada perubahan skema).
- PDF/XLSX diuji struktural (magic bytes, tipe sel, judul sheet), bukan visual. Visual QA browser tetap pekerjaan terpisah.
- Render PDF memakai dompdf lokal; tidak ada panggilan jaringan (`isRemoteEnabled=false`).

---

## 7. Di Luar Cakupan

- Verifikasi browser visual (perlu sesi browser).
- Uji beban ekspor 50.000 baris nyata.
- Verifikasi MySQL `gearora_test`.