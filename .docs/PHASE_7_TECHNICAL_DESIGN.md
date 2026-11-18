# Phase 7 — Technical Design (Retail Sales & Expanded Reports)

> **Produk:** GEARORA — Outdoor Equipment Rental Platform
> **Fase:** Phase 7 — Retail Sales & Expanded Reports
> **Status:** Implemented
> **Tanggal:** 18 November 2026
> **Acuan:** `PRD_GEARORA.md`, `ARCHITECTURE_GEARORA.md`, `ROADMAP_GEARORA.md`, `ADR_SHARED_PAYMENT_FOR_RETAIL.md`

---

## 1. Ruang Lingkup

| Fitur | Ringkasan |
| --- | --- |
| Katalog jual | Produk `ProductType::Sale`/`Both` dengan `sale_price` per varian |
| Keranjang retail | Session terpisah dari keranjang sewa |
| Checkout retail | Order retail + reservasi stok + payment record |
| Pembayaran | Transfer manual, tunai di toko, Midtrans (gateway bersama) |
| Pemenuhan | Ambil sendiri di lokasi (single-location pickup) |
| Admin sales | Daftar, detail, transisi status, verifikasi pembayaran |
| Laporan | Rental, sales, payments, deposits, charges, inventory, operations |
| Ekspor CSV | Streaming, UTF-8 BOM, proteksi formula injection |

Di luar lingkup: pengiriman kurir, multi-cabang, retur/refund retail otomatis, cicilan, PO.

---

## 2. Skema Database

### 2.1 `sales_orders`

| Kolom | Tipe | Catatan |
| --- | --- | --- |
| `id` | bigint PK | |
| `order_code` | string(40) unik | Format `GRS-YYYYMMDD-XXXX` |
| `customer_id` | FK → `users` | `cascadeOnDelete` |
| `subtotal`, `discount_total`, `total` | unsigned bigint | Rupiah utuh |
| `payment_status` | string(32) | `PaymentStatus` |
| `order_status` | string(32), index | `SalesOrderStatus` |
| `notes` | text nullable | Catatan pelanggan |
| `confirmed_at`, `pickup_ready_at`, `completed_at`, `cancelled_at` | timestamp nullable | Jejak waktu |
| `expires_at` | timestamp nullable | Batas reservasi |
| `cancelled_by` | FK nullable → `users` | |
| `cancellation_reason` | text nullable | |

Index gabungan: `(customer_id, order_status)`.

### 2.2 `sales_order_items`

Snapshot agar riwayat tidak berubah saat katalog berubah: `product_id`, `product_variant_id` (nullable, `nullOnDelete`), `product_name_snapshot`, `variant_name_snapshot`, `sku_snapshot`, `unit_price_snapshot`, `quantity`, `line_total`.

### 2.3 `sales_stock_reservations`

`sales_order_id`, `sales_order_item_id`, `product_variant_id`, `quantity`, `status` (`reserved|consumed|released|expired`), `reserved_at`, `expires_at`, `consumed_at`, `released_at`.

Index: `(product_variant_id, status)`, `(sales_order_id, status)`.

### 2.4 `sales_payments`

`sales_order_id`, `method` (`midtrans|transfer|cash`), `amount`, `status` (`PaymentStatus`), `proof_path`, `reference`, `customer_note`, `verification_note`, `verified_by`, `verified_at`, `settled_at`, `proof_submitted_at`.

### 2.5 `sales_payment_attempts`

`sales_payment_id`, `provider`, `provider_order_id` (unik), `provider_transaction_id` (unik nullable), `provider_payment_type`, `status` (`PaymentAttemptStatus`), `fraud_status`, `gross_amount`, `currency`, `snap_token`, `redirect_url`, `idempotency_key`, `expires_at`, `paid_at`, `failed_at`, `provider_metadata`.

---

## 3. State Machine

### 3.1 `SalesOrderStatus`

```
pending_payment ──► payment_review ──► confirmed ──► ready_for_pickup ──► completed
       │                  │                │                  │
       ├──► cancelled     ├──► cancelled   └──► cancelled     └──► cancelled
       └──► expired       └──► pending_payment (kembali)
```

- `Completed`, `Cancelled`, `Expired` final.
- `Confirmed` hanya boleh setelah `hasVerifiedPayment()` benar.
- Pembatalan/kedaluwarsa melepas reservasi stok (tidak mengurangi stok fisik).

