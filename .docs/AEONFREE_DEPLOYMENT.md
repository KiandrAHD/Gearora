# GEARORA — Deployment AeonFree

## Status

Pipeline membuat artefak dan hanya melakukan FTPS bila repository variable `AEONFREE_DEPLOY_ENABLED` bernilai tepat `true`.

**Status saat ini:** biarkan bernilai `false`. Push ke `main` akan build, memvalidasi paket, lalu mengunggah artefak GitHub Actions. Tidak ada upload ke AeonFree.

Jangan commit `.env`, `APP_KEY`, kredensial database, atau kredensial Midtrans.

## Arsitektur server

```text
AeonFree account/
├── gearora-app/                 # privat; bukan document root
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   │   ├── app/public/          # gambar produk dan media katalog
│   │   ├── framework/
│   │   └── logs/
│   ├── vendor/
│   ├── .env                     # dibuat manual di server
│   └── artisan
└── htdocs/                      # document root
    ├── index.php                # bootstrap ../gearora-app
    ├── .htaccess
    ├── build/                   # Vite manifest dan aset hasil build
    ├── images/
    ├── favicon.ico
    └── robots.txt
```

`htdocs/index.php` memuat `../gearora-app`. Karena `bootstrap/app.php` menentukan base path dari `gearora-app`, Laravel tetap memakai `gearora-app/public` untuk `public_path()` dan manifest Vite tersedia di sana.

Gambar katalog pada `storage/app/public` sengaja tetap di `gearora-app/storage/app/public`. `MediaController` menyajikannya lewat `/media/{path}`. Tidak ada symlink `htdocs/storage`, sehingga gambar pembayaran pada disk `local` dan storage privat tidak terekspos.

## GitHub configuration

### Secrets wajib saat FTPS diaktifkan

| Secret | Nilai |
|---|---|
| `FTP_SERVER` | hostname FTP/FTPS AeonFree, tanpa kredensial lain |
| `FTP_USERNAME` | username FTP |
| `FTP_PASSWORD` | password FTP |

### Repository variable

| Variable | Nilai aman saat fase ini | Fungsi |
|---|---|---|
| `AEONFREE_DEPLOY_ENABLED` | `false` | Menahan dua langkah FTPS. Build dan artefak tetap berjalan. |

Jangan ubah variable tersebut menjadi `true` sebelum checklist first deployment selesai.

## Build pipeline

Workflow `.github/workflows/deploy-aeonfree.yml` memakai PHP 8.4 dan Node.js 22.

1. Membuat `.env` CI sementara dengan `APP_ENV=local`, `APP_DEBUG=true`, APP key acak, SQLite in-memory, session/cache array, dan tanpa kredensial produksi.
2. Menjalankan `composer validate --no-check-publish`.
3. Menjalankan `composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader`.
4. Menjalankan `npm ci` dan `npm run build`.
5. Membuat `deploy/app` untuk aplikasi privat dan `deploy/public` untuk `htdocs`.
6. Memvalidasi manifest Vite, entry point, media produk awal, dan ketiadaan `.env`, `vendor`, `storage`, atau PHP selain `index.php` di paket publik.
7. Mengunggah `deploy/` sebagai artefak yang disimpan tujuh hari.
8. Hanya jika gate bernilai `true`, upload `deploy/app/` ke `/gearora-app/` dan `deploy/public/` ke `/htdocs/` melalui FTPS.

Paket mengecualikan `.git`, `.github`, `.docs`, `.env*`, `node_modules`, `tests`, SQLite lokal, cache/log/sesi runtime lokal, dan Vite `public/hot`. `vendor` hanya masuk `gearora-app`. Aset statis `public/images` dan `storage/app/public` yang terlacak Git tetap masuk paket sesuai jalur penyajian aplikasi.

## `.env` produksi

Buat `/gearora-app/.env` melalui File Manager. Jangan upload dari CI.

```dotenv
APP_NAME="GEARORA"
APP_ENV=production
APP_KEY=base64:GENERATE_UNIQUE_32_BYTE_KEY
APP_DEBUG=false
APP_URL=https://gearora.iceiy.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=HOST_DARI_AEONFREE
DB_PORT=3306
DB_DATABASE=NAMA_DATABASE
DB_USERNAME=NAMA_USER
DB_PASSWORD=PASSWORD_KUAT

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

MIDTRANS_FAKE=false
GEARORA_PAYMENT_GATEWAY_ENABLED=false
```

Tambahkan konfigurasi email, Midtrans, dan bank hanya bila fitur dipakai. Simpan server key Midtrans hanya di `.env` server. Production boot memblokir `APP_DEBUG=true`, `APP_URL` non-HTTPS, APP key kosong, cookie tidak secure, atau `MIDTRANS_FAKE=true`.

## First deployment

