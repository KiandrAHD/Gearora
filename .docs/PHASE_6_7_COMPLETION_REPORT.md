# Phase 6/7 — Completion Report

> **Produk:** GEARORA — Outdoor Supply
> **Fase:** Phase 6 (Trip Planner & Equipment Guides) + Phase 7 (Retail Sales & Expanded Reports)
> **Tanggal:** 18 November 2026
> **Bahasa:** Indonesia
> **Commit:** `07359a1 feat: implement trip planner, equipment guides, retail sales, expanded reports`

---

## 1. Ringkasan Eksekutif

Phase 6 dan Phase 7 selesai pada layer backend, routes, Blade UI, RBAC, test, seed demo, dan dokumentasi teknis.

Phase 6 menambah:

- Trip Planner deterministik berbasis rule DB;
- admin CRUD aturan rekomendasi;
- Equipment Guides CMS dengan kategori, sumber, lifecycle publish;
- renderer Markdown subset aman dan upload cover tervalidasi;
- disclaimer keselamatan.

Phase 7 menambah:

- domain retail terpisah dari rental;
- retail cart terpisah;
- server-authoritative pricing;
- reservasi stok dan anti-overselling;
- konsumsi stok exactly-once saat pembayaran settle;
- pembayaran retail transfer/cash/Midtrans melalui gateway bersama;
- laporan expanded + CSV formula-injection protection.

Tidak ada migrasi destructive pada database utama.

---

## 2. Status Verifikasi

| Verifikasi | Status | Hasil |
| --- | --- | --- |
| PHPUnit full suite | PASS | **174 passed, 495 assertions** |
| Phase 6 feature suite | PASS | 40 passed, 98 assertions |
| Phase 7 feature suite | PASS | 50 passed, 126 assertions |
| Pint fix | PASS | formatter selesai |
| `vendor/bin/pint --test` | PASS | clean |
| `php artisan route:list --except-vendor` | PASS | routes terdaftar |
| Syntax lint PHP baru | PASS | tidak ada syntax error |
| MySQL 8 integration (`gearora_test`) | **BLOCKED** | DB test belum tersedia/terverifikasi |
| Midtrans Sandbox nyata | **BLOCKED** | kredensial belum tersedia |
| Browser visual QA | **PENDING/BLOCKED** | perlu menjalankan app + browser session |

Catatan: test suite memakai SQLite in-memory dan `FakePaymentGateway`; ini tidak menggantikan verifikasi MySQL/Midtrans nyata.

---

## 3. Artefak Phase 6

### Kode

- Enum: `TripActivity`, `TripSpecialNeed`, `TripServiceMode`, `GuideStatus`.
- Model: `TripRecommendationRule`, `GuideCategory`, `EquipmentGuide`, `GuideSource`.
- Service: `TripRecommendationService`, `GuideService`.
- Support: `TripPlanData`, `GuideContentRenderer`.
- Controller public/admin, Form Request, Policy, routes, views.
- Migration `2026_11_19_000100` sampai `000400`.
- Factory + `DemoGuidesSeeder` + `DemoTripRulesSeeder`.

### Perilaku penting

- Rule aktif diurutkan `priority DESC, id ASC`.
- Tidak random, tidak LLM, tidak product array hardcoded.
- Produk/varian inactive dikeluarkan.
- Dedup per varian.
- Qty per peserta didukung dan dibatasi 1..99.
- Ketersediaan sewa hanya diklaim bila tanggal valid dan dicek service availability.
- Draft/future/archived guide tidak dapat diakses publik.
- Slug published stabil saat judul berubah.
- Konten di-escape sebelum subset Markdown dirender.
- Cover MIME, ukuran, disk, dan path diatur aman.

---

## 4. Artefak Phase 7

### Kode

- Enum: `SalesOrderStatus`, `SalesReservationStatus`.
- Contract: `PayableOrder`.
- Model: `SalesOrder`, `SalesOrderItem`, `SalesStockReservation`, `SalesPayment`, `SalesPaymentAttempt`.
- Service: `SalesCartService`, `SalesOrderService`, `SalesStockService`, `SalesPaymentService`, `ReportService`, `ProductVariantQuery`.
- Support: `MidtransSignature`, `CsvExporter`.
- Controller public/customer/admin, Form Request, Policy, routes, views.
- Migration `2026_11_19_001000` sampai `001400`.
- Factory + sales test fixtures.

### Perilaku penting

- Cart session `retail_cart`; rental cart tidak disentuh.
- Checkout menyimpan hanya ID varian + qty dari client; harga dihitung ulang server.
- Checkout memakai row lock dan reservasi stok dalam transaksi.
- Stok fisik tidak berkurang sebelum settlement.
- Settlement mengurangi stok satu kali dan membuat satu `StockMovement::Sale`.
- Webhook ganda, verify ganda, dan mark settled ganda idempotent.
- Order final late-payment masuk `processing`, bukan mengonsumsi stok diam-diam.
- Deposit tetap dikeluarkan dari revenue.
- CSV memiliki UTF-8 BOM dan formula-injection guard.

