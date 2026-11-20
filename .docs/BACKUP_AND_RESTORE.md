# Backup & Restore — GEARORA (Runbook)

> Dokumen operasional. Verifikasi nyata ada di `.docs/PHASE_8_BACKUP_RESTORE_REPORT.md`.

## 1. Prinsip

- Backup GEARORA = **dump database MySQL** + **berkas unggahan privat** (`storage/app/private`)
  dan berkas `public` yang diunggah (`storage/app/public` atau disk publik).
- Backup **read-only** terhadap database sumber (`--single-transaction`). Jangan menaruh hasil
  dump di dalam web root atau repositori Git.
- Simpan dengan akses terbatas dan enkripsi bila berisi data pelanggan.

## 2. Backup Database

### 2.1 Linux / macOS

```bash
mysqldump --single-transaction --routines --triggers --events \
  --set-gtid-purged=OFF \
  -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p "$DB_DATABASE" \
  | gzip > storage/backups/gearora-$(date +%Y%m%d-%H%M%S).sql.gz
```

### 2.2 Windows (PowerShell, Laragon)

Simpan kredensial pada berkas `.cnf` privat (agar password tidak muncul di command line):

```ini
# storage/backups/my.cnf  (jangan di-commit)
[client]
host=127.0.0.1
port=3306
user=root
password="***"
```

```powershell
$mysql = "D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin"
& "$mysql\mysqldump.exe" --defaults-extra-file="storage\backups\my.cnf" `
  --single-transaction --routines --triggers --events --set-gtid-purged=OFF gearora `
  > "storage\backups\gearora-$(Get-Date -Format yyyyMMdd-HHmmss).sql"
```

> Tip PowerShell: hindari flag berawalan `--` langsung di baris perintah; bungkus dalam skrip
> `.ps1`/`.php` atau gunakan `--%` agar parser tidak salah tafsir.

## 3. Backup Berkas Unggahan

```bash
tar -czf storage/backups/uploads-$(date +%Y%m%d-%H%M%S).tar.gz storage/app/private storage/app/public
```

## 4. Restore Database (ke lingkungan kosong/uji)

```bash
# 1. Buat database target
mysql -u root -p -e "CREATE DATABASE gearora_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Impor
gunzip -c storage/backups/gearora-YYYYMMDD-HHMMSS.sql.gz | mysql -u root -p gearora_restore
```

Verifikasi setelah restore:

```sql
SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'gearora_restore';
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM products;
```

Jumlah tabel dan baris harus sama dengan sumber (lihat laporan Phase 8).

## 5. Restore Produksi (hati-hati)

Restore menimpa data. **Konfirmasi eksplisit wajib** sebelum menimpa database produksi.

1. Aktifkan maintenance mode: `php artisan down`.
2. Backup kondisi saat ini lebih dulu (dump) sebagai jaring pengaman.
3. Impor dump ke database produksi.
4. `php artisan migrate --force` bila skema berubah.
5. `php artisan config:clear && php artisan cache:clear`.
6. `php artisan up`.
7. Verifikasi: login Owner, buka katalog, cek satu order & pembayaran.

## 6. Rutinitas yang Disarankan

| Frekuensi | Aksi | Retensi |
|---|---|---|
| Harian | Dump DB + unggahan | 14 hari |
| Mingguan | Dump penuh + simpan off-site | 8 minggu |
| Bulanan | Uji restore ke database bersih | 6 bulan |

## 7. Keselamatan

- Jangan pernah menjalankan `migrate:fresh`, `db:wipe`, atau `reset --hard` pada database berisi
  data nyata.
- `.gitignore` mengecualikan `storage/backups` dan `storage/app/backups`.
- Perlakukan dump sebagai data sensitif (hash password, informasi pelanggan).
