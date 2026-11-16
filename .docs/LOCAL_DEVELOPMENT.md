# Local Development — GEARORA

Panduan menjalankan GEARORA dari nol di mesin lokal (Windows/Laragon, macOS, Linux).

---

## 1. Prasyarat

| Tool | Versi minimal | Cara cek |
|---|---|---|
| PHP | 8.2+ (diuji 8.5.9) | `php -v` |
| Composer | 2.x | `composer --version` |
| Node.js | 20+ (diuji 26.13) | `node -v` |
| npm | 10+ (diuji 11.20) | `npm -v` |
| Git | 2.x | `git --version` |
| MySQL | 8.0 (opsional) | `mysql --version` |

Ekstensi PHP wajib: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `ctype`, `curl`, `dom`, `xml`, `tokenizer`, `json`. Untuk test dengan SQLite: `sqlite3` + `pdo_sqlite`.

Cek cepat ekstensi:

```bash
php -m
```

---

## 2. Clone & Install

```bash
git clone https://github.com/KiandrAHD/Gearora.git
cd Gearora

composer install
npm install
```

## 3. Konfigurasi Environment

```bash
cp .env.example .env      # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
```

### Opsi A — Development dengan SQLite (paling cepat)

Tidak perlu install MySQL. Ubah `.env`:

```dotenv
DB_CONNECTION=sqlite
```

Pastikan file database ada:

```bash
# PowerShell
New-Item -ItemType File -Path database/database.sqlite -Force
# bash
touch database/database.sqlite
```

> `database/database.sqlite` sudah di-ignore Git.

### Opsi B — Development dengan MySQL (parity dengan produksi)

Buat database + user:

```sql
CREATE DATABASE gearora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gearora'@'localhost' IDENTIFIED BY 'ganti-password-kuat';
GRANT ALL PRIVILEGES ON gearora.* TO 'gearora'@'localhost';
FLUSH PRIVILEGES;
```

Set `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gearora
DB_USERNAME=gearora
DB_PASSWORD=ganti-password-kuat
```

## 4. Migrasi Database

```bash
php artisan migrate
```

Rollback (opsional, untuk menguji reversibilitas):

```bash
php artisan migrate:rollback
```

## 5. Menjalankan Aplikasi

Dua terminal:

```bash
# Terminal 1 — server aplikasi
php artisan serve            # http://127.0.0.1:8000

# Terminal 2 — Vite (hot reload aset)
npm run dev
```

Buka `http://127.0.0.1:8000`. Health check: `http://127.0.0.1:8000/up`.

## 6. Menjalankan Test

Test memakai SQLite `:memory:` dan **tidak** menyentuh database dev/produksi.

```bash
php artisan test
```

## 7. Build Aset Produksi (uji lokal)

```bash
npm run build
```

Hasil: `public/build/`. Verifikasi halaman tetap tampil normal.

## 8. Perintah Berguna

```bash
php artisan route:list
php artisan about
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

## 9. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| `could not find driver` | ekstensi `pdo_sqlite`/`pdo_mysql` mati | aktifkan di `php.ini`, restart terminal |
| `No application encryption key` | `.env` tanpa `APP_KEY` | `php artisan key:generate` |
| `Vite manifest not found` | aset belum di-build | `npm run dev` atau `npm run build` |
| Halaman 500 dengan session | tabel `sessions` belum ada | `php artisan migrate` |
| CSS tidak berubah | cache view/config | `php artisan optimize:clear` |
| Aset 404 di hosting | `public/build` belum diunggah | build lokal lalu upload |

## 10. Catatan Windows / Laragon

- Pilih PHP ≥ 8.2 di Laragon (Menu → PHP → Version).
- PHP 8.5 memunculkan deprecation pada beberapa default framework; `config/database.php` sudah disesuaikan agar kompatibel PHP 8.2–8.5.
- Bila `node`/`npm` tidak dikenali, tambahkan folder Node ke `PATH`.
