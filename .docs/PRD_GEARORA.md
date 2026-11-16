# Product Requirements Document (PRD) — GEARORA

> **Product:** GEARORA — Outdoor Supply  
> **Tagline:** *Gear Up. Explore More.*  
> **Product type:** Website for outdoor equipment rental and retail  
> **Document version:** 1.0  
> **Date:** 16 November 2026  
> **Status:** Product baseline for design, implementation, and PSAS delivery

---

## 1. Ringkasan Produk

GEARORA adalah platform berbasis website untuk penyewaan dan penjualan perlengkapan outdoor seperti hiking, trekking, camping, dan aktivitas alam lainnya. Platform ini menyatukan pengalaman pelanggan dalam mencari gear, memeriksa spesifikasi dan ketersediaan, membuat reservasi, memperoleh panduan dasar, serta memantau transaksi. Di sisi operasional, GEARORA menyediakan Admin Dashboard untuk mengelola katalog, inventaris, pesanan, pembayaran, pengambilan, pengembalian, kondisi barang, dan laporan.

GEARORA bukan sekadar katalog produk. Nilai utama produk adalah membantu pelanggan mempersiapkan perlengkapan sebelum datang ke lokasi rental, sekaligus membantu pemilik rental mengendalikan aset dan transaksi secara lebih terorganisasi.

## 2. Latar Belakang dan Masalah

Proses rental yang dilakukan melalui percakapan manual dapat membuat pelanggan harus menanyakan ketersediaan, harga, ukuran, kapasitas, dan ketentuan barang satu per satu. Proses tersebut memakan waktu, khususnya bagi pelanggan yang datang dari luar daerah atau memiliki waktu keberangkatan terbatas. Pelanggan pemula juga dapat kesulitan menentukan gear yang sesuai dengan aktivitas, tujuan, jumlah peserta, dan durasi perjalanan.

Di sisi pemilik rental, pencatatan yang tersebar berisiko menimbulkan ketidaksesuaian stok, benturan jadwal sewa, keterlambatan pengembalian yang tidak terpantau, serta riwayat kondisi perlengkapan yang tidak lengkap.

GEARORA menjawab masalah tersebut melalui satu alur digital: discovery → pemeriksaan informasi dan ketersediaan → reservasi → pembayaran/konfirmasi → pengambilan → pengembalian dan inspeksi.

## 3. Visi, Tujuan, dan Nilai Produk

### 3.1 Visi

Menjadi platform outdoor supply yang memudahkan orang menyiapkan perlengkapan perjalanan dan membantu bisnis rental mengelola operasionalnya secara terstruktur.

### 3.2 Tujuan Produk

1. Memudahkan pelanggan menemukan dan mereservasi perlengkapan outdoor sebelum datang ke lokasi rental.
2. Menyediakan informasi produk yang jelas, termasuk foto, spesifikasi, ukuran/kapasitas, harga, dan kondisi.
3. Mengurangi risiko pemesanan melebihi stok yang tersedia pada rentang tanggal tertentu.
4. Membantu pelanggan memahami perlengkapan melalui rekomendasi berbasis aturan dan konten panduan.
5. Menyatukan pengelolaan inventaris, pesanan, pembayaran, pengambilan, pengembalian, inspeksi, dan laporan dalam Admin Dashboard.
6. Mendukung dua model transaksi yang berbeda: rental berdasarkan durasi dan pembelian produk.

### 3.3 Nilai Produk

- **Preparation first:** pelanggan dapat menyiapkan gear sebelum tiba di lokasi rental.
- **Inventory visibility:** status stok dan ketersediaan berdasarkan periode lebih transparan.
- **Helpful information:** spesifikasi, ukuran, kapasitas, dan panduan berada dalam satu tempat.
- **Operational control:** admin memiliki rekam jejak transaksi dan kondisi aset.
- **Rental and retail:** produk sewa dan produk jual didukung tanpa mencampur aturan transaksinya.

## 4. Target Pengguna dan Persona

