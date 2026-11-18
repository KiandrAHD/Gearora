# Phase 5 — Technical Design (Payments & Rental Operations)

> **Produk:** GEARORA — Outdoor Equipment Rental Platform
> **Fase:** Phase 5 — Payments & Rental Operations
> **Status:** Requirement freeze + technical design
> **Tanggal:** 18 November 2026
> **Acuan:** `PRD_GEARORA.md`, `ARCHITECTURE_GEARORA.md`, `RENTAL_POLICY.md` v1.0, `ROADMAP_GEARORA.md` §8

Dokumen ini membekukan desain teknis Phase 5 sebelum implementasi. Semua nomor bagian mengikuti urutan yang diminta pada master execution prompt.

---

## 1. Payment Workflow

Satu **Payment** merepresentasikan satu kewajiban pembayaran untuk satu rental order (bisa mencakup rental + deposit). Banyak percobaan pembayaran (Payment Attempt) dapat menempel pada satu Payment.

```
Rental Order
   └── Payment (kewajiban, amount = snapshot total)
         ├── Payment Attempt #1 (provider=midtrans, status=expired)
         ├── Payment Attempt #2 (provider=midtrans, status=settlement)
         └── Payment Webhook Events (audit + dedupe)
```

- Metode: `midtrans`, `transfer`, `cash` (`PaymentMethod`).
- Status (`PaymentStatus`): `unpaid`, `waiting_verification`, `processing`, `verified`, `rejected`, `expired`, `failed`, `refunded`, `partially_refunded`.
- `Payment.amount` = `order.total` snapshot (rental subtotal + deposit). Payment breakdown disimpan terpisah: `rental_amount`, `deposit_amount`.
- Manual transfer & cash tetap tidak mengubah status final tanpa aksi Admin (kecuali gateway settlement via webhook tervalidasi).
- Metode pembayaran tidak dapat diubah setelah ada attempt aktif/verified.

## 2. Midtrans Transaction Workflow

1. Customer memilih Midtrans di checkout → order dibuat → Payment(method=midtrans, status=unpaid) dibuat.
2. Customer klik "Bayar sekarang" → `MidtransPaymentService::createSnapTransaction()`:
   - buka DB transaction, lock Payment + order, buat/ambil `PaymentAttempt` (status `pending`, provider_order_id unik), commit;
   - panggil Midtrans Snap di luar transaction;
   - simpan `snap_token`, `redirect_url`, `expires_at`;
   - kembalikan redirect URL.
3. Customer menyelesaikan pembayaran di Snap.
4. Midtrans mengirim webhook ke endpoint notifikasi.
5. `PaymentWebhookService` memverifikasi signature + amount, menyimpan `payment_webhook_events`, memproses idempotent, memperbarui attempt + payment + order + deposit.

Precondition pembayaran: order belum `cancelled`/`completed`, belum ada attempt aktif yang dapat dipakai ulang, amount > 0.

## 3. Webhook Workflow

```
POST /api/payments/midtrans/notification
  → rate limit
  → parse JSON
  → hitung signature SHA512(order_id + status_code + gross_amount + server_key)
  → hash_equals compare
  → cari PaymentAttempt via provider_order_id
  → validasi gross_amount == attempt.gross_amount & currency IDR
  → simpan PaymentWebhookEvent (event_hash unik) → dedupe
  → DB transaction + lockForUpdate attempt & payment
  → map status, terapkan efek satu kali (idempotent)
  → catat activity log
  → return 200 OK
```

Webhook invalid → HTTP 4xx, log aman, tanpa memproses. Response selalu 200 untuk event valid yang sudah pernah diproses (idempotent).

## 4. Payment State Transition

```
unpaid ──► waiting_verification ──► verified
   │                 │
   │                 └──► rejected ──► waiting_verification (ulang)
   ├──► processing ──► verified
   ├──► expired
   └──► failed

verified ──► refunded | partially_refunded
```

- `verified` hanya dari: verifikasi manual tervalidasi, cash tercatat, atau webhook settlement/capture-accept.
- `processing` dipakai saat capture+challenge (menunggu peninjauan) atau saat attempt menunggu konfirmasi bank.
- Status final: `refunded`, `partially_refunded` (dan `verified` dapat dianggap settled sebelum refund).
- Penurunan status final dilarang (out-of-order webhook).

