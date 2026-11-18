# Phase 6 — Technical Design (Trip Planner & Equipment Guides)

> **Produk:** GEARORA — Outdoor Equipment Rental Platform
> **Fase:** Phase 6 — Trip Planner & Equipment Guides
> **Status:** Implemented
> **Tanggal:** 18 November 2026
> **Acuan:** `PRD_GEARORA.md`, `ARCHITECTURE_GEARORA.md`, `ROADMAP_GEARORA.md`

Dokumen ini menjelaskan desain teknis Phase 6 yang sudah diimplementasikan. Prinsip utama: **deterministik**, **data-driven**, **tanpa LLM**, dan **tanpa data hardcoded di kode**.

---

## 1. Ruang Lingkup

| Fitur | Ringkasan |
| --- | --- |
| Trip Planner publik | Form rencana perjalanan → rekomendasi gear berbasis aturan DB |
| Admin aturan rekomendasi | CRUD `trip_recommendation_rules` + aktif/nonaktif |
| Equipment Guides publik | Daftar artikel, filter kategori, pencarian, halaman detail |
| CMS panduan admin | CRUD panduan + kategori + sumber referensi + lifecycle publish |
| Keamanan konten | Renderer Markdown aman (escape HTML), upload cover tervalidasi |
| Disclaimer keselamatan | Selalu tampil di halaman Trip Planner |

Di luar lingkup: rekomendasi berbasis AI/LLM, data cuaca/kondisi jalur real-time, prediksi ketersediaan tanpa tanggal.

---

## 2. Skema Database

### 2.1 `trip_recommendation_rules`

| Kolom | Tipe | Catatan |
| --- | --- | --- |
| `id` | bigint PK | |
| `name` | string(160) | Nama aturan, tampil di admin |
| `description` | text nullable | Catatan internal |
| `activity` | string(32) | Nilai `TripActivity` termasuk sentinel `semua` |
| `min_duration_days` / `max_duration_days` | unsigned smallint | `max` nullable = tanpa batas |
| `min_participants` / `max_participants` | unsigned smallint | `max` nullable |
| `required_special_needs` | json nullable | Daftar `TripSpecialNeed`; semua harus terpenuhi |
| `product_category_id` | FK nullable → `categories` | Target kategori (`nullOnDelete`) |
| `product_id` | FK nullable → `products` | Target produk |
| `product_variant_id` | FK nullable → `product_variants` | Target varian spesifik |
| `service_mode` | string(16) | `sewa`, `beli`, `semua` |
| `recommended_quantity` | unsigned smallint | Default 1 |
| `quantity_per_participant` | boolean | Bila true: qty = qty × peserta |
| `priority` | integer | Urut menurun; tie-break `id` menaik |
| `reason_template` | string(255) nullable | Placeholder `:activity`, `:participants`, `:duration`, `:destination` |
| `is_active` | boolean | Hanya rule aktif dibaca engine |
| `created_by` / `updated_by` | FK nullable → `users` | Audit |

Validasi Form Request memastikan minimal satu target gear terisi (`product_variant_id`, `product_id`, atau `product_category_id`).

### 2.2 `guide_categories`

`id`, `name`, `slug` (unik), `description` nullable, `sort_order`, `is_active`, timestamps.

### 2.3 `equipment_guides`

| Kolom | Tipe | Catatan |
| --- | --- | --- |
| `id` | bigint PK | |
| `guide_category_id` | FK nullable → `guide_categories` | `nullOnDelete` |
| `title` | string(180) | |
| `slug` | string(200) unik | Stabil saat update (lihat §5.4) |
| `excerpt` | string(320) nullable | Ringkasan kartu |
| `content` | longText | Markdown subset aman |
| `content_format` | string(16) | Default `markdown` |
| `cover_image_path` | string nullable | Path disk publik |
| `status` | string(16) | `draft`, `published`, `archived` |
| `author_id` | FK nullable → `users` | |
| `publisher_id` | FK nullable → `users` | Diisi saat publish |
| `published_at` | timestamp nullable | Wajib untuk tampil publik |
| `seo_title` / `seo_description` | string nullable | Metadata |
| `deleted_at` | timestamp nullable | Soft delete |

