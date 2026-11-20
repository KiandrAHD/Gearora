# GEARORA

**Gear Up. Explore More.**

GEARORA adalah platform berbasis website untuk **penyewaan dan penjualan perlengkapan outdoor** (hiking, trekking, camping, dan aktivitas alam lainnya). Platform menyatukan pencarian gear, pemeriksaan ketersediaan, reservasi, panduan penggunaan, serta pengelolaan inventaris dan transaksi.

> Status: **Phase 9 selesai implementasi lokal** untuk UX navbar, katalog rental/retail, Trip Planner lima langkah + paket mixed sewa/beli, dan editorial Equipment Guides + Admin Studio. Automated suite: **320 tests / 988 assertions lulus** (SQLite) + **7 tests / 71 assertions** (integrasi MySQL 8 pada `gearora_test`). Browser smoke disposable SQLite lulus untuk homepage, wizard, hasil rekomendasi, dan panduan. Release produksi masih **PARTIALLY COMPLETED**: authenticated checkout browser, Midtrans Sandbox, hosting, dan merge ke main menunggu operator.
>
> Aturan bisnis rental: [`.docs/RENTAL_POLICY.md`](.docs/RENTAL_POLICY.md). Laporan terbaru: [`.docs/PHASE_8_COMPLETION_REPORT.md`](.docs/PHASE_8_COMPLETION_REPORT.md) (sebelumnya: [`.docs/PHASE_7_5_COMPLETION_REPORT.md`](.docs/PHASE_7_5_COMPLETION_REPORT.md)).

---

## 1. Ringkasan Proyek

- Website publik (Blade, server-rendered): Homepage, katalog SEWA/BELI, detail produk, akun, Trip Planner, panduan, keranjang & checkout retail.
- Admin Console: dashboard adaptif per role, navigasi bertingkat, katalog/inventaris, rental order, pembayaran, deposit, inspeksi, pengembalian, sales order retail, aturan trip, panduan, laporan granular + ekspor PDF/XLSX/CSV.
- Arsitektur: **modular monolith**, MVC + Service Layer, satu database MySQL.
- Dirancang **hosting-aware**: tidak bergantung pada Node.js, Composer, SSH, queue worker, WebSocket, atau proses daemon di server.

## 2. Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 12.x |
| Bahasa | PHP 8.2+ |
| Frontend | Blade + Tailwind CSS 4 + Alpine.js 3 |
| Build tool | Vite 6 (Node.js, build di lokal) |
| Database | MySQL 8.0 (target hosting), InnoDB, `utf8mb4` |
| ORM | Eloquent |
| Testing | PHPUnit 11 (SQLite `:memory:` untuk test) |
| Version control | Git + GitHub |
| Hosting | AeonFree (shared hosting) |

## 3. Status Implementasi & Dokumen

Phase 7.5 selesai di source dan test lokal. Phase 8 (release readiness) menutup hardening keamanan, QA, backup/restore, dan CI.

**Phase 9 (UX + transaction experience):**
- [UX Audit](.docs/PHASE_9_UX_AUDIT.md)
- [Implementation](.docs/PHASE_9_IMPLEMENTATION.md)
- [Trip Package ADR](.docs/ADR_PHASE_9_TRIP_PACKAGE.md)
- [Test Plan and Results](.docs/PHASE_9_TEST_PLAN.md)
- [Release Checklist](.docs/PHASE_9_RELEASE_CHECKLIST.md)
- [Completion Report](.docs/PHASE_9_COMPLETION_REPORT.md)
- [Navbar & Catalog](.docs/PHASE_9_NAVBAR_CATALOG.md) · [Trip Planner](.docs/PHASE_9_TRIP_PLANNER.md) · [Guide Studio](.docs/PHASE_9_GUIDE_STUDIO.md)
- [Security Notes](.docs/PHASE_9_SECURITY_NOTES.md) · [Known Limitations](.docs/PHASE_9_KNOWN_LIMITATIONS.md)