1. Pastikan `AEONFREE_DEPLOY_ENABLED=false`.
2. Push pipeline ini ke `main`, buka run GitHub Actions, lalu unduh artefak `gearora-aeonfree-<SHA>`.
3. Dengan File Manager AeonFree, buat folder sibling `/gearora-app` dan pastikan `/htdocs` adalah document root domain `gearora.iceiy.com`.
4. Upload isi artefak `app/` ke `/gearora-app/` dan isi `public/` ke `/htdocs/`, atau gunakan workflow setelah gate diaktifkan.
5. Buat `/gearora-app/.env`, isi konfigurasi produksi, lalu pastikan permission tulis pada `gearora-app/storage` dan `gearora-app/bootstrap/cache`. Mulai dari `755`; gunakan `775` hanya bila user web server berbeda dan kebutuhan tulis terbukti.
6. **Hapus hanya `/htdocs/index.html` halaman default AeonFree** lewat File Manager setelah memastikan nama dan isi file adalah halaman `Website Active!`. Jangan hapus folder atau gunakan clean slate. Workflow FTP memakai `dangerous-clean-slate: false`, sehingga file lama tidak dihapus otomatis. File `index.html` dapat menang atas `index.php` pada urutan DirectoryIndex hosting.
7. Jalankan migrasi terkontrol. Jika terminal tersedia: `php artisan migrate --force`. Jika tidak tersedia, impor skema/migrasi dengan prosedur admin yang telah ditinjau. Jangan membuat route migrasi publik atau akun admin produksi sementara.
8. Bila terminal tersedia, jalankan `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache` setelah `.env` final. Jangan cache konfigurasi sebelum nilai final tersimpan.
9. Smoke test `/`, `/up`, aset Vite, gambar katalog `/media/...`, login, dan halaman penting.
10. Pastikan `/.env`, `/vendor/`, `/storage/`, dan file bukti pembayaran memberi 404/403.
11. Baru setelah checklist selesai dan persetujuan eksplisit tersedia, set variable ke `true` untuk deployment FTPS berikutnya.

## Database dan runtime

Migrasi mencakup tabel session dan cache yang diperlukan oleh konfigurasi contoh. Gunakan backup database sebelum `migrate --force`. Shared hosting memakai `QUEUE_CONNECTION=sync`; jangan mengandalkan worker daemon. Cache dan session dapat memakai database bila tabel migrasi sudah ada.

Jangan menghapus `storage/app/public` saat update. Media unggahan yang muncul setelah rilis pertama adalah data runtime; FTP deployment non-clean hanya menambah/memperbarui file paket dan tidak menggantikan mekanisme backup media. Backup database dan storage privat sebelum perubahan besar.

## Troubleshooting

| Gejala | Periksa / tindakan |
|---|---|
| `Website Active!` tetap tampil | Hapus terbatas `/htdocs/index.html` default setelah verifikasi isi. Pastikan `/htdocs/index.php` artefak ada. |
| Composer package discovery gagal dengan `Unsafe production configuration` | Pastikan langkah `Prepare isolated CI environment` terjadi sebelum Composer dan `APP_ENV=local`. Jangan melemahkan `ProductionReadiness`. |
| HTTP 500 setelah upload | Periksa PHP 8.4, extension, path sibling `../gearora-app`, `vendor/autoload.php`, `.env`, dan write permission `storage` serta `bootstrap/cache`. Periksa log privat `storage/logs/laravel.log`. |
| CSS/JS 404 | Pastikan `htdocs/build/manifest.json` dan `gearora-app/public/build/manifest.json` sama-sama terunggah. Jangan upload `public/hot`. |
| Gambar produk 404 | Pastikan `gearora-app/storage/app/public` ikut terunggah. URL media harus melalui `/media/...`, bukan `/storage/...`. |
| Session/cache SQL error | Jalankan migrasi tabel `sessions` dan `cache`, atau gunakan driver file sementara hanya bila kebijakan operasi mengizinkan. |
| File `.env` terlihat publik | Hentikan deployment. Pastikan `.env` hanya ada di `/gearora-app/.env`, bukan `htdocs`; periksa document root. |

## Rollback

1. Aktifkan maintenance mode bila terminal tersedia.
2. Catat SHA rilis bermasalah dan backup database sebelum rollback data.
3. Ambil artefak build sebelumnya dari GitHub Actions, atau rebuild commit sebelumnya pada branch terpisah.
4. Upload kembali isi aplikasi privat lalu isi publik dari rilis sebelumnya. Jangan hapus storage runtime atau `.env`.
5. Rollback database hanya dengan migrasi yang telah ditinjau dan backup tervalidasi. Jangan menjalankan rollback buta pada data produksi.
6. Clear/cache ulang konfigurasi bila terminal tersedia, lalu smoke test.
7. Nonaktifkan maintenance mode.

## Batasan

Pipeline tidak menjalankan deployment, migrasi, cache production, atau perubahan akun AeonFree saat gate `false`. Tidak ada `dangerous-clean-slate: true`. FTP deployment tidak dapat menjamin rollback atomik pada shared hosting; backup dan artefak rilis menjadi kontrol utama.
