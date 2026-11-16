# Product & Development Roadmap — GEARORA

> **Product:** GEARORA — Outdoor Supply  
> **Document version:** 1.0  
> **Date:** 16 November 2026  
> **Planning style:** Phased delivery with dependencies and exit criteria

---

## 1. Roadmap Strategy

Roadmap GEARORA disusun berdasarkan urutan risiko dan ketergantungan sistem. Prioritas pertama bukan menambah sebanyak mungkin halaman, melainkan memastikan katalog, inventaris, reservasi, pembayaran, dan pengembalian bekerja dalam satu alur yang benar.

Prinsip delivery:

1. Validasi hosting dan spesifikasi runtime sebelum mengunci dependency.
2. Bangun fondasi database, autentikasi, dan otorisasi sebelum Admin Dashboard diperluas.
3. Selesaikan katalog dan pencatatan stok sebelum reservation engine.
4. Selesaikan order dan inventory consistency sebelum laporan/analytics.
5. Tambahkan rekomendasi trip, panduan, dan retail setelah alur rental inti stabil.
6. Sisihkan satu fase khusus untuk hardening, pengujian, deployment, dan dokumentasi.

## 2. Milestone Utama

| Milestone | Hasil yang harus tersedia |
|---|---|
| M0 — Validated Foundation | Spesifikasi final, hosting feasibility, local development environment, repository structure |
| M1 — App Foundation | Laravel berjalan, database terkoneksi, layout dasar dan route siap |
| M2 — Secure Admin | Login, session, role, permission, dan proteksi route admin |
| M3 — Catalog & Inventory | Kategori, produk, varian, unit rental, stok jual, dan stock movement |
| M4 — Rental MVP | Availability, order, perhitungan biaya, pembayaran manual, dan status order |
| M5 — Rental Operations | Pickup, return, inspection, deposit, biaya tambahan, dan laporan dasar |
| M6 — Customer Value Features | Trip recommendation dan equipment guides |
| M7 — Retail + Reporting | Sales order, dashboard/report yang lebih lengkap, ekspor CSV |
| M8 — Release Ready | Security, QA, backup, deployment smoke test, dan dokumentasi pengguna |

## 3. Phase 0 — Requirement Freeze & Hosting Validation

**Tujuan:** memastikan kebutuhan utama dan kemampuan hosting tidak saling bertentangan.

### Tasks

- [ ] Setujui PRD, arsitektur, Tech Stack, dan Roadmap sebagai baseline.
- [ ] Tetapkan kebijakan rental: tanggal/jam, durasi, pickup, return, pembatalan, keterlambatan, deposit, dan metode pembayaran.
- [ ] Tetapkan apakah GEARORA hanya memiliki satu lokasi usaha pada MVP.
- [ ] Buka cPanel AeonFree dan cek versi PHP aktual.
- [ ] Cek ekstensi PHP wajib Laravel dan `pdo_mysql`.
- [ ] Cek MySQL version dan pembuatan database/user.
- [ ] Cek apakah SSH/Terminal, Composer, cron, File Manager, dan phpMyAdmin tersedia.
- [ ] Cek kemampuan menempatkan source Laravel di luar `public_html`.
- [ ] Cek permission `storage` dan `bootstrap/cache`.
- [ ] Siapkan PHP, Composer, Node, dan MySQL lokal yang kompatibel.
- [ ] Tentukan versi Laravel sesuai PHP runtime yang benar-benar tersedia.

### Deliverables

- Hosting validation checklist yang terisi.
- Keputusan final versi PHP/Laravel.
- Daftar aturan bisnis yang disetujui.
- Struktur repository dan environment development.

### Exit Criteria

Tidak ada blocker penting terkait versi PHP, ekstensi, document root, koneksi database, atau permission. Jika hosting gagal pada syarat keamanan/kompatibilitas, lanjutkan development lokal tetapi jangan deploy ke hosting tersebut.

## 4. Phase 1 — Project Foundation

**Tujuan:** membuat kerangka aplikasi dan pola kerja konsisten.

### Tasks

- [ ] Inisialisasi Laravel sesuai versi yang lolos Phase 0.
- [ ] Konfigurasi `.env` lokal dan koneksi MySQL.
- [ ] Siapkan Git repository, `.gitignore`, dan branch workflow.
- [ ] Konfigurasi Vite dan Tailwind CSS.
- [ ] Buat design tokens berdasarkan `Desain_Gearora.md`.
- [ ] Buat Blade layout public, customer, dan admin.
- [ ] Buat komponen dasar: button, input, badge, card, table, modal, alert, pagination.
- [ ] Atur locale Indonesia, format Rupiah, dan timezone `Asia/Jakarta`.
- [ ] Buat error pages dan empty states awal.
- [ ] Tambahkan smoke test bahwa aplikasi berjalan dan database terkoneksi.

