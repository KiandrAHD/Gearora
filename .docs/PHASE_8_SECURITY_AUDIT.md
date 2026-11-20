# PHASE 8 — Security Audit (GEARORA)

> Tanggal: 20 November 2026 · Basis commit: `8c3aaa5` (+ perbaikan Phase 8)
> Lingkup: webhook pembayaran, otorisasi/RBAC, IDOR, CSRF, unggahan berkas, rate limiting,
> header keamanan, penanganan rahasia, dependency, dan konfigurasi produksi.

Metode: tinjauan kode + uji otomatis + verifikasi browser nyata. Setiap klaim memiliki bukti.
Tidak ada kerentanan yang dieksploitasi pada sistem pihak ketiga atau data nyata.

---

## 1. Webhook pembayaran (Midtrans)

**Urutan validasi (setelah B1/B2 diperbaiki):**

1. Validasi struktur payload → `invalid_payload`.
2. **Verifikasi signature** `sha512(order_id + status_code + gross_amount + server_key)` →
   `invalid_signature` (dicatat pada namespace hash terpisah).
3. Resolusi attempt (rental → retail); attempt tak dikenal → `unknown_attempt` tanpa mendaftarkan event.
4. Validasi nominal & currency terhadap attempt.
5. Reservasi event (dedup + retry `Failed` di bawah row lock).
6. Efek finansial dalam transaksi terkunci.

**Properti keamanan:**

- Notifikasi dengan signature salah **tidak** dapat mengklaim `event_hash` notifikasi valid
  (anti *event poisoning*) — B1.
- Hanya event gagal-sementara yang boleh diretry; payload cacat tidak mengunci fingerprint — B2.
- Nominal/currency harus sama dengan attempt; mismatch dicatat `Rejected` dan tidak mengubah status uang.
- Endpoint webhook berada di grup `api` (tanpa CSRF) namun dibatasi `throttle:midtrans-webhook`
  120/menit per IP. `Server Key` tidak pernah dikirim ke browser/log (`payload_safe`).

**Bukti:** `tests/Feature/WebhookSecurityTest.php` (5 kasus), `tests/Feature/Phase5PaymentOperationsTest.php`.

## 2. Otorisasi & RBAC

- Kontrol akses **berlapis di server**: grup admin `['auth','staff']`, dan middleware
  `permission:<name>` pada route sensitif; controller juga memanggil `$this->authorize(...)`
  (policy) dan/atau `hasPermission(...)`.
- Matriks role→permission tunggal: `app/Support/Rbac/RbacDefaults.php` (dipakai seeder & test).
  Owner memegang seluruh permission; Admin operasional; Petugas Gudang inventaris/operasional rentan;
  Customer tanpa permission staff.
- Menu yang disembunyikan di Blade **bukan** kontrol akses (diverifikasi: akses langsung via URL ditolak).
- Middleware `EnsureUserIsStaff`: guest → redirect login; staf non-aktif atau bukan staf → 403.
- `EnsurePermission`: Owner melewati (via `Gate::before`), selain itu 403.

**Bukti:** `tests/Feature/AdminRouteAuthorizationTest.php`, `tests/Feature/RbacAndBookingTest.php`,
`tests/Feature/RouteSmokeTest.php`.

## 3. IDOR / kepemilikan data pelanggan

- Pelanggan tidak dapat melihat/membatalkan order pelanggan lain (rental & retail), tidak dapat
  mengunggah bukti untuk order milik orang lain, dan tidak dapat mengunduh bukti via route admin.
- Bukti pembayaran retail memvalidasi bahwa `payment.sales_order_id == salesOrder.id` (B8).
- ID tak dikenal → 404.

**Bukti:** `tests/Feature/CustomerOwnershipSecurityTest.php` (6 kasus).

## 4. CSRF

- Grup `web` memakai `VerifyCsrfToken`; endpoint webhook berada di grup `api` (di luar CSRF, sesuai
  desain, dan dilindungi signature + throttle).
