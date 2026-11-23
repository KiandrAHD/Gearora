# GEARORA Full QA — Browser E2E Results

Tanggal eksekusi: 2026-11-23
Tool: Playwright (`@playwright/test`) + Chromium.
Server: `php artisan serve` di `127.0.0.1:8123` dengan database SQLite sekali-pakai `storage/qa/e2e.sqlite`.
Data: `migrate:fresh` pada SQLite disposable, seeder demo, akun uji lokal (owner/admin/gudang/pelanggan). **Bukan** database `gearora` MySQL.

## Hasil

| Project | Hasil |
|---|---|
| `desktop-chromium` (1440×900) | 20 passed, 1 skipped (drawer mobile khusus viewport mobile) |
| `mobile-chromium` (Pixel 7) | 21 passed |

## Skenario yang dijalankan

1. Halaman publik: `/`, `/katalog`, `/panduan`, `/rekomendasi-trip`, `/masuk`, `/daftar` — status < 400, `main` terlihat, gambar termuat, tanpa overflow horizontal.
2. Navigasi: `aria-current="page"` hanya pada halaman aktif.
3. 404 terkendali untuk URL tidak dikenal.
4. Panduan: heading hero, grid kartu, filter keyword/kategori, detail artikel, tidak ada `<script>` tersuntik di konten.
5. Trip Planner: wizard 5 tahap (jadwal → peserta → kebutuhan → ringkasan → hasil), isi basecamp/tanggal/jam, validasi menahan langkah saat tanggal kosong, hasil rekomendasi > 0, lanjut ke pratinjau paket.
6. Auth: login pelanggan sukses, kredensial salah ditolak, login staf ke konsol admin.
7. Responsif: 4 halaman kunci tanpa overflow di desktop dan mobile, drawer mobile bisa dibuka/ditutup.

## Screenshot

25 berkas di `storage/qa/screenshots/` (tidak di-commit, sesuai aturan repo).
Contoh: `public-home.png`, `public-panduan.png`, `panduan-detail.png`, `trip-step-1..5-results.png`, `trip-preview-paket.png`, `akun-dashboard.png`, `admin-dashboard.png`, `mobile-drawer-open.png`, `responsive-*.png`.

## Catatan

- Tidak ada screenshot atau hasil yang direkayasa: semua diambil dari eksekusi nyata.
- Playwright ditambahkan sebagai devDependency untuk kebutuhan QA ini (`playwright.config.js`, `tests/e2e/`).