### Deliverables

- Laravel app dapat dijalankan lokal.
- Layout public dan admin skeleton.
- Design token dan komponen dasar.
- Database connection serta migration awal.

### Exit Criteria

Aplikasi dapat start tanpa error, CSS/JS hasil build muncul, migrasi dapat dijalankan ulang pada database development, dan struktur layout konsisten.

## 5. Phase 2 — Database Foundation, Authentication & RBAC

**Tujuan:** membangun identity dan kontrol akses sebelum modul administrasi.

### Tasks

- [ ] Finalisasi ERD dan migration inti.
- [ ] Buat model `User`, `Role`, dan `Permission`.
- [ ] Implementasikan registrasi/login pelanggan.
- [ ] Implementasikan login admin melalui route terpisah atau guard/policy yang jelas.
- [ ] Buat middleware autentikasi admin.
- [ ] Implementasikan RBAC minimum: Owner, Admin, Warehouse Staff, Customer.
- [ ] Implementasikan Policy/Gate untuk setiap aksi sensitif.
- [ ] Pastikan customer hanya dapat melihat pesanan miliknya.
- [ ] Tambahkan rate limit login dan validasi password.
- [ ] Buat audit log untuk perubahan akses dan aksi admin yang penting.

### Deliverables

- Authentication berjalan.
- Role/permission matrix diimplementasikan.
- Admin route terlindungi.
- Tests untuk akses yang diizinkan dan ditolak.

### Exit Criteria

Pengguna tanpa login tidak dapat mengakses halaman privat; customer tidak dapat membuka data customer lain; petugas gudang tidak dapat melakukan verifikasi pembayaran/ubah permission tanpa izin.

## 6. Phase 3 — Catalog & Inventory Management

**Tujuan:** menyediakan data produk yang valid dan stok yang dapat dipercaya.

### Tasks

- [ ] CRUD kategori.
- [ ] CRUD produk dan foto.
- [ ] Tipe produk: rental, sale, atau both.
- [ ] CRUD product variants (ukuran, kapasitas, warna, SKU).
- [ ] Pencatatan stok produk jual.
- [ ] Pencatatan unit fisik rental (`rental_assets`).
- [ ] Status unit: available, reserved, rented, maintenance, damaged, lost.
- [ ] Stock movement untuk barang masuk, penyesuaian, kerusakan, dan kehilangan.
- [ ] Validasi upload foto dan optimasi gambar.
- [ ] Katalog publik: pencarian, kategori, filter tipe layanan, pagination.
- [ ] Detail produk menampilkan spesifikasi, harga, kondisi, dan varian.

### Deliverables

- Admin dapat mengelola katalog dan inventaris.
- Customer dapat melihat katalog dan detail produk.
- Riwayat perubahan stok tersedia.

### Exit Criteria

Setiap produk memiliki tipe dan data harga yang valid; unit rental dapat dilacak; stok jual dan rental tidak tercampur; produk nonaktif tidak dapat dipesan.

## 7. Phase 4 — Rental Availability & Order MVP

**Tujuan:** menyelesaikan alur inti pemesanan rental.

### Tasks

- [ ] Implementasikan form tanggal mulai dan pengembalian.
- [ ] Finalisasi aturan durasi dan overlap periode.
- [ ] Implementasikan `RentalAvailabilityService`.
- [ ] Implementasikan perhitungan harga dengan snapshot saat order dibuat.
- [ ] Implementasikan cart/booking summary.
- [ ] Implementasikan pembuatan rental order dan kode transaksi unik.
- [ ] Tambahkan database transaction dan row locking untuk mencegah double allocation.
- [ ] Buat status order dan validasi transisi.
- [ ] Buat halaman riwayat/detail order pelanggan.
- [ ] Buat halaman daftar/detail order pada Admin Dashboard.
- [ ] Tambahkan test untuk tanggal beririsan, stok habis, quantity, dan order bersamaan.

### Deliverables

- Customer dapat membuat rental order.
- Sistem menolak booking yang tidak tersedia.
- Admin dapat meninjau pesanan dan statusnya.

### Exit Criteria

Seluruh test availability lulus, order menyimpan snapshot harga, dan tidak ada satu aset fisik yang dapat dialokasikan ke dua periode rental aktif yang beririsan.

## 8. Phase 5 — Payments & Rental Operations

**Tujuan:** memastikan proses bisnis setelah pemesanan dapat dijalankan.

### Tasks