### 3.2 `SalesReservationStatus`

`reserved` (menahan stok) → `consumed` (stok fisik berkurang) | `released` | `expired`.

---

## 4. Alur Checkout

```
Customer (login)
  → POST /keranjang/checkout {payment_method, notes}
  → RetailCheckoutRequest validasi
  → SalesOrderService::checkout()
      DB::transaction:
        lock varian (lockForUpdate)
        validasi produk aktif, mendukung sale, varian aktif, harga > 0
        hitung subtotal dari harga server
        buat sales_orders (pending_payment, unpaid)
        buat sales_order_items (snapshot)
        SalesStockService::reserveForOrder() → cek available & buat reservasi
        buat sales_payments (unpaid)
        activity log
      commit
  → keranjang retail dikosongkan
  → redirect ke detail pesanan
```

Harga **tidak pernah** diambil dari client. Client hanya mengirim `variant_id` dan `quantity`.

---

## 5. Alur Pembayaran

### 5.1 Transfer Manual

1. Customer membuka detail pesanan → unggah bukti (`jpg/jpeg/png/pdf`, ≤ `gearora.payment.proof_max_kb`).
2. `SalesPaymentService::submitProof()` → status `waiting_verification`, order → `payment_review`.
3. Admin memverifikasi (`verify`) → status `verified`, `settled_at`, order → `confirmed`, stok dikonsumsi **sekali**.
4. Atau admin menolak (`reject`) → status `rejected`, order kembali `pending_payment`.

### 5.2 Tunai

Admin mencatat pembayaran tunai (`recordCash`) → `markSettled()` → order `confirmed`.

### 5.3 Midtrans

1. `ensurePaymentFor($order, PaymentMethod::Midtrans)` membuat/mengambil `SalesPayment`.
2. `startSnap()` membuat `SalesPaymentAttempt` + memanggil `PaymentGateway::createSnap()` (implementasi `MidtransPaymentGateway`).
3. Webhook `POST /api/payments/midtrans/notification` memverifikasi signature (`MidtransSignature`), amount, lalu meresolusi attempt di `payment_attempts` (rental) atau `sales_payment_attempts` (retail).
4. Settlement → `markSettled()` idempotent → order `confirmed`.

Detail keputusan: `ADR_SHARED_PAYMENT_FOR_RETAIL.md`.

### 5.4 Idempotensi

`markSettled()`:

- bila payment sudah `verified` → return tanpa efek samping;
- bila order sudah final (`cancelled`/`expired`) → payment `processing`, stok tidak disentuh, perlu tindakan manual;
- `SalesStockService::consumeForOrder()` hanya mengonsumsi reservasi berstatus `reserved`, sehingga pemanggilan ganda tidak mengurangi stok dua kali.

---

## 6. Reservasi & Anti-Overselling

- `available = stock_quantity − Σ quantity reservasi aktif`.
- Reservasi dibuat di dalam transaksi yang sama dengan pembuatan order dan row lock varian.
- Bila `quantity > available` → exception, transaksi rollback, order tidak dibuat.
- Stok fisik hanya berkurang saat settlement (`consumeForOrder`).
- Pembatalan/kedaluwarsa melepas reservasi (`releaseForOrder`).
- `StockMovementType::Sale` dicatat tepat satu kali per order saat konsumsi.

Detail: `SALES_STOCK_RESERVATION.md`.

---

## 7. Keranjang Retail

`SalesCartService`:

- Session key `retail_cart` (berbeda dari `rental_booking_draft`).
- Isi hanya `variant_id => quantity`.
- `lines()` memuat ulang produk/varian, memfilter yang tidak aktif/tidak dijual, dan memakai harga server.
- `subtotal()` dihitung dari harga server.
- `checkoutItems()` menghasilkan payload untuk `SalesOrderService`.

Batas kuantitas per baris: `gearora.sales.max_quantity_per_line` (default 99) dan ketersediaan stok saat menambah.

---

## 8. RBAC

