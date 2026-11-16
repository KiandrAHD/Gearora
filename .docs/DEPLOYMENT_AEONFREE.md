# Deployment — AeonFree (GEARORA)

> **Status dokumen:** prosedur sebagian **PENDING** — beberapa langkah baru dapat dikunci setelah akun AeonFree diverifikasi.
> **Fakta & status bukti:** lihat `.docs/PHASE_0_HOSTING_VALIDATION.md`.
> **JANGAN** menaruh kredensial, `.env`, atau data sensitif ke Git.

---

## 0. Peringatan Keamanan (baca dulu)

- **Jangan** menaruh seluruh project Laravel di dalam `public_html`. `.env`, source, dan `vendor` bisa terunduh publik.
- **Jangan** pernah mengirim password/token hosting ke siapa pun.
- **Jangan** mengaktifkan `APP_DEBUG=true` di produksi.
- Bila hosting memaksa source berada di dalam web root tanpa proteksi yang dapat diverifikasi: **hentikan deployment**.

---

## 1. Verifikasi Hosting — WAJIB sebelum deploy

Lakukan checklist di `.docs/PHASE_0_HOSTING_VALIDATION.md` §4. Ringkas:

1. Login cPanel (`http://web.aeonfree.com/login`).
2. **Cek versi PHP aktif.** Wajib **≥ 8.2** (Laravel 12). Bila hanya 7.4 → batalkan.
3. **Cek ekstensi:** `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `ctype`, `curl`, `dom`, `xml`, `tokenizer`, `json`.
4. **Buat database + user MySQL** (MySQL Databases), catat nama/DB user.
5. **Cek document root** (Domains): apakah dapat diubah ke `public/` atau tidak.
6. **Upload `aeonfree-diagnostic.php`** (di `.docs/hosting/`) ke `public_html/`, buka di browser, catat hasil, lalu **hapus**.

Hasil verifikasi dicatat dan mengubah status PENDING → VERIFIED pada dokumen validasi.

---

## 2. Prinsip Deployment (tanpa asumsi)

| Asumsi | Realita hosting | Konsekuensi |
|---|---|---|
| Composer tersedia | **Tidak diverifikasi** | Build `vendor/` di lokal. |
| Node.js tersedia | **Tidak diverifikasi** | Build aset Vite di lokal. |
| SSH/Terminal | **Tidak diverifikasi** | Upload via FTP/File Manager. |
| Cron tersedia | **Tidak diverifikasi** | Fitur terjadwal diperiksa on-demand. |

Semua proses berat dijalankan **di lokal**.

---

## 3. Persiapan Build di Lokal

```bash
# 1. Ambil kode terbaru
git checkout main
git pull

# 2. Dependency produksi
composer install --no-dev --optimize-autoloader

# 3. Aset frontend
npm ci
npm run build

# 4. (opsional) cek cepat
php artisan test
```

Yang akan diunggah: **seluruh source kecuali** `node_modules/`, `tests/`, `.git/`, `database/database.sqlite`, file dev. `vendor/` **diunggah** (karena Composer server tidak diasumsikan ada).

> Opsi hemat kuota: bila SSH/Composer terbukti tersedia, jalankan `composer install` di server dan **jangan** unggah `vendor/`.

---

## 4. Struktur Direktori Target

### Skenario A — document root dapat diatur ke `public/` (paling aman)

```text
public_html/                  <- isi direktori public/ Laravel
├── index.php
├── .htaccess
├── build/
└── assets/

/home/akun/gearora-app/       <- source Laravel (di luar web root)
├── app/ bootstrap/ config/ database/ resources/ routes/ storage/ vendor/
├── .env
└── artisan
```

### Skenario B — source di luar `public_html`, `public_html` hanya file publik

Digunakan bila document root tidak dapat diubah tetapi folder di luar `public_html` dapat diakses.

```text
public_html/
├── index.php      <- require '/home/akun/gearora-app/bootstrap/app.php'
├── .htaccess
├── build/
└── assets/

