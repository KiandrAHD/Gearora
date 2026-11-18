# Kebijakan Rental GEARORA — Baseline MVP

> **Produk:** GEARORA — Outdoor Supply
> **Versi:** 1.0 (baseline MVP)
> **Tanggal ditetapkan:** 17 November 2026
> **Status:** Ditetapkan untuk implementasi Phase 4–5. Dapat diubah oleh pemilik usaha melalui change control di §12.
> **Acuan:** `PRD_GEARORA.md` §9, §14; `REQUIREMENT_FREEZE.md` §5.

Dokumen ini menutup pertanyaan bisnis Phase 0 yang sebelumnya berstatus **OUTSTANDING**. Nilai di bawah adalah **keputusan baseline resmi** untuk MVP, bukan asumsi tersembunyi. Semua nominal dan batas waktu wajib dapat dikonfigurasi pada implementasi (config/DB), bukan di-hardcode di controller.

---

## 1. Lokasi Usaha

| Keputusan | Nilai |
|---|---|
| Jumlah lokasi pada MVP | **Satu lokasi** (single-location) |
| Multi-cabang / multi-gudang | **Di luar cakupan MVP** |
| Model pengambilan | **Ambil di lokasi (self pickup)**, tanpa pengiriman |
| Alamat operasional | Diisi pada konfigurasi aplikasi saat deployment; tidak disimpan di source control |

Implikasi teknis:
- Satu gudang, satu kumpulan unit fisik.
- Tidak ada transfer stok antarcabang.
- Kolom `location_id` tidak diperlukan pada MVP; dapat ditambahkan tanpa breaking change bila multi-cabang dibuka.

---

## 2. Tanggal, Jam, dan Durasi Rental

| Aturan | Keputusan baseline |
|---|---|
| Satuan sewa | **Harian (date-only)**, bukan per jam |
| Zona waktu | `Asia/Jakarta` (WIB) |
| Durasi minimum | **1 hari** |
| Durasi maksimum per order | **30 hari** |
| Batas pemesanan ke depan | **90 hari** dari tanggal hari ini |
| Pemesanan untuk hari ini | Diizinkan selama unit tersedia dan pickup masih dalam jam operasional |
| Perhitungan durasi | `end_date − start_date` dalam hari kalender, minimum 1 |
| Contoh | 1 Jan → 1 Jan = 1 hari; 1 Jan → 3 Jan = 2 hari |
| Model interval | **Half-open `[start_date, end_date)`** |
| Harga | `jumlah unit × harga sewa per hari × durasi` |
| Snapshot | Harga dan nama produk disimpan pada order saat dibuat |

**Aturan overlap:** dua order beririsan jika `A.start < B.end` **dan** `A.end > B.start`. Dengan interval half-open, unit yang kembali pada tanggal `end_date` boleh disewa lagi dengan `start_date` tanggal tersebut, setelah inspeksi dan handover selesai.

---

## 3. Jam Operasional, Pickup, dan Return

| Aturan | Keputusan baseline |
|---|---|
| Jam operasional | **08:00–20:00 WIB**, setiap hari |
| Jendela pickup | **08:00–18:00 WIB** pada `start_date` |
| Batas return | **20:00 WIB** pada `end_date` |
| Pickup lebih awal | Tidak diizinkan sebelum `start_date` |
| Return lebih awal | Diizinkan; **tanpa refund** untuk hari yang tidak terpakai |
| Identitas saat pickup | KTP/SIM + kode order + bukti pembayaran |
| Inspeksi handover | Wajib; kondisi awal dicatat dan disetujui kedua pihak |
| Inspeksi return | Dilakukan saat barang diterima, target selesai ≤ 30 menit |
| Keterlambatan pickup | Order dapat dibatalkan setelah **24 jam** dari jadwal pickup tanpa kabar; kebijakan refund §7 berlaku |

---

## 4. Keterlambatan Pengembalian

| Aturan | Keputusan baseline |
|---|---|
| Grace period | **60 menit** setelah 20:00 WIB (sampai 21:00) |
| Denda setelah grace | **100% harga sewa harian** per hari keterlambatan yang dimulai |
| Pembulatan | Setiap keterlambatan melewati grace dihitung **1 hari penuh**; hari berikutnya dihitung per 24 jam |
| Batas keterlambatan | **7 hari**; setelah itu order masuk proses **barang hilang** (§6) |
| Perpanjangan | Dapat diajukan sebelum `end_date`; hanya disetujui bila unit tidak dialokasikan ke order lain |
| Perpanjangan disetujui | Mengubah `end_date` dan menambah biaya sewa; snapshot harga awal tetap tercatat |

