# Technology Stack — GEARORA

> **Product:** GEARORA — Outdoor Supply  
> **Document version:** 1.0  
> **Date:** 16 November 2026  
> **Hosting constraint:** AeonFree free hosting is the mandatory initial target

---

## 1. Technology Decision Summary

GEARORA menggunakan pendekatan **Laravel monolith + Blade**. Stack dipilih agar seluruh alur pelanggan dan Admin Dashboard berada dalam satu codebase, dapat dikembangkan dengan prinsip OOP, serta tidak bergantung pada Node.js atau service backend yang terus berjalan di hosting.

| Layer | Teknologi | Keputusan |
|---|---|---|
| Backend framework | Laravel 12.x atau 13.x berdasarkan hasil validasi PHP hosting | Laravel 12 bila runtime hanya PHP 8.2; Laravel 13 bila PHP 8.3+ tersedia |
| Language | PHP 8.2+ | Versi runtime harus sama/kompatibel dengan versi framework |
| Architecture | MVC + Service Layer, modular monolith | Tanpa microservices |
| Frontend | Blade | Server-side rendering |
| CSS | Tailwind CSS 4.x | Design tokens mengikuti Master Design Homepage |
| UI interaction | Alpine.js 3.x dan JavaScript seperlunya | Dropdown, modal, tabs, confirmation, dan interaksi ringan |
| Database | MySQL 8.0 | Relational database dengan InnoDB dan foreign keys |
| ORM | Eloquent ORM | Query dan relasi model |
| Asset build | Vite + Node.js/npm (lokal) | Hanya untuk proses build di mesin developer |
| Authentication | Laravel authentication/session | Customer dan admin; admin route wajib authorization |
| Authorization | Middleware + Gates/Policies; role/permission tables | RBAC minimum Owner/Admin/Warehouse |
| Testing | PHPUnit + Laravel feature tests | Uji aturan bisnis dan alur transaksi |
| Code quality | Laravel Pint; PHPStan/Larastan opsional | Format konsisten dan deteksi kesalahan |
| Version control | Git + GitHub | Branch, commit, dan release tag |
| Hosting | AeonFree | FTP/cPanel; kemampuan aktual diverifikasi sebelum deploy |

## 2. Framework Version dan Risiko PHP Hosting

### 2.1 Fakta kompatibilitas yang perlu diperhitungkan

Situs publik AeonFree menampilkan PHP 8.2 dan MySQL 8.0 sebagai spesifikasi hosting. Halaman publik tersebut tidak cukup untuk membuktikan bahwa akun pengguna menyediakan PHP 8.3, Composer, SSH, cron, atau semua ekstensi PHP yang dibutuhkan. Versi dan kemampuan di panel akun aktual harus diperiksa sebelum mengunci dependency.

Laravel 12 membutuhkan PHP minimal 8.2. Laravel 13 membutuhkan PHP minimal 8.3. Menurut support policy Laravel yang dipublikasikan saat penyusunan dokumen ini, dukungan security Laravel 12 berakhir pada **24 Februari 2027**, sedangkan Laravel 13 mendapatkan security fixes sampai **17 Maret 2028**.

### 2.2 Keputusan bersyarat

| Kondisi runtime AeonFree | Rekomendasi | Catatan |
|---|---|---|
| PHP 8.3 atau lebih baru tersedia dan ekstensi lengkap | Laravel 13.x | Pilihan utama untuk masa dukungan lebih panjang |
| Hanya PHP 8.2 tersedia | Laravel 12.x untuk MVP/PSAS dengan rencana migrasi | Kompatibel saat ini, tetapi dukungan security berakhir 24 Februari 2027 |
| PHP di bawah 8.2 atau ekstensi wajib tidak tersedia | Jangan deploy Laravel | Hosting belum kompatibel dengan baseline framework |

**Keputusan operasional:** jangan membuat project final sebelum PHP versi aktif, ekstensi, dan struktur document root telah diuji. Jika akun hanya menyediakan PHP 8.2, GEARORA dapat dibangun sebagai MVP Laravel 12, tetapi sebelum 24 Februari 2027 harus ada salah satu jalan keluar: akun hosting menyediakan PHP 8.3+, aplikasi dipindahkan ke hosting yang mendukung PHP 8.3+, atau aplikasi tidak digunakan untuk transaksi produksi yang menyimpan data pelanggan sampai runtime yang didukung tersedia.

## 3. Backend — Laravel + PHP

### Alasan pemilihan