| Persona | Kebutuhan utama | Hambatan yang diselesaikan GEARORA |
|---|---|---|
| Pendaki pemula | Mengetahui gear yang diperlukan dan cara menggunakannya | Belum memahami kebutuhan gear dan spesifikasi produk |
| Pendaki berpengalaman | Mereservasi gear tertentu untuk periode perjalanan | Perlu memastikan ketersediaan dan kondisi barang |
| Camper / traveler | Menyewa perlengkapan untuk aktivitas sesekali | Tidak ingin membeli perlengkapan yang jarang digunakan |
| Pelanggan pembelian | Membeli perlengkapan pendukung | Informasi dan ketersediaan barang perlu tersaji jelas |
| Admin rental | Mengelola produk, stok, transaksi, dan layanan | Pencatatan manual dan informasi tersebar |
| Owner | Memantau kinerja bisnis dan risiko operasional | Membutuhkan laporan serta kontrol akses |
| Petugas gudang | Menyiapkan, menyerahkan, menerima, dan memeriksa barang | Perlu daftar barang dan status unit yang akurat |

## 5. Ruang Lingkup Produk

### 5.1 Cakupan Pengalaman Pelanggan

1. Homepage berisi proposisi nilai, kategori populer, produk unggulan, alur rental, dan akses menuju fitur utama.
2. Katalog dengan pencarian, filter kategori, dan pemisahan mode **SEWA** serta **BELI**.
3. Detail produk yang menampilkan foto, deskripsi, harga, spesifikasi, ukuran/kapasitas, dan ketentuan yang relevan.
4. Registrasi, login, profil, serta riwayat pesanan.
5. Pemilihan tanggal mulai dan tanggal pengembalian untuk produk rental.
6. Pemeriksaan ketersediaan berdasarkan periode, varian, jumlah, dan kondisi aset.
7. Keranjang/daftar pesanan serta ringkasan biaya sebelum pemesanan dikirim.
8. Pengajuan pesanan dan tampilan status transaksi.
9. Pengiriman bukti pembayaran untuk metode manual yang disediakan usaha.
10. Rekomendasi perlengkapan berdasarkan aktivitas, tujuan, durasi, dan jumlah peserta.
11. Artikel atau panduan penggunaan perlengkapan outdoor.
12. Katalog dan alur pembelian untuk produk yang dijual.

### 5.2 Cakupan Admin Dashboard

Admin Dashboard mencakup keseluruhan area operasional berikut. Modul ditampilkan berdasarkan izin pengguna; tidak semua role boleh mengakses seluruh modul.

| Modul | Kemampuan inti |
|---|---|
| Dashboard | Ringkasan pesanan, rental aktif, pengembalian jatuh tempo, stok rendah, barang rusak/perawatan, dan ringkasan pendapatan |
| Authentication & Access | Login admin, logout, pengamanan sesi, role dan permission |
| Users & Roles | Melihat pelanggan, mengelola akun staf, mengatur role dan izin sesuai kewenangan |
| Categories | CRUD kategori dan status aktif |
| Products | CRUD produk, foto, deskripsi, tipe layanan sewa/jual, harga, dan status tayang |
| Product Variants | Ukuran, kapasitas, warna, atau atribut pembeda lain; harga dan stok per varian jika diperlukan |
| Inventory | Stok fisik, unit rental, kondisi, lokasi penyimpanan sederhana, dan ketersediaan |
| Rental Orders | Daftar, detail, konfirmasi, pembatalan sesuai kebijakan, dan perubahan status pesanan rental |
| Rental Assignments | Penentuan unit/aset yang diserahkan untuk item rental dan pencatatan pengambilan |
| Payments | Pemeriksaan bukti pembayaran, konfirmasi atau penolakan, serta riwayat transaksi pembayaran |
| Deposits | Pencatatan uang jaminan, pengembalian, pemotongan, dan alasan pemotongan |
| Inspections | Pemeriksaan kondisi sebelum dan setelah rental, catatan kerusakan, dan bukti foto bila dibutuhkan |
| Returns | Pencatatan penerimaan barang, waktu kembali, keterlambatan, dan status penyelesaian rental |
| Additional Charges | Biaya keterlambatan, kerusakan, kehilangan, atau biaya lain sesuai kebijakan yang disetujui |
| Sales Orders | Pesanan pembelian, status pembayaran, dan penyelesaian penjualan |
| Stock Movements | Riwayat barang masuk, keluar, penyesuaian stok, kerusakan, dan kehilangan |
| Trip Planner Content | Pengelolaan aturan/paket rekomendasi gear untuk jenis aktivitas dan kebutuhan perjalanan |
| Equipment Guides | CRUD artikel panduan, kategori, gambar/video tertaut, status publikasi, dan sumber referensi |
| Reports | Rekap rental, penjualan, pembayaran, deposit, keterlambatan, kondisi aset, serta ekspor data dasar |

