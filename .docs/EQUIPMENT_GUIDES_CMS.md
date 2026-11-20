# Equipment Guides CMS

> **Modul:** Panduan perlengkapan (publik) + CMS (admin)
> **Fase:** Phase 6
> **Permission:** `guides.*`, `guide_categories.manage`

---

## 1. Tujuan

Menyediakan artikel panduan perlengkapan yang:

- dikelola sepenuhnya lewat admin (tanpa deploy);
- aman dari stored XSS;
- memiliki sumber referensi yang transparan;
- mengikuti lifecycle draft → published → archived;
- dapat dicari dan difilter oleh pengunjung.

---

## 2. Data

### 2.1 Panduan (`equipment_guides`)

Field utama: `title`, `slug`, `guide_category_id`, `excerpt`, `content`, `content_format`, `cover_image_path`, `status`, `is_featured`, `author_id`, `publisher_id`, `published_at`, `seo_title`, `seo_description`.

`is_featured` (Phase 9) menandai panduan unggulan di landing publik. Tidak mengubah lifecycle.

### 2.2 Kategori (`guide_categories`)

`name`, `slug`, `description`, `sort_order`, `is_active`. Kategori nonaktif tidak muncul di filter publik, tetapi tetap tersimpan untuk arsip.

### 2.3 Sumber (`guide_sources`)

`source_title` (wajib bila ada sumber), `publisher`, `source_url`, `accessed_at`, `notes`. Maksimum 20 sumber per panduan (validasi).

---

## 3. Alur Admin

### 3.1 Membuat Panduan

1. Buka **Admin → Panduan → Tambah**.
2. Isi judul, kategori, ringkasan, konten Markdown.
3. Opsional: unggah cover JPG/PNG/WEBP (≤ `gearora.guides.cover_max_kb`).
4. Opsional: isi metadata SEO dan sumber.
5. Centang **Tampilkan sebagai panduan unggulan** bila perlu.
6. Pilih status `draft` atau `published`, simpan.

Sejak Phase 9, editor menyediakan toolbar format dan pratinjau langsung (`admin.guides.preview`). Pratinjau memakai `GuideContentRenderer` yang sama dengan halaman publik, jadi hasil pratinjau identik dengan tampilan publik dan tetap aman.

### 3.2 Mengubah Panduan

- Mengubah judul **tidak** mengubah slug yang sudah ada, kecuali slug baru diisi eksplisit.
- Mengganti cover akan menghapus cover lama dari disk.
- Menyimpan sumber akan menyinkronkan daftar sumber (tambah/ubah/hapus sesuai input).

### 3.3 Publikasi

| Aksi | Efek |
| --- | --- |
| `publish` | status `published`, `published_at = now()` bila kosong, `publisher_id = aktor` |
| `unpublish` | status `draft`, `published_at = null` |
| `archive` | status `archived`; tidak tampil publik meski punya `published_at` |

Semua aksi dicatat di `activity_logs`.

### 3.4 Menghapus

Panduan memakai soft delete (`deleted_at`). Cover dihapus dari disk saat panduan dihapus permanen lewat service. Perlu permission `guides.delete`.

---

## 4. Halaman Publik

### 4.1 Daftar (`GET /panduan`)

- Hanya `status = published` **dan** `published_at <= now()`.
- Filter kategori: `?kategori={slug}`.
- Pencarian: `?q={kata}` pada judul dan konten.
- Urut: `published_at DESC`, tie-break `id DESC`.
- Kategori aktif ditampilkan sebagai chip filter.
- Sejak Phase 9: hero editorial, section **Panduan unggulan** (maks. 3, `is_featured`), grid kartu dengan fallback cover, dan estimasi waktu baca.

### 4.2 Detail (`GET /panduan/{slug}`)

- 404 untuk draft, archived, atau `published_at` di masa depan.
- Menampilkan cover, judul, kategori, tanggal publikasi, konten, dan daftar sumber.
- Sumber dengan URL dirender sebagai tautan `target="_blank"` + `rel="noopener noreferrer nofollow"`.
- Sejak Phase 9: lede ringkasan, waktu baca, panduan terkait (kategori sama), meta description/SEO dari `seo_title`/`seo_description`, dan embed/gambar aman dari renderer.

