# Sales Stock Reservation

> **Komponen:** `App\Services\SalesStockService`
> **Tabel:** `sales_stock_reservations`
> **Fase:** Phase 7

---

## 1. Prinsip

1. **Stok fisik hanya berkurang saat pembayaran terkonfirmasi**, bukan saat order dibuat.
2. Order yang belum dibayar **menahan** stok lewat reservasi.
3. Reservasi dan konsumsi **idempotent**: aksi ganda tidak mengubah stok dua kali.
4. Semua perubahan stok menghasilkan `StockMovement` bertipe `sale`.
5. Stok retail memakai `product_variants.stock_quantity`, terpisah dari `rental_assets`.

Rumus:

```
reserved(variant)  = Σ quantity reservasi status "reserved"
available(variant) = max(0, stock_quantity − reserved)
```

---

## 2. Siklus Status Reservasi

```
             order dibuat
                  │
                  ▼
             ┌─────────┐
             │ reserved│
             └────┬────┘
       bayar ✔    │    batal / kedaluwarsa
        ┌─────────┴─────────┐
        ▼                   ▼
  ┌──────────┐       ┌──────────┐
  │ consumed │       │ released │
  └──────────┘       └──────────┘
                     (atau expired)
```

| Status | Menahan stok? | Stok fisik |
| --- | --- | --- |
| `reserved` | ✔ | tidak berubah |
| `consumed` | — | berkurang sekali |
| `released` | — | tidak berubah |
| `expired` | — | tidak berubah |

---

## 3. Operasi

### 3.1 `reservedQuantity(int $variantId): int`

Menjumlahkan reservasi aktif (`status = reserved`). Dipakai untuk menghitung ketersediaan dan laporan inventory.

### 3.2 `availableQuantity(ProductVariant $variant): int`

`max(0, stock_quantity − reservedQuantity)`. Nilai tidak pernah negatif.

### 3.3 `reserveForOrder(SalesOrder $order, array $items): void`

Dipanggil di dalam transaksi checkout yang sudah mengunci varian (`lockForUpdate`).

Validasi per baris:

- produk & varian ada;
- produk `Active`;
- produk mendukung penjualan;
- varian aktif;
- harga jual efektif > 0;
- `quantity <= availableQuantity`.

Bila salah satu gagal → `InvalidArgumentException`, transaksi dirollback, order tidak tersimpan.

Efek: satu baris `sales_stock_reservations` per item, `expires_at = now + gearora.sales.reservation_minutes`.

### 3.4 `consumeForOrder(SalesOrder $order, ?User $actor = null): int`

Dipanggil setelah pembayaran terverifikasi (transfer/tunai/Midtrans).

- Mengunci reservasi aktif lalu varian terkait.
- Bila `stock_quantity < quantity` → exception (data tidak konsisten; perlu investigasi manual).
- Mengurangi `stock_quantity`, membuat `StockMovement(type=sale)`, menandai reservasi `consumed`.
- Mengembalikan jumlah reservasi yang diproses.
- Pemanggilan kedua mengembalikan `0` dan tidak menyentuh stok.

### 3.5 `releaseForOrder(SalesOrder $order, SalesReservationStatus $status, ?User $actor = null): int`

- Hanya menerima `released` atau `expired`.
- Menandai seluruh reservasi aktif sebagai status tersebut + `released_at`.
- **Tidak** mengubah `stock_quantity`.
- Idempotent; pemanggilan kedua mengembalikan `0`.

### 3.6 `activeReservationsForOrder(SalesOrder $order): Collection`

Untuk tampilan admin dan diagnosa.

---

## 4. Titik Panggil

| Kejadian | Operasi |
| --- | --- |
| Checkout retail | `reserveForOrder()` di dalam transaksi + row lock |
| Verifikasi transfer | `consumeForOrder()` via `SalesPaymentService::markSettled()` |
| Catat tunai | idem |
| Webhook Midtrans settlement | idem |
| Order `cancelled` | `releaseForOrder(..., Released)` |
| Order `expired` | `releaseForOrder(..., Expired)` |

`markSettled()` memeriksa status payment terlebih dahulu; bila sudah `verified`, tidak ada efek tambahan.

---

## 5. Anti-Overselling

Tiga lapis pertahanan:

1. **Validasi keranjang** — menolak kuantitas melebihi ketersediaan saat menambah.
2. **Validasi checkout** — diulang di dalam transaksi dengan `lockForUpdate` pada baris varian, sehingga dua checkout bersamaan diproses berurutan.
3. **Validasi konsumsi** — bila stok fisik sudah tidak cukup saat settlement, transaksi gagal dan tidak ada pengurangan parsial.

Tidak ada operasi "set stok = x" dari input client.

---

## 6. Idempotensi

| Operasi | Mekanisme |
| --- | --- |
| `reserveForOrder` | Dipanggil sekali per order (transaksi checkout) |
| `consumeForOrder` | Hanya memproses reservasi `reserved`; setelah `consumed` tidak diproses lagi |
| `releaseForOrder` | Update hanya pada reservasi `reserved` |
| `markSettled` | Early return bila payment sudah `verified` |

Kombinasi ini menahan skenario webhook ganda, klik verifikasi ganda, dan retry manual.

---

## 7. Kasus Tepi

| Kasus | Perilaku |
| --- | --- |
| Pembayaran masuk setelah order `cancelled` | Payment `processing`, stok **tidak** dikonsumsi, butuh tindakan manual |
| Order `expired` lalu dibayar | Sama seperti di atas; reservasi sudah dilepas |
| Stok fisik dikoreksi manual saat reservasi aktif | `available` bisa menjadi 0; checkout baru ditolak |
| Barang rusak/hilang | Gunakan penyesuaian stok terpisah; bukan jalur penjualan |
| Reservasi menggantung | `expires_at` tersimpan; job auto-expire belum diaktifkan |

---

## 8. Laporan Terkait

`ReportService::inventory()` menampilkan:

- `sale_stock` — total `stock_quantity`;
- `reserved_sale_stock` — total reservasi aktif;
- `available_sale_stock` — selisih;
- `sold_quantity` — total kuantitas terjual pada rentang;
- insiden damage/lost.

---

## 9. Pengujian

`tests/Feature/SalesStockReservationTest.php`:

- ketersediaan = stok − reservasi;
- reservasi melebihi stok ditolak;
- konsumsi mengurangi stok tepat sekali dan idempotent;
- pelepasan tidak mengubah stok fisik dan idempotent;
- pelepasan menolak status tidak valid;
- order `expired` melepas reservasi.

`tests/Feature/SalesPaymentTest.php` memverifikasi stok berkurang setelah pembayaran dan tidak bertambah dua kali pada aksi ganda.

---

## 10. Batasan

- Tidak ada multi-gudang; satu lokasi.
- Tidak ada alokasi FIFO/batch.
- Auto-expire order belum dijadwalkan (kolom `expires_at` sudah tersedia).
- Koreksi stok manual tidak melalui service ini; gunakan alur inventory yang ada.