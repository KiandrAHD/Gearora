# GEARORA Full QA — Responsive Results

Tanggal: 2026-11-23. Diuji dengan Playwright Chromium.

## Viewport

| Project | Viewport |
|---|---|
| `desktop-chromium` | 1440 × 900 |
| `mobile-chromium` | Pixel 7 (412 × 915) |

## Hasil overflow horizontal (target ≤ 1 px)

| Halaman | Desktop | Mobile |
|---|---|---|
| `/` | PASS | PASS |
| `/katalog` | PASS | PASS |
| `/panduan` | PASS | PASS |
| `/rekomendasi-trip` | PASS | PASS |

## Perilaku responsif yang diverifikasi

- Trip Planner: 2 kolom ≥1000 px, panel visual menjadi header 64 vh di <760 px, form 1 kolom, sticky action bar di mobile.
- Guide library: grid 3 kolom → 1 kolom; featured lead menghilang ke layout tunggal; filter menjadi 1 kolom dengan tombol full width.
- Detail panduan: title clamp, iframe 16:9 responsif, typography 15 px di mobile.
- Drawer navigasi mobile: tombol buka/tutup berfungsi, backdrop aktif, tidak ada scroll body ganda.
- Gambar: `width`/`height` eksplisit pada hero & card untuk mencegah CLS; `loading="lazy"` pada kartu.

## Catatan

- Screenshot `responsive-*.png` dan `mobile-*.png` tersimpan di `storage/qa/screenshots/` (tidak di-commit).
- Uji pada 375 px tidak dijalankan terpisah; Pixel 7 (412 px) mencakup breakpoint `max-width:400px` tidak; lihat batas di bawah.
- **Batas**: breakpoint `max-width:400px` pada Trip Planner belum diverifikasi E2E. Verifikasi dilakukan manual lewat CSS review saja, sehingga klaim di sini dibatasi ke viewport yang benar-benar dijalankan.