### 5.3 Batasan Ruang Lingkup MVP

MVP harus dapat didemonstrasikan secara utuh dari sisi pelanggan dan admin. Prioritas wajib:

- Homepage, katalog, detail produk, registrasi/login.
- Admin login dengan role minimum Owner/Admin/Petugas Gudang.
- CRUD kategori, produk, varian, serta pengelolaan stok.
- Pembuatan rental order dengan tanggal mulai dan tanggal kembali.
- Pemeriksaan ketersediaan dan pencegahan overbooking.
- Ringkasan biaya dan status pembayaran manual.
- Admin dapat mengonfirmasi pembayaran, mencatat pengambilan, pengembalian, dan kondisi barang.
- Dashboard operasional dasar dan laporan sederhana.

Rekomendasi trip dan konten panduan disiapkan pada tahap berikutnya, tetapi model konten dan navigasinya harus dipertimbangkan sejak awal.

## 6. Functional Requirements

Prioritas: **P0** = wajib untuk MVP; **P1** = penting setelah alur inti stabil; **P2** = pengembangan lanjutan.

### 6.1 Akun dan Hak Akses

| ID | Requirement | Prioritas |
|---|---|---|
| FR-AUTH-01 | Pelanggan dapat mendaftar menggunakan identitas dan kredensial yang valid. | P0 |
| FR-AUTH-02 | Pengguna terdaftar dapat login dan logout. | P0 |
| FR-AUTH-03 | Admin Dashboard hanya dapat diakses oleh akun staf yang telah diberi izin. | P0 |
| FR-AUTH-04 | Sistem memeriksa permission pada setiap aksi sensitif, bukan hanya menyembunyikan menu. | P0 |
| FR-AUTH-05 | Owner dapat mengelola akun staf dan role sesuai kebijakan. | P1 |

### 6.2 Katalog dan Inventaris

| ID | Requirement | Prioritas |
|---|---|---|
| FR-CAT-01 | Pelanggan dapat menelusuri produk berdasarkan kategori dan tipe layanan. | P0 |
| FR-CAT-02 | Pelanggan dapat mencari produk berdasarkan nama/kata kunci. | P0 |
| FR-CAT-03 | Detail produk memuat foto, deskripsi, harga, spesifikasi, ukuran/kapasitas, dan ketentuan yang relevan. | P0 |
| FR-CAT-04 | Admin dapat membuat, membaca, memperbarui, dan menonaktifkan kategori/produk. | P0 |
| FR-CAT-05 | Admin dapat mengelola varian dan atribut produk. | P0 |
| FR-INV-01 | Sistem mencatat stok produk jual dan aset fisik produk rental. | P0 |
| FR-INV-02 | Sistem mencatat status unit rental: tersedia, dipesan, disewa, perawatan, rusak, atau hilang. | P0 |
| FR-INV-03 | Setiap perubahan stok memiliki alasan dan jejak stock movement. | P1 |
| FR-INV-04 | Produk yang sudah memiliki transaksi tidak dihapus permanen; produk dinonaktifkan/diarsipkan. | P0 |