- [ ] Implementasikan instruksi pembayaran manual.
- [ ] Upload bukti pembayaran yang aman.
- [ ] Buat verifikasi pembayaran admin: verified/rejected + catatan.
- [ ] Buat deposit record terpisah dari revenue.
- [ ] Buat pencatatan pickup/handover dan petugas.
- [ ] Implementasikan asset assignment untuk unit yang diserahkan.
- [ ] Buat form inspeksi awal dan akhir.
- [ ] Implementasikan return management dan due/overdue listing.
- [ ] Buat additional charges dengan alasan, nominal, dan persetujuan admin.
- [ ] Implementasikan penyelesaian/pengembalian deposit.
- [ ] Buat dashboard operasional: rental aktif, due today, overdue, barang perawatan.
- [ ] Buat laporan rental dasar.

### Deliverables

- Alur rental dari pembayaran hingga kembali selesai.
- Admin dapat memproses dan mengaudit serah-terima/pengembalian.
- Deposit dan biaya tambahan memiliki riwayat.

### Exit Criteria

Order hanya selesai setelah proses return ditangani; unit rusak tidak kembali tersedia; pembayaran dan deposit tidak tercampur; setiap perubahan penting memiliki audit trail.

## 9. Phase 6 — Trip Planner & Equipment Guides

**Tujuan:** menghadirkan nilai edukasi dan persiapan perjalanan yang membedakan GEARORA dari katalog rental biasa.

### Tasks

- [ ] Buat input trip planner: aktivitas, tujuan, durasi, peserta, kebutuhan khusus.
- [ ] Definisikan rule set rekomendasi berdasarkan kategori/varian produk.
- [ ] Buat service rekomendasi deterministik dan dapat diuji.
- [ ] Tampilkan rekomendasi beserta alasan/label kategori.
- [ ] Buat CRUD equipment guides untuk admin/editor.
- [ ] Buat kategori panduan, slug, status draft/published, dan metadata sumber.
- [ ] Buat halaman panduan publik.
- [ ] Tambahkan disclaimer bahwa daftar rekomendasi bukan jaminan keselamatan perjalanan.

### Deliverables

- Trip planner dasar berbasis aturan.
- Admin dapat mengelola artikel panduan.
- Customer dapat membaca artikel dan rekomendasi.

### Exit Criteria

Setiap rekomendasi dapat ditelusuri ke aturan yang jelas; produk nonaktif tidak direkomendasikan; artikel draft tidak tampil ke publik; konten keselamatan menyertakan sumber tepercaya.

## 10. Phase 7 — Retail Sales & Expanded Reports

**Tujuan:** melengkapi jalur penjualan produk serta laporan manajemen.

### Tasks

- [ ] Buat keranjang/pemesanan produk jual.
- [ ] Pisahkan sales order dari rental order.
- [ ] Terapkan pengurangan stok jual pada status transaksi yang ditetapkan.
- [ ] Buat verifikasi pembayaran/konfirmasi penjualan sesuai metode yang tersedia.
- [ ] Buat Admin Sales Order Management.
- [ ] Tambahkan laporan pendapatan sewa vs penjualan.
- [ ] Tambahkan laporan pembayaran, deposit, keterlambatan, kerusakan, dan stock movement.
- [ ] Buat filter periode dan export CSV.
- [ ] Validasi semua total laporan terhadap transaksi sumber.

### Deliverables

- Customer dapat membeli produk tipe sale.
- Admin dapat memproses sales order.
- Laporan membedakan omzet rental, sales, deposit, dan biaya tambahan.

### Exit Criteria

Sales tidak mengubah data rental; stok berkurang sesuai kebijakan; laporan dapat direkonsiliasi dengan transaksi yang sudah diverifikasi.

## 11. Phase 8 — Quality, Security & Release Readiness

**Tujuan:** mempersiapkan demonstrasi dan deployment yang aman.

### Tasks

- [ ] Jalankan seluruh unit dan feature tests.
- [ ] Uji role/permission untuk semua route admin.
- [ ] Uji CSRF, validasi, rate limiting, upload, dan akses file privat.
- [ ] Uji date overlap dan transaksi bersamaan.
- [ ] Periksa query N+1, pagination, dan indeks database.
- [ ] Uji responsive customer pages dan Admin Dashboard.
- [ ] Lakukan accessibility pass pada form, tabel, modal, dan status.
- [ ] Jalankan dependency audit dan hapus package tidak terpakai.
- [ ] Siapkan backup database dan file upload.
- [ ] Siapkan production `.env` tanpa memasukkannya ke Git.
- [ ] Build asset dan Composer dependencies secara lokal.
- [ ] Deploy ke AeonFree hanya setelah document root dan ekstensi dinyatakan aman.
- [ ] Jalankan deployment smoke tests.
- [ ] Buat README instalasi, panduan admin, dan panduan penggunaan.

### Deliverables

- Release candidate.
- Test report/checklist.
- Backup & restore procedure.
- Deployment notes dan user documentation.

### Exit Criteria

