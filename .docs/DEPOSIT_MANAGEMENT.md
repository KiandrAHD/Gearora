# Manajemen Deposit — GEARORA Phase 5

## 1. Prinsip

- Deposit adalah **liabilitas** (uang pelanggan yang ditahan), **bukan pendapatan**.
- Saldo deposit hanya berubah melalui **ledger** `deposit_transactions` (ADR-5.4). Tidak ada mutasi saldo langsung tanpa transaksi.
- Satu order = satu baris `deposits` (dibuat idempotent via `DepositService::ensureForOrder()`).
- Nominal = integer Rupiah penuh.

## 2. Struktur

### `deposits`
| Kolom | Arti |
|---|---|
| `required_amount` | Deposit yang wajib dikumpulkan |
| `collected_amount` | Total yang sudah diterima |
| `deducted_amount` | Total yang sudah dipotong untuk biaya |
| `refunded_amount` | Total yang sudah dikembalikan |
| `forfeited_amount` | Total yang hangus |
| `outstanding_amount` | Sisa kewajiban deposit |
| `status` | Lihat `DepositStatus` |
| `held_at`, `settled_at` | Waktu tahan / selesai |

**Saldo aktif** = `collected_amount − deducted_amount − refunded_amount − forfeited_amount`.

### `deposit_transactions` (ledger)
| Kolom | Arti |
|---|---|
| `transaction_code` | `DPT-YYYYMMDD-XXXXX` |
| `type` | `collected`, `deduction`, `refund`, `forfeiture`, `adjustment` |
| `amount` | Nominal mutasi |
| `balance_after` | Saldo setelah mutasi |
| `reason`, `reference`, `evidence_path` | Konteks |
| `created_by`, `approved_by`, `approved_at` | Jejak audit |

## 3. Operasi (`DepositService`)

| Metode | Efek | Aturan |
|---|---|---|
| `ensureForOrder(order)` | Membuat deposit bila belum ada | `required = order.deposit_total` |
| `collect(deposit, amount, reason, actor, ref)` | Menambah saldo | Tidak boleh melebihi `required_amount` |
| `deduct(deposit, amount, reason, actor, approver, evidence)` | Memotong saldo | Tidak pernah membuat saldo negatif; sisa jadi outstanding |
| `refund(deposit, amount, reason, actor, evidence)` | Mengembalikan saldo | Gagal bila saldo tidak cukup |
| `forfeit(deposit, reason, actor)` | Menghanguskan sisa saldo | Menandai status `forfeited` |
| `settle(deposit)` | Menandai selesai bila saldo 0 | — |

Semua operasi berjalan dalam transaksi DB dengan `lockForUpdate()`.

## 4. Kaitan dengan Pembayaran & Order

- Settlement pembayaran gateway/transfer/tunai → `collect()` sebesar `payment.deposit_amount` (idempotent: tidak menambah dua kali untuk sumber yang sama).
- Unit **tidak boleh diserahkan** bila deposit belum terkumpul penuh (`RentalHandoverService::assertPaidAndDeposit`).
- Order **tidak boleh `completed`** selama masih ada saldo deposit tertahan atau biaya tambahan belum selesai (`RentalReturnService::tryComplete` + `RentalOrderService::assertPreconditions`).

## 5. Alur Khas

1. Pembayaran terverifikasi → deposit `collected`.
2. Handover: deposit dicek harus penuh.
3. Return + inspeksi: biaya kerusakan/kehilangan disetujui → `deduct`.
4. Sisa saldo → `refund` ke pelanggan (bukti wajib).
5. Saldo 0 → order dapat diselesaikan.

## 6. UI Admin

- `GET /admin/deposits` — daftar deposit + saldo.
- `GET /admin/deposits/{deposit}` — ringkasan + ledger + form refund/potong/hangus.
- Izin: `deposits.view`, `deposits.manage`, `deposits.refund`, `deposits.deduct`, `deposits.approve`.