Denda keterlambatan dicatat sebagai **additional charge** dan tidak mengubah harga sewa awal.

---

## 5. Deposit

| Aturan | Keputusan baseline |
|---|---|
| Sifat | **Uang jaminan (liabilitas)**, terpisah dari pendapatan rental |
| Produk wajib deposit | Ditentukan per produk melalui flag `requires_deposit` |
| Nominal default | **50% × harga sewa 1 hari**, dibulatkan ke atas kelipatan **Rp 10.000** |
| Batas minimum | **Rp 50.000** untuk produk ber-flag deposit |
| Batas maksimum | **Rp 2.000.000** per unit |
| Override | Admin/Owner dapat menetapkan nominal per produk/varian; perubahan tercatat di audit log |
| Pengembalian | Setelah inspeksi selesai, maksimal **1×24 jam** kerja |
| Pemotongan | Didahulukan dari deposit, wajib memiliki alasan, bukti, dan persetujuan Admin/Owner |
| Sisa deposit | Dikembalikan tunai atau transfer sesuai metode pembayaran awal |
| Deposit hangus | Hanya bila order dinyatakan hilang atau pelanggan menolak kewajiban yang sah; wajib audit trail |

Deposit **tidak boleh** dimasukkan ke laporan pendapatan rental.

---

## 6. Kerusakan, Kehilangan, dan Biaya Tambahan

| Kasus | Perlakuan |
|---|---|
| Kerusakan ringan (masih layak pakai) | Biaya perbaikan aktual atau nominal yang disetujui Admin/Owner |
| Kerusakan berat / tidak layak pakai | Biaya perbaikan atau penggantian sesuai nilai aset |
| Kehilangan | Nilai penggantian aset yang tercatat pada data inventaris |
| Keterlambatan | Denda §4 |
| Biaya lain | Wajib memiliki `reason`, nominal, bukti (bila ada), dan persetujuan Admin/Owner |
| Urutan penyelesaian | Deposit dipotong lebih dulu, sisanya ditagihkan sebagai additional charge |
| Sengketa | Unit tetap berstatus `maintenance`/`damaged`/`lost` sampai diputuskan; order tidak boleh `completed` sebelum selesai |

---

## 7. Pembatalan dan Refund

| Waktu pembatalan oleh pelanggan | Refund biaya sewa | Deposit |
|---|---|---|
| Lebih dari 48 jam sebelum `start_date` | **100%** | Dikembalikan penuh |
| 24–48 jam sebelum `start_date` | **50%** | Dikembalikan penuh |
| Kurang dari 24 jam sebelum `start_date` | **0%** | Dikembalikan penuh |
| Tidak pickup (no-show) | **0%** | Dikembalikan penuh |
| Dibatalkan GEARORA karena unit tidak tersedia/rusak | **100%** | Dikembalikan penuh |

Ketentuan tambahan:
- Refund diproses maksimal **7 hari kerja** dan dicatat sebagai transaksi refund terpisah.
- Pembatalan harus memiliki status `cancelled`, alasan, waktu, dan aktor yang mencatat.
- Refund tidak mengurangi nilai additional charge yang sudah sah (misalnya kerusakan).
- Kebijakan ini tidak mengikat untuk kasus force majeure; keputusan dicatat manual oleh Owner.

---

## 8. Metode Pembayaran

| Metode | Status | Bukti |
|---|---|---|
| Transfer bank manual | **Wajib didukung** | Unggah bukti transfer |
| Tunai di lokasi | **Didukung** | Dicatat oleh Admin saat pembayaran diterima |
| Payment gateway otomatis (Midtrans Snap) | **Didukung (Phase 5)** | Verifikasi otomatis via webhook bertanda tangan |

