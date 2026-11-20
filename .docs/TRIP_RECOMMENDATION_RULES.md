# Trip Recommendation Rules

> **Tabel:** `trip_recommendation_rules`
> **Pengelolaan:** Admin → menu "Aturan trip"
> **Fase:** Phase 6

Dokumen ini adalah panduan operasional untuk menyusun aturan rekomendasi. Semua contoh di sini adalah **data**, bukan kode hardcoded.

---

## 1. Struktur Rule

| Field | Arti | Tips |
| --- | --- | --- |
| `name` | Nama aturan | Deskriptif: "Tenda untuk camping 2–4 orang" |
| `description` | Catatan internal | Tidak tampil ke pelanggan |
| `activity` | Aktivitas sasaran | `camping`, `trekking`, `hiking`, `mountaineering`, `picnic`, `overlanding`, atau `semua` |
| `min_duration_days` / `max_duration_days` | Rentang durasi | `max` kosong = tanpa batas |
| `min_participants` / `max_participants` | Rentang peserta | `max` kosong = tanpa batas |
| `required_special_needs` | Kebutuhan wajib | Semua nilai harus dipilih pelanggan |
| `required_experience_levels` | Level pengalaman yang cocok (Phase 9) | JSON array `pemula`/`menengah`/`mahir`; kosong = semua level |
| `product_category_id` | Target kategori | Paling fleksibel untuk katalog yang berubah |
| `product_id` | Target produk | Untuk produk unggulan spesifik |
| `product_variant_id` | Target varian | Paling spesifik (ukuran/tipe tertentu) |
| `service_mode` | `sewa`, `beli`, `semua` | `semua` mengikuti mode pelanggan |
| `recommended_quantity` | Qty dasar | 1..99 |
| `quantity_per_participant` | Qty × peserta | Cocok untuk sleeping bag, matras, headlamp |
| `priority` | Urutan evaluasi | Angka besar dievaluasi lebih dulu |
| `reason_template` | Alasan tampil | Placeholder `:activity`, `:participants`, `:duration`, `:destination` |
| `is_active` | Aktif/nonaktif | Rule nonaktif tidak dibaca engine |

Minimal satu dari `product_variant_id`, `product_id`, `product_category_id` harus terisi.

---

## 2. Strategi Prioritas

Urutan evaluasi: `priority DESC, id ASC`.

Saran pembagian:

| Rentang | Kegunaan |
| --- | --- |
| 900–1000 | Override / kampanye khusus (mis. paket musim hujan) |
| 500–899 | Rule spesifik produk/varian unggulan |
| 100–499 | Rule kategori standar |
| 1–99 | Rule pelengkap / umum |

Karena dedup varian, rule berprioritas tinggi "mengunci" varian lebih dulu. Tempatkan rule paling spesifik di prioritas tinggi.

Contoh:

- **950** — "Paket tenda 4P musim hujan" (varian spesifik)
- **600** — "Tenda untuk camping" (produk/kategori)
- **200** — "Perlengkapan tidur" (kategori, qty per peserta)
- **50** — "Pelengkap umum" (kategori luas)

---

## 3. Contoh Rule

### 3.1 Tenda berdasarkan kategori

```json
{
  "name": "Tenda utama camping",
  "activity": "camping",
  "min_duration_days": 1,
  "min_participants": 1,
  "product_category_id": 3,
  "service_mode": "sewa",
  "recommended_quantity": 1,
  "quantity_per_participant": false,
  "priority": 600,
  "reason_template": "Tenda utama untuk :participants peserta selama :duration hari di :destination."
}
```

### 3.2 Perlengkapan tidur per peserta

```json
{
  "name": "Sleeping bag per peserta",
  "activity": "semua",
  "min_duration_days": 2,
  "min_participants": 2,
  "product_category_id": 5,
  "service_mode": "semua",
  "recommended_quantity": 1,
  "quantity_per_participant": true,
  "priority": 300,
  "reason_template": "Satu unit per peserta (:participants orang) untuk tidur yang layak."
}
```

### 3.3 Regel khusus cuaca dingin

```json
{
  "name": "Layer hangat untuk cuaca dingin",
  "activity": "semua",
  "min_duration_days": 2,
  "min_participants": 1,
  "required_special_needs": ["cold_weather"],
  "product_category_id": 8,
  "service_mode": "sewa",
  "recommended_quantity": 1,
  "priority": 450,
  "reason_template": "Dingin di :destination — bawa layer hangat untuk :participants peserta."
}
```

