# PHASE 9 — Browser E2E Results

Tanggal: 20 November 2026

## Lingkungan uji

- Server: `php artisan serve --host=127.0.0.1 --port=8100`.
- `APP_ENV=local`, `DB_CONNECTION=sqlite`,
  `DB_DATABASE=storage/phase9-e2e.sqlite`, `SESSION_DRIVER=file`,
  `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `APP_URL=http://127.0.0.1:8100`.
- Database disegar (`migrate --seed`) — **tidak** menyentuh basis data `gearora`.
- Akun uji dibuat eksplisit: Owner E2E (role Owner), Customer E2E (role Customer).
- Browser otomasi: Chrome headless via agent-browser. Tangkapan layar disimpan di
  `storage/e2e/` dan **tidak** di-commit.

## Hasil

| # | Alur | Langkah | Hasil |
|---|---|---|---|
| 1 | Home publik | GET `/` | HTTP 200, judul & navbar tampil |
| 2 | Katalog sewa | GET `/katalog?mode=sewa` | HTTP 200, hero + kartu + filter |
| 3 | Katalog sewa scroll | scroll 900px | sidebar `sticky`, top tetap 96px |
| 4 | Katalog beli | GET `/katalog?mode=beli` | HTTP 200, harga mode beli |
| 5 | Wizard langkah 1–4 | isi destinasi/durasi/peserta/mode/tanggal | ringkasan ter-update |
| 6 | Wizard langkah 5 | "Buat rekomendasi" | "3 item cocok", form paket ter-render |
| 7 | Pilih gear + mode | pilih item, satu diubah ke `beli` | GET `/rekomendasi-trip/paket` |
| 8 | Pratinjau (guest) | buka review tanpa login | tombol "Masuk untuk checkout" |
| 9 | Login return | `/masuk?redirect=/rekomendasi-trip/paket` | kembali ke review setelah login |
| 10 | Checkout transfer | POST checkout | redirect `/akun/paket/GRP-20261120-VGIL` |
| 11 | Dokumen paket | GET `.../dokumen` | HTML siap cetak ter-render |
| 12 | PDF paket | GET `.../pdf` | `200 application/pdf`, 879722 byte |
| 13 | Admin paket trip | GET `/admin/trip-packages` | HTTP 200, daftar + filter status |
| 14 | Studio panduan | login owner, GET `/admin/guides/create` | editor + toolbar ter-render |
| 15 | Unggah gambar inline | POST `/admin/guides/media` (PNG) | `201 {path,url,alt}` |
| 16 | Tolak berkas tak aman | POST `.php` bertipe `application/x-php` | `422` |
| 17 | Sisip YouTube | `insertYoutubeUrl()` URL valid | `@youtube[dQw4w9WgXcQ]` disisipkan |
| 18 | Tolak URL YouTube invalid | `insertYoutubeUrl()` non-YouTube | pesan "URL YouTube tidak valid." |
| 19 | Simpan + terbitkan panduan | submit form (published) | panduan tersimpan & tampil publik |
| 20 | Halaman panduan publik | GET `/panduan/panduan-gunung-prau-e2e` | HTTP 200 |

## Verifikasi keamanan konten (langkah 20)

Konten uji memuat `<script>alert('xss')</script>`,
`<iframe src="https://evil.example">`, dan `[tautan jahat](javascript:alert(1))`.

| Pemeriksaan DOM | Hasil |
|---|---|
| `script` di dalam konten | 0 |
| `iframe` total | 1 |
| `src` iframe | `https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ` |
| `<script>` mentah | ter-escape menjadi teks (`&lt;script&gt;`) |
| `<iframe>` mentah | ter-escape menjadi teks |
| Tautan `javascript:` | dinetralkan menjadi teks (tanpa `href`) |
| URL YouTube mandiri (`?t=10`) | di-normalisasi, parameter dibuang |
| Gambar inline lokal | disajikan dari `/media/guide-inline/...` |

## Integritas checkout

- Harga pada halaman pratinjau: sewa Rp 300.000 + beli Rp 850.000 = Rp 1.150.000;
  nilai ini dihitung ulang server saat checkout, bukan dari input klien.
- Checkout ganda dengan `checkout_key` sama tidak membuat pesanan kedua
  (dibuktikan otomatis di `TripPackageTest`).

## Batasan uji browser

1. Chrome headless di lingkungan ini memakai ukuran jendela tetap (~1264px);
   override `--window-size` diabaikan (`innerWidth` tetap 1264). Emulasi viewport
   mobile sejati **tidak** tercapai, sehingga kesesuaian layout piksel di ponsel
   tidak diverifikasi browser. Verifikasi mobile bersandar pada breakpoint CSS,
   uji fungsional drawer filter, dan `tests/Feature/NavigationAndMobileTest.php`
   serta `PublicNavigationLayoutTest.php` (9 passed masing-masing).
2. Pembayaran memakai metode **transfer** (gateway Midtrans tidak dipanggil).
   Tidak ada transaksi Midtrans produksi.
3. Tidak ada akses Figma; tidak ada klaim kecocokan piksel.