Tidak ada blocker security atau data integrity; alur utama lulus test; file sensitif tidak publik; backup dapat diuji; aplikasi dapat digunakan pada environment target tanpa fitur yang memerlukan daemon.

## 12. Cross-Phase Definition of Done

Setiap fitur dianggap selesai jika:

- [ ] Acceptance criteria pada PRD terpenuhi.
- [ ] Request divalidasi di server.
- [ ] Permission diperiksa di server.
- [ ] Aturan bisnis ditempatkan pada service/model yang tepat.
- [ ] Loading, empty, success, dan error states ditangani.
- [ ] Data penting memiliki migration dan constraint yang sesuai.
- [ ] Test otomatis ditambahkan untuk logika kritis.
- [ ] UI responsive dan dapat diakses dengan keyboard.
- [ ] Tidak ada credential atau data sensitif di source control.
- [ ] Dokumentasi singkat diperbarui.

## 13. Dependency Map

```mermaid
flowchart TD
    P0[Phase 0: Requirements & Hosting] --> P1[Phase 1: Project Foundation]
    P1 --> P2[Phase 2: Auth & RBAC]
    P2 --> P3[Phase 3: Catalog & Inventory]
    P3 --> P4[Phase 4: Rental Availability & Orders]
    P4 --> P5[Phase 5: Payments & Rental Operations]
    P3 --> P6[Phase 6: Trip Planner & Guides]
    P5 --> P7[Phase 7: Retail & Reports]
    P6 --> P8[Phase 8: Release Readiness]
    P7 --> P8
```

Phase 6 dapat berjalan paralel setelah katalog tersedia, tetapi seluruh fitur harus lolos quality gate Phase 8.

## 14. Prioritas Implementasi Admin Dashboard

Urutan implementasi admin yang direkomendasikan:

1. Admin Authentication, RBAC, dan layout dashboard.
2. Dashboard overview dengan data riil minimal.
3. Categories, Products, Product Variants.
4. Inventory, Rental Assets, dan Stock Movements.
5. Rental Orders dan Availability.
6. Payments dan verifikasi manual.
7. Pickup, Returns, Inspections, Deposits, Additional Charges.
8. Sales Orders.
9. Trip Planner Rules dan Equipment Guides.
10. Reports, export, audit, dan release hardening.

Jangan membangun semua halaman dashboard sebagai UI statis terlebih dahulu. Setiap modul harus dihubungkan dengan model, policy, validasi, alur status, dan test terkait.

## 15. Delivery Tracking Template

Gunakan status berikut pada issue/board proyek:

- `BACKLOG`: belum dimulai.
- `READY`: kebutuhan dan acceptance criteria sudah jelas.
- `IN_PROGRESS`: sedang dikembangkan.
- `IN_REVIEW`: menunggu review/testing.
- `BLOCKED`: bergantung pada keputusan atau masalah eksternal.
- `DONE`: Definition of Done terpenuhi.

Untuk tiap task, catat: phase, modul, acceptance criteria, migration yang dibutuhkan, test yang ditambahkan, dan bukti uji.

## 16. Risiko Roadmap

| Risiko | Dampak | Mitigasi |
|---|---|---|
| PHP/extension AeonFree tidak sesuai | Aplikasi tidak dapat berjalan | Validasi Phase 0 sebelum lock dependency |
| PHP 8.2 hanya tersedia | Laravel 12 memiliki sisa masa security support terbatas | Periksa opsi PHP 8.3/upgrade sebelum 24 Feb 2027 |
| Tidak tersedia SSH/Composer | Deployment lebih manual | Build dependencies secara lokal, dokumentasikan upload |
| Document root tidak dapat diamankan | Source/.env dapat terpapar | Hentikan deployment sampai private/public split aman |
| Aturan tanggal rental belum jelas | Salah hitung biaya/overbooking | Tetapkan kebijakan dan uji edge cases sebelum Phase 4 |
| Inventaris hanya dihitung sebagai angka total | Unit ganda/riwayat kondisi tidak akurat | Gunakan unit fisik untuk aset rental |
| Scope dashboard terlalu luas | Fitur inti tidak selesai | Ikuti urutan P0 lalu P1/P2 |
| Tidak ada cron/worker | Pengingat/expiry otomatis tidak berjalan | MVP memakai pemeriksaan on-demand dan operasi manual |

## 17. Dokumen Acuan

- [PRD GEARORA](./PRD_GEARORA.md)
- [Architecture GEARORA](./ARCHITECTURE_GEARORA.md)
- [Tech Stack GEARORA](./TECH_STACK_GEARORA.md)
- [Proposal konsep GEARORA](https://github.com/KiandrAHD/Gearora/blob/main/.Gearora/GEARORA.md)
- [Homepage design reference](https://github.com/KiandrAHD/Gearora/blob/main/.docs/Desain_Gearora.md)
