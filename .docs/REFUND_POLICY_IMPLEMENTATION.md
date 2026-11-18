# Implementasi Kebijakan Refund — GEARORA Phase 5

## 1. Dasar

Merujuk `RENTAL_POLICY.md` §7 (Pembatalan dan Refund):
- Pembatalan > 48 jam sebelum `start_date`: refund biaya sewa **100%**.
- Pembatalan 24–48 jam sebelum `start_date`: refund biaya sewa **50%**.
- Pembatalan < 24 jam / no-show: biaya sewa **tidak** dikembalikan.
- **Deposit selalu dikembalikan penuh** (dikurangi potongan sah dari inspeksi).

## 2. Perhitungan (`RefundCalculationService`)

```php
calculate(RentalOrder $order, $at = null, ?int $rentalPercentOverride = null): RefundBreakdown
```

`RefundBreakdown` memuat: `rental_amount`, `deposit_amount`, `rental_refund`, `deposit_refund`,
`non_refundable`, `total`, `rental_percent`, `policy_note`.

- Sumber nominal: **snapshot order** (`subtotal`, `deposit_total`), bukan katalog terkini.
- `rental_refund = floor(rental_amount × percent / 100)` (pembulatan ke bawah, tidak pernah melebihi kewajiban).
- Deposit selalu `deposit_refund = deposit_amount`.

Konfigurasi: `gearora.refund.full_refund_hours` (default 48), `gearora.refund.half_refund_hours` (default 24).

## 3. Refund sebagai Transaksi Tersendiri

Refund **bukan** sekadar mengubah status pembayaran. Disimpan di `refunds`:

| Kolom | Arti |
|---|---|
| `refund_code` | `RFD-YYYYMMDD-XXXXX` |
| `payment_id`, `payment_attempt_id`, `rental_order_id` | Kaitan |
| `refund_amount`, `rental_amount`, `deposit_amount` | Nominal |
| `refund_method` | `gateway`, `manual_transfer`, `cash`, `deposit_offset` |
| `refund_type` | `rental`, `deposit`, `combined` |
| `status` | `pending_approval`, `approved`, `rejected`, `processing`, `manual_required`, `succeeded`, `failed` |
| `requested_by`, `approved_by`, `processed_by` | Audit |
| `evidence_path`, `notes` | Bukti & catatan |

## 4. Alur (`RefundService`)

1. `requestFromPolicy(order, actor)` atau `request(order, actor, breakdown, method, reason)` → status `pending_approval`.
2. `approve(refund, actor)` → `approved`.
3. `process(refund, actor)`:
   - Bila gateway (metode `midtrans`, attempt sukses, `refund_method = gateway`) → panggil `PaymentGateway::refund()`, status `succeeded`, tandai pembayaran `refunded`/`partially_refunded`.
   - Bila bukan gateway → `manual_required` (admin kembalikan dana manual).
4. `completeManual(refund, actor, evidencePath, note)` → upload bukti pengembalian dana, status `succeeded`, tandai pembayaran.
5. `reject(refund, actor, note)` → `rejected`.

Semua langkah idempotent (memanggil ulang tidak menggandakan efek).

## 5. Penandaan Status Pembayaran

`markPaymentRefunded()` menjumlahkan seluruh refund `succeeded`:
- total refund ≥ `payment.amount` → `refunded`;
- selain itu → `partially_refunded`.

## 6. UI & Izin

- `GET /admin/refunds` — daftar + filter status.
- Aksi: ajukan, setujui, tolak, proses, selesaikan manual.
- Izin: `payments.refund`.

## 7. Catatan

- Refund tidak menghapus riwayat pembayaran atau deposit.
- Nominal refund selalu integer Rupiah.
- Bila tidak ada pembayaran `verified`, refund ditolak (`settledPaymentFor`).
