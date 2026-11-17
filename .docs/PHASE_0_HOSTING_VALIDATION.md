# Phase 0 — Hosting Validation (AeonFree) — GEARORA

> **Tanggal audit awal:** 16 November 2026
> **Tanggal verifikasi ulang publik:** 17 November 2026
> **Sumber:** situs publik AeonFree + repository lokal.
> **Aturan status:** setiap item diberi label bukti. Tidak ada asumsi yang ditulis sebagai fakta.

---

## 1. Legenda Status

| Status | Arti |
|---|---|
| VERIFIED | Didukung bukti pemeriksaan aktual (akun/panel terperiksa). |
| PUBLICLY DOCUMENTED | Tercantum di dokumentasi/marketing resmi provider. |
| NOT AVAILABLE | Terbukti tidak tersedia. |
| NOT VERIFIED | Belum dapat dipastikan. |
| BLOCKED | Pemeriksaan butuh akses akun/tindakan pengguna. |

**Penting:** seluruh item yang memerlukan login akun AeonFree berstatus **BLOCKED** pada audit ini karena kredensial panel tidak tersedia dan memang tidak diminta.

---

## 2. Spesifikasi Publik yang Ditemukan

Diambil langsung dari situs AeonFree (16 Nov 2026).

### 2.1 Beranda `https://aeonfree.com/`

- "Our Free PHP Web Hosting Features": Unlimited Bandwidth, **1 FTP Account**, **400 MySQL Databases**, **5 GB Disk Space**, **PHP 8.2**, **MySQL 8.0**, Supports full `.htaccess`, Free subdomain names, Free SSL, Free Cloudflare CDN, Free DNS.
- cPanel (free hosting control panel).
- Softaculous one-click installer (WordPress, Flarum, Joomla, Drupal).
- Powered by iFastNet.

### 2.2 Halaman `https://aeonfree.com/free-php-mysql-hosting`

- Fitur: Unlimited Disk Space, Unlimited Bandwidth, 1 FTP Account, 400 MySQL Databases, **PHP 7.4**, **MySQL 5.7**, full `.htaccess`, free subdomain, free SSL, Cloudflare CDN, free DNS.

### 2.3 Konflik Terverifikasi

| Item | Beranda | /free-php-mysql-hosting |
|---|---|---|
| PHP | 8.2 | 7.4 |
| MySQL | 8.0 | 5.7 |
| Disk | 5 GB | Unlimited |

**Kesimpulan:** spesifikasi publik tidak konsisten. Keduanya hanya **PUBLICLY DOCUMENTED**, bukan bukti runtime akun. Versi PHP/MySQL **harus** diverifikasi di cPanel akun sebelum mengunci versi framework.

### 2.4 Verifikasi Ulang Publik — 17 November 2026

Pemeriksaan ulang langsung pada dua halaman publik dilakukan untuk memastikan konflik bukan informasi kedaluwarsa:

| Halaman | PHP | MySQL | Disk |
|---|---|---|---|
| `https://aeonfree.com/` | 8.2 | 8.0 | 5 GB |
| `https://aeonfree.com/free-php-mysql-hosting` | 7.4 | 5.7 | Unlimited |

**Hasil:** konflik **masih ada** pada 17 November 2026. Tidak ada bukti baru yang dapat menaikkan status item akun dari **BLOCKED** menjadi **VERIFIED**. Verifikasi akun tetap wajib dan hanya dapat dilakukan pemilik akun melalui cPanel.

### 2.5 Keputusan Go/No-Go Deployment

| Temuan runtime | Keputusan |
|---|---|
| PHP ≥ 8.2 + ekstensi wajib lengkap + document root aman | **GO** — deployment boleh dilanjutkan |
| PHP 8.2 tetapi document root tidak dapat diamankan | **NO-GO** — cari hosting lain atau tahan deployment |
| PHP < 8.2 | **NO-GO** — Laravel 12 tidak dapat berjalan; jangan turunkan framework |
| Ekstensi wajib tidak lengkap dan tidak dapat diaktifkan | **NO-GO** — ganti hosting |