## 5. Refund Workflow

- Refund adalah transaksi tersendiri (`refunds`), bukan sekadar mengubah `payments.status`.
- Perhitungan deterministik di `RefundCalculationService` berdasarkan `RENTAL_POLICY.md` §7 (lihat §7 dokumen ini).
- Gateway refund otomatis hanya jika: payment settled, metode mendukung refund API, amount valid, belum direfund, provider mengizinkan.
- Jika metode tidak mendukung refund API Midtrans → jalur **manual**: Admin mengembalikan dana, unggah bukti, catat aktor + waktu.
- Transaksi pending/authorized → gunakan provider cancel, bukan refund API.
- Idempotency: `refund_code` unik + `provider_refund_id` unik; retry memakai key yang stabil.

## 6. Deposit Lifecycle

Deposit = liabilitas, terpisah dari pendapatan rental.

```
required (dihitung saat order)
   → collected (payment berhasil / manual terverifikasi)
   → deducted (additional charge disetujui, dipotong dari deposit)
   → refunded (sisa dikembalikan)
   → forfeited (hangus, hanya jika order hilang / pelanggan menolak kewajiban sah)
```

- Satu `deposits` record per order; mutasi hanya via `deposit_transactions` (append-only operasional).
- `outstanding_amount` = required − collected (belum masuk). Jika charge > deposit → sisa menjadi outstanding liability yang ditagih via additional charge.
- Float dilarang; semua uang integer Rupiah (disimpan sebagai integer) atau decimal-safe string.

## 7. Handover Workflow

```
ready_for_pickup → [validasi payment+deposit+identitas] → handover record + per-unit item
   → unit: reserved → rented
   → order: picked_up
```

Precondition: order `ready_for_pickup`, payment verified (atau cash/deposit sesuai ketentuan), deposit terkumpul sesuai kewajiban, identitas diperiksa, unit sesuai assignment, customer acknowledge. Atomic: semua sukses atau rollback.

## 8. Return Workflow

```
picked_up → return record (bisa partial per unit)
   → unit: rented → inspection_pending
   → inspeksi final per unit
   → good → available ; problem → maintenance/damaged/lost
   → hitung additional charge
   → settle deposit
   → semua unit tertangani → order completed
```

Partial return didukung: unit belum kembali tetap `rented`, order belum `completed`.

## 9. Inspection Workflow

- Stage: `handover`, `return`.
- Field: asset, order, assignment/return-item ref, condition_before, condition_after, completeness_status, damage_description, missing_items, photos (private disk), inspector, waktu, notes, result (`pass`/`fail`).
- Hasil inspeksi menentukan status unit baru dan dasar additional charge.

## 10. Additional Charges Workflow

```
warehouse/admin membuat charge (alasan + nominal + bukti)
   → pending_approval
   → Admin/Owner approve → approved → offset dari deposit (deduction) → paid/partially_settled
   → atau reject → rejected
```

- Warehouse Staff tidak boleh approve charge sendiri.
- Settlement: potong deposit dulu; jika kurang → outstanding balance ditagih (Payment tambahan).
- Nominal integer Rupiah.

## 11. Asset Lifecycle

```
available ⇄ reserved
reserved → rented → inspection_pending → available
                                       → maintenance → available
                                       → damaged (terminal/repairable)
                                       → lost (terminal)
```

- `inspection_pending` baru (bukan `available` sebelum inspeksi selesai).
- Assignment per unit: `assigned_at`, `handed_over_at`, `returned_at`, `released_at`, plus `handover_item_id`/`return_item_id`.
- `released_at` hanya diisi setelah unit selesai inspeksi (atau order cancelled), **bukan** saat order `returned`.

## 12. Rental Order State Transition

```
pending_payment → payment_review → confirmed → ready_for_pickup → picked_up → returned → completed
      └────────────► cancelled (dengan alasan + aturan refund)
```

Perubahan dari Phase 4:
- `pending_payment → confirmed` **dihapus** (harus lewat review/verified payment).
- `picked_up`/`returned`/`completed` hanya via workflow service (`RentalHandoverService`, `RentalReturnService`), bukan dropdown mentah.
- `completed` hanya setelah semua unit diinspeksi & deposit/charge selesai.
- Semua transisi: DB transaction + `lockForUpdate` + validasi precondition + audit log.

