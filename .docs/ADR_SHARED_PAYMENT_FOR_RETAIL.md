# ADR — Shared Payment Gateway for Retail

> **Status:** Accepted
> **Tanggal:** 18 November 2026
> **Konteks:** Phase 7 — Retail Sales
> **Terkait:** `MIDTRANS_INTEGRATION.md`, `PAYMENT_WORKFLOW.md`, `PHASE_5_TECHNICAL_DESIGN.md`

---

## 1. Konteks

Phase 5 sudah memiliki integrasi Midtrans untuk rental:

- `payments` (kewajiban bayar per rental order);
- `payment_attempts` (percobaan ke provider, `provider_order_id` unik);
- `payment_webhook_events` (audit + dedup);
- `PaymentGateway` + `MidtransPaymentGateway`;
- satu endpoint webhook `POST /api/payments/midtrans/notification`;
- verifikasi signature SHA512.

Phase 7 menambah domain retail dengan `sales_orders` dan `sales_payments`. Pertanyaan desain: **apakah retail memakai tabel payment yang sama, membuat gateway baru, atau memisahkan agregat namun berbagi adaptor?**

Opsi yang dipertimbangkan:

### Opsi 1 — Reuse `payments` untuk retail

Menambahkan `sales_order_id` nullable ke `payments` dan memakai ulang `payment_attempts`.

- Kelebihan: satu tabel, satu alur.
- Kekurangan: `payments` sangat terikat pada konsep rental (deposit, `rental_amount`, `deposit_amount`, relasi `rental_order_id`). Menambah relasi kedua membuat constraint longgar, sulit menjaga integritas, dan berisiko mencampur laporan rental/retail. Ditambah lagi `Payment` sudah dipakai banyak service Phase 5.

### Opsi 2 — Gateway baru khusus retail

Membuat `RetailPaymentGateway`, endpoint webhook kedua, dan tabel attempt terpisah.

- Kelebihan: isolasi penuh.
- Kekurangan: duplikasi logika keamanan (signature, amount, dedup), dua endpoint webhook, dua konfigurasi Midtrans. Melanggar prinsip "satu provider, satu titik verifikasi".

### Opsi 3 (dipilih) — Agregat terpisah + adaptor & webhook bersama

- Tabel `sales_payments` + `sales_payment_attempts` terpisah.
- `PaymentGateway` diperluas agar menerima `PaymentAttempt|SalesPaymentAttempt` dan `PayableOrder`.
- `RentalOrder` dan `SalesOrder` mengimplementasikan `PayableOrder`.
- `MidtransSignature` diekstrak menjadi helper bersama.
- Satu endpoint webhook; attempt diresolusi dari `payment_attempts` lalu `sales_payment_attempts`.
- `payment_webhook_events` tetap dipakai bersama untuk dedup.

---

## 2. Keputusan

**Mengadopsi Opsi 3.**

```
RentalOrder ──► Payment ──► PaymentAttempt ─┐
                                            ├──► PaymentGateway (MidtransPaymentGateway)
SalesOrder  ──► SalesPayment ──► SalesPaymentAttempt ─┘
                                            │
                              POST /api/payments/midtrans/notification
                                            │
                          PaymentNotificationService
                          ├─ verifikasi MidtransSignature
                          ├─ validasi amount & currency
                          ├─ dedup payment_webhook_events
                          ├─ resolve attempt (rental → retail)
                          └─ terapkan efek idempotent
```

### 2.1 Kontrak `PayableOrder`

```php
interface PayableOrder
{
    public function payableCode(): string;      // kode order untuk provider
    public function payableAmount(): int;       // nominal dalam rupiah
    public function payableCustomer(): ?User;   // untuk metadata pelanggan
    public function payableItems(): array;      // item ringkas untuk Snap
}
```

Diimplementasikan oleh `RentalOrder` dan `SalesOrder`.

### 2.2 `PaymentGateway`

```php
interface PaymentGateway
{
    public function createSnap(PaymentAttempt|SalesPaymentAttempt $attempt, PayableOrder $order): array;
    public function refund(PaymentAttempt|SalesPaymentAttempt $attempt, int $amount, string $refundKey): array;
}
```

`MidtransPaymentGateway` dan `FakePaymentGateway` mengikuti kontrak ini.

### 2.3 Resolusi Attempt di Webhook

1. Cari `PaymentAttempt` berdasarkan `provider_order_id`.
2. Bila tidak ada, cari `SalesPaymentAttempt`.
3. Bila tetap tidak ada → catat event, kembalikan status yang sesuai (tidak memproses dana).
4. Efek diterapkan oleh service domain masing-masing (`PaymentService` rental, `SalesPaymentService` retail).

`provider_order_id` unik per tabel; karena prefix kode berbeda (`GRR-` rental, `GRS-` retail), tabrakan antar-domain tidak terjadi.

### 2.4 Dedup

`payment_webhook_events.event_hash` unik. Hash dihitung dari provider + `order_id` + `transaction_id` + `transaction_status` + `gross_amount` (lihat `MidtransSignature::eventHash()`). Event duplikat tidak diproses dua kali.

---

## 3. Konsekuensi

### Positif

- Satu sumber kebenaran untuk signature & verifikasi amount.
- Satu endpoint webhook, satu konfigurasi Midtrans.
- Integritas domain terjaga: `payments` tetap milik rental, `sales_payments` milik retail.
- Laporan bisa memisahkan rental vs retail tanpa ambiguitas.
- Tidak ada `rental_order_id` palsu di order retail.
- Tidak ada relasi polimorfik yang menyulitkan query dan constraint.

### Negatif / Biaya

- Dua tabel attempt mirip; perubahan kontrak gateway harus memperbarui keduanya.
- Resolusi attempt menambah satu query saat attempt retail.
- Duplikasi kolom status di dua domain (disengaja demi isolasi).

### Mitigasi

- Test `SalesPaymentTest` menutup alur retail termasuk signature invalid dan dedup.
- `MidtransSignature` adalah satu-satunya tempat perhitungan signature.
- `FakePaymentGateway` dipakai di test dan lokal tanpa kredensial.

---

## 4. Alternatif yang Ditolak

| Alternatif | Alasan ditolak |
| --- | --- |
| Reuse `payments` + `sales_order_id` | Mencampur agregat, constraint lemah, risiko regresi Phase 5 |
| Gateway & webhook retail terpisah | Duplikasi logika keamanan, dua titik verifikasi |
| Relasi polimorfik `payable` | Query/report kompleks, integritas FK sulit dijaga di MySQL |
| Menyimpan `rental_order_id` dummy | Data menyesatkan, melanggar integritas domain |

---

## 5. Status Implementasi

| Komponen | Status |
| --- | --- |
| `PayableOrder` + implementasi Rental/Sales | Selesai |
| `PaymentGateway` menerima dua tipe attempt | Selesai |
| `MidtransSignature` bersama | Selesai |
| Webhook tunggal + resolusi attempt | Selesai |
| `sales_payments` / `sales_payment_attempts` | Selesai |
| Verifikasi Midtrans sandbox nyata | **BLOCKED** — tanpa kredensial |
| Refund retail otomatis | Belum diimplementasikan (di luar lingkup Phase 7) |

---

## 6. Referensi

- `app/Contracts/PayableOrder.php`
- `app/Contracts/PaymentGateway.php`
- `app/Contracts/MidtransPaymentGateway.php`
- `app/Support/MidtransSignature.php`
- `app/Services/PaymentNotificationService.php`
- `app/Services/SalesPaymentService.php`
- `tests/Feature/SalesPaymentTest.php`