**Phase 8 (release readiness):**
- [Completion Report](.docs/PHASE_8_COMPLETION_REPORT.md)
- [Baseline Audit](.docs/PHASE_8_BASELINE_AUDIT.md)
- [Bug Register](.docs/PHASE_8_BUG_REGISTER.md)
- [Security Audit](.docs/PHASE_8_SECURITY_AUDIT.md)
- [Route Authorization Matrix](.docs/PHASE_8_ROUTE_AUTHORIZATION_MATRIX.md)
- [Test Plan](.docs/PHASE_8_TEST_PLAN.md)
- [E2E Test Results](.docs/PHASE_8_E2E_TEST_RESULTS.md)
- [MySQL Integration Report](.docs/PHASE_8_MYSQL_INTEGRATION_REPORT.md)
- [Backup & Restore Report](.docs/PHASE_8_BACKUP_RESTORE_REPORT.md) · [Runbook](.docs/BACKUP_AND_RESTORE.md)
- [Release Checklist](.docs/PHASE_8_RELEASE_CHECKLIST.md) · [Deployment Readiness](.docs/PHASE_8_DEPLOYMENT_READINESS.md)

**Phase 7.5:**

- [Phase 7.5 Completion Report](.docs/PHASE_7_5_COMPLETION_REPORT.md)
- [Technical Design](.docs/PHASE_7_5_TECHNICAL_DESIGN.md)
- [Admin Navigation Architecture](.docs/ADMIN_NAVIGATION_ARCHITECTURE.md)
- [Report Export Guide](.docs/REPORT_EXPORT_GUIDE.md)
- [Report Access Control](.docs/REPORT_ACCESS_CONTROL.md)
- [Test Plan](.docs/PHASE_7_5_TEST_PLAN.md)

## 4. System Requirements

### Development lokal
- PHP **8.2+** (diuji pada 8.5.9) dengan ekstensi: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `ctype`, `curl`, `dom`, `xml`, `tokenizer`, `json`, `bcmath`, `sqlite3` (untuk test).
- Composer 2.x.
- Node.js 20+ dan npm.
- MySQL 8.0 (opsional untuk dev; SQLite cukup untuk memulai).
- Git.

### Produksi (AeonFree)
- PHP **8.2+** dan MySQL — **wajib diverifikasi** di cPanel (lihat `.docs/PHASE_0_HOSTING_VALIDATION.md`).

## 4. Instalasi

```bash
git clone https://github.com/KiandrAHD/Gearora.git
cd Gearora
composer install
cp .env.example .env      # Windows: copy .env.example .env
php artisan key:generate
npm install
```

## 5. Environment Configuration

Konfigurasi utama di `.env` (jangan di-commit). Ringkasan:

| Variabel | Nilai GEARORA | Catatan |
|---|---|---|
| `APP_NAME` | `GEARORA` | — |
| `APP_ENV` | `local` / `production` | — |
| `APP_DEBUG` | `true` (local) / `false` (produksi) | Wajib `false` di produksi |
| `APP_TIMEZONE` | `Asia/Jakarta` | — |
| `APP_LOCALE` | `id` | UI Bahasa Indonesia |
| `DB_CONNECTION` | `mysql` (produksi) | `sqlite` untuk dev tanpa MySQL |
| `SESSION_DRIVER` | `database` | Redis tidak diasumsikan tersedia |
| `CACHE_STORE` | `database` | — |
| `QUEUE_CONNECTION` | `sync` | Tanpa worker persisten |
| `MAIL_MAILER` | `log` | Placeholder aman |

## 6. Database Setup

**MySQL (target produksi / parity):**

```sql
CREATE DATABASE gearora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gearora'@'localhost' IDENTIFIED BY 'ganti-password-kuat';
GRANT ALL PRIVILEGES ON gearora.* TO 'gearora'@'localhost';
FLUSH PRIVILEGES;
```