## 13. RBAC Matrix (Phase 5)

Permission baru (prefix existing): `payments.refund`, `payments.settings.manage`, `rentals.handover`, `rentals.return`, `rentals.complete`, `inspections.view`, `inspections.create`, `inspections.approve`, `deposits.view`, `deposits.manage`, `deposits.refund`, `deposits.deduct`, `deposits.approve`, `charges.view`, `charges.create`, `charges.approve`, `charges.reject`, `reports.rentals.view`, `reports.payments.view`, `reports.deposits.view`.

| Role | Ringkasan |
|---|---|
| Owner | Semua |
| Admin | Payments (view/verify/reject/refund), rentals (view/update/cancel/handover/return/complete), inspections, deposits, charges, reports |
| Warehouse Staff | rentals.view/update, rentals.handover, rentals.return, inspections.view/create, charges.view/create, inventory.* |
| Customer | Order & data milik sendiri saja |

Authorization selalu dicek server-side (Policy + middleware `permission:`), bukan hanya sembunyikan menu.

## 14. Database Schema Changes

Migration incremental baru (tanpa mengubah migration lama): lihat daftar di implementasi. Ringkas: `payment_attempts`, `payment_webhook_events`, `refunds`, `deposits`, `deposit_transactions`, `rental_handovers`, `rental_handover_items`, `rental_returns`, `rental_return_items`, `inspections`, `inspection_photos`, `additional_charges`, plus perluasan kolom `payments`, `rental_orders.order_status`, `rental_assets.status`, dan kolom tracking unit di `rental_asset_assignments`.

- InnoDB, `utf8mb4`, FK, unique constraints, index, monetary integer/decimal, date/time konsisten.
- Kolom ENUM diubah menjadi `string` agar kompatibel MySQL **dan** SQLite (test).

## 15. Hosting Constraints (AeonFree)

- Tidak ada cron/queue worker/SSH diasumsikan. Overdue & expired dihitung **on-demand** saat dashboard/availability diakses + Artisan command opsional.
- `QUEUE_CONNECTION=sync`, cache/session `database`.
- Build asset & composer lokal; upload manual.
- Private storage untuk bukti pembayaran & foto inspeksi.

## 16. Testing Strategy

- Unit + Feature (SQLite `:memory:`) untuk logika & HTTP.
- MySQL `gearora_test` untuk row locking, unique constraint, ledger atomicity, concurrency.
- Midtrans: `FakePaymentProvider` (interface) untuk automated test → tidak ada request nyata.
- Webhook: signature valid/invalid, dedupe, out-of-order, late settlement.
- Overdue boundary: 20:00, 20:59, 21:00, 21:01, 24/48 jam, hari ke-7.

---

## ADR Ringkas (Phase 5)

- **ADR-5.1 — Payment Attempt terpisah dari Payment.** Satu kewajiban, banyak percobaan; mencegah duplikasi kewajiban dan mendukung retry/expiry.
- **ADR-5.2 — Asset release per unit.** Melepas unit hanya setelah inspeksi unit tersebut selesai; menghilangkan overbooking/under-blocking.
- **ADR-5.3 — ENUM → string column.** Kompatibel MySQL & SQLite, memungkinkan perluasan status tanpa `MODIFY` ENUM rapuh.
- **ADR-5.4 — Deposit sebagai ledger append-only.** Semua mutasi deposit wajib menghasilkan `deposit_transactions`; saldo tidak pernah diubah langsung.
- **ADR-5.5 — Provider abstraction.** `PaymentProvider` interface + `MidtransPaymentProvider` + `FakePaymentProvider` agar integrasi gateway dapat diuji tanpa kredensial nyata.
- **ADR-5.6 — Uang sebagai integer Rupiah.** Menghilangkan galat floating point pada deposit/refund/charge.
- **ADR-5.7 — Metode pembayaran otomatis (gateway) memperluas RENTAL_POLICY §8.** Dibekukan via ADR ini; kebijakan lama menyebut gateway "di luar MVP" → diubah untuk Phase 5. Dokumen `RENTAL_POLICY.md` diperbarui menyesuaikan.
