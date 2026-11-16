# Phase 0 — Requirement Audit — GEARORA

> **Produk:** GEARORA — Outdoor Equipment Rental Platform
> **Tagline:** Gear Up. Explore More.
> **Repo:** https://github.com/KiandrAHD/Gearora
> **Branch:** `main`
> **Tanggal audit:** 16 November 2026
> **Status dokumen:** Hasil audit Phase 0 (Requirement Freeze & Hosting Validation)

---

## A. Project Overview

### Identitas Produk

- **Nama:** GEARORA (Outdoor Equipment Rental Platform)
- **Tagline:** Gear Up. Explore More.
- **Tema:** Business & Retail
- **Bentuk:** Platform berbasis website (penyewaan **dan** penjualan perlengkapan outdoor).

### Permasalahan yang Ingin Diselesaikan

Berdasarkan proposal (`.Gearora/GEARORA.md`) dan PRD (`.docs/PRD_GEARORA.md`):

1. Reservasi masih dilakukan lewat percakapan WhatsApp, tidak terorganisasi.
2. Ketersediaan barang pada rentang tanggal tertentu sulit dipastikan dari jauh.
3. Pelanggan pemula kesulitan memilih perlengkapan yang sesuai aktivitas/durasi.
4. Informasi ukuran, kapasitas, kondisi, dan spesifikasi belum terpusat.
5. Panduan penggunaan perlengkapan belum tersedia terstruktur.
6. Transaksi, pembayaran, dan deposit belum terintegrasi.
7. Pemilik rental sulit memantau jadwal pengembalian, keterlambatan, dan kondisi barang.

### Target Pengguna

| Persona | Kebutuhan utama |
|---|---|
| Pendaki pemula | Tahu gear yang dibutuhkan + cara menggunakannya |
| Pendaki berpengalaman | Reservasi gear tertentu untuk periode perjalanan |
| Camper / traveler | Menyewa perlengkapan yang jarang dipakai |
| Pelanggan pembelian | Membeli perlengkapan pendukung |
| Admin rental | Mengelola produk, stok, transaksi |
| Owner | Memantau kinerja bisnis dan risiko, kontrol akses |
| Petugas gudang | Menyiapkan, menyerahkan, menerima, memeriksa barang |

### Business Value

- **Preparation first** — pelanggan menyiapkan gear sebelum datang ke lokasi.
- **Inventory visibility** — ketersediaan berbasis periode lebih transparan.
- **Helpful information** — spesifikasi, ukuran, kapasitas, panduan satu tempat.
- **Operational control** — admin punya rekam jejak transaksi dan kondisi aset.
- **Rental + retail** — dua model transaksi didukung tanpa dicampur aturannya.

### Ruang Lingkup Proyek

Satu aplikasi web (Laravel, modular monolith) berisi:
- Website publik: Homepage, katalog, detail produk, akun pelanggan, trip planner, panduan.
- Admin Console: dashboard, katalog/inventaris, rental order, pembayaran, deposit, inspeksi, pengembalian, sales order, konten, laporan.

Roadmap resmi (`.docs/ROADMAP_GEARORA.md`) membagi ke Phase 0–8. **Prompt eksekusi ini hanya mencakup Phase 0 dan Phase 1.**

---

## B. Requirement Classification

### Confirmed Requirements

Berasal dari PRD (P0/MVP) dan proposal:

- Homepage, katalog, detail produk, registrasi/login pelanggan.
- Katalog memisahkan mode **SEWA** dan **BELI**; produk bertipe `rental`/`sale`/`both`.
- Rental memiliki tanggal mulai dan tanggal kembali; durasi minimum 1 hari.
- Pemeriksaan ketersediaan per varian dan periode + pencegahan overbooking (validasi ulang di server dalam database transaction).
- Snapshot harga saat order dibuat.
- Admin login + RBAC minimum (Owner, Admin, Petugas Gudang, Customer).
- Verifikasi pembayaran manual (bukti transfer), dipisah dari status order.
- Deposit sebagai liabilitas terpisah dari pendapatan.
- Serah-terima (pickup) dan pengembalian (return) + inspeksi kondisi.
- Dashboard operasional & laporan dasar.
- Bahasa UI Indonesia, format Rupiah, timezone `Asia/Jakarta`.
- Unit rental dilacak sampai tingkat aset fisik (`rental_assets`).

### Technical Decisions (lihat juga `PHASE_0_TECHNICAL_DECISIONS.md`)

- Backend Laravel, arsitektur MVC + Service Layer, modular monolith.
- Frontend Blade + Tailwind CSS + Alpine.js (interaksi ringan).
- Database MySQL 8.0 (target hosting) / Eloquent ORM.
- Build aset dengan Vite **di lokal**, upload hasil build.
- Tanpa microservices, queue worker permanen, WebSocket, Redis wajib, atau daemon.
- Hosting target: AeonFree (shared hosting).

### Open Questions

1. Versi PHP **aktual** pada akun AeonFree (8.2 vs 8.3+ vs konflik dokumentasi 7.4).
2. Ketersediaan SSH/Terminal, Composer, cron, symlink, dan pemilihan versi PHP.
3. Apakah document root dapat diatur ke `public/` atau source dapat diletakkan di luar `public_html`.
4. Hak tulis `storage/` dan `bootstrap/cache/`.
5. Versi MySQL aktual (8.0 vs 5.7 per konflik dokumentasi).
6. Kebijakan bisnis final: jam pengambilan, grace period, denda keterlambatan, deposit per produk, aturan pembatalan/refund, satu lokasi atau multi-cabang.
7. Format & batas ukuran bukti pembayaran (default PRD: JPG/PNG/PDF).