Keputusan versi Laravel sudah final untuk pengembangan (Laravel 12.72.0, minimum PHP 8.2). Hosting yang tidak memenuhi syarat **diganti**, bukan framework yang diturunkan.

---

## 3. Checklist Validasi Hosting

| Item | Yang diperiksa | Status | Bukti / Catatan |
|---|---|---|---|
| PHP Version | Versi PHP aktif di akun | BLOCKED | Publik: 8.2 (beranda) vs 7.4 (halaman PHP/MySQL). Perlu cek cPanel → Select PHP Version. |
| PHP Extensions | Ekstensi wajib Laravel (`pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `ctype`, `curl`, `dom`, `xml`, `tokenizer`) | BLOCKED | Tidak ada daftar ekstensi publik. Jalankan `aeonfree-diagnostic.php`. |
| MySQL | Versi database | BLOCKED | Publik: 8.0 vs 5.7. Perlu cek cPanel → MySQL Databases. |
| phpMyAdmin | Ketersediaan | PUBLICLY DOCUMENTED | Termasuk tumpukan cPanel standar; belum diverifikasi pada akun. |
| Composer | Ketersediaan | NOT VERIFIED | Tidak diklaim publik. **Jangan diasumsikan.** Rencana: dependency di-build lokal. |
| SSH | Ketersediaan | NOT VERIFIED | Tidak diklaim publik. **Jangan diasumsikan.** |
| Terminal (cPanel) | Ketersediaan | NOT VERIFIED | Tidak diklaim publik. |
| File Manager | Ketersediaan | PUBLICLY DOCUMENTED | Termasuk tumpukan cPanel standar; belum diverifikasi pada akun. |
| FTP | Ketersediaan | PUBLICLY DOCUMENTED | 1 FTP Account (beranda). Belum diverifikasi pada akun. |
| `public_html` / document root | Struktur & kemungkinan ubah root | NOT VERIFIED | Tidak ada info publik. Krusial untuk keamanan (lihat §5). |
| `.htaccess` | Dukungan rewrite | PUBLICLY DOCUMENTED | "Supports full .htaccess". Belum diuji. |
| Symlink | Ketersediaan & izin | NOT VERIFIED | Tidak diklaim publik. Opsi fallback keamanan. |
| Storage writable | `storage/` & `bootstrap/cache/` writable | BLOCKED | Perlu uji tulis pada akun. |
| Cron Jobs | Ketersediaan | NOT VERIFIED | Tidak diklaim publik. Sistem inti dirancang **tanpa** ketergantungan cron. |
| SSL | HTTPS tersedia | PUBLICLY DOCUMENTED | Free SSL + Cloudflare CDN. Belum diverifikasi per akun. |
| Batas CPU/RAM/inode/upload | Kuota aktual | NOT VERIFIED | Tidak dipublikasikan. |

---

## 4. Cara Memverifikasi (Checklist untuk Pengguna)

**Jangan pernah** mengirim password/token hosting ke siapa pun, termasuk ke agen. Cukup jalankan langkah berikut dan laporkan **hasilnya** (bukan kredensialnya).

1. Login ke cPanel AeonFree (`http://web.aeonfree.com/login`).
2. **PHP Version:** cari "Select PHP Version" / "MultiPHP Manager". Catat versi aktif.
3. **Extensions:** pada halaman Select PHP Version, buka tab Extensions. Pastikan aktif: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `ctype`, `curl`, `dom`, `xml`, `tokenizer`, `json`, `bcmath`, `session`.
4. **MySQL:** cPanel → MySQL® Databases. Catat versi server (biasanya di sisi kanan atas). Buat database + user, hubungkan user ke database dengan ALL PRIVILEGES.
5. **Checks lain:** cari keberadaan ikon Terminal, Cron Jobs, File Manager, FTP Accounts, Domains.
6. **Document root:** cPanel → Domains. Periksa apakah document root subdomain/domain dapat diubah ke folder selain `public_html`.
7. **Upload script diagnostik** (§4.1) ke `public_html/`, buka di browser, salin hasilnya, lalu **hapus** script.
8. **Permission:** melalui File Manager, pastikan tulis folder dapat dibuat, atau jalankan diagnostik §4.1 yang mengecek `storage` dan `bootstrap/cache`.

### 4.1 Script Diagnostik Aman

Tersedia di `.docs/hosting/aeonfree-diagnostic.php`.

Aturan script:
- Hanya menampilkan versi PHP, ekstensi, versi MySQL server, kapabilitas server (rewrite, tulis direktori).
- **Tidak** menampilkan environment variable, isi `.env`, password database, path rahasia, atau server secret.
- Wajib **dihapus** setelah selesai validasi.

---

## 5. Laravel Deployment Compatibility

### 5.1 Struktur target (diutamakan)

```text
hosting-account/
├── gearora-app/            # source Laravel (DI LUAR document root)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── artisan
│
└── public_html/            # document root publik
    ├── index.php           # menunjuk ke ../gearora-app/bootstrap/app.php
    ├── .htaccess
    ├── build/              # hasil Vite
    └── assets/
```

### 5.2 Skenario & keputusan

| Skenario | Kemungkinan pada AeonFree | Keputusan |
|---|---|---|
| Document root dapat diubah ke `public/` (atau source di luar `public_html`) | NOT VERIFIED | **Target utama.** Aman: `.env`, `app/`, `vendor/` tidak terekspos. |
| Document root tetap `public_html`, source tidak dapat dipindah | NOT VERIFIED | **Fallback aman:** taruh source di `gearora-app/` (di luar `public_html`) dan taruh hanya `index.php` + `.htaccess` + `build/` di `public_html`, dengan `index.php` meng-`require` bootstrap aplikasi dari `../gearora-app/`. |
| Hosting memaksa seluruh source di dalam web root tanpa proteksi apa pun | Belum diketahui | **Hentikan deployment.** Jangan menaruh seluruh project Laravel di dalam `public_html`. |

### 5.3 Risiko yang terdokumentasi

- **Paparan `.env`/source:** bila seluruh project diletakkan di `public_html` tanpa proteksi → kredensial DB dan rahasia aplikasi dapat diunduh. **Tidak dapat diterima.**
- **Tanpa SSH/Composer:** dependency harus di-install di lokal, `vendor/` diunggah manual (berat, rawan versi).
- **Tanpa Node di server:** `npm run build` dijalankan lokal; hanya `public/build/` yang diunggah.
- **PHP 7.4 jika benar:** Laravel 12 (min PHP 8.2) **tidak dapat** berjalan → deployment diblokir, development tetap di lokal.
- **Batasan kuota (5 GB / unlimited, inode):** `vendor/` + `node_modules` tidak boleh diunggah; hanya artefak yang diperlukan.

### 5.4 Larangan pada fase ini

- **Tidak** melakukan deployment nyata.
- **Tidak** meminta kredensial hosting.
- **Tidak** mengekspos `.env`, konfigurasi DB, source backend, atau dependency ke web publik.

---

## 6. Ringkasan Blocker Phase 0

Semua blocker berasal dari **kebutuhan akses akun AeonFree**:

1. Versi PHP & MySQL aktual (untuk mengunci versi Laravel).
2. Daftar ekstensi PHP.
3. Ketersediaan SSH/Composer/Terminal/cron/symlink.
4. Kemampuan mengatur document root / menempatkan source di luar `public_html`.
5. Hak tulis `storage/` dan `bootstrap/cache/`.

Tindak lanjut: pengguna menjalankan §4 dan melaporkan hasil → update dokumen ini dengan status **VERIFIED**.
