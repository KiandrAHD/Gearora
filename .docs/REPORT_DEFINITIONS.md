# Report Definitions

> **Sumber:** `App\Services\ReportService`
> **Fase:** Phase 7
> **Mata uang:** Rupiah, integer (tanpa pecahan)
> **Timezone:** `config('app.timezone')` = Asia/Jakarta

---

## 1. Prinsip Umum

- Semua angka berasal dari tabel transaksi; tidak ada dummy data.
- Rentang tanggal dari `start_date` hingga `end_date` inklusif (`00:00:00` sampai `23:59:59` Asia/Jakarta).
- Nilai uang disimpan dan dijumlah dalam Rupiah utuh.
- `PaymentStatus::Verified` berarti dana diterima; status lain tidak dihitung sebagai revenue terkumpul.
- **Deposit bukan revenue.** Deposit selalu dilaporkan sebagai kelompok terpisah.
- Tabel dibatasi maksimum 500 baris per report untuk layar dan CSV streaming membaca generator report yang sama.

---

## 2. Filter

| Filter | Parameter | Dipakai oleh | Arti |
| --- | --- | --- | --- |
| Rentang awal | `start_date` | Semua | format `Y-m-d` |
| Rentang akhir | `end_date` | Semua | harus ≥ awal |
| Status | `status` | Rental, sales | status order |
| Tipe | `type` | Kontrak filter | `rental`, `sales`, `all` |
| Metode | `method` | Rental revenue, payments | `midtrans`, `transfer`, `cash` |
| Produk | `product_id` | Inventory | membatasi varian produk |
| Kategori | `category_id` | Inventory | membatasi produk kategori |

Filter produk/kategori untuk inventory diteruskan ke `ProductVariantQuery::rows()`. Filter yang tidak relevan diabaikan secara aman.

---

## 3. Summary (`summary`)

Dashboard gabungan, tidak memiliki tabel detail.

| Kelompok | Sumber |
| --- | --- |
| `rental` | `rentalRevenue()` |
| `sales` | `retailSales()` |
| `payments` | `payments()` |
| `deposits` | `deposits()` |
| `charges` | `additionalCharges()` |
| `inventory` | `inventory()` |
| `operations` | `rentalOperations()` |

CSV summary memiliki pasangan metrik/nilai: rental net revenue, retail net sales, payment collected, deposit held balance, additional charges outstanding, overdue rentals.

---

## 4. Rental Revenue (`rental`)

### Dataset

- Payment rental `status = verified`, berdasarkan `settled_at` dalam rentang.
- Refund rental `status = succeeded`, berdasarkan `processed_at` dalam rentang.
- Order rental berdasarkan `created_at` dalam rentang untuk jumlah/order table.

### Metrik

| Metrik | Rumus |
| --- | --- |
| `rental_subtotal` | Σ `rental_orders.subtotal` pada rentang pembuatan order |
| `rental_gross` | Σ `payments.rental_amount` untuk payment verified pada rentang settlement |
| `rental_refund` | Σ `refunds.rental_amount` untuk refund succeeded pada rentang proses |
| `net_rental_revenue` | `rental_gross − rental_refund` |
| `deposit_collected` | Σ `payments.deposit_amount` verified; **bukan** revenue |
| `order_count` | jumlah rental order pada rentang |
| `completed_orders` | order status `completed` |
| `cancelled_orders` | order status `cancelled` |
| `average_order_value` | `rental_subtotal / order_count`, 0 bila tidak ada order |

Tabel/CSV: kode, pelanggan, mulai/selesai, durasi, status, subtotal, deposit, total, sewa terbayar.

---

## 5. Retail Sales (`sales`)

### Dataset

`SalesOrder` yang memenuhi:

- `payment_status = verified`;
- `confirmed_at` tidak null dan berada dalam rentang;
- opsional `order_status = status` dari filter.

### Metrik

| Metrik | Rumus |
| --- | --- |
| `gross_sales` | Σ `sales_orders.subtotal` |
| `discount_total` | Σ `sales_orders.discount_total` |
| `net_sales` | Σ `sales_orders.total` |
| `refunded_sales` | 0 (refund retail belum ada) |
| `order_count` | jumlah order cocok |
| `items_sold` | Σ `sales_order_items.quantity` dari order cocok |
| `average_order_value` | `net_sales / order_count`, pembulatan rupiah, 0 bila kosong |
| `top_products` | group `product_name_snapshot`, urut qty desc, maksimum 10 |
| `top_variants` | group product/variant/SKU snapshot, urut qty desc, maksimum 10 |

Tabel/CSV: kode order, pelanggan, waktu konfirmasi, status, subtotal, diskon, total, jumlah item.

---

## 6. Payments (`payments`)

### Dataset

Gabungan `payments` (rental) dan `sales_payments` (retail) berdasarkan `created_at` dalam rentang. Filter metode diterapkan pada keduanya.

### Metrik

