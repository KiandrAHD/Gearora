# Retail Sales Workflow

> **Domain:** Penjualan retail (produk, bukan sewa)
> **Fase:** Phase 7
> **Pemenuhan:** Ambil sendiri di lokasi (single-location pickup)

---

## 1. Ringkasan

Retail memungkinkan pelanggan membeli produk `ProductType::Sale`/`Both` secara online, dengan pembayaran transfer, tunai, atau Midtrans. Order retail **terpisah** dari rental: tabel, status, keranjang, dan laporan sendiri.

---

## 2. Peran

| Peran | Tanggung jawab |
| --- | --- |
| Customer | Menambah ke keranjang, checkout, membayar, membatalkan, memantau status |
| Admin | Melihat order, memverifikasi pembayaran, mengubah status, mencatat tunai |
| Owner | Semua aksi Admin + akses penuh laporan |
| Warehouse | Tidak mengakses sales admin; fokus inventaris |

---

## 3. Alur Customer

### 3.1 Menambah ke Keranjang

1. Buka halaman produk yang dijual.
2. Pilih varian dan jumlah → tambah ke keranjang.
3. Sistem memvalidasi: produk aktif, mendukung penjualan, varian aktif, harga > 0, stok tersedia.
4. Keranjang retail disimpan di session `retail_cart` (terpisah dari draft sewa).

Harga di keranjang **selalu** dihitung ulang di server. Client tidak dapat mengirim harga.

### 3.2 Checkout

`POST /keranjang/checkout` dengan `payment_method` (`transfer|cash|midtrans`) dan catatan opsional.

Yang terjadi:

1. Keranjang kosong ditolak.
2. Order dibuat (`pending_payment`, `unpaid`) dengan kode `GRS-YYYYMMDD-XXXX`.
3. Item disimpan sebagai snapshot (nama, SKU, harga saat itu).
4. Stok direservasi (tidak mengurangi stok fisik).
5. Payment record dibuat sesuai metode.
6. Keranjang dikosongkan.
7. Customer diarahkan ke detail pesanan.

### 3.3 Membayar

**Transfer**

1. Transfer ke rekening yang tertera (`gearora.payment.instructions`).
2. Unggah bukti (JPG/PNG/PDF, ≤ `gearora.payment.proof_max_kb`), isi referensi/nominal bila perlu.
3. Status pembayaran menjadi `waiting_verification`; order menjadi `payment_review`.
4. Tunggu Admin memverifikasi.

**Tunai**

Bayar di lokasi; Admin mencatat pembayaran dan order langsung dikonfirmasi.

**Midtrans**

1. Klik "Bayar otomatis".
2. Sistem membuat attempt dan mengarahkan ke halaman Snap.
3. Setelah pembayaran selesai, webhook Midtrans mengonfirmasi order.

Bila gateway belum aktif, tombol otomatis ditolak dengan pesan agar memakai transfer/tunai.

### 3.4 Membatalkan

Customer dapat membatalkan selama order belum final. Reservasi stok dilepas; stok fisik tidak berubah.

### 3.5 Mengambil Pesanan

Setelah order `ready_for_pickup`, customer datang ke lokasi dengan kode order. Admin menandai `completed` setelah barang diserahkan.

---

## 4. Alur Admin

### 4.1 Daftar Order

`/admin/sales` — filter status, pencarian kode order, pagination 20.

### 4.2 Detail Order

`/admin/sales/{order}` menampilkan pelanggan, item, pembayaran + attempt, reservasi, dan aksi yang tersedia.

### 4.3 Verifikasi Pembayaran Transfer

1. Buka detail order → bagian pembayaran.
2. Periksa bukti (tautan proof).
3. **Verifikasi** → status `verified`, order `confirmed`, stok dikonsumsi satu kali.
4. **Tolak** (wajib catatan) → status `rejected`, order kembali `pending_payment`.

Nominal pembayaran harus sama dengan total order; bila tidak, verifikasi ditolak sistem.