### 6.3 Rental dan Ketersediaan

| ID | Requirement | Prioritas |
|---|---|---|
| FR-REN-01 | Pelanggan dapat menentukan tanggal mulai dan tanggal pengembalian. | P0 |
| FR-REN-02 | Sistem menghitung durasi dan estimasi harga sewa berdasarkan kebijakan harga. | P0 |
| FR-REN-03 | Sistem menghitung ketersediaan per varian dan periode yang diminta. | P0 |
| FR-REN-04 | Sistem menolak kuantitas yang melebihi ketersediaan. | P0 |
| FR-REN-05 | Pelanggan dapat mengirim rental order dan memperoleh kode transaksi unik. | P0 |
| FR-REN-06 | Admin dapat mengonfirmasi, menolak, atau membatalkan pesanan sesuai aturan dan mencatat alasannya. | P0 |
| FR-REN-07 | Admin dapat menetapkan aset fisik untuk pesanan sebelum barang diserahkan. | P1 |
| FR-REN-08 | Admin dapat mencatat pengambilan dan pengembalian barang. | P0 |
| FR-REN-09 | Sistem menyimpan hasil inspeksi kondisi sebelum dan sesudah rental. | P1 |
| FR-REN-10 | Sistem mencatat keterlambatan dan biaya tambahan jika ketentuan usaha menetapkannya. | P1 |

### 6.4 Pembayaran, Deposit, dan Penjualan

| ID | Requirement | Prioritas |
|---|---|---|
| FR-PAY-01 | Sistem menampilkan rincian biaya rental sebelum order dikirim. | P0 |
| FR-PAY-02 | Pelanggan dapat mengunggah bukti pembayaran untuk metode manual yang disediakan. | P0 |
| FR-PAY-03 | Admin dapat memverifikasi atau menolak bukti pembayaran dan memasukkan catatan. | P0 |
| FR-PAY-04 | Status pembayaran tersimpan terpisah dari status rental order. | P0 |
| FR-DEP-01 | Deposit dicatat sebagai uang jaminan terpisah dari pendapatan rental. | P1 |
| FR-DEP-02 | Admin dapat mengembalikan deposit atau mencatat pemotongan beserta alasan. | P1 |
| FR-SAL-01 | Sistem mendukung order pembelian untuk produk bertipe jual. | P1 |
| FR-SAL-02 | Sistem mengurangi stok jual setelah transaksi mencapai status yang ditetapkan usaha. | P1 |

### 6.5 Rekomendasi, Panduan, dan Laporan

| ID | Requirement | Prioritas |
|---|---|---|
| FR-TRIP-01 | Pelanggan dapat mengisi aktivitas, tujuan, durasi, jumlah peserta, dan kebutuhan khusus. | P1 |
| FR-TRIP-02 | Sistem memberi rekomendasi gear berdasarkan aturan yang dapat ditinjau admin. | P1 |
| FR-GUIDE-01 | Pelanggan dapat membaca panduan perlengkapan yang telah dipublikasikan. | P1 |
| FR-GUIDE-02 | Admin/editor dapat mengelola artikel dan status publikasinya. | P1 |
| FR-REP-01 | Owner/Admin dapat melihat ringkasan rental, penjualan, pembayaran, dan pengembalian. | P0 |
| FR-REP-02 | Laporan dapat difilter berdasarkan rentang tanggal dan status. | P1 |
| FR-REP-03 | Laporan dasar dapat diekspor ke CSV. | P1 |

## 7. Peran dan Matriks Akses