- Laravel menyediakan routing, middleware, validation, authentication, authorization, ORM, migration, session, hashing, dan testing dalam satu framework.
- PHP dapat dijalankan pada hosting PHP shared hosting tanpa membutuhkan runtime Node.js production.
- MVC cocok untuk pemisahan halaman publik, customer area, dan Admin Dashboard.
- Service Layer menjaga aturan availability, pricing, assignment, payment verification, deposit, dan return inspection tetap terpusat.

### Aturan implementasi

1. Gunakan struktur MVC Laravel standar.
2. Controller harus tipis; aturan bisnis ditempatkan di Service/Action.
3. Gunakan Form Request untuk validasi input kompleks.
4. Gunakan Policy/Gate/Middleware untuk otorisasi.
5. Gunakan Enum atau constant terpusat untuk status yang terbatas.
6. Gunakan database transaction untuk pembuatan order, alokasi aset, pembayaran, penyesuaian stok, dan penyelesaian pengembalian.
7. Jangan menambahkan package besar tanpa kebutuhan yang jelas dan kompatibilitas hosting yang teruji.

## 4. Frontend — Blade, Tailwind, Alpine

### Blade

Blade menjadi templating utama untuk Homepage, katalog, detail, customer account, dan Admin Dashboard. Reusable UI dibuat sebagai Blade components/partials, misalnya button, input, badge, modal, table, pagination, status label, dan layout.

### Tailwind CSS

Gunakan Tailwind CSS 4.x sebagai utility CSS, dengan design tokens warna dan typography yang berasal dari dokumen desain GEARORA.

| Token | HEX |
|---|---|
| Forest | `#183D32` |
| Navy | `#08213F` |
| Blue | `#2377C9` |
| Sage | `#A8B99A` |
| Sand | `#E9DDC9` |
| Cloud | `#F7F8F3` |
| Earth | `#80664E` |
| Line | `#DBE0D7` |
| Hero background | `#EDF1E9` |

Typography Homepage: **Poppins** untuk heading/brand/title dan **Inter** untuk body/UI. Accent serif pada kata “alam.” menggunakan Georgia sesuai acuan. Admin Dashboard perlu memakai token yang sama, tetapi layout, navigasi, tabel, dan dashboard cards tetap perlu didesain khusus; dokumen repository yang tersedia belum menjadi spesifikasi visual admin.

### Alpine.js

Gunakan Alpine.js hanya untuk interaksi UI ringan, seperti mobile navigation, dropdown, modal konfirmasi, tabs, dan disclosure. Hindari membangun SPA terpisah untuk MVP.

## 5. Database — MySQL 8.0

Gunakan MySQL 8.0 dan storage engine InnoDB.

Ketentuan database:

- Charset/collation: `utf8mb4` dengan collation yang kompatibel dengan environment MySQL.
- Foreign key untuk relasi penting.
- Index pada foreign key, status, kode transaksi, tanggal rental, SKU, dan kolom pencarian yang terbukti diperlukan.
- Gunakan `DECIMAL(12,2)` atau presisi yang disepakati untuk nominal Rupiah; jangan gunakan floating point untuk transaksi.
- Gunakan transaction dan locking untuk proses pemesanan/alokasi aset.
- Simpan snapshot harga, nama/varian, dan aturan biaya pada item transaksi agar histori tidak berubah saat katalog diperbarui.
- Simpan tanggal dan timestamp dengan kebijakan timezone konsisten; tampilan lokal `Asia/Jakarta`.
- Jangan menghapus data transaksi permanen melalui proses normal.

## 6. Asset dan File Upload

- Build CSS/JS dengan Vite secara lokal.
- Jalankan `npm run build` pada komputer developer sebelum deployment.
- Upload hanya hasil build frontend yang diperlukan.
- Foto produk, bukti pembayaran, dan dokumentasi inspeksi memiliki validasi MIME, ukuran, dan ekstensi.
- Nama file dibuat acak; jangan menggunakan nama asli pengguna sebagai path file.
- Bukti pembayaran dan dokumen privat disimpan di lokasi yang tidak dapat diakses langsung tanpa authorization.
- Optimasi foto katalog (format modern jika kompatibel, ukuran wajar, thumbnail) agar tidak membebani kuota dan kecepatan hosting.

## 7. Development Tools

| Kebutuhan | Tool |
|---|---|
| IDE | Visual Studio Code |
| PHP runtime lokal | PHP versi yang cocok dengan target hosting |
| Dependency PHP | Composer |
| JS toolchain | Node.js LTS + npm |
| Local server/database | PHP built-in/Laravel Herd/XAMPP sesuai lingkungan, MySQL 8 untuk parity yang lebih baik |
| API/database inspection | phpMyAdmin lokal atau DB client |
| Version control | Git + GitHub |
| UI reference | Figma / Master Design Reference repository |
| Browser testing | Chrome/Edge responsive tools |