Aturan:
- Instruksi pembayaran ditampilkan setelah order dibuat.
- Bukti pembayaran manual: **JPG, PNG, atau PDF**, maksimal **5 MB**, divalidasi di server.
- Bukti pembayaran manual **tidak otomatis** berarti lunas; status pembayaran berubah hanya setelah verifikasi Admin.
- Status pembayaran (`unpaid`, `waiting_verification`, `processing`, `verified`, `rejected`, `expired`, `failed`, `refunded`, `partially_refunded`) terpisah dari status order.
- Order dengan metode tunai dapat dikonfirmasi setelah Admin mencatat penerimaan uang.
- Verifikasi pembayaran manual hanya boleh dilakukan role **Owner/Admin**.
- Pembayaran gateway diverifikasi otomatis melalui webhook Midtrans yang diverifikasi tanda tangan SHA512; pembayaran gateway **tidak dapat** diverifikasi manual (lihat `.docs/MIDTRANS_INTEGRATION.md`).
- Setiap notifikasi webhook divalidasi terhadap nominal, mata uang, dan dideduplikasi berdasarkan hash event; efek finansial idempotent.
- Data rekening usaha berada di konfigurasi/DB, bukan di source control atau dokumentasi publik.
- `MIDTRANS_SERVER_KEY` hanya ada di server; tidak pernah dikirim ke browser atau ditulis ke log.

---

## 9. Status Order Rental

| Status | Arti | Transisi berikutnya |
|---|---|---|
| `pending_payment` | Order dibuat, belum ada pembayaran terverifikasi | `payment_review`, `cancelled` |
| `payment_review` | Bukti pembayaran diunggah, menunggu verifikasi | `confirmed`, `pending_payment` (ditolak) |
| `confirmed` | Pembayaran terverifikasi, unit dialokasikan | `ready_for_pickup`, `cancelled` |
| `ready_for_pickup` | Unit disiapkan dan siap diserahkan | `picked_up`, `cancelled` |
| `picked_up` | Unit diserahkan dan sedang disewa | `returned` |
| `returned` | Unit diterima kembali, menunggu inspeksi final | `completed` |
| `completed` | Inspeksi selesai, deposit dan biaya tambahan diselesaikan | — |
| `cancelled` | Order dibatalkan sesuai kebijakan §7 | — |

Aturan transisi:
- Transisi tidak valid harus ditolak di server.
- `completed` hanya boleh setelah semua unit dikembalikan atau ditangani sebagai rusak/hilang.
- Pembatalan setelah `picked_up` tidak mengubah status pengembalian; tetap harus melalui `returned`.
- Setiap perubahan status menyimpan aktor, waktu, status lama, dan status baru (audit trail).

---

## 10. Identitas dan Kode Transaksi

| Aturan | Keputusan baseline |
|---|---|
| Format kode rental | `GRR-YYYYMMDD-XXXX` (XXXX = urutan acak/sequence harian) |
| Format kode sales | `GRS-YYYYMMDD-XXXX` |
| Keunikan | Dijamin unique index pada database |
| Data pelanggan | Nama, telepon, email, alamat; NIK tidak disimpan kecuali diperlukan |
| Verifikasi pickup | Kode order + identitas + bukti pembayaran |

---

## 11. Ringkasan Dampak Implementasi

| Phase | Dampak kebijakan |
|---|---|
| Phase 3 | Flag `requires_deposit`, harga harian, unit fisik, status unit |
| Phase 4 | Half-open interval, minimum/maksimum durasi, snapshot harga, kode order, validasi overlap dalam transaction |
| Phase 5 | Grace period, denda, deposit, additional charge, refund, alur status, audit trail |
| Phase 7 | Pemisahan laporan rental, deposit, refund, dan additional charge |

Nilai yang wajib masuk konfigurasi aplikasi:
- jam operasional dan jendela pickup;
- grace period dan tarif denda;
- durasi minimum/maksimum dan horizon pemesanan;
- persentase/batas deposit;
- batas waktu pembatalan dan persentase refund;
- format/ukuran bukti pembayaran.

---

## 12. Change Control

Perubahan kebijakan dilakukan dengan:
1. Update dokumen ini beserta versi dan tanggal.
2. Catat alasan bisnis dan dampak teknis.
3. Sesuaikan konfigurasi, test, dan dokumentasi terkait.
4. Pastikan order lama tetap memakai snapshot kebijakan/harga saat order dibuat.

**Keputusan yang tidak boleh diubah retroaktif untuk order yang sudah berjalan:** harga sewa, nominal deposit, tanggal mulai/kembali, dan biaya tambahan yang sudah disetujui.