### 2.4 `guide_sources`

`id`, `equipment_guide_id` (FK cascade), `source_title`, `publisher` nullable, `source_url` nullable, `accessed_at` nullable, `notes` nullable, timestamps.

Sumber dipakai untuk transparansi materi panduan; URL eksternal dirender dengan `rel="noopener noreferrer nofollow"`.

---

## 3. Arsitektur Kode

```
app/
  Data/TripPlanData.php
  Enums/{TripActivity,TripSpecialNeed,TripServiceMode,GuideStatus}.php
  Models/{TripRecommendationRule,GuideCategory,EquipmentGuide,GuideSource}.php
  Services/TripRecommendationService.php
  Services/GuideService.php
  Support/GuideContentRenderer.php
  Http/Controllers/PublicSite/{TripPlannerController,GuideController}.php
  Http/Controllers/Admin/{TripRecommendationRuleController,GuideController,GuideCategoryController}.php
  Http/Requests/Admin/{TripRecommendationRuleRequest,GuideRequest,GuideCategoryRequest}.php
  Http/Requests/PublicSite/{TripPlannerRequest,CartRequest}.php
  Policies/{TripRecommendationRulePolicy,EquipmentGuidePolicy,GuideCategoryPolicy}.php
```

Rute publik:

- `GET /rekomendasi-trip` → form
- `POST /rekomendasi-trip` → hasil rekomendasi (throttle)
- `GET /panduan` → daftar panduan (`q`, `kategori`)
- `GET /panduan/{guide:slug}` → detail (hanya published & `published_at <= now`)

Rute admin (prefix `/admin`, middleware auth + permission):

- `trip-recommendations` (resource, parameter `tripRecommendation`)
- `guide-categories` (resource)
- `guides` (resource + `publish`, `unpublish`, `archive`)

---

## 4. Alur Trip Planner

1. Customer mengisi aktivitas, destinasi, durasi, peserta, kebutuhan khusus, mode (`sewa`/`beli`), tanggal opsional.
2. `TripPlannerRequest` memvalidasi seluruh input terhadap enum dan batas `config('gearora.trip.*')`.
3. `TripPlanData` membungkus input yang sudah valid.
4. `TripRecommendationService::recommend()` memuat rule aktif sesuai aktivitas (termasuk `semua`), terurut `priority DESC, id ASC`.
5. Setiap rule dievaluasi: aktivitas, mode, rentang durasi, rentang peserta, kebutuhan khusus.
6. Kandidat gear diambil dari varian/produk/kategori target, hanya produk & varian aktif.
7. Duplikasi varian dibuang (varian pertama menang, mengikuti prioritas rule).
8. Bila mode sewa dan tanggal lengkap, ketersediaan dicek lewat `RentalAvailabilityService`; hasil tidak tersedia dibuang.
9. Kuantitas dihitung (`recommended_quantity`, opsional × peserta, dibatasi 1..99).
10. Alasan dihasilkan dari `reason_template` (fallback default) dengan substitusi placeholder.
11. Maksimum hasil = `config('gearora.trip.max_results')` (default 12).

Detail aturan dan contoh konfigurasi: `TRIP_RECOMMENDATION_ENGINE.md` dan `TRIP_RECOMMENDATION_RULES.md`.

---

## 5. Equipment Guides CMS

### 5.1 Lifecycle

```
draft ──publish──► published ──unpublish──► draft
  │                    │
  └──archive──► archived ◄──archive────────┘
```

- `publish()` mengisi `publisher_id` dan `published_at` (bila belum ada).
- `unpublish()` mengembalikan ke `draft` dan mengosongkan `published_at`.
- `archived` tidak pernah tampil publik meski `published_at` terisi.
- Semua aksi dicatat melalui `ActivityLogger`.

### 5.2 Kategori

Kategori dikelola terpisah (`guide_categories.manage`), mendukung urutan tampil dan status aktif. Kategori nonaktif tidak tampil di filter publik.

### 5.3 Sumber