---

## 5. RBAC

Owner memiliki semua permission. Admin mendapat permission business untuk trip, guides, sales, dan reports. Warehouse tetap inventory/operasional yang diizinkan; tidak mendapat akses sales business/report business. Customer hanya order retail miliknya dan halaman publik.

Menu admin/public diperbarui berdasarkan permission. Policy customer memeriksa ownership order.

---

## 6. Dokumentasi Dihasilkan

| Dokumen | Status |
| --- | --- |
| `PHASE_6_TECHNICAL_DESIGN.md` | selesai |
| `TRIP_RECOMMENDATION_ENGINE.md` | selesai |
| `TRIP_RECOMMENDATION_RULES.md` | selesai |
| `EQUIPMENT_GUIDES_CMS.md` | selesai |
| `PHASE_6_TEST_PLAN.md` | selesai |
| `PHASE_7_TECHNICAL_DESIGN.md` | selesai |
| `ADR_SHARED_PAYMENT_FOR_RETAIL.md` | selesai |
| `RETAIL_SALES_WORKFLOW.md` | selesai |
| `SALES_STOCK_RESERVATION.md` | selesai |
| `REPORT_DEFINITIONS.md` | selesai |
| `PHASE_7_TEST_PLAN.md` | selesai |
| `PHASE_6_7_COMPLETION_REPORT.md` | dokumen ini |

Dokumen existing `PRD_GEARORA.md`, `ARCHITECTURE_GEARORA.md`, `ROADMAP_GEARORA.md`, `.docs/README.md`, dan ERD perlu diberi pointer Phase 6/7 pada update dokumentasi berikutnya bila belum tercantum.

---

## 7. Seeder Demo

`DatabaseSeeder` sekarang memanggil pada environment `local`/`testing`:

1. `RolePermissionSeeder`
2. `SettingsSeeder`
3. `DemoCatalogSeeder`
4. `DemoGuidesSeeder`
5. `DemoTripRulesSeeder`

Production tidak mendapat demo guides/rules/catalog melalui guard environment.

---

## 8. Konfigurasi `.env.example`

Ditambahkan placeholder non-secret:

```dotenv
GEARORA_TRIP_MAX_RESULTS=12
GEARORA_TRIP_MAX_DAYS=60
GEARORA_TRIP_MAX_PARTICIPANTS=100
GEARORA_GUIDE_COVER_MAX_KB=4096
GEARORA_GUIDE_COVER_DISK=public
GEARORA_GUIDE_CONTENT_MAX_KB=200
GEARORA_SALES_RESERVATION_MINUTES=120
GEARORA_SALES_MAX_QTY_PER_LINE=99
GEARORA_PAYMENT_PROOF_MAX_KB=5120
GEARORA_PAYMENT_PROOF_DISK=local
```

Midtrans tetap default disabled. Secret tidak ditulis ke repository.

---

## 9. Blocker Release

### 9.1 MySQL

Buat database terpisah:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS gearora_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test --configuration=phpunit.mysql.xml
```

Jangan gunakan `migrate:fresh`, `db:wipe`, atau `migrate:reset` pada `gearora`.

### 9.2 Midtrans

Isi credential Sandbox di `.env` lokal, aktifkan gateway, lalu jalankan flow Snap + webhook dengan signature nyata. Pastikan event retail dan rental tetap terpisah secara agregat tetapi diproses endpoint bersama.

### 9.3 Browser QA

Jalankan `php artisan serve` + `npm run dev`/build, lalu uji:

- Trip Planner mobile/desktop;
- guide list/detail/CMS;
- retail cart/checkout/detail/payment;
- admin sales;
- reports/filter/download CSV;
- navigation berdasarkan role.

---

## 10. Risiko Tersisa

1. Auto-expire reservasi belum dijadwalkan; `expires_at` sudah disimpan.
2. Refund retail otomatis belum diimplementasikan.
3. Fulfillment hanya single-location pickup.
4. MySQL concurrency belum diverifikasi pada `gearora_test`.
5. Midtrans Sandbox nyata belum diverifikasi.
6. Browser visual QA belum tercatat sebagai pass.

Risiko 1–3 adalah batasan scope. Risiko 4–6 wajib ditutup sebelum produksi.

---

## 11. Rekomendasi Next Step

1. Siapkan `gearora_test` dan jalankan PHPUnit MySQL.
2. Isi credential Midtrans Sandbox dan uji webhook nyata.
3. Jalankan browser visual QA, catat screenshot hanya di luar repository.
4. Tambah scheduler/job expiry pada fase berikutnya.
5. Tetapkan kebijakan refund retail sebelum mengaktifkan refund.
6. Push commit `07359a1` setelah review final.

---

## 12. Kesimpulan

Phase 6/7 backend dan automated tests selesai. Suite penuh hijau: **174 tests, 495 assertions**. Produksi masih tertahan oleh verifikasi eksternal MySQL, Midtrans, dan visual browser; blocker ditandai eksplisit, tanpa klaim verifikasi palsu.