### 4.4 Mencatat Tunai

Aksi "Catat tunai" pada pembayaran metode tunai → order dikonfirmasi.

### 4.5 Transisi Status

| Dari | Ke | Syarat |
| --- | --- | --- |
| `pending_payment` | `payment_review` | bukti transfer diunggah |
| `pending_payment` | `cancelled` / `expired` | — |
| `payment_review` | `confirmed` | pembayaran terverifikasi |
| `payment_review` | `pending_payment` | bukti ditolak |
| `payment_review` | `cancelled` | — |
| `confirmed` | `ready_for_pickup` | — |
| `confirmed` | `cancelled` | — |
| `ready_for_pickup` | `completed` | — |
| `ready_for_pickup` | `cancelled` | — |

Transisi di luar tabel ditolak dan menampilkan pesan error (bukan exception ke pengguna).

---

## 5. Status

### 5.1 `order_status`

| Status | Arti | Stok ditahan? |
| --- | --- | --- |
| `pending_payment` | Menunggu pembayaran | ✔ |
| `payment_review` | Menunggu verifikasi | ✔ |
| `confirmed` | Pembayaran terverifikasi | ✔ |
| `ready_for_pickup` | Siap diambil | ✔ |
| `completed` | Selesai | — |
| `cancelled` | Dibatalkan | — |
| `expired` | Kedaluwarsa | — |

### 5.2 `payment_status`

Mengikuti `PaymentStatus`: `unpaid`, `waiting_verification`, `processing`, `verified`, `rejected`, `expired`, `failed`, `refunded`, `partially_refunded`.

---

## 6. Kasus Tepi

| Kasus | Perilaku |
| --- | --- |
| Stok habis saat checkout | Checkout gagal, tidak ada order dibuat |
| Dua customer checkout bersamaan | Row lock + cek ketersediaan mencegah overselling |
| Bukti diunggah dua kali | Bukti diganti, status tetap menunggu verifikasi |
| Pembayaran masuk setelah order dibatalkan | Payment `processing`, stok tidak dikonsumsi, perlu tindakan manual |
| Webhook duplikat | Dedup `payment_webhook_events` mencegah efek ganda |
| Order kedaluwarsa | Reservasi dilepas; stok tersedia kembali |
| Verifikasi ganda | Idempotent; stok hanya berkurang sekali |

---

## 7. Rute

Publik/customer:

- `GET /keranjang` — keranjang
- `POST /keranjang` — tambah item
- `PATCH /keranjang/{variant}` — ubah jumlah
- `DELETE /keranjang/{variant}` — hapus item
- `POST /keranjang/clear` — kosongkan
- `POST /keranjang/checkout` — checkout
- `GET /pembelian` — daftar pesanan
- `GET /pembelian/{order}` — detail
- `POST /pembelian/{order}/batal` — batalkan
- `POST /pembelian/{order}/bayar/{payment}/bukti` — unggah bukti
- `POST /pembelian/{order}/bayar-midtrans` — mulai Midtrans

Admin:

- `GET /admin/sales`
- `GET /admin/sales/{order}`
- `POST /admin/sales/{order}/transition`
- `POST /admin/sales/payments/{payment}/verify`
- `POST /admin/sales/payments/{payment}/reject`
- `POST /admin/sales/payments/{payment}/cash`
- `GET /admin/sales/payments/{payment}/proof`

---

## 8. Checklist Operasional

- [ ] Rekening tujuan dan lokasi tunai diisi di environment.
- [ ] `GEARORA_PAYMENT_GATEWAY_ENABLED` hanya diaktifkan bila kredensial Midtrans valid.
- [ ] Bukti pembayaran diperiksa sebelum verifikasi.
- [ ] Barang diserahkan hanya setelah order `ready_for_pickup`.
- [ ] Pembatalan setelah pembayaran terverifikasi ditangani manual (belum ada refund otomatis).
- [ ] Stok fisik dicek berkala; laporan inventory menjadi acuan.