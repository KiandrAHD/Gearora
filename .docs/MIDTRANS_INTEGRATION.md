# Integrasi Pembayaran Midtrans — GEARORA Phase 5

Dokumen ini menjelaskan cara kerja integrasi pembayaran otomatis (Midtrans Snap) di GEARORA,
alur data, keamanan webhook, dan langkah aktivasi sandbox/production.

## 1. Ringkasan

- Provider gateway: **Midtrans Snap** (SDK `midtrans/midtrans-php` `^2.6`).
- Metode pembayaran baru: `PaymentMethod::Midtrans`.
- Aktivasi otomatis hanya bila:
  - `GEARORA_PAYMENT_GATEWAY_ENABLED=true`, dan
  - `MIDTRANS_SERVER_KEY` + `MIDTRANS_CLIENT_KEY` terisi.
- Bila kredensial tidak ada, tombol "Bayar sekarang (Midtrans)" tidak tampil dan pelanggan memakai transfer manual / tunai.
- Default environment: **sandbox** (`MIDTRANS_IS_PRODUCTION=false`). Production harus diaktifkan manual.

## 2. Konfigurasi

`config/gearora.php` → blok `midtrans`:

| Key | ENV | Default | Keterangan |
|---|---|---|---|
| `server_key` | `MIDTRANS_SERVER_KEY` | — | Server Key (rahasia, hanya di server) |
| `client_key` | `MIDTRANS_CLIENT_KEY` | — | Client Key (boleh dipakai di front-end Snap) |
| `merchant_id` | `MIDTRANS_MERCHANT_ID` | — | ID merchant |
| `is_production` | `MIDTRANS_IS_PRODUCTION` | `false` | `false` = sandbox |
| `expiry_minutes` | `MIDTRANS_EXPIRY_MINUTES` | `60` | Masa berlaku transaksi Snap |
| `notification_url` | `MIDTRANS_NOTIFICATION_URL` | — | URL webhook publik (opsional, didaftarkan di dashboard Midtrans) |
| `http_timeout` | `MIDTRANS_HTTP_TIMEOUT` | `20` | Timeout HTTP ke Midtrans (detik) |
| `fake` | `MIDTRANS_FAKE` | `false` | Pakai provider tiruan tanpa jaringan (dev tanpa kredensial) |

Blok `payment`:

| Key | ENV | Keterangan |
|---|---|---|
| `gateway_enabled` | `GEARORA_PAYMENT_GATEWAY_ENABLED` | Master switch tombol gateway |
| `proof_disk` | — | Disk bukti pembayaran (privat) |
| `inspection_disk` | — | Disk foto inspeksi |

> **Server Key tidak boleh** dikirim ke browser, masuk ke respons JSON, atau ditulis ke log.
> Yang boleh dikirim ke browser hanya `client_key` (untuk Snap.js bila memakai mode popup/embed).

## 3. Abstraksi Provider

`app/Contracts/PaymentGateway.php`

```php
interface PaymentGateway {
    public function createSnap(PaymentAttempt $attempt, RentalOrder $order): array;
    public function refund(PaymentAttempt $attempt, int $amount, string $refundKey): array;
    public function cancel(PaymentAttempt $attempt): array;
}
```

Implementasi:
- `MidtransPaymentGateway` — memanggil Midtrans Snap/Transaction API.
- `FakePaymentGateway` — deterministik, tanpa jaringan, dipakai di test/local.

Binding di `AppServiceProvider::register()`:

```php
$this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
    if (app()->environment('testing') || config('gearora.midtrans.fake')) {
        return new FakePaymentGateway;
    }
    return new MidtransPaymentGateway;
});
```

## 4. Alur Pembayaran (Snap Redirect)

1. Pelanggan menekan **Bayar sekarang (Midtrans)** →
   `POST /akun/pesanan/{order}/pembayaran-midtrans` → `MidtransCheckoutController@start`.
2. Controller memastikan order dapat dibayar dan memanggil `MidtransPaymentService::startCheckout()`.
3. `startCheckout()` **Fase 1** (dalam transaksi DB): membuat/menemukan `PaymentAttempt` aktif dan `provider_order_id` unik (format `GRR-YYYYMMDD-XXXX-A01`).
4. **Fase 2** (di luar transaksi DB): memanggil Snap API untuk mendapatkan `token` dan `redirect_url`, lalu menyimpannya. Panggilan jaringan **tidak** dilakukan di dalam transaksi DB yang mengunci inventori.
5. Pelanggan diarahkan (`redirect()->away`) ke `redirect_url` Midtrans.
6. Setelah selesai, pelanggan kembali ke `GET /akun/pesanan/{order}/pembayaran-midtrans/selesai`.
   **Status pembayaran TIDAK pernah dipercaya dari query string halaman kembali** — status hanya berasal dari webhook/database.
7. Midtrans mengirim notifikasi ke webhook; status pembayaran berubah otomatis.

### Idempotensi & anti double-click

- Bila ada `PaymentAttempt` berstatus `pending`/`capture_challenged` yang belum kedaluwarsa dan sudah memiliki `redirect_url`, Snap tidak dipanggil ulang.
- `provider_order_id` dibuat unik (`A01`, `A02`, …) dengan fallback acak bila bentrok.

## 5. Webhook (Notifikasi Midtrans)

Endpoint: `POST /api/payments/midtrans/notification`
(route `payments.midtrans.notification`, grup `api` tanpa CSRF web, throttle `midtrans-webhook`).

Handler: `PaymentNotificationService::handle()`.