---

## 5. Keamanan Konten

### 5.1 Renderer

`App\Support\GuideContentRenderer`:

1. Escape seluruh HTML (`htmlspecialchars`).
2. Baru mengenali subset Markdown: heading, daftar, blockquote, divider, paragraf, bold, italic, inline code.
3. Tautan hanya `http(s)`, `mailto:`, atau path internal; skema lain dibuang (teks tetap tampil).
4. Gambar inline hanya aset lokal `media/...`; URL eksternal/hotlink dibuang.
5. Embed hanya `@youtube[VIDEO_ID]` (ID 11 karakter) → `https://www.youtube-nocookie.com/embed/{id}`.
6. Tidak ada HTML mentah atau iframe arbitrer yang lolos.

Dengan pendekatan escape-first, payload seperti `<script>alert(1)</script>` tampil sebagai teks, bukan dieksekusi.

### 5.1.1 Pratinjau Admin

`POST /admin/panduan/pratinjau` (`admin.guides.preview`) mengembalikan `{ html, minutes }` dari renderer yang sama. Endpoint dilindungi permission create panduan dan hanya mengembalikan HTML hasil render aman.

### 5.2 Upload

- `image` + `mimetypes` (`image/jpeg`, `image/png`, `image/webp`).
- Ukuran dibatasi `cover_max_kb` untuk sampul dan `inline_max_kb` untuk gambar inline.
- Disimpan dengan nama acak via `Storage::putFile`.
- Disk dikonfigurasi (`public` default); path lama dihapus saat diganti.
- Gambar inline diunggah lewat `POST /admin/guides/media` (throttle 30/menit) dan hanya untuk staf dengan izin create/update panduan. Respons berupa `path`, `url` (`/media/...`), dan `alt`.

### 5.3 Otorisasi

- `GuidePolicy`/`EquipmentGuidePolicy` memeriksa permission untuk create/update/publish/archive/delete.
- Customer tidak memiliki akses admin; halaman publik tetap terbuka.
- Navigasi admin hanya menampilkan menu yang diizinkan.

---

## 6. SEO

- `seo_title` dan `seo_description` opsional; fallback ke `title` dan `excerpt`.
- URL memakai slug stabil sehingga backlink tidak rusak saat judul diedit.
- Tidak ada klaim medis/keselamatan absolut di konten contoh.

---

## 7. Konfigurasi

```php
'guides' => [
    'cover_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'cover_max_kb' => 4096,
    'cover_disk' => 'public',
    'cover_directory' => 'guide-covers',
    'max_content_kb' => 200,
],
```

Environment: `GEARORA_GUIDE_COVER_MAX_KB`, `GEARORA_GUIDE_COVER_DISK`, `GEARORA_GUIDE_CONTENT_MAX_KB`.

---

## 8. Seeder Demo

`DemoGuidesSeeder` membuat tiga kategori dan dua panduan published (bertanda "Demo"). Hanya berjalan di `local`/`testing` dan idempotent via `updateOrCreate`.

---

## 9. Pengujian

| Berkas | Cakupan |
| --- | --- |
| `EquipmentGuideTest` | Visibilitas publik, draft/arsip 404, XSS, pencarian, filter kategori, sumber, slug |
| `AdminEquipmentGuideTest` | Create draft/published, slug stabil, upload valid/invalid, publish/unpublish/archive, RBAC, kategori |

---

## 10. Checklist Operasional

- [ ] Kategori dibuat sebelum panduan.
- [ ] Konten tidak berisi HTML mentah yang diharapkan aktif (renderer tidak mengeksekusi HTML).
- [ ] Cover diuji di browser (rasio & ukuran).
- [ ] Sumber dicantumkan bila materi mengutip pihak lain.
- [ ] Panduan dipublikasikan hanya setelah review konten.
- [ ] Panduan usang di-`archive`, bukan dihapus, agar jejak tetap ada.