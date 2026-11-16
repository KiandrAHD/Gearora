# Phase 0 — Technical Decision Record (TDR) — GEARORA

> **Tanggal:** 16 November 2026
> **Status:** keputusan yang bergantung hosting diberi status **PENDING** sampai akun diverifikasi.
> **Sumber prioritas:** instruksi pengguna > proposal `.Gearora/GEARORA.md` > PRD > Architecture > Tech Stack > Roadmap > Desain > validasi hosting > dokumentasi resmi teknologi.

Legenda status keputusan: **ACCEPTED** (final untuk Phase 1), **PENDING** (menunggu verifikasi), **PROVISIONAL** (dipakai untuk Phase 1, dapat berubah).

---

## TDR-01 — Versi Laravel

- **Context:** Hosting AeonFree hanya mempublikasikan PHP 8.2 (beranda) / 7.4 (halaman lain). Versi PHP akun belum terverifikasi. Laravel 12 minimum PHP 8.2; Laravel 13 minimum PHP 8.3.
- **Options:** (a) Laravel 12.x; (b) Laravel 13.x; (c) menunggu verifikasi penuh.
- **Selected Decision:** **Laravel 12.x (terpasang: 12.72.0)**.
- **Reason:** Kompatibel dengan runtime terendah yang kemungkinan besar tersedia (PHP 8.2) sekaligus berjalan pada PHP lokal 8.5.9. Laravel 13 akan **tidak dapat** berjalan bila hosting hanya PHP 8.2. Security support Laravel 12 sampai **24 Februari 2027**.
- **Trade-offs:** masa dukungan lebih pendek daripada Laravel 13. Bila hosting terbukti PHP 8.3+, dapat ditingkatkan ke Laravel 13 (dampak upgrade: rendah untuk fondasi, tinggi bila sudah ada banyak kode bisnis).
- **Status:** **PROVISIONAL** — dinaikkan ke ACCEPTED bila akun dikonfirmasi ≥ PHP 8.2, atau ditinjau ulang bila < 8.2.

## TDR-02 — Versi PHP

- **Context:** PHP lokal 8.5.9; hosting publik 8.2/7.4 (konflik). Laravel 12 butuh ≥ 8.2.
- **Options:** (a) target PHP 8.2, (b) target PHP 8.3+, (c) menunggu.
- **Selected Decision:** target produksi **PHP 8.2+**; development lokal **8.5.9**.
- **Reason:** 8.2 = batas bawah yang dipublikasikan; kode harus jalan di 8.2 agar aman.
- **Trade-offs:** PHP 8.5 lokal memunculkan deprecation pada beberapa default Laravel (mis. `PDO::MYSQL_ATTR_SSL_CA`); tidak memengaruhi produksi 8.2.
- **Status:** **PROVISIONAL** (blocked: verifikasi akun).

## TDR-03 — Versi MySQL

- **Context:** publik 8.0 vs 5.7 (konflik).
- **Options:** (a) MySQL 8.0, (b) MySQL 5.7, (c) menunggu.
- **Selected Decision:** target **MySQL 8.0**, InnoDB; charset `utf8mb4`.
- **Reason:** sesuai PRD/Tech Stack dan fitur yang dibutuhkan (foreign key, index, transaksi). MySQL 5.7 masih mendukung `utf8mb4`, tetapi 8.0 jadi baseline dokumen.
- **Trade-offs:** jika akun benar 5.7, sebagian sintaks/opsi 8.0 harus dihindari (tidak relevan untuk Phase 1 — belum ada tabel bisnis).
- **Status:** **PENDING** (blocked: verifikasi akun).

## TDR-04 — Strategi Frontend

- **Options:** (a) Blade SSR, (b) SPA React/Vue, (c) hybrid.
- **Selected Decision:** **Blade server-rendered**.
- **Reason:** cocok shared hosting, tanpa Node runtime di server, aligns dengan PRD/Architecture.
- **Trade-offs:** interaksi kompleks dibatasi, ditangani Alpine.js/JS kecil.
- **Status:** **ACCEPTED**.

## TDR-05 — CSS Framework

- **Options:** (a) Tailwind CSS 4, (b) Bootstrap, (c) CSS kustom.
- **Selected Decision:** **Tailwind CSS 4** (via `@tailwindcss/vite`).
- **Reason:** sesuai Tech Stack; design token GEARORA dipetakan ke `@theme`.
- **Trade-offs:** perlu proses build Vite (dijalankan lokal).
- **Status:** **ACCEPTED**.

## TDR-06 — Strategi JavaScript

- **Options:** (a) Alpine.js + JS minimal, (b) framework SPA, (c) tanpa JS.
- **Selected Decision:** **Alpine.js 3.x + JS minimal** untuk interaksi ringan (menu, modal, dropdown).
- **Reason:** sesuai PRD/Tech Stack; tidak membangun SPA untuk MVP.
- **Trade-offs:** state kompleks tetap di server via full-page requests.
- **Status:** **ACCEPTED**.

