# Trip Recommendation Engine

> **Komponen:** `App\Services\TripRecommendationService`
> **Fase:** Phase 6
> **Sifat:** Deterministik, data-driven, tanpa LLM

---

## 1. Tujuan

Memberi rekomendasi gear yang **dapat dijelaskan** dan **dapat direproduksi**: input yang sama + data rule yang sama selalu menghasilkan output yang sama.

Engine tidak:

- memakai `random`, shuffle, atau sampling;
- memanggil layanan AI/LLM;
- menyimpan daftar produk hardcoded di kode;
- menebak cuaca/kondisi jalur;
- mengklaim ketersediaan tanpa tanggal sewa yang valid.

## 1.1 Update Phase 9 — Level pengalaman dan paket

Sejak Phase 9, engine juga memfilter berdasarkan `experience` (`pemula`, `menengah`, `mahir`) terhadap kolom `required_experience_levels` (JSON, nullable) pada `trip_recommendation_rules`. Array kosong/null berarti aturan cocok untuk semua level. Output setiap hasil kini membawa `supports_rental`, `supports_sale`, `rental_price`, dan `sale_price` agar wizard dapat menawarkan fulfillment `sewa`/`beli` tanpa harga hardcoded di Blade.

Pemilihan gear dari hasil rekomendasi disimpan sementara di session (`trip_package_draft`) dan dihitung ulang server-side oleh `TripPackageCheckoutService` saat checkout. Detail transaksi: [ADR Paket Trip](./ADR_PHASE_9_TRIP_PACKAGE.md).

Engine hanya membaca:

- `trip_recommendation_rules` yang `is_active = true`;
- katalog aktif (`products.status = active`, `product_variants.is_active = true`).

---

## 2. Kontrak Input

`App\Data\TripPlanData`:

| Field | Tipe | Wajib | Batas |
| --- | --- | --- | --- |
| `activity` | string | ✔ | salah satu `TripActivity::values()` |
| `destination` | string | ✔ | 2..120 karakter |
| `location` | ?string | — | ≤160 |
| `description` | ?string | — | ≤1000 |
| `durationDays` | int | ✔ | 1..`gearora.trip.max_duration_days` |
| `participants` | int | ✔ | 1..`gearora.trip.max_participants` |
| `specialNeeds` | string[] | — | subset `TripSpecialNeed::values()` |
| `mode` | `TripServiceMode` | ✔ | `sewa` atau `beli` |
| `startDate` / `endDate` | ?string | — | harus berpasangan; `end >= start` |

Validasi dilakukan `PublicSite\TripPlannerRequest` sebelum data mencapai service.

---

## 3. Kontrak Output

`recommend()` mengembalikan `array<int, array{...}>`:

```php
[
    'rule' => TripRecommendationRule,   // rule pemenang
    'product' => Product,
    'variant' => ProductVariant,
    'quantity' => int,                  // 1..99
    'reason' => string,                 // sudah tersubstitusi
    'mode' => string,                   // sewa|beli
    'availability_checked' => bool,     // true hanya bila tanggal valid + mode sewa
    'available' => ?bool,               // null bila tidak dicek
]
```

Urutan hasil mengikuti urutan rule: `priority DESC, id ASC`, lalu urutan varian (`sort_order`, `id`).

---

## 4. Pipeline

```
TripPlanData
  → muat rule aktif untuk aktivitas (activity = input OR 'semua')
  → urutkan priority DESC, id ASC
  → untuk setiap rule:
       matches(rule, plan)?  ── tidak → skip
       kandidat gear:
         variant_id  → [variant]
         product_id  → varian produk (sort_order, id)
         category_id → produk aktif di kategori + varian aktif
       untuk setiap kandidat:
         sudah pernah tampil? ── ya → skip (dedup varian)
         eligible(product, variant, mode)? ── tidak → skip
         bila mode sewa + tanggal lengkap:
             cek RentalAvailabilityService
             tidak tersedia → skip
         hitung quantity
         bangun reason
         tambahkan hasil
         bila hasil == max_results → berhenti
```

---

## 5. Aturan Pencocokan (`matches`)

Sebuah rule cocok bila **semua** kondisi berikut benar:

1. **Aktivitas** — `rule.activity === plan.activity` atau `rule.activity === 'semua'`.
2. **Mode** — `rule.service_mode` cocok dengan mode pelanggan (`semua` cocok dengan keduanya; lihat `TripServiceMode::matches()`).
3. **Durasi** — `min_duration_days <= duration` dan (`max_duration_days` null atau `duration <= max`).
4. **Peserta** — `min_participants <= participants` dan (`max_participants` null atau `participants <= max`).
5. **Kebutuhan khusus** — setiap nilai di `required_special_needs` ada di `plan.specialNeeds`.