### 3.4 Perlindungan hujan

```json
{
  "name": "Rain cover & poncho",
  "activity": "trekking",
  "required_special_needs": ["rain_protection"],
  "product_category_id": 11,
  "service_mode": "beli",
  "recommended_quantity": 1,
  "quantity_per_participant": true,
  "priority": 420,
  "reason_template": "Proteksi hujan untuk :participants peserta di jalur :destination."
}
```

### 3.5 Grup besar

```json
{
  "name": "Kit grup besar",
  "activity": "camping",
  "min_participants": 8,
  "required_special_needs": ["large_group"],
  "product_category_id": 4,
  "service_mode": "sewa",
  "recommended_quantity": 1,
  "priority": 350,
  "reason_template": "Kapasitas grup besar untuk :participants peserta."
}
```

### 3.6 Varian spesifik (override)

```json
{
  "name": "Tenda Arunika 4P (varian standar)",
  "activity": "camping",
  "min_participants": 3,
  "max_participants": 5,
  "product_variant_id": 21,
  "service_mode": "sewa",
  "recommended_quantity": 1,
  "priority": 950,
  "reason_template": "Pilihan pas untuk :participants peserta."
}
```

---

## 4. Matriks Perilaku

| Input pelanggan | Rule | Hasil |
| --- | --- | --- |
| kegiatan `camping`, rule `camping` | cocok | tampil |
| kegiatan `hiking`, rule `camping` | tidak | tidak tampil |
| kegiatan apa pun, rule `semua` | cocok | tampil |
| durasi 2, rule min 3 | tidak | tidak tampil |
| durasi 4, rule max 5 | cocok | tampil |
| peserta 10, rule max 6 | tidak | tidak tampil |
| butuh `cold_weather`, rule butuh `rain_protection` | tidak | tidak tampil |
| mode `beli`, rule `sewa` | tidak | tidak tampil |
| mode `beli`, produk rental-only | tidak lolos eligible | tidak tampil |
| tanggal sewa lengkap, stok habis | availability gagal | dibuang |
| tanpa tanggal, mode `sewa` | availability tidak dicek | tampil dengan `available = null` |

---

## 5. Alasan yang Baik

Alasan harus menjelaskan **mengapa gear ini relevan**, bukan menjanjikan keselamatan.

Baik:

- "Tenda utama untuk 4 peserta selama 3 hari di Bromo."
- "Satu set tidur per peserta agar tetap hangat di suhu dingin."
- "Proteksi hujan untuk 6 peserta di jalur basah."

Hindari:

- "Gear ini menjamin Anda aman."
- "Pasti tersedia saat Anda datang."
- "Satu-satunya pilihan yang cocok."

---

## 6. Operasional

1. Buat kategori produk yang stabil sebelum membuat rule kategori.
2. Utamakan `product_category_id` agar rule tidak patah saat produk baru ditambah.
3. Gunakan `product_variant_id` hanya untuk override jangka pendek.
4. Set `is_active = false`, jangan hapus, bila rule ingin dihentikan sementara agar jejak audit tetap ada.
5. Uji dengan mode `sewa` dan `beli`; pastikan produk `Rental` tidak muncul di mode `beli`.
6. Periksa hasil di `/rekomendasi-trip` sebelum mengaktifkan rule untuk publik.

---

## 7. Kesalahan Umum

| Gejala | Penyebab |
| --- | --- |
| Rule tidak muncul | `is_active = false`, produk nonaktif, atau varian nonaktif |
| Produk tidak muncul di mode beli | `sale_price` kosong / `ProductType::Rental` |
| Hasil berubah antar-permintaan | ada rule ganda dengan prioritas sama; gunakan prioritas unik |
| Alasan tidak tersubstitusi | placeholder salah tulis (harus `:participants`, bukan `{participants}`) |
| Item hilang saat tanggal diisi | stok tidak cukup pada periode tersebut (perilaku benar) |
| Item tetap tampil tanpa tanggal | ketersediaan tidak dapat dipastikan; UI menandai belum dicek |

---

## 8. Seeder Demo

`DemoTripRulesSeeder` membuat dua rule contoh (tenda & perlengkapan tidur) yang mengacu pada kategori produk demo. Seeder hanya berjalan di environment `local`/`testing` dan idempotent melalui `updateOrCreate`.