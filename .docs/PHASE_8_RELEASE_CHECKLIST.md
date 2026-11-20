# PHASE 8 — Release Checklist

> Tanggal: 20 November 2026 · Status: **SIAP RILIS (kode)** dengan item hosting/Midtrans PENDING
> yang bergantung pada akses operator.

Legenda: ✅ selesai · ⛔ BLOCKED (butuh akses/lingkungan eksternal) · n/a tidak berlaku.

## 1. Kualitas Kode

| # | Item | Status | Bukti |
|---|---|---|---|
| 1 | Suite uji utama hijau | ✅ | 249 tests / 747 assertions |
| 2 | Integrasi MySQL hijau | ✅ | 5 tests / 58 assertions |
| 3 | Gaya kode (`pint --test`) | ✅ | lulus |
| 4 | `composer validate --strict` | ✅ | valid |
| 5 | Build aset (`npm run build`) | ✅ | sukses |
| 6 | Tidak ada `dd()`/`dump()`/kode debug tertinggal | ✅ | tinjauan |

## 2. Keamanan

| # | Item | Status | Bukti |
|---|---|---|---|
| 7 | Webhook signature-first (anti poisoning) | ✅ | WebhookSecurityTest |
| 8 | Retry event `Failed`, payload cacat tak mengunci | ✅ | WebhookSecurityTest |
| 9 | Gateway palsu dilarang di produksi | ✅ | PaymentGatewayGuardTest |
| 10 | Gate konfigurasi produksi | ✅ | ProductionConfigurationTest |
| 11 | IDOR/RBAC area admin & customer | ✅ | AdminRouteAuthorizationTest, CustomerOwnershipSecurityTest |
| 12 | CSRF grup web; webhook di luar CSRF tapi bertanda tangan | ✅ | CsrfSecurityTest |
| 13 | Unggahan privat & media publik aman (tanpa SVG) | ✅ | PrivateUploadSecurityTest |
| 14 | Rate limiting login | ✅ | RateLimitSecurityTest |
| 15 | `composer audit` bersih | ✅ | tidak ada advisory |
| 16 | `npm audit` bersih | ✅ | 0 kerentanan |
| 17 | Rahasia tidak ter-commit | ✅ | `.env` di-ignore; dokumen tanpa rahasia |
| 18 | Password Owner hanya via prompt rahasia | ✅ | ProvisionOwnerCommand |

## 3. Data & Integritas

| # | Item | Status | Bukti |
|---|---|---|---|
| 19 | Expiry reservasi stok retail | ✅ | SalesReservationExpiryTest, command `gearora:expire-sales-orders` |
| 20 | Late-settlement guard | ✅ | SalesReservationExpiryTest |
| 21 | Row-lock InnoDB (anti overselling) | ✅ | MySqlConcurrencyTest |
| 22 | Backup & restore tervalidasi | ✅ | PHASE_8_BACKUP_RESTORE_REPORT.md |
| 23 | Tidak ada operasi destruktif di DB dev | ✅ | audit prosedur |

## 4. QA Browser

| # | Item | Status | Bukti |
|---|---|---|---|
| 24 | 8 halaman publik render 200 | ✅ | PHASE_8_E2E_TEST_RESULTS.md |
| 25 | Alur daftar + login nyata | ✅ | Browser E2E |
| 26 | Kontrol akses admin nyata (403) | ✅ | Browser E2E |
| 27 | Bug halaman 403 ditemukan & diperbaiki | ✅ | ErrorPageRenderingTest + browser |

## 5. Dokumentasi & Proses

| # | Item | Status |
|---|---|---|
| 28 | Laporan Phase 8 lengkap (`.docs/PHASE_8_*`) | ✅ |
| 29 | Runbook backup/restore | ✅ |
| 30 | README/ARSITEKTUR/ROADMAP diperbarui | ✅ |
| 31 | CI (`quality.yml`) | ✅ |
| 32 | Commit terdokumentasi & push ke `main` | ✅ |

## 6. Item Bergantung Operator (PENDING)

| # | Item | Status | Catatan |
|---|---|---|---|
| 33 | Validasi hosting AeonFree (PHP/ekstensi/document root) | ⛔ | `.docs/PHASE_0_HOSTING_VALIDATION.md` |
| 34 | Midtrans Sandbox end-to-end (webhook HTTPS nyata) | ⛔ | Butuh URL publik |
| 35 | Set `APP_KEY`, `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false` di produksi | ⛔ | Dijaga `gearora:production-check` |

## 7. Keputusan

**Kode dinyatakan SIAP RILIS.** Item 33–35 adalah tugas konfigurasi/deployment yang tidak dapat
diselesaikan dari lingkungan audit ini dan sudah dipagari oleh gate otomatis
(`gearora:production-check`) sehingga deployment yang salah konfigurasi akan gagal lebih dulu.
