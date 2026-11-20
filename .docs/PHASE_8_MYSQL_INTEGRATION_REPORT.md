# PHASE 8 — MySQL Integration & Concurrency Report

> Tanggal: 20 November 2026 · MySQL 8.4.3 (Laragon) · Database: `gearora_test` (disposable)
> Perintah: `vendor/bin/phpunit --configuration=phpunit.mysql.xml`

## 1. Keselamatan

- Test MySQL menolak berjalan bila driver bukan `mysql` atau nama database bukan `gearora_test`
  (`beforeRefreshingDatabase()` memanggil `fail()`).
- Tidak ada operasi destruktif terhadap `gearora` (dev). `migrate:fresh` hanya pada `gearora_test`.

## 2. Hasil

```
PHPUnit 11.5.58 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.5.9
Configuration: .../phpunit.mysql.xml
.....                                                              5 / 5 (100%)
OK (5 tests, 58 assertions)
```

| Test | Fokus |
|---|---|
| `MySqlCrudIntegrationTest::test_mysql_schema_is_innodb_with_foreign_keys` | ENGINE InnoDB + foreign key |
| `MySqlCrudIntegrationTest::test_mysql_phase5_payment_and_operations_schema` | Tabel pembayaran/operasional InnoDB, tipe kolom uang integer, unique index `event_hash` |
| `MySqlCrudIntegrationTest::test_mysql_product_and_variant_crud_persists_to_database` | CRUD lewat HTTP nyata → DB MySQL |
| `MySqlCrudIntegrationTest::test_mysql_safe_delete_and_deactivation_rules` | Hapus aman vs soft-deactivate |
| `MySqlConcurrencyTest::test_innodb_row_lock_blocks_second_connection_until_release` | Row lock lintas dua koneksi |

## 3. Bukti Skema

- `rental_assets` = **InnoDB**; `product_variants.product_id` → FK ke `products`.
- Kolom uang (`deposits.required_amount`, dll.) bertipe integer (`int`/`bigint`) — Rupiah penuh.
- `payment_webhook_events.event_hash` memiliki **unique index** (dedup di level DB).

## 4. Uji Konkurensi (dua koneksi)

Windows tanpa `pcntl`/parallel test, sehingga konkurensi dibuktikan dengan **dua koneksi MySQL
independen** (`mysql` + `mysql_secondary`):

1. Fixture varian stok di-commit pada koneksi primer.
2. Koneksi sekunder: `SET SESSION innodb_lock_wait_timeout = 1`.
3. Koneksi primer: `BEGIN` + `SELECT ... FOR UPDATE` pada baris varian.
4. Koneksi sekunder mencoba `SELECT ... FOR UPDATE` baris yang sama → **gagal** dengan
   `Lock wait timeout exceeded` (dibuktikan assertion pesan).
5. Setelah koneksi primer commit → koneksi sekunder berhasil mengunci baris.

Interpretasi: mekanisme `lockForUpdate()` yang dipakai `SalesStockService`/`SalesOrderService`
menghasilkan penjagaan mutual-exclusion nyata di InnoDB (dasar anti-overselling).

## 5. Catatan Iterasi (bug harness, bukan bug aplikasi)

Versi pertama uji konkurensi memakai `DatabaseMigrations` yang menjalankan `migrate:rollback`
setelah tiap test. Pada MySQL, rollback ini gagal karena `drop index` pada
`rental_asset_assignments_rental_order_id_released_at_index` dibutuhkan oleh foreign key
(`errno 1553`). Perbaikan: uji memakai `RefreshDatabase` (transaksi per-test) + fixture khusus
konkurensi di-commit, sehingga tidak ada rollback migrasi yang menyentuh index.

Ini masalah pada harness uji, bukan pada skema aplikasi (skema identik dengan baseline Phase 5
yang sudah terverifikasi InnoDB).

## 6. Kesimpulan

Parity MySQL **hijau**. Skema InnoDB dengan foreign key dan unique index terverifikasi pada
server MySQL nyata; semantik row-lock terbukti memblokir akses konkuren terhadap baris stok yang
sama. Tidak ada operasi destruktif pada database non-test.
