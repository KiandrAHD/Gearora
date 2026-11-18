# Phase 7 — Test Plan (Retail Sales & Expanded Reports)

> **Fase:** Phase 7
> **Status:** Automated suite green
> **Basis:** PHPUnit + SQLite in-memory; `FakePaymentGateway` untuk payment provider

---

## 1. Perintah

```bash
php artisan test --compact
# atau khusus Phase 7:
php artisan test --compact --filter='RetailCartTest|SalesOrderTest|SalesPaymentTest|SalesStockReservationTest|AdminSalesOrderTest|ExpandedReportsTest|CsvReportExportTest'
vendor/bin/pint --test
```

Seluruh test menggunakan `RefreshDatabase`. Test yang memberi role menjalankan `RolePermissionSeeder`.

---

## 2. Matriks Cakupan

| Berkas | Domain | Kasus utama |
| --- | --- | --- |
| `RetailCartTest` | Cart | session terpisah, harga server, stok, produk valid |
| `SalesOrderTest` | Checkout/order | item snapshot, reservasi, state machine, anti-overselling |
| `SalesStockReservationTest` | Stok | reserve, consume, release, expiry, idempotensi |
| `SalesPaymentTest` | Payment | transfer, cash, Midtrans fake, webhook, late payment |
| `AdminSalesOrderTest` | Admin | list/detail/filter, cash, transisi, RBAC |
| `ExpandedReportsTest` | Report UI | semua tipe report, filter tanggal, RBAC |
| `CsvReportExportTest` | CSV | header, BOM, formula injection, RBAC |

---

## 3. `RetailCartTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Session key | memakai `retail_cart`, tidak menyentuh `rental_booking_draft` |
| 2 | Tambah varian | menyimpan `variant_id => quantity` |
| 3 | Harga client | subtotal dihitung ulang dari DB |
| 4 | Produk rental-only | ditolak |
| 5 | Qty > stok | ditolak |
| 6 | Update/hapus/kosongkan | session konsisten |
| 7 | Produk setelah nonaktif | tidak muncul di lines |

---

## 4. `SalesOrderTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Checkout | order, item snapshot, payment, reservasi dibuat |
| 2 | Sebelum bayar | stok fisik tidak berkurang |
| 3 | Dua checkout stok terbatas | checkout kedua ditolak |
| 4 | Produk rental-only | tidak dapat dijual |
| 5 | Confirm tanpa verified payment | transisi ditolak |
| 6 | Alur verified → confirmed → ready → completed | state valid |
| 7 | Cancel | reservasi dilepas, stok fisik tetap |
| 8 | Lompat ke completed | transisi ditolak |

---

## 5. `SalesStockReservationTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | `availableQuantity` | stok fisik - reservasi aktif |
| 2 | Reserve melebihi stok | `InvalidArgumentException` |
| 3 | Consume dua kali | stok berkurang sekali, movement satu kali |
| 4 | Release | availability kembali, stok fisik tetap |
| 5 | Status release invalid | ditolak |
| 6 | Expired order | reservasi status `expired`, tidak lagi menahan stok |

---

## 6. `SalesPaymentTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Verifikasi transfer tanpa bukti | ditolak |
| 2 | Transfer valid | payment verified, order confirmed, stok berkurang |
| 3 | Cash | settle & confirm |
| 4 | `markSettled` dua kali | stok/movement tidak ganda |
| 5 | Nominal mismatch | verifikasi ditolak |
| 6 | Midtrans Snap | memakai gateway bersama/fake |
| 7 | Webhook settlement valid | retail attempt settled, order confirmed |
| 8 | Signature invalid | event tidak diproses |
| 9 | Late payment setelah cancel | `processing`, stok tidak dikonsumsi |

---

## 7. `AdminSalesOrderTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Owner list & filter | order sesuai status tampil |
| 2 | Owner detail | data order/pelanggan tampil |
| 3 | Catat tunai dua kali | stok berkurang satu kali |
| 4 | Target status invalid | flash error / status tetap |
| 5 | Nilai status invalid | error validasi |
| 6 | Warehouse | 403 |
| 7 | Customer | 403 |
| 8 | Admin | dapat melihat detail sesuai permission |

---

## 8. `ExpandedReportsTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Summary | metrik sales terverifikasi tampil |
| 2 | Sales report | hanya sales verified/confirmed masuk dataset |
| 3 | Semua key report | 200 (`summary,rental,sales,payments,deposits,charges,inventory,operations`) |
| 4 | Key tak dikenal | fallback summary (UI) |
| 5 | Rentang tanggal terbalik | validasi `end_date` |
| 6 | Warehouse/customer | 403 |

---

## 9. `CsvReportExportTest`

| # | Kasus | Ekspektasi |
| --- | --- | --- |
| 1 | Export sales | CSV, header benar, `Content-Type`, filename attachment, BOM |
| 2 | Nama pelanggan formula | diprefiks apostrof |
| 3 | Summary | metrik inti ada |
| 4 | Key export tak dikenal | 404 |
| 5 | Warehouse/customer | 403 |

---

## 10. Manual / Integrasi yang Wajib Sebelum Produksi

### 10.1 Midtrans Sandbox — BLOCKED tanpa kredensial

Prasyarat:

```dotenv
GEARORA_PAYMENT_GATEWAY_ENABLED=true
MIDTRANS_SERVER_KEY=...
MIDTRANS_CLIENT_KEY=...
MIDTRANS_IS_PRODUCTION=false
```

Langkah:

1. Buat order retail metode Midtrans dari akun customer.
2. Pastikan redirect Snap valid.
3. Selesaikan pembayaran Sandbox.
4. Pastikan webhook mencapai `/api/payments/midtrans/notification`.
5. Pastikan signature valid, event tersimpan satu kali, `sales_payment_attempts` settlement, `sales_payments` verified, order confirmed, stok berkurang satu kali.
6. Kirim ulang webhook yang sama; pastikan tidak ada movement kedua.
7. Uji denied/expired/cancel dari dashboard Sandbox.

### 10.2 MySQL 8 Integration — BLOCKED bila `gearora_test` belum tersedia

Prasyarat: buat database test terpisah, bukan DB utama `gearora`.

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS gearora_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test --configuration=phpunit.mysql.xml
```

Verifikasi khusus MySQL:

- foreign key migration Phase 6/7;
- unique `provider_order_id` dan `order_code`;
- `lockForUpdate` dua proses checkout;
- JSON `required_special_needs`/`provider_metadata`;
- CSV export respons streaming.

Tidak pernah jalankan `migrate:fresh`, `db:wipe`, atau `migrate:reset` pada database utama `gearora`.

### 10.3 Browser QA

- [ ] Cart retail responsif mobile/desktop.
- [ ] Harga, total, dan jumlah item jelas.
- [ ] Bukti pembayaran upload + error validasi jelas.
- [ ] Status badge order mudah dibaca.
- [ ] Admin sales table dapat dipakai di viewport kecil.
- [ ] Laporan tampil angka Rupiah yang mudah dipindai.
- [ ] Download CSV benar di Excel/LibreOffice dan nilai formula menjadi teks.
- [ ] Navigasi customer/admin menunjukkan menu sales sesuai permission.

---

## 11. Kriteria Lulus

- [x] `php artisan test --compact` hijau.
- [x] `vendor/bin/pint --test` hijau.
- [ ] Midtrans Sandbox webhook nyata — BLOCKED tanpa kredensial.
- [ ] MySQL 8 suite — BLOCKED sampai `gearora_test` tersedia.
- [ ] Browser manual QA — harus dilakukan sebelum release produksi.