# PHASE 8 — E2E Test Results (Browser QA)

> Tanggal: 20 November 2026 · Alat: agent-browser (Chrome headless) · Target: `php artisan serve`
> (`http://127.0.0.1:8123` lalu `:8124`) · Database: `gearora` (dev)

Tujuan: memvalidasi alur nyata melalui browser (HTTP nyata + CSRF nyata + render Blade), bukan
hanya test HTTP in-process. Semua permintaan di bawah dijalankan pada server nyata.

## 1. Render Halaman Publik

| Halaman | URL | Judul dokumen | Status |
|---|---|---|---|
| Beranda | `/` | `Gear Up. Explore More. — GEARORA` | 200 |
| Katalog | `/katalog` | `Katalog — GEARORA` | 200 |
| Panduan | `/panduan` | `Panduan — GEARORA` | 200 |
| Rekomendasi Trip | `/rekomendasi-trip` | `Rekomendasi Trip — GEARORA` | 200 |
| Keranjang | `/keranjang` | `Keranjang Belanja — GEARORA` | 200 |
| Masuk | `/masuk` | `Masuk — GEARORA` | 200 |
| Daftar | `/daftar` | `Daftar — GEARORA` | 200 |
| Login Admin | `/admin/login` | `Masuk Admin — GEARORA` | 200 |

Screenshot diambil untuk tiap halaman (artefak tersimpan di direktori kerja agent-browser).
Struktur DOM diperiksa: header navigasi, formulir, dan footer sesuai desain.

## 2. Redirect Akses Admin (tamu)

- Permintaan `/admin` sebagai tamu → **redirect ke `/masuk`** (judul `Masuk — GEARORA`).
- Di luar browser, `fetch('/admin', {Accept: application/json})` mengembalikan **403**
  (`"Akun ini tidak memiliki akses ke Admin Console."`) untuk customer terautentikasi.

## 3. E2E Pendaftaran & Login Customer (HTTP + CSRF nyata)

1. Buka `/daftar`, isi nama, email, telepon, alamat, password + konfirmasi, submit.
2. **Hasil:** akun dibuat (diverifikasi di database: `users` bertambah; password `Hash::check` = true).
3. Buka `/masuk`, isi email/password, submit.
4. **Hasil:** login sukses → **redirect ke `/akun`** (judul `Ringkasan akun — GEARORA`),
   menampilkan ringkasan akun pelanggan (0 pesanan).

Akun QA memakai domain `gearora-test.invalid` dan **dihapus kembali** setelah verifikasi,
sehingga database dev tetap pada keadaan awal (1 user seed).

## 4. Temuan QA Browser (B10) — DIPERBAIKI

- **Gejala awal:** customer terautentikasi membuka `/admin` menerima respons dengan **status 403**
  tetapi isi halaman bertuliskan **"500 — Terjadi kesalahan"**.
- **Akar masalah:** hanya tersedia view `errors/404`, `errors/500`, `errors/minimal`; fallback 4xx
  memakai view 500.
- **Perbaikan:** ditambah `errors/403.blade.php` dan fallback `errors/4xx.blade.php`.
- **Verifikasi ulang (browser nyata):**

| Permintaan | Status HTTP | Judul | Isi |
|---|---|---|---|
| `/admin` (customer) | 403 | `403 — GEARORA` | "Akses ditolak — Kamu tidak memiliki izin…" |
| `/admin/reports` (customer) | 403 | `403 — GEARORA` | "Akses ditolak — Kamu tidak memiliki izin…" |

**Kesimpulan:** status HTTP dan tampilan kini konsisten.

## 5. Verifikasi Tambahan

| Skenario | Hasil |
|---|---|
| `/admin/dashboard` (URL non-standar) | 404 (route benar adalah `/admin`) — memicu audit view 404 |
| Halaman 404 (rute acak) | `404 — Halaman tidak ditemukan` |
| POST login dengan kredensial benar | sukses → `/akun` |
| POST webhook tanpa body | 400 (tidak crash) |

## 6. Keterbatasan

- **Midtrans Sandbox nyata:** BLOCKED — tidak ada URL webhook HTTPS publik di lingkungan audit.
  Alur gateway diuji lewat `FakePaymentGateway` + unit test signature.
- **Hosting AeonFree:** BLOCKED — tanpa akses cPanel.
- Inspeksi visual piksel tidak dilakukan otomatis; verifikasi memakai judul dokumen, teks DOM,
  dan artefak screenshot.

Semua halaman publik, alur otentikasi, dan kontrol akses admin lolos verifikasi browser nyata.
