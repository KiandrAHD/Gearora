# PHASE 8 — Backup & Restore Report

> Tanggal: 20 November 2026 · MySQL 8.4.3 · Sumber: `gearora` (dev) · Target uji: `gearora_restore_test` (disposable)
> Alat: `mysqldump` / `mysql` (Laragon) dijalankan via berkas `.cnf` kredensial (password tidak
> ditulis ke command line, riwayat shell, atau dokumen ini).

## 1. Prosedur yang Diuji

1. Baca kredensial dari `.env` (tidak dicetak).
2. **Dump** `gearora` (read-only) → `g8_gearora_backup.sql`.
3. **Restore** ke database disposable `gearora_restore_test`.
4. **Verifikasi parity** (jumlah tabel + hitung baris persis pada tabel kunci).
5. Bersihkan: hapus `gearora_restore_test`.

Perintah inti:

```bash
mysqldump --single-transaction --routines --triggers --events --set-gtid-purged=OFF gearora > backup.sql
mysql gearora_restore_test < backup.sql
```

## 2. Hasil Dump

| Metrik | Nilai |
|---|---|
| Database sumber | `gearora` |
| Database target | `gearora_restore_test` |
| Tabel sumber | 47 |
| Ukuran dump | 104.631 byte |
| `mysqldump` exit code | 0 |
| `mysql` (restore) exit code | 0 |
| Tabel ter-restore | 47 |
| Jumlah tabel per skema | `47` (sumber) / `47` (restore) |

## 3. Parity Baris (hitung persis, bukan perkiraan)

| Tabel | Sumber | Restore | Cocok |
|---|---:|---:|:---:|
| users | 1 | 1 | ✅ |
| roles | 4 | 4 | ✅ |
| permissions | 67 | 67 | ✅ |
| categories | 4 | 4 | ✅ |
| products | 5 | 5 | ✅ |
| product_variants | 5 | 5 | ✅ |
| rental_assets | 22 | 22 | ✅ |
| rental_orders | 0 | 0 | ✅ |
| sales_orders | 0 | 0 | ✅ |
| payments | 0 | 0 | ✅ |
| sales_payments | 0 | 0 | ✅ |
| stock_movements | 0 | 0 | ✅ |
| sales_stock_reservations | 0 | 0 | ✅ |

Spot-check tabel kunci (`users`, `products`, `sales_orders`, `payments`) ada di salinan restore: 4/4.

## 4. Kesimpulan

- Backup penuh berhasil dari **database dev tanpa perlakuan destruktif**.
- Restore ke database bersih menghasilkan jumlah tabel dan baris yang identik.
- Prosedur aman untuk diulang dan didokumentasikan sebagai runbook operasional:
  lihat `.docs/BACKUP_AND_RESTORE.md`.

## 5. Catatan Keamanan

- Berkas dump mengandung data (termasuk hash password seed). Simpan pada lokasi privat, jangan
  di-commit ke Git. `.gitignore` sudah mengecualikan artefak backup.
- Untuk produksi, jalankan juga `mysqldump --single-transaction` pada jam sepi dan simpan di luar
  document root dengan akses terbatas.
