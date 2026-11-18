# Operasional Rental (Overdue & Status Order) — GEARORA Phase 5

## 1. Perhitungan Keterlambatan — `RentalOverdueService`

Dasar: `RENTAL_POLICY.md` §4.

Aturan:
- Batas kembali: pukul **20:00** (config `gearora.rental.return_deadline`) pada `end_date`.
- **Grace period 60 menit** (config `gearora.late_fee.grace_minutes`) → batas efektif 21:00.
- Setelah grace:
  - lewat ≤ 24 jam → dihitung **1 hari**;
  - setiap 24 jam berikutnya menambah 1 hari.
- Denda = **100% harga sewa harian** × jumlah hari terlambat.
- Harga sewa harian = `subtotal / duration_days` (dari **snapshot order**, bukan katalog terkini).
- Terlambat > **7 hari** (config `gearora.late_fee.max_days`) → masuk proses **barang hilang** (`is_lost = true`).

Output `OverdueResult`: `isOverdue`, `daysLate`, `lateFee`, `isLost`, `deadline`, `note`.

Dihitung **on-demand** (tanpa cron/worker), memakai `Asia/Jakarta`.

## 2. Status Order (`RentalOrderStatus`)

| Status | Arti | Transisi manual | Workflow-only |
|---|---|---|---|
| `pending_payment` | Belum ada pembayaran terverifikasi | `cancelled` | — |
| `payment_review` | Menunggu verifikasi | `pending_payment`, `confirmed`*, `cancelled` | — |
| `confirmed` | Pembayaran terverifikasi | `ready_for_pickup`, `cancelled` | — |
| `ready_for_pickup` | Siap diserahkan | `cancelled` | `picked_up` |
| `picked_up` | Sedang disewa | — | `returned` |
| `returned` | Unit kembali, menunggu inspeksi | — | `completed` |
| `completed` | Selesai | — | — |
| `cancelled` | Dibatalkan | — | — |

\* `confirmed` memerlukan pembayaran `verified` (precondition).

### Aturan penting
- **`pending_payment → confirmed` langsung DIHAPUS.** Konfirmasi hanya lewat pembayaran terverifikasi.
- `picked_up`, `returned`, `completed` adalah **workflow-only target**: hanya dapat dicapai via service operasional (`viaWorkflow: true`). Dropdown admin memakai `manualTransitions()` sehingga tidak dapat memilihnya.
- Bypass lama `pending_payment → confirmed` dihapus dari `allowedTransitions()`.

## 3. Precondition Transisi (`RentalOrderService::assertPreconditions`)

| Target | Syarat |
|---|---|
| `confirmed` | Ada pembayaran `verified` |
| `picked_up` | Ada `rental_handovers` status `completed` |
| `returned` | Ada `rental_returns` status `completed` |
| `completed` | Semua assignment `released_at` terisi; tidak ada biaya terbuka |

Transisi memakai `lockForUpdate()` dan memvalidasi ulang status setelah lock (mencegah race).

## 4. Pelepasan Unit (ADR-5.2)

- `RentalOrderService::releaseAssignments()` (pelepasan seluruh unit) hanya dipakai saat order **dibatalkan**.
- Untuk `returned`/`completed`, pelepasan dilakukan **per unit** oleh `InspectionService::finalizeReturn()` setelah inspeksi.

## 5. Dashboard Overdue

- `GET /admin/operations/overdue` — daftar order `picked_up` yang lewat batas, dengan jumlah hari dan estimasi denda.
- Detail order admin menampilkan status overdue (`RentalOrderController@show`).

## 6. Konfigurasi

`config/gearora.php`:
- `rental.return_deadline` (default `20:00`)
- `late_fee.grace_minutes` (default `60`)
- `late_fee.days_multiplier` / tarif harian dari snapshot
- `late_fee.max_days` (default `7`)