| Kemampuan | Owner | Admin | Petugas Gudang | Pelanggan |
|---|:---:|:---:|:---:|:---:|
| Melihat dashboard operasional | ✓ | ✓ | Terbatas | — |
| Mengelola role dan permission | ✓ | Terbatas* | — | — |
| Mengelola kategori/produk | ✓ | ✓ | Baca | — |
| Mengelola inventaris dan stock movement | ✓ | ✓ | ✓ | — |
| Mengelola rental order | ✓ | ✓ | Baca/operasional | Pesanan sendiri |
| Verifikasi pembayaran | ✓ | ✓ | — | — |
| Serah-terima dan pengembalian | ✓ | ✓ | ✓ | Melihat status |
| Mengelola deposit/biaya tambahan | ✓ | ✓ | Catat kondisi | Melihat rincian |
| Mengelola panduan/rekomendasi | ✓ | ✓ | — | — |
| Melihat laporan | ✓ | ✓ | Terbatas | Riwayat sendiri |

`*` Hak Admin terhadap pengguna/role harus dapat dibatasi Owner; Admin tidak boleh menaikkan privilege dirinya sendiri.

## 8. Alur Pengguna Utama

### 8.1 Alur Rental Pelanggan

1. Pelanggan membuka Homepage dan masuk ke katalog.
2. Pelanggan memilih kategori/produk dan membaca detail spesifikasi.
3. Pelanggan memilih varian, jumlah, tanggal mulai, dan tanggal pengembalian.
4. Sistem menghitung durasi, estimasi biaya, dan ketersediaan.
5. Pelanggan meninjau ringkasan pesanan dan ketentuan rental.
6. Pelanggan login/registrasi bila belum memiliki sesi.
7. Sistem membuat order dengan kode unik dan status awal.
8. Pelanggan melakukan pembayaran sesuai instruksi dan mengirim bukti bila memakai transfer manual.
9. Admin memverifikasi pembayaran dan mengubah status order.
10. Petugas menyiapkan unit, mencatat kondisi awal, dan mencatat serah-terima.
11. Pelanggan mengembalikan perlengkapan pada tanggal yang disepakati.
12. Petugas memeriksa kondisi, mencatat biaya tambahan bila sah, dan memperbarui status unit.
13. Admin menyelesaikan transaksi dan memproses pengembalian deposit sesuai hasil pemeriksaan.

### 8.2 Alur Operasional Admin

1. Admin login dan melihat ringkasan dashboard.
2. Admin mengelola katalog, varian, stok, serta status unit.
3. Admin meninjau order baru, ketersediaan, dan pembayaran.
4. Admin mengonfirmasi order setelah syarat terpenuhi.
5. Petugas gudang menyiapkan dan menyerahkan unit yang sesuai.
6. Sistem menyimpan unit yang dialokasikan, waktu, dan petugas.
7. Dashboard menampilkan jadwal pengembalian yang akan datang dan terlambat.
8. Saat pengembalian, petugas mencatat kondisi, kelengkapan, dan biaya tambahan bila ada.
9. Sistem memperbarui status aset, deposit, dan order.
10. Owner/Admin meninjau laporan operasional.

## 9. Business Rules

1. **Pemisahan rental dan penjualan:** rental memiliki periode pemakaian dan pengembalian; penjualan adalah perpindahan kepemilikan barang. Kedua tipe transaksi tidak boleh menggunakan aturan stok yang sama secara membabi buta.
2. **Perhitungan durasi:** untuk baseline MVP, durasi dihitung dari selisih tanggal kembali dan tanggal mulai dengan minimum satu hari. Kebijakan jam pengambilan, grace period, dan pembulatan jam perlu disepakati sebelum implementasi final.
3. **Harga rental:** estimasi biaya = jumlah unit × harga sewa per hari × jumlah hari. Simpan harga saat order dibuat sebagai snapshot agar perubahan harga katalog tidak mengubah transaksi lama.
4. **Ketersediaan:** jumlah tersedia dihitung dari unit yang layak pakai dikurangi unit yang telah dialokasikan pada order yang masih memblokir stok untuk periode yang beririsan.
5. **Pencegahan overbooking:** validasi harus diulang di server ketika order disimpan, di dalam database transaction; hasil pemeriksaan pada halaman katalog tidak boleh dianggap sebagai jaminan stok.
6. **Status unit:** unit dalam perawatan, rusak, atau hilang tidak dapat disewakan.
7. **Pembayaran:** status pembayaran disimpan terpisah dari status order. Bukti transfer tidak otomatis berarti pembayaran sudah lunas.
8. **Deposit:** deposit adalah liabilitas/uang jaminan yang harus dilacak sampai dikembalikan atau dipotong dengan alasan dan persetujuan yang tercatat; jangan masukkan ke pendapatan rental.
9. **Pengembalian:** order tidak dapat diselesaikan sebelum unit yang dialokasikan dicatat kembali atau memiliki status kehilangan/kerusakan yang ditangani.
10. **Audit:** perubahan harga, stok, status pembayaran, status order, deposit, dan hasil inspeksi harus mencatat pengguna serta waktu perubahan.
11. **Soft delete/archive:** data yang telah direferensikan transaksi tidak dihapus permanen melalui UI normal.
12. **Rekomendasi:** rekomendasi gear adalah saran persiapan, bukan jaminan keselamatan dan bukan pengganti informasi resmi destinasi, cuaca, atau pelatihan.

