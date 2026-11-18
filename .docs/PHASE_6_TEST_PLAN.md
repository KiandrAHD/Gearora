# Phase 6 — Test Plan (Trip Planner & Equipment Guides)

> **Fase:** Phase 6
> **Status:** Automated suite green
> **Basis:** PHPUnit + Laravel testing helpers, SQLite in-memory (`phpunit.xml`)

---

## 1. Lingkungan

```bash
php artisan test --compact
# atau khusus Phase 6:
php artisan test --compact --filter='TripPlannerTest|TripRecommendationRuleTest|EquipmentGuideTest|AdminEquipmentGuideTest|AdminTripRecommendationTest'
```

- `RefreshDatabase` pada seluruh test Phase 6.
- `withoutVite()` dari `Tests\TestCase`.
- Test admin melakukan `seed(RolePermissionSeeder::class)`.
- Tidak membutuhkan Midtrans maupun DB eksternal.

---

## 2. Matriks Cakupan

| Area | Test | Jenis |
| --- | --- | --- |
| Form publik | `TripPlannerTest` | Feature |
| Engine aturan | `TripRecommendationRuleTest` | Feature/unit-style |
| Panduan publik | `EquipmentGuideTest` | Feature |
| CMS panduan | `AdminEquipmentGuideTest` | Feature |
| CRUD aturan | `AdminTripRecommendationTest` | Feature |

---

## 3. `TripPlannerTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | GET `/rekomendasi-trip` | 200, form tampil, disclaimer keselamatan tampil |
| 2 | POST valid | 200, hasil rekomendasi tampil, destinasi tampil |
| 3 | POST aktivitas tidak dikenal | redirect + error `activity` |
| 4 | POST field kosong | redirect + error validasi |
| 5 | POST valid (revalidasi) | input sebelumnya dipertahankan (`selected`) |
| 6 | Disclaimer pada hasil | selalu tampil |

Catatan: throttle pada POST tidak diuji secara eksplisit di suite ini; konfigurasi throttle ada di `routes/web.php`.

---

## 4. `TripRecommendationRuleTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Rule aktivitas tertentu | hanya cocok untuk aktivitas itu |
| 2 | Rule `semua` | cocok untuk aktivitas apa pun |
| 3 | Durasi di luar rentang | tidak muncul |
| 4 | Peserta di luar rentang | tidak muncul |
| 5 | `required_special_needs` | wajib ada di input |
| 6 | Prioritas + dedup varian | varian prioritas tinggi menang, varian lain menyusul |
| 7 | Rule nonaktif | diabaikan |
| 8 | Mode `beli` + produk rental-only | tidak muncul |
| 9 | Produk nonaktif | tidak muncul |
| 10 | `quantity_per_participant` | qty = qty dasar × peserta |
| 11 | Placeholder `reason_template` | `:destination`, `:participants` tersubstitusi |

---

## 5. `EquipmentGuideTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Daftar panduan | hanya published tampil |
| 2 | Detail draft | 404 |
| 3 | `published_at` masa depan | 404 |
| 4 | XSS di konten | HTML di-escape, tidak ada `<script>` |
| 5 | Pencarian `q` | cocok pada judul & konten |
| 6 | Filter `kategori` | hanya kategori terpilih |
| 7 | Sumber | tampil dengan `rel="noopener noreferrer nofollow"` |
| 8 | Slug stabil | URL `/panduan/{slug}` dapat diakses |
| 9 | Arsip + `published_at` | tetap 404 |

---

## 6. `AdminEquipmentGuideTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Create draft | tersimpan dengan slug otomatis |
| 2 | Create published | `published_at` & `publisher_id` terisi |
| 3 | Ubah judul, slug kosong | slug lama dipertahankan |
| 4 | Upload cover valid | file tersimpan di disk `public` |
| 5 | Upload cover invalid | error validasi `cover` |
| 6 | Publish → unpublish → archive | status berubah sesuai aksi |
| 7 | Customer membuka admin | 403 |
| 8 | Create kategori | slug otomatis, tersimpan |

---

## 7. `AdminTripRecommendationTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Create/update/delete rule | tersimpan, `created_by` terisi, terhapus |
| 2 | Tanpa target gear | error validasi |
| 3 | Aktivitas invalid | error `activity` |
| 4 | `max < min` durasi | error `max_duration_days` |
| 5 | Customer membuka admin | 403 |
| 6 | Index rule | menampilkan nama rule |

---

## 8. Manual QA (Browser)

Checklist yang tidak tercakup unit test:

- [ ] Form Trip Planner nyaman di mobile (≤390px) dan desktop.
- [ ] Checkbox kebutuhan khusus dapat dipilih lebih dari satu.
- [ ] Hasil rekomendasi menampilkan alasan, kuantitas, dan mode dengan jelas.
- [ ] Disclaimer keselamatan terbaca (bukan teks tersembunyi).
- [ ] Daftar panduan: kartu, cover, kategori, pencarian, dan empty state.
- [ ] Detail panduan: tipografi konten, daftar, tautan sumber.
- [ ] Admin: form aturan & panduan tidak overflow di layar kecil.
- [ ] Upload cover menampilkan error yang ramah untuk file salah tipe.
- [ ] Navigasi admin hanya menampilkan menu sesuai role.
- [ ] Kontras warna sesuai design system GEARORA (Forest Green, Mountain Navy, Alpine Blue, Sage Green, Warm Sand, Cloud White).

---

## 9. Kriteria Lulus

- Seluruh test Phase 6 hijau.
- Tidak ada regresi pada suite Phase 0–5 (`php artisan test`).
- `vendor/bin/pint --test` lolos.
- `php artisan route:list --except-vendor` tidak error.
- Tidak ada data hardcoded produk di service.

---

## 10. Batasan

- Ketersediaan sewa hanya diuji lewat perilaku service; tidak ada pengujian beban.
- Throttle publik dikonfigurasi di route, bukan diuji per request.
- Tidak ada pengujian aksesibilitas otomatis (axe); dilakukan manual.