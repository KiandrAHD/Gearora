# GEARORA — Laporan Penyelesaian Revamp UI/UX

Tanggal: 27 November 2026
Ruang lingkup: Laravel 12 (Modular Monolith + MVC + Service Layer), Blade + Tailwind 4 + Alpine.js, Vite 6, PHPUnit 11.

## 1. Ringkasan status

| Fase | Fokus | Status | Commit |
| --- | --- | --- | --- |
| 0 | Audit + rencana | Selesai | `864e23e` |
| 1 | Design token, tipografi, navbar floating smart-reveal | Selesai | `0f02981` |
| 2 | Hero katalog premium, filter server-side, badge stok, kartu responsif | Selesai | `c30d644` |
| 3 | Trip Planner: basecamp dropdown + validasi tanggal | Selesai | `49a2602` |
| 4 | Panduan: landing editorial, detail majalah + TOC, 8 artikel | Selesai | `f2a818f` |
| 5 | Akun: shell responsif + statistik nyata | Selesai | `6397157` |
| 6 | Admin Console: topbar/judul konteks + workspace responsif | Selesai | `8dc0639` |
| 7 | Pembayaran: pemilih metode, QRIS, label tunai saat pengambilan | Selesai | `96445c3` |
| 8 | Aksesibilitas + polish responsif (drawer filter, tabel) | Selesai | (commit fase ini) |
| 9 | Verifikasi akhir + laporan | Selesai | (commit fase ini) |

## 2. Perubahan utama

### Fondasi desain (Fase 1)
- Token warna: Forest `#123F32`, Deep Navy `#0F2027`, Dark Green `#173F34`, Warm White `#F7F7F2`, Sage `#E7EDE5`, Soft Gray `#F0F2EE`, Border `#DFE5DE`, Trail Orange `#D9794C`, Text Primary `#172A29`, Text Secondary `#74817D`.
- Heading Poppins, body Inter (menggantikan Plus Jakarta Sans).
- Navbar pill mengambang: sembunyi saat scroll turun, muncul saat scroll naik/pointer mendekati atas; drawer mobile dengan focus-trap dan Escape.

### Katalog (Fase 2)
- Hero premium dengan statistik katalog.
- Filter server-side: kata kunci, kategori, rentang harga (satuan benar: `/ hari` untuk sewa, `/ unit` untuk beli), ketersediaan/tanggal.
- Drawer filter mobile dengan `aria-expanded`, `aria-controls`, `visibility` saat tertutup.
- Kartu produk: badge merek, `GEARORA SELECT`, status ketersediaan; grid 3/2/2/1 kolom (>900 / ≤900 / ≤600 / ≤400 px).

### Trip Planner (Fase 3)
- Wizard 5 langkah dipertahankan.
- Basecamp dropdown: Patak Banteng, Dieng, Kalilembu, Wates, Igirmranak, + "Lainnya" dengan input `basecamp_other`; diresolusi server-side ke `location`. Posting lama berbasis `location` tetap didukung.
- Tanggal keberangkatan ditolak jika lampau (hari ini diperbolehkan) di frontend (`min`) dan backend (`Asia/Jakarta`), pesan Bahasa Indonesia.

### Panduan (Fase 4)
- 8 artikel nyata berbahasa Indonesia di `DemoGuidesSeeder` (hanya `local`/`testing`, idempotent via `updateOrCreate` by slug, tidak menyentuh artikel production).
- Landing editorial dengan hero bergambar, statistik, unggulan, dan grid artikel.
- Detail gaya majalah: breadcrumb, lede, daftar isi ber-anchor (`GuideTableOfContents`), sumber, artikel terkait, dan catatan keselamatan.
- `GuideContentRenderer` + Guide Studio dipertahankan (render aman, tanpa HTML mentah).

### Akun (Fase 5)
- Shell akun responsif: hero sapaan, kartu statistik nyata (menunggu pembayaran, berjalan, selesai, pembelian retail), daftar pesanan, empty state, aksi cepat.
- Navigasi hanya ke route yang ada: Ringkasan, Sewa saya, Pembelian saya, Profil & keamanan.
- Tabel pesanan diberi `.table-scroll`, badge status, dan layout kartu di mobile.

### Admin Console (Fase 6)
- Topbar standar: kicker, judul, subtitle konteks, tautan situs publik, chip pengguna.
- Sidebar RBAC dipertahankan; workspace anti-overlap; tabel lebar dapat digeser di mobile.
- KPI dashboard dari database; modal memakai `x-ui.modal` (fokus, Escape, `body.modal-open`).

### Pembayaran (Fase 7)
- Komponen `x-partials.payment-method-picker`: radio group ber-legend, ikon metode, deskripsi, dan penonaktifan QRIS bila gateway tidak tersedia.
- Label metode terpusat di `PaymentMethod`:
  - `midtrans` → "QRIS / e-wallet & transfer otomatis (Midtrans)"
  - `transfer` → "Transfer bank manual"
  - `cash` → "Bayar tunai saat pengambilan" (bukan COD pengiriman)
- Pemilihan metode sebelum checkout di detail produk (sewa), keranjang retail, dan pratinjau paket trip.
- Halaman pesanan sewa/pembelian menampilkan instruksi per metode; tunai menampilkan catatan pengambilan.
- Admin: aksi metode-aware (transfer → verifikasi/tolak; tunai → "Catat tunai diterima").
- Pipeline lama dipertahankan: Midtrans Snap + webhook signature, transfer + bukti, `PaymentService::recordCash` idempotent + cek nominal. Tidak ada pipeline pembayaran kedua.

### Aksesibilitas + responsif (Fase 8)
- Filter drawer: `aria-expanded` sinkron, Escape menutup, `visibility:hidden` saat tertutup agar tidak fokusable.
- Tabel lebar dalam panel bisa digeser horizontal di ≤600 px.
- `:focus-visible` global, skip-link, `prefers-reduced-motion`.

## 3. Hasil verifikasi

- `vendor/bin/phpunit`: **OK (361 tests, 1129 assertions)**.
- `vendor/bin/pint --test`: **passed**.
- `composer validate --strict`: **valid**.
- `composer audit`: **No security vulnerability advisories found** setelah update patch Symfony 7.4.24 (routing, http-kernel, http-foundation, mailer, mime, var-dumper) + `egulias/email-validator` 4.0.5.
- `npm run build`: **berhasil** (`app.css` ±132 kB, `app.js` ±122 kB).
- `npm audit`: **0 vulnerabilities**.

## 4. Catatan keputusan

- Tidak ada migrasi/skema baru untuk pembayaran; pencatatan tunai memakai kolom yang ada dan idempotensi `markSettled`.
- "Lainnya" basecamp disimpan sebagai `location` (tanpa tabel baru), divalidasi `required_if` + `max:120`.
- Seeder panduan dibatasi `local`/`testing`; tidak ada auto demo seed ke produksi.
- `public/build` tetap gitignored; artefak build tidak di-commit.

## 5. Batasan / belum diverifikasi

- Uji visual browser otomatis (Playwright/agent-browser) pada rentang viewport 360×800–1920×1080 belum dijalankan di lingkungan ini; verifikasi difokuskan pada test suite, kompilasi Blade (`view:cache`), build aset, dan aturan breakpoint CSS. Disarankan satu putaran QA visual manual sebelum rilis.
- Pemeriksaan `composer audit`/`npm audit` bergantung pada feed advisory; jalankan ulang sebelum deploy.