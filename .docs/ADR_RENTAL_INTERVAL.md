# ADR — Interval Sewa & Anti-Double-Booking

> **Status:** Accepted
> **Tanggal:** 2026-11-17
> **Konteks fase:** Phase 4 (Rental Availability & Order MVP)
> **Keputusan terkait:** representasi periode sewa, blocking date, dan pengecekan ketersediaan.

---

## 1. Konteks

GEARORA memakai sewa berbasis tanggal (date-only, tanpa jam) dengan aturan:

- Sewa minimum 1 hari, maksimum 30 hari.
- Horizon pemesanan 90 hari ke depan.
- Satu lokasi, ambil sendiri (self pickup).

Masalah muncul saat pelanggan menyewa **hanya satu hari** (`start_date == end_date`). Jika periode disimpan apa adanya sebagai `[start_date, end_date]` dan dibandingkan dengan overlap tertutup (inclusive), dua order pada tanggal yang sama akan teranggap tidak konflik, atau justru unit yang sama bisa dipakai dua kali.

Contoh: pelanggan A menyewa tanggal 20 (selesai tanggal 20), pelanggan B menyewa tanggal 20 juga. Dengan interval tertutup yang salah, keduanya bisa lolos validasi di hari yang sama.

Selain itu, pengecekan ketersediaan tidak boleh hanya mengandalkan validasi frontend. Race condition dua request bersamaan harus dicegah di level database.

## 2. Keputusan

### 2.1 Interval setengah terbuka `[start, end)`

Periode sewa diperlakukan sebagai interval setengah terbuka: tanggal `start_date` dihitung, tanggal `end_date` **tidak** dihitung. Dua order dianggap bentrok jika:

```
existing.start_date < candidate.blocking_end_date
AND existing.blocking_end_date > candidate.start_date
```

### 2.2 Blocking date untuk sewa satu hari

Order menyimpan kolom `blocking_end_date` yang dihitung:

```
blocking_end_date = max(end_date, start_date + 1 hari)
```

Ini menjamin sewa satu hari tetap memblokir satu hari penuh, sementara sewa multi-hari memblokir `[start_date, end_date)`.

Konsekuensi:

- Order `20 → 20` memblokir tanggal 20 saja.
- Order `20 → 22` memblokir tanggal 20 dan 21 (bukan 22).
- Order berikutnya yang mulai tepat di `end_date` order sebelumnya **boleh** memakai unit yang sama (reuse bersebelahan). Ini perilaku yang diuji di `RbacAndBookingTest::test_adjacent_half_open_period_can_reuse_asset`.

### 2.3 Perhitungan ketersediaan

Ketersediaan dihitung dari jumlah unit fisik yang dapat disewa (`rental_assets` berstatus tersedia) dikurangi jumlah assignment pada order berstatus **blocking**:

```
blockingStatuses = pending_payment, payment_review, confirmed, ready_for_pickup, picked_up
```

Assignment dilepas saat order berpindah ke `cancelled`, `returned`, atau `completed`.

### 2.4 Anti-overbooking di dalam transaksi + row locking

Pengecekan ketersediaan dijalankan **ulang di dalam `DB::transaction`** saat order dibuat, dengan `lockForUpdate` pada baris varian. Order tidak dibuat jika stok tidak lagi cukup. Validasi frontend hanya untuk UX, bukan pengaman.

Unit yang ditetapkan dipilih deterministik berdasarkan `asset_code`.

## 3. Alternatif yang Ditolak

| Alternatif | Alasan ditolak |
| --- | --- |
| Interval tertutup penuh `[start, end]` | Melebih-blokir: tanggal `end_date` tidak boleh dipakai order berikutnya. |
| Menyimpan jam (timestamp) | Di luar cakupan MVP; kebijakan sewa hanya tanggal. |
| Pengecekan ketersediaan hanya di frontend | Rentan race condition; dua request bersamaan bisa overbooking. |
| Mengunci seluruh tabel order | Terlalu kasar; menurunkan throughput tanpa perlu. |

## 4. Konsekuensi

- Kolom tambahan `blocking_end_date` disimpan di `rental_orders` dan dipakai pada `scopeOverlapping`.
- Setiap perubahan aturan blocking harus memperbarui `RentalOrderStatus::blockingStatuses()` dan perhitungan `blocking_end_date` secara bersamaan.
- Pengujian concurrency nyata memerlukan MySQL. Test suite memakai sqlite `:memory:` sehingga hanya menguji logika; verifikasi race condition sebenarnya dilakukan manual di MySQL/lingkungan produksi (lihat catatan di laporan fase).

## 5. Referensi

- `app/Support/RentalPeriod.php` — normalisasi dan validasi periode.
- `app/Services/RentalAvailabilityService.php` — perhitungan ketersediaan.
- `app/Services/RentalOrderService.php` — pembuatan order dalam transaksi.
- `app/Models/RentalOrder.php` — `scopeOverlapping`, `blocking_end_date`.
- `tests/Feature/RbacAndBookingTest.php` — uji overbooking dan reuse bersebelahan.
