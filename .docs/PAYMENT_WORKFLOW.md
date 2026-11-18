# Alur Pembayaran (Payment Workflow) — GEARORA Phase 5

## 1. Prinsip

- Satu baris `payments` = satu **kewajiban pembayaran** untuk sebuah order (per metode).
- Uang disimpan sebagai **integer Rupiah penuh** pada kolom baru (`rental_amount`, `deposit_amount`, kolom uang di tabel baru). Kolom `decimal` lama dipertahankan untuk kompatibilitas.
- Deposit **bukan** pendapatan; deposit adalah liabilitas terpisah (lihat `DEPOSIT_MANAGEMENT.md`).
- Efek samping pembayaran terverifikasi dijalankan **sekali** dan **idempotent** (`PaymentService::markSettled()`).
- Riwayat bukti pembayaran **tidak dihapus** (`payment_proofs`).

## 2. Status Pembayaran (`PaymentStatus`)

| Status | Arti | Final? |
|---|---|---|
| `unpaid` | Belum dibayar | tidak |
| `waiting_verification` | Bukti transfer diunggah, menunggu verifikasi | tidak |
| `processing` | Dibayar setelah order final / challenge gateway | tidak |
| `verified` | Dana diterima & dialokasikan | ya |
| `rejected` | Bukti ditolak | tidak (bisa unggah ulang) |
| `expired` | Transaksi kedaluwarsa | ya |
| `failed` | Pembayaran gagal | ya |
| `refunded` | Dana dikembalikan penuh | ya |
| `partially_refunded` | Dana dikembalikan sebagian | tidak |

## 3. Metode Pembayaran (`PaymentMethod`)

| Metode | Gateway? | Manual? | Verifikasi |
|---|---|---|---|
| `transfer` | tidak | ya | Admin (wajib ada bukti) |
| `cash` | tidak | ya | Admin mencatat penerimaan tunai |
| `midtrans` | ya | tidak | Otomatis via webhook |

## 4. Alur Manual

### 4.1 Transfer
1. Order dibuat → `Payment` (method `transfer`, status `unpaid`, `amount = order.total`).
2. Pelanggan unggah bukti (`PaymentService::submitProof`):
   - hanya untuk `transfer`;
   - ditolak bila pembayaran sudah final;
   - menyimpan baris baru di `payment_proofs` (file lama tetap ada);
   - set `status = waiting_verification`, `proof_submitted_at = now()`;
   - bila order `pending_payment` → transisi ke `payment_review`.
3. Admin memverifikasi (`PaymentService::verify`):
   - dilarang untuk gateway;
   - wajib ada bukti; status harus `waiting_verification`;
   - nominal harus cocok dengan total order;
   - → `markSettled()`.
4. Admin menolak (`PaymentService::reject`): status `rejected`, order kembali ke `pending_payment`.

### 4.2 Tunai
- Admin mencatat uang benar-benar diterima (`PaymentService::recordCash`) → `markSettled(source='cash')`.
- Nominal harus cocok.

## 5. Efek Settlement (`markSettled`)

Dipanggil oleh verifikasi manual, pencatatan tunai, dan webhook gateway. Idempotent.

```
markSettled(payment, actor, source, note, context):
  lock payment + order
  if status == verified: return            # idempotent
  if status in (refunded, partially_refunded): throw
  lateOrFinal = order.cancelled OR status in (expired, failed)
  set status = lateOrFinal ? processing : verified, settled_at = now
  if not lateOrFinal:
      collect deposit (deposit_amount) -> DepositService.collect()
      confirm order (pending_payment/payment_review -> confirmed)
  log event
```

- **Deposit** dialokasikan ke ledger deposit (bukan revenue).
- Order hanya naik ke `confirmed` setelah ada pembayaran `verified` (precondition di `RentalOrderService::assertPreconditions`).

## 6. Alur Gateway (Midtrans)

Lihat `MIDTRANS_INTEGRATION.md`. Ringkas:
1. `MidtransPaymentService::startCheckout()` → `PaymentAttempt` + Snap `redirect_url`.
2. Webhook → `PaymentNotificationService` → validasi → `markSettled()`.
3. Pembayaran gateway tidak dapat diverifikasi manual.

## 7. Attempt Pembayaran (`PaymentAttempt`)

- `provider_order_id` unik, format `GRR-YYYYMMDD-XXXX-A01`.
- Status: `pending`, `settlement`, `capture_accepted`, `capture_challenged`, `denied`, `cancelled`, `expired`, `failed`, `refunded`, `partially_refunded`, `chargeback`.
- `isSuccessful()` = `settlement` atau `capture_accepted`.

## 8. Kedaluwarsa (tanpa cron)

`PaymentExpiryService::expireStale()`:
- attempt `pending` dengan `expires_at < now` → `expired`;
- pembayaran belum final → `expired`;
- order `pending_payment`/`payment_review` → dibatalkan (menyebabkan pelepasan seluruh assignment).

## 9. Keamanan & Audit

- Bukti pembayaran di disk privat; hanya pemilik order / staf berizin dapat mengunduh.
- Semua perubahan penting dicatat via `ActivityLogger`.
- Nominal tidak pernah diambil dari input pelanggan; selalu dari snapshot order.
