# Report Export Guide

> **Produk:** GEARORA — Outdoor Supply
> **Fase:** Phase 7.5
> **Tanggal:** 19 November 2026

Panduan pemakaian dan ekstensi ekspor laporan (PDF, Excel/XLSX, CSV).

---

## 1. Endpoint

```
GET /admin/reports/{report}/export/{format?}
```

- Nama route: `admin.reports.export`.
- `{report}`: `summary | rental | sales | payments | deposits | charges | inventory | operations`.
- `{format}`: `csv` (default) | `pdf` | `xlsx`.
- Report/format tak dikenal → HTTP 404.
- Query filter sama dengan layar laporan (`start_date`, `end_date`, `status`, `method`, `transaction_type`, `product_id`, `category_id`).

Contoh pemanggilan di Blade:

```blade
{{ route('admin.reports.export', ['report' => 'sales', 'format' => 'xlsx', 'start_date' => $filters['start_date']]) }}
```

Nama berkas: `gearora-{report}-{start}_{end}.{ext}`.

---

## 2. Otorisasi

Ekspor butuh **dua** hal:

1. Permission melihat laporan terkait (Owner bypass).
2. Permission `reports.export` (Owner bypass).

Pengguna yang hanya punya `reports.export` tanpa permission lihat laporan akan ditolak `403`. Rincian: `REPORT_ACCESS_CONTROL.md`.

---

## 3. Batas Baris

| Format | Batas | Perilaku bila melebihi |
| --- | --- | --- |
| CSV | `EXPORT_ROW_LIMIT = 50000` | HTTP 422, pesan minta persempit filter. |
| PDF | `PDF_MAX_ROWS = 1000` | HTTP 422, sarankan Excel/CSV. |
| XLSX | `XLSX_MAX_ROWS = 10000` | HTTP 422, sarankan CSV. |

Tidak ada pemotongan data diam-diam: pengguna selalu diberi tahu lewat pesan error.

Di layar (non-ekspor) tabel dibatasi 500 baris (300 untuk laporan pembayaran); ini hanya tampilan, tidak memengaruhi ekspor.

---

## 4. Format PDF

- Renderer: `App\Services\ReportExportService::pdf()` via `Barryvdh\DomPDF\Facade\Pdf`.
- View: `resources/views/admin/reports/pdf.blade.php`.
- Kertas A4; landscape untuk `rental | sales | payments | deposits | charges | inventory`, portrait untuk `summary | operations`.
- `isRemoteEnabled = false` (tanpa fetch aset jaringan).
- Header brand + footer dengan nomor halaman.
- Kolom uang diformat `rupiah()` berdasarkan `$currencyKeys`; metrik uang di bagian ringkasan mengikuti daftar `moneyMetrics`.

---

## 5. Format XLSX

- Renderer: `App\Services\ReportExportService::xlsx()` (PhpSpreadsheet `Xlsx` writer).
- Dikirim `streamDownload` dengan `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` dan `Cache-Control: no-store, no-cache`.
- Laporan detail: satu sheet, header brand baris 1–3, header kolom baris 4, data mulai baris 5, autofilter + freeze pane `A6`, orientasi halaman mengikuti jenis laporan.
- Laporan `summary`: sheet "Ringkasan" (Grup/Metrik/Nilai) + satu sheet per grup yang diizinkan role.
- Kolom uang ditulis `TYPE_NUMERIC` dengan format `"Rp"#,##0`; selain itu string.
- Semua sel teks melewati `safeString()` (formula-injection safe).

Persyaratan runtime: ekstensi PHP `zip` aktif.

---

## 6. Format CSV

- Renderer: `App\Support\CsvExporter::stream()` (tidak berubah dari Phase 7).
- UTF-8 dengan BOM, `Content-Type: text/csv; charset=UTF-8`.
- Header kolom dari `ReportService::csvHeaders($report)`.
- Guard formula injection tetap aktif.
- Backward compatible: pemanggilan `route('admin.reports.export', ['report' => 'sales'])` tetap menghasilkan CSV.
- Laporan `summary` mengekspor baris Metrik/Nilai hanya untuk grup yang boleh dilihat pengguna.

---

## 7. Menambah Format atau Laporan Baru

**Laporan baru:**

1. Tambah kunci ke `ReportController::TYPES` dan mapping permission di `AdminNavigation::reportPermissionsByName()` + child menu laporan.
2. Tambah metode di `ReportService` dan cabang `report()`, `csvHeaders()`, serta kolom uang di `ReportExportService::currencyKeys()` dan label di `ReportExportService::label()`.
3. Tambah permission di `PermissionName` + `RbacDefaults`, jalankan seeder.
4. Tambah test otorisasi + ekspor.

**Format baru:**

1. Tambah ke `ReportController::FORMATS` dan constraint `where('format', ...)` di `routes/web.php`.
2. Tambah cabang di `ReportController::export()` dan metode renderer di `ReportExportService`.
3. Tambah test.

---

## 8. Catatan Hosting (AeonFree)

- Ekspor sinkron tanpa queue/Redis; aman untuk shared hosting.
- `set_time_limit`/memory: batas baris menjaga peak memory PDF/XLSX.
- Unduhan memakai `Cache-Control: no-store` agar data keuangan tidak tersimpan di cache proxy/browser.