## 10. Non-Functional Requirements

| Area | Persyaratan |
|---|---|
| Security | Validasi server-side, CSRF, hashing password melalui Laravel, authorization/policies, session protection, rate limit login, pembatasan unggahan, HTTPS, dan secret di `.env` |
| Data integrity | Foreign key, transaksi database untuk perubahan stok/order, nominal uang menggunakan `DECIMAL`, dan snapshot harga pada order |
| Performance | Query dipaginasi, eager loading untuk relasi, gambar dioptimasi, dan dashboard menghindari query agregat berulang yang tidak perlu |
| Availability | Sistem harus tetap dapat digunakan tanpa worker atau proses background persisten; fitur berjadwal tidak menjadi ketergantungan MVP |
| Usability | Bahasa UI Indonesia, format Rupiah, timezone `Asia/Jakarta`, validasi dan pesan kesalahan yang mudah dipahami |
| Responsive | Pengalaman pelanggan dan Admin Dashboard harus dapat digunakan di desktop dan mobile; tabel admin dapat memiliki pola responsive yang sesuai |
| Maintainability | Kode mengikuti MVC Laravel, pemisahan Service untuk aturan bisnis, migration/seeders, test, dan dokumentasi |
| Accessibility | Label formulir, keyboard focus yang terlihat, kontras cukup, semantic HTML, dan pesan status yang tidak hanya mengandalkan warna |
| Backup | Backup database dan file upload secara terjadwal oleh operator; jangan mengandalkan backup otomatis hosting yang belum dijanjikan |

## 11. Acceptance Criteria MVP

MVP dinyatakan layak untuk demonstrasi apabila seluruh kriteria berikut dapat dibuktikan:

- [ ] Pengunjung dapat membuka Homepage, katalog, dan detail produk.
- [ ] Pelanggan dapat membuat akun, login, dan melihat pesanan miliknya sendiri.
- [ ] Admin yang belum login tidak dapat membuka route Admin Dashboard.
- [ ] Role petugas gudang tidak dapat membuka halaman pengaturan role atau verifikasi pembayaran tanpa izin.
- [ ] Admin dapat mengelola kategori, produk, varian, serta stok.
- [ ] Produk rental memiliki tanggal sewa dan tanggal pengembalian yang tervalidasi.
- [ ] Sistem menolak order ketika kuantitas yang diminta tidak tersedia pada periode tersebut.
- [ ] Dua order yang masuk bersamaan tidak dapat mengalokasikan unit fisik yang sama.
- [ ] Ringkasan harga dapat dihitung ulang di server dan tersimpan sebagai snapshot transaksi.
- [ ] Admin dapat meninjau order dan memverifikasi/menolak bukti pembayaran manual.
- [ ] Petugas dapat mencatat serah-terima dan pengembalian.
- [ ] Hasil pemeriksaan kondisi dapat memindahkan unit menjadi tersedia, perawatan, rusak, atau hilang.
- [ ] Dashboard menampilkan data operasional dari database, bukan angka dummy.
- [ ] Tidak ada file `.env`, kredensial, atau source privat yang dapat diunduh melalui web publik.
- [ ] Alur utama telah diuji pada desktop dan mobile.

