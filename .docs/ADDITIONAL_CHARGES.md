# Biaya Tambahan (Additional Charges) — GEARORA Phase 5

## 1. Dasar

Merujuk `RENTAL_POLICY.md` §6 (Kerusakan, Kehilangan, Biaya Tambahan).

Biaya tambahan tidak pernah ditagihkan otomatis tanpa persetujuan; dibuat sebagai pengajuan lalu disetujui.

## 2. Tipe (`AdditionalChargeType`)

`late_fee`, `damage`, `missing_accessory`, `lost_item`, `cleaning`, `other`.

## 3. Status (`AdditionalChargeStatus`)

| Status | Arti |
|---|---|
| `pending_approval` | Diajukan, menunggu persetujuan |
| `approved` | Disetujui, belum ada potongan deposit |
| `partially_settled` | Sebagian sudah dibayar/dipotong |
| `offset_from_deposit` | Seluruhnya ditutup dari deposit |
| `paid` | Lunas |
| `rejected` | Ditolak |
| `cancelled` | Dibatalkan |

## 4. Struktur (`additional_charges`)

| Kolom | Arti |
|---|---|
| `charge_code` | `CHG-YYYYMMDD-XXXXX` |
| `rental_order_id`, `rental_asset_id`, `inspection_id` | Kaitan |
| `type`, `reason` | Jenis & alasan |
| `amount` | Nominal (integer) |
| `deposit_offset_amount` | Bagian yang ditutup dari deposit |
| `outstanding_amount` | Sisa tagihan pelanggan |
| `evidence_path` | Bukti (mis. foto kerusakan) |
| `status`, `payment_status` | Status |
| `created_by`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `notes` | Audit |

## 5. Alur (`AdditionalChargeService`)

1. `create(order, data, actor)` → `pending_approval`, `outstanding_amount = amount`.
2. `approve(charge, approver)`:
   - potong saldo deposit lebih dulu (`DepositService::deduct`);
   - `offset = dipotong`, `outstanding = amount − offset`;
   - status `offset_from_deposit` (sisa 0) / `partially_settled` (ada potongan) / `approved` (tanpa potongan).
3. `recordPayment(charge, amount, actor)` → kurangi `outstanding_amount`, status `paid` bila lunas.
4. `reject(charge, actor, note)` → `rejected`.

Semua operasi idempotent dan dalam transaksi DB dengan lock.

## 6. Aturan Integritas

- Biaya **tidak** mengubah saldo deposit secara langsung; selalu melalui ledger deposit.
- Order **tidak dapat `completed`** selama ada biaya berstatus `pending_approval`/`approved`/`partially_settled`.
- Nominal wajib > 0 dan alasan wajib diisi.

## 7. UI & Izin

- `GET /admin/charges` — daftar + filter.
- Aksi: ajukan, setujui & potong deposit, tolak, catat bayar.
- Izin: `charges.view`, `charges.create`, `charges.approve`, `charges.reject`.