### 5.1 Verifikasi tanda tangan

```
signature_key == SHA512(order_id + status_code + gross_amount + ServerKey)
```

- Dibandingkan memakai `hash_equals()` (timing-safe).
- Bila `ServerKey` kosong atau tanda tangan kosong → **ditolak** (`invalid_signature`, HTTP 401).

### 5.2 Validasi tambahan

- Nominal `gross_amount` **harus** sama dengan `PaymentAttempt.gross_amount` → jika beda: HTTP 422.
- Mata uang harus cocok (`IDR`) → jika beda: HTTP 422.
- `PaymentAttempt` harus ditemukan berdasarkan `provider_order_id` → jika tidak: HTTP 404.

### 5.3 Deduplikasi

- Setiap notifikasi disimpan sebagai `payment_webhook_events` dengan `event_hash` unik
  (`sha256(provider|order_id|transaction_id|transaction_status|fraud_status|gross_amount)`).
- `firstOrCreate` → notifikasi berulang mengembalikan `duplicate` (HTTP 200) tanpa efek ganda.
- `payload_safe` hanya menyimpan field non-rahasia yang diizinkan (`order_id`, `status_code`, `transaction_status`, `fraud_status`, `transaction_id`, `payment_type`, `gross_amount`, `currency`, `transaction_time`, `settlement_time`).

### 5.4 Pemetaan status

| `transaction_status` | Efek |
|---|---|
| `settlement` | Pembayaran settled (verified) → deposit dialokasikan → order dikonfirmasi |
| `capture` + `fraud_status=accept` | Sama seperti settlement |
| `capture` + `fraud_status=challenge` | Attempt `capture_challenged`, pembayaran `processing` (menunggu keputusan) |
| `pending` | Attempt tetap `pending` |
| `deny` / `cancel` / `failure` | Attempt gagal, pembayaran `failed`, order pending dibatalkan |
| `expire` | Attempt `expired`, pembayaran `expired`, order pending dibatalkan |
| `refund` / `partial_refund` | Pembayaran `refunded` / `partially_refunded` |
| `chargeback` / `partial_chargeback` | Attempt `chargeback`, dicatat untuk audit |

- Notifikasi "lama" (`pending`/`deny`/`cancel`/`expire`/`failure`) **tidak menurunkan** pembayaran yang sudah final (`verified`/`refunded`/`partially_refunded`) → status `ignored_final`.

### 5.5 Respons HTTP

| Status internal | HTTP |
|---|---|
| `settled`, `pending`, `capture_challenged`, `duplicate`, `ignored_final`, `noop` | 200 |
| `invalid_signature` | 401 |
| `unknown_attempt` | 404 |
| `amount_mismatch`, `currency_mismatch` | 422 |
| `failed` (error internal) | 500 |

> Midtrans akan mencoba ulang notifikasi yang tidak dijawab 2xx. Karena deduplikasi dan idempotensi,
> retry aman.

## 6. Kedaluwarsa tanpa cron

Hosting target tidak menjamin cron/worker. Karena itu:

- `PaymentExpiryService::expireStale()` dipanggil on-demand (dashboard/availability) dan tersedia lewat Artisan command.
- Pemeriksaan menandai attempt `pending` yang lewat `expires_at` menjadi `expired`, pembayaran belum final menjadi `expired`, dan order `pending_payment`/`payment_review` dibatalkan.

## 7. Refund Gateway

- `RefundService::process()` memanggil `PaymentGateway::refund()` bila metode pembayaran `Midtrans` dan attempt sukses serta `refund_method = gateway`.
- Refund non-gateway → `refund_method = manual_transfer` (admin mengembalikan dana, unggah bukti).
- Refund **tidak** mengubah riwayat pembayaran; refund adalah transaksi tersendiri (`refunds`) dengan status sendiri.

## 8. Aktivasi di Midtrans Dashboard

1. Daftar/masuk akun Midtrans (sandbox maupun production).
2. Ambil **Server Key** dan **Client Key** di **Settings → Access Keys**.
3. Set **Payment Notification URL** ke:
   `https://<domain-anda>/api/payments/midtrans/notification`
4. Isi `.env` server (jangan commit):

```dotenv
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_EXPIRY_MINUTES=60
MIDTRANS_NOTIFICATION_URL=https://<domain-anda>/api/payments/midtrans/notification
GEARORA_PAYMENT_GATEWAY_ENABLED=true
```

5. `php artisan config:clear` dan (production) `php artisan config:cache`.
6. Uji transaksi sandbox, lalu cek baris `payment_webhook_events` dan status pembayaran.

## 9. Status Verifikasi Sandbox

> **BLOCKED** — verifikasi transaksi Midtrans sandbox nyata belum dilakukan pada implementasi ini
> karena tidak tersedia `MIDTRANS_SERVER_KEY`/`MIDTRANS_CLIENT_KEY` asli di lingkungan pengembangan.
>
> Yang sudah terbukti berjalan dan teruji:
> - pembuatan attempt & Snap flow via `FakePaymentGateway` (test otomatis);
> - verifikasi tanda tangan webhook, validasi nominal/mata uang, deduplikasi, idempotensi, dan pemetaan status (test otomatis, 11 skenario);
> - pelepasan unit per-unit, ledger deposit, refund, denda keterlambatan (test otomatis).
>
> Yang belum terbukti: panggilan HTTP nyata ke Midtrans, format respons Snap asli, dan notifikasi asli dari Midtrans.
> Setelah kredensial sandbox tersedia, lakukan uji end-to-end pada langkah §8.