### Out of Scope (di luar MVP & Phase 1)

- Aplikasi native Android/iOS.
- Multi-cabang/multi-gudang dengan transfer stok.
- Payment gateway otomatis & pencairan dana otomatis.
- Integrasi kurir/marketplace shipping.
- AI generatif untuk rekomendasi/keselamatan.
- Chat real-time, notifikasi WhatsApp otomatis, WebSocket, worker persisten.
- Dynamic pricing, loyalty, voucher kompleks, affiliate.
- Sistem akuntansi penuh.

### Future Development

Trip recommendation, equipment guides, payment gateway, pengingat pengembalian, review/ulasan, laporan lanjutan + ekspor CSV.

---

## C. Feature Scope

### Core Product

- Homepage.
- Katalog rental & penjualan.
- Detail perlengkapan.
- Registrasi dan login.
- Reservasi dasar (tanggal mulai/kembali).
- Data transaksi (order, pembayaran manual, pengembalian).
- Dashboard admin (fase berikutnya).

### Supporting Features

- Rekomendasi trip (rule-based).
- Panduan penggunaan gear.
- Ketersediaan perlengkapan berdasarkan periode.
- Informasi ukuran dan spesifikasi.

### Future Features

- Payment gateway.
- Pengingat pengembalian.
- Review pelanggan.
- Laporan lanjutan + ekspor CSV.
- Fitur lain sesuai proposal.

> Prioritas fitur mengikuti PRD (`P0`/`P1`/`P2`) dan ROADMAP. **Tidak diubah** dalam audit ini.

---

## D. Business Rules Awal

Aturan yang **didukung dokumen** (PRD §9, Architecture §8–9):

1. **Pemisahan rental vs penjualan** — rental punya periode pemakaian & pengembalian; penjualan = perpindahan kepemilikan.
2. **Perhitungan durasi** — selisih tanggal kembali dan tanggal mulai, minimum 1 hari (baseline MVP).
3. **Harga rental** — jumlah unit × harga/hari × jumlah hari; disimpan sebagai snapshot saat order dibuat.
4. **Ketersediaan** — unit layak pakai dikurangi unit teralokasi pada order aktif yang periodenya beririsan.
5. **Overlap periode (baseline)** — bentrok bila `A.start_date < B.end_date` **dan** `A.end_date > B.start_date`; harus diuji untuk peminjaman berurutan pada hari yang sama.
6. **Anti-overbooking** — validasi diulang di server dalam database transaction + row locking.
7. **Status unit** — `available`, `reserved`, `rented`, `maintenance`, `damaged`, `lost`. Unit `maintenance`/`damaged`/`lost` tidak dapat disewakan.
8. **Pembayaran** — status pembayaran terpisah dari status order; bukti transfer ≠ lunas.
9. **Deposit** — uang jaminan/liabilitas terpisah dari pendapatan; dilacak sampai dikembalikan/dipotong dengan alasan tercatat.
10. **Pengembalian** — order belum `completed` sebelum unit teralokasi dicatat kembali / ditangani.
11. **Audit** — perubahan harga, stok, status pembayaran/order, deposit, inspeksi mencatat pengguna + waktu.
12. **Arsip, bukan hapus** — data yang sudah direferensikan transaksi tidak dihapus permanen.
13. **Rekomendasi** = saran persiapan, bukan jaminan keselamatan.

> **Tidak menetapkan** nominal deposit, tarif denda, grace period, atau kebijakan pembayaran tanpa dasar dokumen/keputusan usaha.

---

## E. Konflik & Risiko Dokumentasi

1. **Versi PHP/MySQL AeonFree bertentangan.** Beranda menyebut PHP 8.2 + MySQL 8.0; halaman `/free-php-mysql-hosting` menyebut PHP 7.4 + MySQL 5.7. Keduanya "publicly documented", bukan bukti akun aktual. Dampak: pemilihan versi Laravel bergantung verifikasi akun.
2. **Tidak ada dokumentasi publik** tentang SSH, Composer, cron, symlink, pemilihan versi PHP, dan layout document root. Tidak boleh diasumsikan tersedia hanya karena cPanel.
3. **README dokumentasi (`.docs/README.md`)** menegaskan hal yang sama: belum ada source aplikasi pada saat dokumen disusun. Audit ini mengonfirmasi kondisi itu (root repo hanya docs + LICENSE sebelum Phase 1).
4. **Aturan bisnis tanggal/jam/denda belum final.** Tidak boleh diimplementasikan sebelum disahkan (Phase 4+).
5. **Nama pada dokumen tidak seragam**: "Gearora" (repo/commit), "GEARORA" (produk), project folder target `gearora-app`. Disepakati: produk = **GEARORA**, project folder hosting = `gearora-app` (sesuai prompt).

---

## Referensi

- `.Gearora/GEARORA.md` (proposal)
- `.docs/PRD_GEARORA.md`
- `.docs/ARCHITECTURE_GEARORA.md`
- `.docs/TECH_STACK_GEARORA.md`
- `.docs/ROADMAP_GEARORA.md`
- `.docs/Desain_Gearora.md` (master design reference)
- https://aeonfree.com/ , https://aeonfree.com/free-php-mysql-hosting