Versi dependency harus dikunci di `composer.lock` dan `package-lock.json`. Jangan menggunakan `latest` tanpa lockfile.

## 8. Testing dan Quality Gates

- PHPUnit: unit tests untuk pricing, date overlap, status transition, dan permission.
- Laravel feature tests: autentikasi, katalog, CRUD admin, order, verifikasi pembayaran, pickup/return, dan pembatasan akses.
- Manual test: ukuran desktop/mobile, upload file, session timeout, pagination, empty state, error state, dan deployment smoke test.
- Pint: format PHP.
- Larastan/PHPStan: opsional untuk static analysis jika cocok dengan versi PHP dan dependency project.
- GitHub: pull request/review atau checklist sebelum merge, walaupun dikerjakan satu orang.

## 9. Hosting AeonFree — Deployment Contract

Situs AeonFree yang diperiksa menampilkan:

- PHP 8.2.
- MySQL 8.0.
- 5 GB disk space.
- 1 FTP account.
- cPanel.
- `.htaccess`.
- SSL dan Cloudflare-related services.

Tetapi, hal berikut **belum terverifikasi pada akun aktual**:

- Pemilihan versi PHP 8.3+.
- Ekstensi PHP lengkap untuk Laravel.
- SSH/Terminal dan Composer.
- Cron Jobs.
- Hak tulis `storage` dan `bootstrap/cache`.
- Kemampuan menempatkan source aplikasi di luar `public_html` atau mengubah document root.
- Batas CPU, RAM, inode, ukuran upload, dan batas database aktual.

### Deployment strategy

1. Install dependency dengan Composer pada komputer lokal.
2. Build frontend dengan Vite pada komputer lokal.
3. Jalankan test dan build verification.
4. Buat backup database/file bila melakukan update.
5. Upload file aplikasi menggunakan FTP/File Manager sesuai layout private/public yang telah diverifikasi.
6. Atur `.env` untuk production tanpa membagikan file tersebut.
7. Pastikan `APP_DEBUG=false`, HTTPS aktif, permission direktori aman, dan route cache/config cache dijalankan secara lokal hanya bila aman untuk environment target.
8. Lakukan smoke test halaman publik, login admin, katalog, database, upload, rental order, dan proteksi file privat.

Jangan menjalankan deployment jika source aplikasi, `.env`, atau `vendor` dapat diakses sebagai file publik. Jangan mengandalkan Composer/Node/SSH tersedia sebelum dibuktikan.

## 10. Dependency yang Tidak Direkomendasikan untuk MVP

- Redis, queue worker permanen, Horizon, Reverb/WebSocket server.
- Docker di hosting shared.
- Node.js runtime untuk melayani halaman production.
- Payment gateway sebelum kebijakan refund, deposit, dan settlement ditetapkan.
- AI API untuk rekomendasi awal; rule-based recommendation lebih mudah diuji.
- Banyak package UI/admin panel yang memaksakan dependency dan struktur tambahan.

## 11. Alternatif jika Hosting Tidak Memenuhi Syarat

Urutan mitigasi:

1. Periksa pengaturan PHP dan extension pada akun aktif AeonFree.
2. Tanyakan/cek dokumentasi resmi apakah PHP 8.3+ dan struktur private directory dapat digunakan.
3. Jika PHP 8.3 tersedia, gunakan Laravel 13.
4. Jika hanya PHP 8.2 tersedia, gunakan Laravel 12 untuk tujuan MVP/PSAS dengan batas waktu dukungan yang dicatat dan rencana upgrade.
5. Jika document root atau extension tidak memadai, jangan mengorbankan keamanan; pertahankan aplikasi di environment lokal/staging sampai tersedia hosting kompatibel.

## 12. Referensi Teknis

- [AeonFree — Official Website](https://aeonfree.com/)
- [Laravel 12 — Deployment Requirements](https://laravel.com/docs/12.x/deployment)
- [Laravel 12 — Release Notes and Support Policy](https://laravel.com/docs/12.x/releases)
- [Laravel 13 — Deployment Requirements](https://laravel.com/docs/13.x/deployment)
- [Laravel 13 — Release Notes and Support Policy](https://laravel.com/docs/13.x/releases)
- [GEARORA Homepage Design Reference](https://github.com/KiandrAHD/Gearora/blob/main/.docs/Desain_Gearora.md)