Atur `DB_*` di `.env`, lalu:

```bash
php artisan migrate
```

**SQLite (tanpa install MySQL):** ubah `.env` menjadi `DB_CONNECTION=sqlite` (dan pastikan `database/database.sqlite` ada), lalu `php artisan migrate`.

> Migration mencakup seluruh domain Phase 0–7 (katalog, rental, pembayaran/deposit, operasional, trip planner, guides, retail sales). Tidak ada seeder akun produksi; akun Owner dibuat via `php artisan gearora:provision-owner`. Seeder demo hanya berjalan di `local`/`testing`.

## 7. Running Development Server

```bash
php artisan serve
# http://127.0.0.1:8000
```

Health check internal bawaan Laravel: `GET /up`.

## 8. Running Vite

```bash
npm run dev      # mode watch, untuk development
```

## 9. Running Tests

Test memakai SQLite `:memory:` (terpisah dari database dev/produksi).

```bash
php artisan test
# atau
./vendor/bin/phpunit
```

Hasil terakhir: **249 tests, 747 assertions** (SQLite in-memory). Pemeriksaan tambahan:

```bash
vendor/bin/pint --test              # gaya kode
composer validate --strict          # manifest
composer audit                      # advisory PHP
npm audit                           # advisory JS
npm run build                       # build aset
```

Untuk parity MySQL (butuh database terpisah `gearora_test`, dijalankan dengan MySQL 8 aktif):

```bash
vendor/bin/phpunit --configuration=phpunit.mysql.xml
```

Hasil terakhir MySQL: **5 tests, 58 assertions** (termasuk uji row-lock InnoDB dua koneksi).

Jangan pernah memakai `migrate:fresh`, `db:wipe`, atau `migrate:reset` pada database `gearora`.
Test MySQL menolak berjalan bila `DB_DATABASE` bukan `gearora_test`.

Backup/restore: lihat `.docs/BACKUP_AND_RESTORE.md`. Gate konfigurasi produksi:
`php artisan gearora:production-check`. Expiry reservasi retail:
`php artisan gearora:expire-sales-orders`.

## 10. Build Production