- Laravel melewati CSRF saat `APP_ENV=testing`; test menetapkan `env=local` agar proteksi benar-benar
  dievaluasi: POST tanpa token → **419** dan tidak membuat data.

**Bukti:** `tests/Feature/CsrfSecurityTest.php`.

## 5. Unggahan berkas & endpoint media

- Bukti pembayaran: `file`, `max` dari config, `mimes:jpg,jpeg,png,pdf`. Disksimpan pada disk
  **privat** (`local` → `storage/app/private`). Diverifikasi: file valid tersimpan di disk privat;
  ekstensi terlarang dan berkas kebesaran ditolak; tidak ada berkas jatuh ke disk publik.
- `MediaController` (publik): hanya raster `jpg/jpeg/png/webp`; menolak traversal (`..`, `\`, null
  byte) dan ekstensi berbahaya; menambah `nosniff` dan CSP `default-src 'none'` + `sandbox` (B7).

**Bukti:** `tests/Feature/PrivateUploadSecurityTest.php`.

## 6. Rate limiting & autentikasi

- Login customer & staf memakai `RateLimiter` per email+IP (`gearora.login.max_attempts`,
  `decay_seconds`); melewati batas → pesan "Terlalu banyak…"; login sukses menghapus counter.
- Limiter global `api` (60/menit) dan `midtrans-webhook` (120/menit).

**Bukti:** `tests/Feature/RateLimitSecurityTest.php`.

## 7. Manajemen rahasia

- `.env` di-ignore Git; `APP_KEY`, key Midtrans SB, kredensial DB **tidak** di-commit.
- Server Key tidak dikembalikan ke klien maupun ditulis ke log; payload webhook disimpan melalui
  `MidtransSignature::safePayload`.
- Password Owner hanya via prompt rahasia (B6).
- Dokumen Phase 8 ini tidak memuat nilai rahasia apa pun.

## 8. Dependency & build

| Pemeriksaan | Hasil |
|---|---|
| `composer audit` | Tidak ada advisory |
| `npm audit` | 0 kerentanan |
| `composer validate --strict` | Valid |
| `npm run build` | Sukses |

## 9. Konfigurasi produksi (gate otomatis)

`ProductionReadiness::violations()` (dipakai saat boot produksi dan command
`gearora:production-check`) memeriksa: `APP_DEBUG=false`, `APP_URL` https, `APP_KEY` terisi,
`SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE` lax/strict,
`MIDTRANS_FAKE=false`. Pelanggaran → boot gagal / command keluar non-nol.

**Bukti:** `tests/Feature/ProductionConfigurationTest.php`, `tests/Feature/PaymentGatewayGuardTest.php`.

## 10. Halaman error (information disclosure)

- `APP_DEBUG=true` hanya untuk local; gate produksi mewajibkan `false`.
- View error generik (`404`, `403`, `4xx`, `500`) tidak membocorkan stack trace (B10).
- Respons 403/419 kini dirender dengan kode & pesan yang benar.

## 11. Risiko Sisa / Catatan

| Item | Tingkat | Catatan |
|---|---|---|
| Endpoint webhook tanpa allowlist IP Midtrans | Rendah | Signature + throttle sudah memadai; allowlist opsional bila Midtrans mempublikasikan rentang IP |
| CSP aplikasi utama belum seketat endpoint media | Rendah | Pertimbangkan kebijakan CSP global saat deployment produksi |
| Verifikasi hosting (HTTPS, document root) | **PENDING** | Harus diselesaikan operator (`.docs/PHASE_0_HOSTING_VALIDATION.md`) |
| Verifikasi Midtrans Sandbox nyata | **PENDING** | Butuh URL webhook HTTPS publik |

Tidak ada temuan **P0/P1** yang tersisa terbuka. Semua temuan P0/P1 dari `PHASE_8_BUG_REGISTER.md`
sudah diperbaiki dan diuji.
