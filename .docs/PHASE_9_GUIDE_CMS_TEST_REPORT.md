# PHASE 9 — Guide CMS Test Report

Tanggal: 20 November 2026

## Scope

CMS panduan: penyimpanan, publikasi, pratinjau aman, unggahan gambar inline,
embed YouTube, dan halaman publik.

## Hasil otomatis

| Berkas | Hasil |
|---|---|
| `tests/Feature/GuideMediaUploadTest.php` | 4 passed (8 assertions) |
| `tests/Feature/AdminGuideStudioTest.php` | 9 passed (29 assertions) |
| `tests/Feature/EquipmentGuideTest.php` | 9 passed (23 assertions) |
| `tests/Feature/AdminEquipmentGuideTest.php` | termasuk suite penuh |

Suite penuh: **331 passed, 1020 assertions**.

## Cakupan kasus

- Pratinjau aman: `<script>`/HTML mentah menjadi teks; estimasi menit dihitung.
- Normalisasi URL YouTube mandiri (`youtu.be/...?t=10`) → embed nocookie tanpa
  parameter tambahan.
- Unggah gambar inline: staf berizin → `201` dengan `path`, `url`, `alt`;
  tamu → diarahkan ke login; berkas tak aman (`.php`) → `422`.
- Penandaan unggulan, metadata SEO, status draf/terbit.
- Halaman publik `/panduan` dan `/panduan/{slug}` merender konten aman.

## Bukti browser (lihat `PHASE_9_BROWSER_E2E_RESULTS.md`)

Panduan uji dibuat via form Studio dengan payload berbahaya
(`<script>`, `<iframe>`, `javascript:`). Hasil DOM halaman publik:

- `script` di konten: **0**
- `iframe`: **1** (hanya `youtube-nocookie.com/embed/dQw4w9WgXcQ`)
- HTML mentah & tautan `javascript:`: dinetralkan menjadi teks
- Gambar inline disajikan dari `/media/guide-inline/...`
- Tombol Studio (Alpine) berfungsi: `uploadImage` menyisipkan
  `![sunrise-prau](/media/guide-inline/...)`, `insertYoutubeUrl` menyisipkan
  `@youtube[...]` dan menolak URL non-YouTube.

## Catatan

Unggahan inline disimpan pada disk publik dengan nama acak. Tidak ada HTML mentah
yang dapat disimpan; renderer bersifat escape-first.
