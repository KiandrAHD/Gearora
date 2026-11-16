# GEARORA

**Gear Up. Explore More.**

GEARORA adalah platform berbasis website untuk **penyewaan dan penjualan perlengkapan outdoor** (hiking, trekking, camping, dan aktivitas alam lainnya). Platform menyatukan pencarian gear, pemeriksaan ketersediaan, reservasi, panduan penggunaan, serta pengelolaan inventaris dan transaksi.

> Status: **Phase 0 (Requirement Freeze & Hosting Validation) selesai** dan **Phase 1 (Project Foundation) selesai**. Modul bisnis (auth, katalog, rental order, pembayaran, dll.) dibangun pada fase berikutnya.

---

## 1. Ringkasan Proyek

- Website publik (Blade, server-rendered): Homepage, katalog SEWA/BELI, detail produk, akun, trip planner, panduan.
- Admin Console (fase berikutnya): dashboard, katalog/inventaris, rental order, pembayaran, deposit, inspeksi, pengembalian, sales, laporan.
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

## 3. System Requirements

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

> Phase 1 hanya memuat migration **baseline** bawaan Laravel (`users`, `sessions`, `cache`, `jobs`). Tabel bisnis (produk, rental order, dsb.) dibuat pada fase berikutnya.

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

## 10. Build Production

```bash
npm run build                       # menghasilkan public/build/
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

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
| [`.docs/LOCAL_DEVELOPMENT.md`](.docs/LOCAL_DEVELOPMENT.md) | Setup lokal dari nol |
| [`.docs/DEPLOYMENT_AEONFREE.md`](.docs/DEPLOYMENT_AEONFREE.md) | Prosedur deployment |
| [`.docs/PHASE_0_1_COMPLETION_REPORT.md`](.docs/PHASE_0_1_COMPLETION_REPORT.md) | Laporan penyelesaian Phase 0 & 1 |
| [`.Gearora/GEARORA.md`](.Gearora/GEARORA.md) | Proposal konsep |

---

**GEARORA — Gear Up. Explore More.**
