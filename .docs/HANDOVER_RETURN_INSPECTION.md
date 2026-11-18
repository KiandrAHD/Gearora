# Serah Terima, Pengembalian, dan Inspeksi — GEARORA Phase 5

## 1. Status Unit (`RentalAssetStatus`)

| Status | Arti |
|---|---|
| `available` | Tersedia untuk disewa |
| `reserved` | Dipesan untuk order yang akan datang |
| `rented` | Sedang disewa pelanggan |
| `inspection_pending` | Baru dikembalikan, menunggu inspeksi (belum boleh disewa) |
| `maintenance` | Dalam perawatan |
| `damaged` | Rusak |
| `lost` | Hilang |

`isRentable()` = `available` / `reserved`. `isHeld()` = status yang masih tertahan proses rental.

## 2. Siklus Hidup Assignment (`RentalAssetAssignment`)

```
assigned_at → handed_over_at → returned_at → (inspeksi) → released_at
```

- `status`: `assigned`, `handed_over`, `returned`, `inspected`.
- Unit dilepas (`released_at`) **per unit**, saat inspeksi return selesai (ADR-5.2), **bukan** saat order `returned`.

## 3. Serah Terima (Pickup) — `RentalHandoverService`

Prasyarat:
- order berstatus `ready_for_pickup`;
- pembayaran `verified` ada;
- deposit terkumpul penuh (bila `required_amount > 0`);
- `identity_checked` dan `customer_acknowledged` wajib `true`.

Langkah (atomic):
1. Buat `rental_handovers` (kode `HOV-YYYYMMDD-XXXXX`, status `completed`).
2. Per unit: buat `rental_handover_items`, set `handed_over_at`, unit → `rented`, buat inspeksi handover.
3. Transisi order → `picked_up` **via workflow** (`viaWorkflow: true`).

Bila salah satu langkah gagal, transaksi DB di-rollback; status order tidak berubah.

## 4. Pengembalian (Return) — `RentalReturnService`

- Mendukung **partial return**: unit dicatat per unit.
- Unit yang kembali → `rental_return_items`, `returned_at`, unit → `inspection_pending`.
- Order → `returned` **hanya bila seluruh unit** sudah kembali (via workflow).
- Unit **tidak** menjadi `available` sebelum inspeksi selesai.

## 5. Inspeksi — `InspectionService`

- `stage`: `handover` (awal) atau `return` (akhir).
- `Inspection_result`: `pass` / `fail`.
- Foto disimpan di `inspection_photos` (disk privat).
- Saat inspeksi return (`finalizeReturn`):
  - gagal bila assignment sudah `released_at`;
  - `result = pass` → unit → `available`;
  - `result = fail` → unit → `maintenance` / `damaged` / `lost` (sesuai `outcome_status`);
  - assignment → `inspected`, `released_at = now()`.

## 6. Penyelesaian Order — `RentalReturnService::tryComplete`

Order → `completed` (via workflow) hanya bila:
- semua assignment sudah dilepas (`released_at` tidak null);
- tidak ada biaya tambahan berstatus terbuka;
- saldo deposit = 0.

## 7. Kedaluwarsa / Keterlambatan

Lihat `RENTAL_OPERATIONS.md` (`RentalOverdueService`).

## 8. UI Admin

| Route | Fungsi | Izin |
|---|---|---|
| `GET admin/operations/pickups` | Daftar order siap pickup | `rentals.view` |
| `GET admin/operations/pickups/{order}` | Form handover | `rentals.handover` |
| `POST admin/operations/pickups/{order}/handover` | Proses handover | `rentals.handover` |
| `GET admin/operations/returns` | Daftar order aktif | `rentals.view` |
| `GET admin/operations/returns/{order}` | Form penerimaan | `rentals.return` |
| `POST admin/operations/returns/{order}/receive` | Catat pengembalian | `rentals.return` |
| `GET admin/operations/inspections` | Unit menunggu inspeksi | `inspections.view` |
| `POST admin/operations/inspections/{assignment}` | Finalisasi inspeksi | `inspections.create` |
| `GET admin/operations/overdue` | Dashboard overdue | `rentals.view` |
