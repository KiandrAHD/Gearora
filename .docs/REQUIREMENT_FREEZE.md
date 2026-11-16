# Requirement Freeze — GEARORA

> **Produk:** GEARORA — Outdoor Equipment Rental Platform
> **Tagline:** Gear Up. Explore More.
> **Tanggal freeze:** 16 November 2026
> **Cakupan freeze:** Phase 0 & Phase 1
> **Aturan:** Phase 1 mengikuti dokumen ini. Perubahan hanya lewat update eksplisit + catatan alasan.

---

## 1. Project Vision

GEARORA adalah platform web untuk **menyewa dan menjual perlengkapan outdoor**. Nilai utamanya: pelanggan menyiapkan gear sebelum datang ke lokasi rental, dan pemilik rental mengendalikan aset serta transaksi secara terorganisasi. Alur inti: `discovery → info & ketersediaan → reservasi → pembayaran/konfirmasi → pengambilan → pengembalian & inspeksi`.

---

## 2. Confirmed Scope

### In scope (produk, jangka panjang)
- Homepage, katalog (mode **SEWA**/**BELI**), detail produk.
- Registrasi/login pelanggan; Admin login + RBAC (Owner/Admin/Petugas Gudang).
- Rental: tanggal mulai/kembali, ketersediaan per periode, anti-overbooking, snapshot harga, kode transaksi.
- Pembayaran manual + verifikasi admin; deposit terpisah; pickup/return + inspeksi.
- Sales order untuk produk jual; laporan dasar.
- Trip planner (rule-based) & equipment guides; rekomendasi = saran, bukan jaminan keselamatan.

### In scope (Phase 0 & Phase 1 — pekerjaan prompt ini)
- **Phase 0:** audit requirement, validasi hosting, TDR, requirement freeze.
- **Phase 1:** fondasi Laravel 12, konfigurasi environment MySQL, fondasi frontend (Blade + Tailwind 4 + Vite + token GEARORA), routing dasar (homepage + health), error handling & logging, security baseline, testing foundation, dokumentasi (README, local dev, deployment guide).

---

## 3. Excluded Scope

Dilarang dibangun pada Phase 1 (fase berikutnya): Admin Dashboard, autentikasi bisnis lengkap, CRUD produk/kategori, inventaris, rental orders, payment system, deposit, return management, trip recommendation, outdoor guides, sales management, reports.

Dilarang dibuat pada seluruh MVP: microservices, WebSocket/Reverb, queue worker permanen, Redis wajib, Docker di shared hosting, Node.js runtime produksi, payment gateway sebelum kebijakan refund/settlement disahkan, AI API.

---

## 4. Confirmed Technical Decisions

Mengacu `PHASE_0_TECHNICAL_DECISIONS.md`:

- **Laravel 12.x** (PROVISIONAL; dinaikkan bila hosting ≥ PHP 8.2 terverifikasi).
- **PHP** target 8.2+ (lokal 8.5.9).
- **MySQL 8.0** (PENDING), InnoDB, `utf8mb4`.
- **Blade** SSR, **Tailwind CSS 4**, **Alpine.js 3.x**, **Vite** (build lokal).
- **Modular monolith + MVC + Service Layer**.
- **Eloquent ORM**; MySQL target, sqlite untuk testing.
- **PHPUnit 11**; test DB = sqlite `:memory:`.
- Session/Cache = `database`, Queue = `sync`.
- Dependency PHP di-build lokal (`composer.lock` dikunci).
- Locale `id`, timezone `Asia/Jakarta`.

---

## 5. Business Constraints

- Rental dan penjualan = tipe transaksi berbeda (aturan stok berbeda).
- Durasi rental = selisih tanggal, minimum 1 hari (baseline).
- Harga rental = jumlah × harga/hari × hari; snapshot saat order.
- Ketersediaan dihitung dari unit layak pakai − unit teralokasi pada order beririsan; validasi ulang di server dalam transaction.
- Overlap baseline: `A.start < B.end` dan `A.end > B.start`.
- Status unit: `available`, `reserved`, `rented`, `maintenance`, `damaged`, `lost`.
- Pembayaran terpisah dari status order; bukti ≠ lunas.
- Deposit = liabilitas terpisah dari pendapatan.
- Order `completed` hanya setelah unit dikembalikan/ditangani.
- Perubahan sensitif wajib audit trail.
- Data bertransaksi diarsipkan, bukan dihapus permanen.
- **Belum ditetapkan** (jangan diimplementasikan tanpa keputusan): nominal deposit, tarif denda, grace period, kebijakan pembatalan/refund, jam pengambilan, multi-cabang.

---

## 6. Hosting Constraints (AeonFree)

- Shared hosting; **tanpa** asumsi SSH/Composer/Node/cron/Redis/symlink tersedia.
- Deployment: dependency & aset di-build lokal; upload artefak.
- Target utama: source di luar `public_html`, hanya file publik di document root.
- Spesifikasi publik bertentangan (PHP 8.2/7.4, MySQL 8.0/5.7) → wajib verifikasi akun.
- Jika hosting memaksa source di dalam web root tanpa proteksi → **deployment ditahan**.

Detail: `PHASE_0_HOSTING_VALIDATION.md`.

---

## 7. Assumptions

1. Hosting akhirnya menyediakan **minimal PHP 8.2** dengan ekstensi wajib Laravel.
2. Hosting menyediakan MySQL (versi 8.0 atau 5.7) dengan akses pembuatan database/user.
3. Document root dapat diatur, atau source dapat ditempatkan di luar `public_html`.
4. Tidak ada kebutuhan proses daemon/worker pada MVP.
5. Development dilakukan di Windows + Laragon (PHP 8.5.9, MySQL 8.4.3, Node 26).

> Semua asumsi di atas **BELUM terverifikasi** dan dapat membatalkan deployment bila salah.

---

## 8. Open Questions

1. Versi PHP & MySQL aktual akun AeonFree.
2. Ekstensi PHP wajib tersedia? SSH/Composer/cron/symlink?
3. Document root dapat diatur?
4. Kebijakan bisnis final (jam, grace, denda, deposit, pembatalan, cabang).
5. Format/ukuran bukti pembayaran final.
6. Siapa yang berwenang mengelola staf/pembayaran/deposit (default: Owner tertinggi).

---

## 9. Known Risks

| Risiko | Dampak | Mitigasi |
|---|---|---|
| PHP akun < 8.2 | Laravel 12 tidak jalan | Tahan deployment; dev tetap lokal; cari hosting kompatibel |
| Spesifikasi publik bertentangan | Salah pilih versi | Verifikasi akun sebelum lock dependency |
| Tidak ada SSH/Composer | Deployment manual | Build lokal, unggah `vendor/` + `public/build` |
| Document root tidak aman | `.env`/source terpapar | Tahan deployment; pakai pola private/public |
| Aturan tanggal/denda belum final | Salah hitung biaya | Tetapkan sebelum Phase 4 |
| Laravel 12 EOL 24 Feb 2027 | Kehilangan security patch | Rencana upgrade ke Laravel 13 bila PHP 8.3+ tersedia |

---

## 10. Phase 0 Exit Criteria

- [x] Repository diaudit.
- [x] Proposal & design reference dianalisis.
- [x] Scope dikelompokkan.
- [x] Constraint hosting didokumentasikan.
- [x] Informasi hosting diberi status verifikasi (mayoritas BLOCKED — butuh akun).
- [x] Risiko deployment dianalisis.
- [x] Versi Laravel dipilih dengan status **PROVISIONAL** eksplisit (Laravel 12.x).
- [x] Technology Decision Record tersedia.
- [x] Requirement Freeze tersedia.
- [x] Tidak ada asumsi hosting yang dinyatakan sebagai fakta.

Blocker tersisa: verifikasi akun AeonFree (lihat §8 & `PHASE_0_HOSTING_VALIDATION.md` §6). Analisis non-blokir telah selesai; hasil tidak dikarang.

---

## 11. Change Control

Bila fakta baru (mis. hasil verifikasi hosting) mengharuskan perubahan keputusan:

1. Update dokumen terkait secara eksplisit.
2. Catat tanggal, alasan, dan dampak.
3. Pastikan tidak ada kontradiksi dengan `PHASE_0_REQUIREMENT_AUDIT.md` dan TDR.
