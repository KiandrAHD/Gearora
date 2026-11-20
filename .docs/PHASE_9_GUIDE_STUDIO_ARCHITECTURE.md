# PHASE 9 — Guide Studio Architecture

Tanggal: 20 November 2026
Lihat juga: `.docs/PHASE_9_GUIDE_STUDIO.md`, `.docs/EQUIPMENT_GUIDES_CMS.md`

## Tujuan

Studio Panduan memungkinkan staf menulis panduan editorial (Markdown aman)
dengan pratinjau server-side, unggahan gambar inline lokal, dan embed YouTube
tervalidasi — tanpa pernah menyimpan HTML mentah.

## Alur

```
GET  /admin/guides/create            → form Studio
POST /admin/guides/preview           → HTML aman + estimasi menit (JSON)
POST /admin/guides/media             → unggah gambar inline (JSON)
POST /admin/guides                   → simpan (GuideRequest)
PUT  /admin/guides/{guide}           → ubah
POST /admin/guides/{guide}/publish   → terbitkan
GET  /panduan                        → landing editorial
GET  /panduan/{guide}                → halaman publik
```

## Renderer aman — `app/Support/GuideContentRenderer.php`

Strategi **escape-first**: seluruh konten di-escape (`htmlspecialchars`) lebih
dulu, baru subset sintaks yang diubah menjadi markup.

- Heading `#`/`##`/`###` → `<h2>`/`<h3>`/`<h4>`.
- Daftar `-` dan `1.`, blockquote `>`, pemisah `---`/`***`.
- Inline: `**tebal**`, `*miring*`, `` `kode` ``.
- Gambar: `![alt](path)` hanya untuk aset **lokal** (`/media/...` atau `media/`);
  hotlink eksternal ditolak (diubah menjadi teks alt).
- Tautan: `[teks](url)` hanya skema `http(s)`, `mailto:`, atau path internal;
  tautan eksternal memakai `target="_blank" rel="noopener noreferrer nofollow"`.
- Embed: `@youtube[VIDEO_ID]` → iframe `https://www.youtube-nocookie.com/embed/{id}`.
  ID wajib 11 karakter `[A-Za-z0-9_-]`.
- URL YouTube mandiri (watch/shorts/youtu.be) pada satu baris dinormalisasi
  menjadi penanda embed sebelum escaping; hanya ID tervalidasi yang dipakai,
  parameter tambahan (`?t=`, dll.) dibuang.
- **Tidak ada** HTML mentah atau iframe arbitrer: tag apa pun menjadi teks.

## Editor Studio (Alpine) — `resources/js/app.js` `guideEditor`

- `wrap`/`prefix`/`insert` menyisipkan markup pada posisi kursor.
- `uploadImage(event)` — kirim `image` + `alt` ke `admin.guides.media.store`,
  sisipkan `![alt](url)` hasil server.
- `insertYoutubeUrl()` — validasi URL, sisipkan `@youtube[ID]`; URL tidak valid
  menampilkan pesan kesalahan.
- `loadPreview()` — POST ke `admin.guides.preview`, memakai `guide_id` untuk
  otorisasi pratinjau create ATAU update.

## Endpoint unggah — `Admin\GuideMediaController`

- Otorisasi: hanya pengguna dengan izin `create` atau `update` `EquipmentGuide`.
- Validasi: `required|file|image|mimetypes:image/jpeg,image/png,image/webp|max:4096`.
- Simpan nama acak 40 karakter pada disk publik (`guide-inline/`), kembalikan
  `path`, `url` (`/media/...`), dan `alt` dengan status `201`.
- Rate limit route: 30 permintaan/menit.

## Konfigurasi

- `gearora.guides.inline_directory` (default `guide-inline`).
- `gearora.guides.inline_max_kb` (default 4096).
- `gearora.guides.cover_mimes` (default `image/jpeg,image/png,image/webp`).

## Bukti uji

| Berkas | Hasil |
|---|---|
| `tests/Feature/GuideMediaUploadTest.php` | 4 passed (8 assertions) |
| `tests/Feature/AdminGuideStudioTest.php` | 9 passed (29 assertions) |
| `tests/Feature/EquipmentGuideTest.php` | 9 passed (23 assertions) |

Bukti detail di `.docs/PHASE_9_GUIDE_CMS_TEST_REPORT.md`.