/home/akun/gearora-app/   <- source lengkap + .env
```

`public_html/index.php` (sesuaikan path absolut):

```php
<?php

use Illuminate\Foundation\Application;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../gearora-app/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../gearora-app/bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
```

Salin juga `public/.htaccess` ke `public_html/.htaccess`.

### Skenario C — seluruh source di dalam web root
**DITOLAK.** Tidak dilakukan tanpa mekanisme proteksi terverifikasi.

---

## 5. Konfigurasi `.env` Produksi

Buat `.env` **di server** (jangan pernah commit/unggah dari repo). Contoh isi:

```dotenv
APP_NAME="GEARORA"
APP_ENV=production
APP_KEY=base64:...          # php artisan key:generate --show (jalankan di lokal, salin hasilnya)
APP_DEBUG=false
APP_URL=https://domain-anda.tld

APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=akun_gearora
DB_USERNAME=akun_gearora
DB_PASSWORD=<password-kuat>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

> Generate `APP_KEY` di lokal: `php artisan key:generate --show` → salin nilai ke `.env` server.

---

## 6. Upload

1. Upload source ke `gearora-app/` (FTP/File Manager) — sertakan `vendor/`.
2. Upload isi `public/` ke document root (`public_html/` atau sesuai Skenario A/B).
3. Buat `.env` di server.
4. Pastikan permission: `storage/` dan `bootstrap/cache/` **writable** (biasanya `755`, atau `775` bila perlu).

## 7. Migrasi Database

Bila SSH tidak ada, jalankan migrasi lewat salah satu cara:

- **Sementara** buat route sekali-pakai yang memanggil `Artisan::call('migrate', ['--force' => true])`, akses sekali, lalu **hapus** route.
- Atau impor skema via phpMyAdmin.
- Atau bila SSH tersedia: `php artisan migrate --force`.

## 8. Cache Produksi (setelah `.env` benar)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Jalankan setelah memastikan `.env` final; `config:cache` akan mengunci nilai environment.

## 9. Smoke Test Setelah Deploy

- [ ] Homepage `https://domain-anda.tld/` → HTTP 200, CSS & JS termuat.
- [ ] `https://domain-anda.tld/up` → HTTP 200.
- [ ] Halaman tidak menampilkan stack trace.
- [ ] `.env` **tidak** dapat diakses: `https://domain-anda.tld/.env` → 404/403.
- [ ] `https://domain-anda.tld/vendor/` → 403/404.
- [ ] `https://domain-anda.tld/storage/` → 403/404.
- [ ] Hapus `aeonfree-diagnostic.php` dari `public_html/`.

## 10. Rollback

1. Screenshot/backup database sebelum migrasi.
2. Simpan rilis sebelumnya (`gearora-app` versi lama) agar bisa dipulihkan.
3. Untuk migrasi: `php artisan migrate:rollback --step=1` (bila SSH ada).

## 11. Checklist Keamanan Pra-Deploy

- [ ] `APP_DEBUG=false`.
- [ ] `.env` ada di server, **tidak** di Git, **tidak** dapat diunduh.
- [ ] HTTPS aktif (SSL Cloudflare/cPanel).
- [ ] Permission `storage/` & `bootstrap/cache/` benar.
- [ ] Source di luar document root (Skenario A/B).
- [ ] Tidak ada file diagnostik/sementara tertinggal.

---

## 12. Bagian yang Masih PENDING (butuh verifikasi akun)

| Item | Status | Menunggu |
|---|---|---|
| Versi PHP ≥ 8.2 | PENDING | Cek cPanel |
| Ekstensi PHP | PENDING | Cek cPanel / diagnostik |
| Versi MySQL | PENDING | Cek cPanel |
| Composer / SSH | PENDING | Cek cPanel |
| Document root | PENDING | Cek cPanel → Domains |
| Permission storage | PENDING | Uji tulis di server |
| Cron | PENDING | Cek cPanel |