| Metrik | Rumus |
| --- | --- |
| `by_method` | Σ `amount` per metode dari kedua domain |
| `by_status` | jumlah record per status dari kedua domain |
| `collected` | Σ `amount` payment verified berdasarkan `settled_at` dari kedua domain |

Tabel/CSV: tipe (`rental`/`sales`), referensi order, pelanggan, metode, status, nominal, dibuat, settled.

**Catatan:** `collected` adalah cash collection, bukan pendapatan bersih. Payment rental bisa mencakup deposit; gunakan report rental/deposit untuk memisahkannya.

---

## 7. Deposits (`deposits`)

Dataset `deposits` berdasarkan `created_at` dalam rentang; held balance dihitung seluruh data karena merupakan posisi saldo saat ini.

| Metrik | Rumus |
| --- | --- |
| `required` | Σ `required_amount` |
| `collected` | Σ `collected_amount` |
| `held_balance` | Σ (`collected − deducted − refunded − forfeited`) seluruh deposit |
| `deducted` | Σ `deducted_amount` |
| `refunded` | Σ `refunded_amount` |
| `forfeited` | Σ `forfeited_amount` |
| `outstanding` | Σ `outstanding_amount` |
| `flows` | Σ transaksi deposit per `DepositTransactionType` dalam rentang |

Tidak satu pun metrik ini masuk ke revenue rental atau sales.

---

## 8. Additional Charges (`charges`)

Dataset `additional_charges.created_at` dalam rentang.

| Metrik | Rumus |
| --- | --- |
| `by_type` | Σ amount per `AdditionalChargeType` |
| `total` | Σ seluruh `by_type` |
| `pending_approval` | count status pending approval |
| `approved` | count status approved |
| `rejected` | count status rejected |
| `paid` | count status paid/offset from deposit |
| `outstanding` | Σ `outstanding_amount` status approved/partially settled |

Tabel/CSV: kode biaya, order, tipe, nominal, outstanding, status, status bayar.

---

## 9. Inventory (`inventory`)

| Metrik | Rumus |
| --- | --- |
| `sale_stock` | Σ `product_variants.stock_quantity` |
| `reserved_sale_stock` | Σ reservasi `status = reserved` |
| `available_sale_stock` | `max(0, sale_stock − reserved_sale_stock)` |
| `sold_quantity` | Σ (`stock_movements.quantity_before − quantity_after`) untuk type `sale` dalam rentang |
| `assets_by_status` | count rental asset per status |
| `stock_movements` | count movement per tipe dalam rentang |
| `damage_incidents` | count additional charge type damage dalam rentang |
| `lost_incidents` | count additional charge type lost dalam rentang |

Tabel/CSV varian: produk, varian, SKU, stok, reservasi, tersedia. Filter `product_id` dan `category_id` berlaku pada tabel varian.

---

## 10. Rental Operations (`operations`)

Dataset order rental berdasarkan `created_at` dalam rentang, kecuali overdue yang dihitung dari seluruh order aktif melewati `end_date` hari ini.

| Metrik | Rumus |
| --- | --- |
| `total_orders` | jumlah semua status order dalam rentang |
| `by_status` | count per `RentalOrderStatus` |
| `active_rental` | count status `picked_up` |
| `completed` | count status `completed` |
| `cancelled` | count status `cancelled` |
| `overdue` | status confirmed/ready/picked_up dan `end_date < hari ini` |
| `average_duration_days` | rata-rata `duration_days`, 1 desimal |
| `inspections` | count inspection dibuat dalam rentang |
| `damage_incidents` | count charge damage dalam rentang |
| `lost_assets` | count rental asset status lost saat ini |

---

## 11. CSV

| Report | Header CSV |
| --- | --- |
| summary | Metrik, Nilai |
| rental | Kode Order, Pelanggan, Mulai, Selesai, Durasi, Status, Subtotal, Deposit, Total, Sewa Terbayar |
| sales | Kode Order, Pelanggan, Dikonfirmasi, Status, Subtotal, Diskon, Total, Jumlah Item |
| payments | Tipe, Referensi, Pelanggan, Metode, Status, Nominal, Dibuat, Settled |
| deposits | Kode Order, Pelanggan, Wajib, Terkumpul, Dipotong, Direfund, Hangus, Saldo, Status |
| charges | Kode Biaya, Kode Order, Tipe, Nominal, Outstanding, Status, Status Bayar |
| inventory | Produk, SKU, Stok, Reservasi, Tersedia |
| operations | Kode Order, Pelanggan, Mulai, Selesai, Durasi, Status |

Semua nilai disanitasi `CsvExporter::safe()`: nilai awal `=`, `+`, `-`, `@`, tab, atau carriage return diberi apostrof agar tidak dieksekusi sebagai formula spreadsheet.

---

## 12. Batasan

- Refund retail belum dihitung (`refunded_sales = 0`).
- Top produk memakai snapshot item agar tetap historis, bukan nama katalog saat ini.
- Held deposit adalah posisi global, sehingga tidak dibatasi rentang tanggal.
- Tidak ada multi-currency; semua IDR.