## 12. Metrik Keberhasilan

Metrik awal yang dapat dipakai selama uji coba:

- Persentase order yang berhasil diproses tanpa konflik stok.
- Jumlah konflik/overbooking yang ditemukan dalam pengujian (target: nol).
- Waktu rata-rata admin menyelesaikan pencatatan order dan serah-terima.
- Persentase rental yang memiliki catatan pengembalian dan inspeksi lengkap.
- Persentase bukti pembayaran yang memiliki status verifikasi dan petugas pemeriksa.
- Tingkat keberhasilan tugas pengguna uji: mencari produk, memeriksa tanggal, membuat pesanan, dan melihat status.

Target angka kuantitatif ditetapkan setelah baseline uji coba tersedia; jangan menetapkan angka performa bisnis tanpa data operasional.

## 13. Di Luar Cakupan MVP

- Aplikasi native Android/iOS.
- Multi-cabang dan multi-gudang dengan transfer stok antarcabang.
- Payment gateway otomatis dan pencairan dana otomatis.
- Integrasi kurir/shipping marketplace.
- AI generatif untuk menentukan perlengkapan atau memberi saran keselamatan.
- Chat real-time, notifikasi WhatsApp otomatis, WebSocket, dan worker persisten.
- Dynamic pricing, loyalty point, voucher kompleks, dan affiliate.
- Sistem akuntansi penuh.

Fitur tersebut dapat dinilai kembali setelah alur rental, stok, pembayaran, dan pengembalian stabil.

## 14. Risiko dan Keputusan yang Masih Harus Ditetapkan

| Topik | Keputusan yang dibutuhkan | Default rancangan sementara |
|---|---|---|
| Cabang usaha | Satu lokasi atau multi-cabang? | Satu lokasi rental |
| Jadwal rental | Menggunakan tanggal saja atau jam? | Tanggal kalender dengan minimum satu hari |
| Pembayaran | Transfer manual, tunai, atau gateway? | Transfer manual/bukti pembayaran dan konfirmasi admin; tunai dapat dicatat admin bila disetujui |
| Deposit | Produk mana yang wajib deposit dan berapa nilainya? | Dapat dikonfigurasi per produk/aturan; belum menetapkan nominal |
| Keterlambatan | Grace period dan tarif denda? | Belum ditetapkan; denda tidak boleh dihitung sebelum kebijakan disahkan |
| Pembatalan | Batas pembatalan/refund? | Status dan catatan pembatalan tersedia; aturan nominal perlu disepakati |
| Penjualan | Apakah ada pengiriman atau hanya pengambilan? | Pengambilan di lokasi untuk MVP |
| Bukti pembayaran | Format dan batas ukuran file? | JPG/PNG/PDF dengan validasi server dan limit konfigurasi |
| Hak akses | Siapa boleh mengelola staf, pembayaran, dan deposit? | Owner memegang kewenangan tertinggi |
| Hosting | Apakah PHP 8.2/8.3, ekstensi, public root, dan permission tersedia? | Harus diverifikasi sebelum deployment |

## 15. Referensi Produk

- Proposal konsep: [`.Gearora/GEARORA.md`](https://github.com/KiandrAHD/Gearora/blob/main/.Gearora/GEARORA.md)
- Master design reference Homepage: [`.docs/Desain_Gearora.md`](https://github.com/KiandrAHD/Gearora/blob/main/.docs/Desain_Gearora.md)

Dokumen desain yang tersedia mengatur Homepage secara detail. Visual Admin Dashboard di dokumen ini merupakan kebutuhan produk dan perlu dibuat sebagai desain tersendiri sebelum implementasi UI dashboard.