```bash
npm run build                       # menghasilkan public/build/
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10.1 UI Foundation (Phase 1)

Halaman skeleton: `/` (home), `/katalog`, `/panduan`, `/akun`. Admin Dashboard skeleton ada di `resources/views/admin/dashboard.blade.php` dan belum diberi route publik (menunggu RBAC Phase 2).

| Komponen | Pemakaian |
|---|---|
| `x-layouts.public` / `x-layout` | Layout publik |
| `x-layouts.customer` | Layout area pelanggan |
| `x-layouts.admin` | Layout Admin Dashboard |
| `x-ui.button` | `variant="primary\|secondary\|outline\|ghost\|danger"`, `size`, `href` |
| `x-ui.input` | label, hint, error, `aria-describedby` otomatis |
| `x-ui.badge` | `variant="neutral\|success\|warning\|danger\|info\|forest"` |
| `x-ui.card` | title, description, `footer` slot |
| `x-ui.table` | `:headers`, `empty`, `caption` |
| `x-ui.modal` | Alpine.js, `id`, `title`, `footer` slot; buka via `x-dispatch` `open-modal` |
| `x-ui.alert` | `variant`, `dismissible` |
| `x-ui.pagination` | `:paginator` |
| `x-ui.empty-state` | title, description, icon, action slot |

Format Rupiah:

```php
rupiah(1250000);        // "Rp 1.250.000"
rupiah('1250000.50', 2); // "Rp 1.250.000,50"
```

## 10.2 Fitur Phase 6 (Trip Planner & Guides)

- **Trip Planner** `/rekomendasi-trip`: input aktivitas, destinasi, durasi, peserta, kebutuhan khusus, mode sewa/beli, tanggal opsional. Rekomendasi dihasilkan `TripRecommendationService` dari tabel `trip_recommendation_rules` — deterministik, tanpa LLM, tanpa daftar produk hardcoded.
- **Admin aturan trip** `/admin/trip-recommendations`: CRUD rule, target kategori/produk/varian, prioritas, kuantitas per peserta, template alasan.
- **Panduan publik** `/panduan`: pencarian, filter kategori, halaman detail.
- **CMS panduan** `/admin/guides`: konten Markdown aman (escape-first), kategori, sumber, cover upload tervalidasi, lifecycle `draft → published → archived`.
- Disclaimer keselamatan selalu tampil di Trip Planner.

## 10.3 Fitur Phase 7 (Retail Sales & Reports)

- **Keranjang retail** `/keranjang`: session terpisah dari draft rental; harga & stok dihitung ulang server.
- **Checkout retail**: order `sales_orders` terpisah, reservasi stok, anti-overselling dengan row lock.
- **Pembayaran**: transfer manual + bukti, tunai dicatat admin, atau Midtrans (adaptor gateway bersama rental).
- **Stok**: stok fisik berkurang **hanya** saat pembayaran settle, tepat satu kali; pembatalan/expiry melepas reservasi.
- **Admin sales** `/admin/sales`: daftar, filter, detail, transisi status, verifikasi/tolak pembayaran.
- **Laporan** `/admin/reports`: rental, sales, payments, deposits, charges, inventory, operations; filter periode; ekspor CSV dengan UTF-8 BOM dan proteksi formula injection.
- Deposit **tidak** dihitung sebagai revenue.

Dokumentasi: [`PHASE_6_TECHNICAL_DESIGN.md`](.docs/PHASE_6_TECHNICAL_DESIGN.md), [`PHASE_7_TECHNICAL_DESIGN.md`](.docs/PHASE_7_TECHNICAL_DESIGN.md), [`REPORT_DEFINITIONS.md`](.docs/REPORT_DEFINITIONS.md).

## 11. AeonFree Deployment Strategy

Ringkas (detail + langkah lengkap: `.docs/DEPLOYMENT_AEONFREE.md`):

1. Verifikasi hosting lebih dulu (`.docs/PHASE_0_HOSTING_VALIDATION.md`).
2. Build `vendor/` dan `public/build/` **di lokal**.
3. Taruh source Laravel **di luar** `public_html` (mis. `gearora-app/`); hanya file publik (`index.php`, `.htaccess`, `build/`, `assets/`) di document root.
4. Set `APP_DEBUG=false`, `APP_ENV=production`, isi kredensial DB nyata (jangan commit).
5. Jalankan smoke test.

> Status dokumentasi deployment: **sebagian PENDING** menunggu verifikasi akun AeonFree (versi PHP, Composer/SSH, document root).

## 12. Project Documentation Links

| Dokumen | Isi |
|---|---|
| [`.docs/PRD_GEARORA.md`](.docs/PRD_GEARORA.md) | Visi, persona, kebutuhan fungsional/nonfungsional, business rules |
| [`.docs/ARCHITECTURE_GEARORA.md`](.docs/ARCHITECTURE_GEARORA.md) | Arsitektur, modul, domain model, alur transaksi, keamanan |
| [`.docs/TECH_STACK_GEARORA.md`](.docs/TECH_STACK_GEARORA.md) | Teknologi, kompatibilitas versi, batasan AeonFree |
| [`.docs/ROADMAP_GEARORA.md`](.docs/ROADMAP_GEARORA.md) | Fase implementasi dan exit criteria |
| [`.docs/Desain_Gearora.md`](.docs/Desain_Gearora.md) | Master design reference Homepage |
| [`.docs/PHASE_0_REQUIREMENT_AUDIT.md`](.docs/PHASE_0_REQUIREMENT_AUDIT.md) | Audit requirement & konflik dokumen |
| [`.docs/PHASE_0_HOSTING_VALIDATION.md`](.docs/PHASE_0_HOSTING_VALIDATION.md) | Validasi hosting + status bukti |
| [`.docs/PHASE_0_TECHNICAL_DECISIONS.md`](.docs/PHASE_0_TECHNICAL_DECISIONS.md) | Technology Decision Record |
| [`.docs/REQUIREMENT_FREEZE.md`](.docs/REQUIREMENT_FREEZE.md) | Requirement freeze |
| [`.docs/RENTAL_POLICY.md`](.docs/RENTAL_POLICY.md) | Kebijakan rental baseline MVP (satu lokasi, durasi, deposit, refund, pembayaran) |
| [`.docs/LOCAL_DEVELOPMENT.md`](.docs/LOCAL_DEVELOPMENT.md) | Setup lokal dari nol |
| [`.docs/DEPLOYMENT_AEONFREE.md`](.docs/DEPLOYMENT_AEONFREE.md) | Prosedur deployment |
| [`.docs/PHASE_0_1_FINAL_REPORT.md`](.docs/PHASE_0_1_FINAL_REPORT.md) | Laporan penyelesaian Phase 0 & 1 terbaru |
| [`.docs/PHASE_0_1_COMPLETION_REPORT.md`](.docs/PHASE_0_1_COMPLETION_REPORT.md) | Laporan penyelesaian Phase 0 & 1 (16 Nov 2026) |
| [`.docs/PHASE_5_COMPLETION_REPORT.md`](.docs/PHASE_5_COMPLETION_REPORT.md) | Laporan Phase 5 (payments & rental operations) |
| [`.docs/PHASE_6_TECHNICAL_DESIGN.md`](.docs/PHASE_6_TECHNICAL_DESIGN.md) | Desain Trip Planner & Equipment Guides |
| [`.docs/TRIP_RECOMMENDATION_ENGINE.md`](.docs/TRIP_RECOMMENDATION_ENGINE.md) | Pipeline rekomendasi deterministik |
| [`.docs/TRIP_RECOMMENDATION_RULES.md`](.docs/TRIP_RECOMMENDATION_RULES.md) | Panduan aturan rekomendasi |
| [`.docs/EQUIPMENT_GUIDES_CMS.md`](.docs/EQUIPMENT_GUIDES_CMS.md) | CMS panduan & keamanan konten |
| [`.docs/PHASE_6_TEST_PLAN.md`](.docs/PHASE_6_TEST_PLAN.md) | Test plan Phase 6 |
| [`.docs/PHASE_7_TECHNICAL_DESIGN.md`](.docs/PHASE_7_TECHNICAL_DESIGN.md) | Desain retail sales & laporan |
| [`.docs/ADR_SHARED_PAYMENT_FOR_RETAIL.md`](.docs/ADR_SHARED_PAYMENT_FOR_RETAIL.md) | ADR gateway bersama rental/retail |
| [`.docs/RETAIL_SALES_WORKFLOW.md`](.docs/RETAIL_SALES_WORKFLOW.md) | Alur retail customer & admin |
| [`.docs/SALES_STOCK_RESERVATION.md`](.docs/SALES_STOCK_RESERVATION.md) | Reservasi stok & anti-overselling |
| [`.docs/REPORT_DEFINITIONS.md`](.docs/REPORT_DEFINITIONS.md) | Definisi metrik laporan |
| [`.docs/PHASE_7_TEST_PLAN.md`](.docs/PHASE_7_TEST_PLAN.md) | Test plan Phase 7 |
| [`.docs/PHASE_6_7_COMPLETION_REPORT.md`](.docs/PHASE_6_7_COMPLETION_REPORT.md) | Laporan penyelesaian Phase 6/7 |
| [`.Gearora/GEARORA.md`](.Gearora/GEARORA.md) | Proposal konsep |

---

**GEARORA — Gear Up. Explore More.**
