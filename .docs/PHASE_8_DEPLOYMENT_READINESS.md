# PHASE 8 — Deployment Readiness

> Tanggal: 20 November 2026 · Target hosting: AeonFree (shared hosting, Apache + PHP + MySQL)

## 1. Ringkasan

Kode siap deploy. Deployment sebenarnya **ditahan** sampai operator menyelesaikan validasi hosting
(`.docs/PHASE_0_HOSTING_VALIDATION.md`) dan konfigurasi produksi. Gate otomatis
`php artisan gearora:production-check` memastikan konfigurasi tidak aman menggagalkan deploy.

## 2. Prasyarat Runtime Produksi

| Item | Syarat | Catatan |
|---|---|---|
| PHP | ≥ 8.2 | Ekstensi: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `curl`, `dom`, `xml`, `tokenizer`, `bcmath`, `intl`, `gd`, `zip` |
| MySQL | 8.0+ InnoDB, `utf8mb4` | Satu database |
| Node/Composer di server | **Tidak diperlukan** | Build `vendor/` + `public/build/` di lokal, unggah |
| Document root | Hanya isi `public/` | Source privat di luar web root |

## 3. Variabel Lingkungan Produksi (wajib)

| Variabel | Nilai produksi | Dicek oleh |
|---|---|---|
| `APP_ENV` | `production` | — |
| `APP_DEBUG` | `false` | ProductionReadiness |
| `APP_URL` | `https://…` | ProductionReadiness |
| `APP_KEY` | hasil `key:generate` | ProductionReadiness |
| `SESSION_SECURE_COOKIE` | `true` | ProductionReadiness |
| `SESSION_HTTP_ONLY` | `true` | ProductionReadiness |
| `SESSION_SAME_SITE` | `lax`/`strict` | ProductionReadiness |
| `MIDTRANS_FAKE` | `false` | ProductionReadiness |
| `MIDTRANS_IS_PRODUCTION` | sesuai keinginan business | — |
| `MIDTRANS_SERVER_KEY`/`CLIENT_KEY` | kredensial nyata | — |
| `GEARORA_PAYMENT_GATEWAY_ENABLED` | `true` bila gateway aktif | — |

> `Server Key` hanya di server. Jangan kirim ke browser/log/commit.

## 4. Urutan Deploy

```bash
# (lokal) build aset & vendor
npm ci && npm run build
composer install --no-dev --optimize-autoloader

# (server) setelah unggah vendor/ + public/build/
php artisan key:generate            # hanya sekali, jika APP_KEY belum ada
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder   # matriks role/permission
php artisan gearora:production-check # GATE: harus sukses
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Buat akun Owner:

```bash
php artisan gearora:provision-owner   # password via prompt rahasia (tidak lewat argumen)
```

## 5. Pasca-Deploy (Smoke Test Manual)

1. `GET /up` → sehat.
2. Beranda, katalog, detail produk, panduan, rekomendasi trip → 200.
3. Login admin (`/admin/login`) → dashboard.
4. Tamu buka `/admin` → redirect login; customer → 403.
5. Buat sales order kecil, bayar tunai (dicatat admin) → stok berkurang sekali.
6. Biarkan satu order transfer tanpa bayar → jalankan `php artisan gearora:expire-sales-orders` →
   order `Expired`, stok kembali.
7. Webhook Midtrans (bila gateway aktif) dari dashboard Midtrans → payment `settled` tepat sekali.

## 6. Rencana Rollback

1. `php artisan down`.
2. Pulihkan database dari dump terakhir (lihat `.docs/BACKUP_AND_RESTORE.md` §5).
3. Kembalikan rilis kode sebelumnya (checkout tag/commit sebelumnya).
4. `composer install --no-dev --optimize-autoloader` bila perlu.
5. `php artisan config:clear && php artisan route:clear && php artisan view:clear`.
6. `php artisan up`.
7. Verifikasi smoke test.

## 7. Scheduler / Expiry

Karena hosting tanpa cron worker persisten, expiry dijalankan dua cara:

- **Opportunistik**: `scopeActive()` menyaring reservasi kedaluwarsa saat query stok (stok tak
  tertahan walau job belum jalan).
- **Terjadwal/manual**: `php artisan gearora:expire-sales-orders` (dijadwalkan via cron cPanel bila
  tersedia, atau dijalankan berkala oleh operator).

## 8. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| PHP hosting < 8.2 | Ganti versi PHP di cPanel, bukan menurunkan framework |
| Document root tidak aman | Tahan deploy sampai konfigurasi aman terverifikasi |
| Cron tidak tersedia | Expiry oportunistik + command manual |
| Gateway salah konfigurasi | Gate `MIDTRANS_FAKE` + `production-check` |

## 9. Kesimpulan

Prasyarat teknis terpenuhi di sisi kode dan uji. Deployment menunggu aksi operator pada
`PHASE_0_HOSTING_VALIDATION.md`; gate otomatis akan mencegah rilis berkonfigurasi tidak aman.