Tidak ada bobot numerik atau skor; hanya boolean match + prioritas.

---

## 6. Kelayakan Gear (`eligible`)

| Mode | Syarat |
| --- | --- |
| `sewa` | produk mendukung rental (`ProductType::Rental`/`Both`) |
| `beli` | produk mendukung sale **dan** harga jual efektif > 0 |
| `semua` | salah satu di atas terpenuhi |

Selalu: `product.status = Active` dan `variant.is_active = true`. Varian tanpa harga jual tidak pernah direkomendasikan untuk mode `beli`.

---

## 7. Deduplikasi Varian

Satu varian hanya muncul sekali. Varian yang lebih dulu ditemukan menang, sehingga **rule berprioritas lebih tinggi menentukan alasan dan kuantitas** untuk varian tersebut.

Konsekuensi desain: bila dua rule menargetkan kategori yang sama, rule prioritas tinggi "mengunci" varian; rule prioritas rendah hanya menambah varian yang belum tampil.

---

## 8. Kuantitas

```
quantity = rule.quantity_per_participant
    ? rule.recommended_quantity * plan.participants
    : rule.recommended_quantity

quantity = clamp(quantity, 1, 99)
```

Batas 99 mencegah input tidak wajar dan menjaga tampilan tetap masuk akal.

---

## 9. Alasan (Reason)

`reason_template` mendukung placeholder:

| Placeholder | Nilai |
| --- | --- |
| `:activity` | label aktivitas (`Camping`, `Hiking`, …) |
| `:participants` | jumlah peserta |
| `:duration` | durasi hari |
| `:destination` | destinasi |

Bila template kosong, dipakai default:

> Cocok untuk aktivitas `:activity` dengan `:participants` peserta selama `:duration` hari.

Tidak ada kalimat yang mengklaim kondisi lapangan.

---

## 10. Ketersediaan

- Hanya dicek bila `mode` mendukung rental **dan** `startDate` + `endDate` terisi.
- Menggunakan `RentalPeriod::from()` + `assertValid(config('gearora.rental'))`.
- Memakai `RentalAvailabilityService::check($variant, $quantity, $period)`.
- Bila tidak tersedia → item dibuang.
- Bila tanggal tidak ada / periode tidak valid → item tetap tampil dengan `availability_checked = false`, `available = null`.

Ini mencegah klaim "tersedia" palsu.

---

## 11. Determinisme & Batas Hasil

- Tidak ada sumber non-deterministik.
- Urutan data dijamin oleh `ORDER BY priority DESC, id ASC` dan `ORDER BY sort_order, id`.
- `max_results` (default 12) memotong hasil lebih awal untuk menjaga performa dan fokus UI.
- Rule nonaktif, produk nonaktif, dan varian nonaktif selalu diabaikan.

---

## 12. Performa

- Satu query rule + eager loading katalog (`category`, `product.variants`, `variant.product`, `primaryImage`).
- Kandidat kategori memakai query produk aktif dengan varian aktif terurut.
- Tidak ada N+1 pada jalur utama.
- Untuk katalog besar, index yang relevan: `trip_recommendation_rules(is_active, activity, priority)`, `products(status, category_id)`, `product_variants(product_id, is_active)`.

---

## 13. Ekstensi yang Aman

Yang boleh ditambah tanpa mengubah kontrak:

- nilai `TripActivity` baru (validasi via enum);
- nilai `TripSpecialNeed` baru;
- rule baru dengan target kategori/produk/varian;
- template alasan baru.

Yang **tidak** boleh tanpa revisi desain:

- memanggil LLM atau layanan eksternal di dalam `recommend()`;
- menambahkan `random`;
- menyimpan daftar produk di kode;
- mengklaim ketersediaan tanpa tanggal.

---

## 14. Pengujian

`tests/Feature/TripRecommendationRuleTest.php` mengunci perilaku:

- filter aktivitas dan sentinel `semua`;
- rentang durasi & peserta;
- kebutuhan khusus wajib;
- prioritas + dedup varian;
- rule nonaktif diabaikan;
- mode beli mengecualikan produk rental-only dan varian tanpa harga;
- produk nonaktif diabaikan;
- kuantitas per peserta;
- substitusi placeholder alasan.