## TDR-07 — Arsitektur Aplikasi

- **Options:** (a) modular monolith MVC + Service Layer, (b) microservices, (c) layered tanpa service.
- **Selected Decision:** **Modular monolith, MVC + Service Layer**; controller tipis, aturan bisnis di Services.
- **Reason:** sesuai Architecture §2 & §5; sederhana, testable.
- **Trade-offs:** service perlu disiplin agar tidak jadi "god class".
- **Status:** **ACCEPTED**.

## TDR-08 — Strategi Deployment

- **Context:** shared hosting; document root belum terverifikasi.
- **Options:** (a) source di luar `public_html` + `public_html` hanya file publik, (b) seluruh project di dalam `public_html`.
- **Selected Decision:** **target (a)**. Fallback (dengan `index.php` yang menunjuk ke `../gearora-app/bootstrap/app.php`) **hanya** jika (a) tidak mungkin **dan** terbukti aman. Opsi (b) **ditolak** (paparan `.env`).
- **Reason:** keamanan source/rahasia lebih penting daripada kemudahan.
- **Trade-offs:** setup `index.php`/document root tambahan; jika tidak aman → deployment ditahan.
- **Status:** **PENDING** (blocked: verifikasi document root).

## TDR-09 — Manajemen Aset

- **Options:** (a) Vite lokal, upload `public/build`; (b) build di server.
- **Selected Decision:** **Vite lokal**; hanya artefak `public/build/` yang diunggah. `node_modules` tidak diunggah.
- **Reason:** Node.js tidak diasumsikan tersedia di hosting.
- **Trade-offs:** perlu disiplin menjalankan `npm run build` sebelum deploy.
- **Status:** **ACCEPTED**.

## TDR-10 — Strategi Testing

- **Options:** (a) PHPUnit + sqlite in-memory untuk test; (b) Pest; (c) test di MySQL.
- **Selected Decision:** **PHPUnit 11 + Laravel feature/unit tests**; database testing = **sqlite `:memory:`**, terpisah dari DB dev/produksi.
- **Reason:** cepat, terisolasi, tidak menyentuh DB produksi; sesuai Tech Stack (PHPUnit).
- **Trade-offs:** tidak menguji parity MySQL secara langsung (ditambah test integrasi manual/di server bila diperlukan).
- **Status:** **ACCEPTED**.

## TDR-11 — Cache, Session, Queue (keterbatasan shared hosting)

- **Options:** (a) database driver; (b) Redis; (c) file.
- **Selected Decision:** Session = `database`, Cache = `database`, Queue = **`sync`**.
- **Reason:** Redis tidak diasumsikan tersedia; queue worker persisten tidak tersedia → default `sync` menghindari ketergantungan worker. Tabel `sessions` & `cache` tersedia dari migration bawaan.
- **Trade-offs:** cache/session menambah beban DB; dapat dipindah ke `file` bila perlu.
- **Status:** **PROVISIONAL** (sesuai hasil verifikasi akun).

## TDR-12 — Manajemen Dependency PHP

- **Options:** (a) `composer install` di server; (b) build `vendor/` lokal lalu unggah.
- **Selected Decision:** **build lokal** (`composer install --no-dev --optimize-autoloader`) → unggah `vendor/`. `composer.lock` dikunci di repo.
- **Reason:** Composer di hosting tidak diasumsikan tersedia.
- **Trade-offs:** ukuran unggahan besar, jumlah file banyak (perhatikan inode).
- **Status:** **PENDING** (blocked: verifikasi Composer/SSH).

## TDR-13 — Bahasa, Timezone, Format

- **Selected Decision:** locale aplikasi **`id`** (fallback `en`), timezone **`Asia/Jakarta`**, format mata uang **Rupiah** (ditunda ke fase pembuatan fitur), faker locale `id_ID`.
- **Reason:** sesuai PRD NFR (Usability).
- **Status:** **ACCEPTED**.

## TDR-14 — Kontrol Akses & Keamanan Baseline

- **Selected Decision:** CSRF aktif, session aman, output escaping Blade default, `APP_DEBUG=false` di produksi, mass-assignment allowlist, rate limit login (fase auth), secrets hanya di `.env` (di-ignore Git).
- **Reason:** NFR Security + Architecture §12.
- **Status:** **ACCEPTED** (implementasi RBAC detail = Phase 2).

---

## Ringkasan Keputusan Pending/Provisional

| TDR | Status | Blocker |
|---|---|---|
| TDR-01 Laravel version | PROVISIONAL | Verifikasi PHP akun (≥ 8.2) |
| TDR-02 PHP version | PROVISIONAL | Verifikasi PHP akun |
| TDR-03 MySQL version | PENDING | Verifikasi MySQL akun |
| TDR-08 Deployment | PENDING | Verifikasi document root |
| TDR-11 Cache/Session/Queue | PROVISIONAL | Verifikasi ekstensi/DB/RAM akun |
| TDR-12 Dependency | PENDING | Verifikasi Composer/SSH |