Sumber disimpan per panduan (`guide_sources`), boleh lebih dari satu. Metadata minimal: judul sumber; URL opsional; tanggal akses; catatan.

### 5.4 Slug Stabil

`Admin\GuideRequest::prepareForValidation()`:

- slug kosong + panduan sudah published → slug lama dipertahankan;
- slug kosong + panduan draft → slug dibuat dari judul;
- slug eksplisit dinormalisasi dan divalidasi unik.

Tujuannya menjaga URL publik tidak berubah saat judul diedit.

### 5.5 Keamanan Konten

`GuideContentRenderer::render()`:

1. Normalisasi line ending.
2. `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, UTF-8)` pada **seluruh** konten.
3. Baru kemudian mengenali subset Markdown: heading `#`/`##`/`###`, daftar `-`/`1.`, paragraf, `**bold**`, `` `code` ``.
4. Tidak ada HTML mentah, script, iframe, atau atribut event yang lolos.

`GuideContentRenderer::plain()` dipakai untuk meta/SEO/excerpt bila perlu.

### 5.6 Upload Cover

- MIME diizinkan: `image/jpeg`, `image/png`, `image/webp` (`gearora.guides.cover_mimes`).
- Ukuran maksimum: `gearora.guides.cover_max_kb` (default 4096 KB).
- Disimpan di disk `gearora.guides.cover_disk` (default `public`) folder `guide-covers`.
- Nama file dibuat acak oleh `Storage::putFile`; nama asli tidak dipakai.
- Cover lama dihapus saat diganti/dihapus.

---

## 6. Otorisasi (RBAC)

| Permission | Owner | Admin | Warehouse | Customer |
| --- | --- | --- | --- | --- |
| `trip_recommendations.view/create/update/delete` | ✔ | ✔ | — | — |
| `guides.view/create/update` | ✔ | ✔ | — | — |
| `guides.publish`, `guides.archive`, `guides.delete` | ✔ | ✔ | — | — |
| `guide_categories.manage` | ✔ | ✔ | — | — |

Customer hanya mengakses halaman publik. Navigasi admin menampilkan menu terkait hanya bila permission dimiliki.

---

## 7. Disclaimer Keselamatan

Halaman Trip Planner selalu menampilkan blok "Catatan keselamatan" yang menegaskan bahwa:

- rekomendasi bersifat saran, bukan jaminan;
- kondisi lapangan, cuaca, dan jalur harus diverifikasi sendiri;
- keputusan membawa gear tetap tanggung jawab pengguna.

Tidak ada klaim "pasti aman" atau "tersedia" tanpa pengecekan tanggal.

---

## 8. Konfigurasi

`config/gearora.php`:

```php
'trip' => [
    'max_results' => 12,
    'max_duration_days' => 60,
    'max_participants' => 100,
],
'guides' => [
    'cover_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'cover_max_kb' => 4096,
    'cover_disk' => 'public',
    'cover_directory' => 'guide-covers',
    'max_content_kb' => 200,
],
```

Environment terkait: `GEARORA_TRIP_MAX_RESULTS`, `GEARORA_TRIP_MAX_DAYS`, `GEARORA_TRIP_MAX_PARTICIPANTS`, `GEARORA_GUIDE_COVER_MAX_KB`, `GEARORA_GUIDE_COVER_DISK`, `GEARORA_GUIDE_CONTENT_MAX_KB`.

---

## 9. Pengujian

| Berkas | Cakupan |
| --- | --- |
| `tests/Feature/TripPlannerTest.php` | Halaman form, validasi, hasil, disclaimer |
| `tests/Feature/TripRecommendationRuleTest.php` | Matching rule, dedup, prioritas, mode, kuantitas |
| `tests/Feature/EquipmentGuideTest.php` | Visibilitas publik, XSS, pencarian, sumber, slug |
| `tests/Feature/AdminEquipmentGuideTest.php` | CRUD, publish lifecycle, upload, RBAC |
| `tests/Feature/AdminTripRecommendationTest.php` | CRUD aturan, validasi, RBAC |

Rencana lengkap: `PHASE_6_TEST_PLAN.md`.