# PHASE 9 — Design Spec

Tanggal: 20 November 2026
Branch: `feat/phase-9-gearora-experience`

## Tujuan

Menyelaraskan pengalaman publik GEARORA dengan brief Phase 9: navigasi publik
yang terpusat, katalog sewa/beli yang jelas, alur Trip Planner Gunung Prau
lima langkah dengan paket campuran sewa+beli, dan Studio Panduan editorial —
tanpa mengubah arsitektur (tetap Laravel 12 + Blade + Tailwind 4 + Alpine).

## Prinsip desain

1. **Berorientasi tugas.** Setiap halaman punya satu aksi utama (sewa, beli,
   susun paket, tulis panduan).
2. **Jujur secara status.** Stok, harga, dan pembayaran selalu ditampilkan apa
   adanya dan dihitung ulang di server.
3. **Aman secara default.** Teks admin di-escape; hanya sintaks terbatas yang
   dirender.
4. **Konsisten.** Token warna FE (forest/navy/cloud/line), tipografi Poppins +
   Inter, dan komponen status badge dipakai ulang lintas modul.

## Navigasi publik (navbar)

- Header lengket (`position: sticky`) dengan latar tembus pandang.
- Grid tiga kolom: merek (kiri) · navigasi utama terpusat (tengah) · aksi
  (kanan: cari, keranjang, akun).
- Item utama: **Sewa**, **Beli**, **Rekomendasi Trip**, **Panduan**.
- `aria-current="page"` menandai halaman aktif dengan garis bawah.
- Di ≤900px: navigasi utama dan aksi akun disembunyikan, muncul tombol menu;
  panel mobile menampilkan tautan yang sama plus tombol tutup.

## Katalog (sewa/beli)

- Hero ringkas + toolbar (mode, urutan, cari) + sidebar filter.
- Filter: kategori (radio), rentang harga min/max, "hanya stok tersedia".
- Urutan: Unggulan & terbaru, Produk terbaru, Nama A–Z, Harga terendah,
  Harga tertinggi.
- Kartu produk menampilkan harga sesuai mode (sewa: per hari; beli: per unit).
- ≤900px: sidebar menjadi drawer dari kiri dengan tombol "Filter".

## Trip Planner (Gunung Prau)

- Lima langkah progresif: 1 Aktivitas, 2 Durasi & peserta, 3 Mode & tanggal,
  4 Ringkasan, 5 Pilih gear.
- Destinasi ditetapkan server (Gunung Prau) — input tampil read-only.
- Langkah 5 menampilkan rekomendasi deterministik dari aturan katalog, dengan
  kontrol per item: mode (sewa/beli) dan jumlah.
- Halaman pratinjau paket menampilkan rincian biaya sewa, biaya beli, dan
  ringkasan total, plus pemilihan metode pembayaran.

## Studio Panduan

- Editor Markdown aman dengan pratinjau server-side memakai renderer publik
  yang sama.
- Toolbar: tebal, miring, H2, daftar, tautan, **Unggah gambar**, **YouTube URL**,
  **Pratinjau**.
- Metadata: kategori, ringkasan, sampul, unggulan, SEO title/description, status
  (Draf/Terbit).

## Batasan yang tidak dilanggar

- Tidak ada SPA/React/rewrite arsitektur.
- Harga, ketersediaan, dan stok hanya ditentukan server.
- Tidak ada HTML mentah atau iframe arbitrer di konten panduan.

## Catatan visual

Tidak ada akses berkas Figma pada fase ini. Kesesuaian visual mengacu pada
audit dan brief Phase 9 (`.docs/PHASE_9_UX_AUDIT.md`), bukan perbandingan
piksel-per-piksel.