| Permission | Owner | Admin | Warehouse | Customer |
| --- | --- | --- | --- | --- |
| `sales.view` | ✔ | ✔ | — | — |
| `sales.create` | ✔ | ✔ | — | — |
| `sales.update` | ✔ | ✔ | — | — |
| `sales.cancel` | ✔ | ✔ | — | — |
| `sales.complete` | ✔ | ✔ | — | — |
| `sales.manage` | ✔ | ✔ | — | — |
| `reports.rentals.view` | ✔ | ✔ | — | — |
| `reports.sales.view` | ✔ | ✔ | — | — |
| `reports.payments.view` | ✔ | ✔ | — | — |
| `reports.deposits.view` | ✔ | ✔ | — | — |
| `reports.inventory.view` | ✔ | ✔ | — | — |
| `reports.rental_operations.view` | ✔ | ✔ | — | — |
| `reports.export` | ✔ | ✔ | — | — |

Customer hanya melihat order miliknya (`SalesOrderPolicy::view`) dan dapat membatalkan order miliknya selama belum final.

---

## 9. Laporan

`ReportService` menyediakan:

| Key | Metrik utama | Tabel |
| --- | --- | --- |
| `summary` | agregat semua domain | — |
| `rental` | gross, refund, net rental revenue, deposit, order count | order rental |
| `sales` | gross sales, diskon, net sales, item terjual, AOV, top produk/varian | order retail terverifikasi |
| `payments` | collected, breakdown metode | payment rental + retail |
| `deposits` | required, collected, held, deducted, refunded, forfeited | deposit |
| `charges` | total, approval, outstanding | biaya tambahan |
| `inventory` | stok jual, reservasi, tersedia, terjual, damage/lost | varian |
| `operations` | order rental, aktif, selesai, overdue, inspeksi | operasional |

Prinsip:

- **Deposit bukan revenue.** `net_rental_revenue` hanya dari sewa; deposit dilaporkan terpisah.
- Sales hanya menghitung order `payment_status = verified` dan `confirmed_at` dalam rentang.
- Filter: `start_date`, `end_date`, `status`, `type`, `method`, `product_id`, `category_id`.
- Tanggal memakai timezone aplikasi (Asia/Jakarta), inklusif hingga akhir hari.

Definisi formal: `REPORT_DEFINITIONS.md`.

---

## 10. Ekspor CSV

`CsvExporter::stream()`:

- Streaming (`streamDownload`) agar aman untuk data besar.
- UTF-8 dengan BOM agar Excel membaca karakter Indonesia dengan benar.
- `Content-Type: text/csv; charset=UTF-8`.
- `Cache-Control: no-store, no-cache`.
- Setiap nilai (termasuk header) melewati `safe()`: nilai yang diawali `= + - @ \t \r` diberi prefiks apostrof untuk mencegah formula injection.
- Permission ekspor: `reports.export` (Owner/Admin).

---

## 11. Konfigurasi

```php
'sales' => [
    'reservation_minutes' => 120,
    'max_quantity_per_line' => 99,
],
```

Environment: `GEARORA_SALES_RESERVATION_MINUTES`, `GEARORA_SALES_MAX_QTY_PER_LINE`, serta variable payment/Midtrans yang sudah ada pada Phase 5.

---

## 12. Pengujian

| Berkas | Cakupan |
| --- | --- |
| `RetailCartTest` | Session terpisah, harga server, batas stok, filter produk |
| `SalesOrderTest` | Checkout, anti-overselling, transisi, pembatalan |
| `SalesStockReservationTest` | Reserve/consume/release, idempotensi, expiry |
| `SalesPaymentTest` | Transfer, tunai, Midtrans, webhook, idempotensi |
| `AdminSalesOrderTest` | Daftar/detail/filter, verifikasi, RBAC |
| `ExpandedReportsTest` | Semua tipe laporan, filter, RBAC |
| `CsvReportExportTest` | Header, BOM, formula injection, RBAC |

Rencana lengkap: `PHASE_7_TEST_PLAN.md`.

---

## 13. Batasan yang Diketahui

- Verifikasi Midtrans nyata (sandbox/production) belum dapat dijalankan tanpa kredensial; suite memakai `FakePaymentGateway`.
- Pemenuhan hanya pickup di lokasi; tidak ada alamat kirim atau ongkir.
- Belum ada retur/refund retail otomatis; penanganan manual oleh Owner.
- Job terjadwal untuk auto-expire order belum diaktifkan; `expires_at` disimpan untuk implementasi berikutnya.