# Master Design Reference — Homepage GEARORA

> **Nama produk:** GEARORA — Outdoor Supply  
> **Tagline:** “Gear Up. Explore More.”  
> **Status dokumen:** Master reference untuk implementasi Homepage  
> **Bahasa antarmuka:** Bahasa Indonesia  
> **Tanggal audit:** 2026-08-10  
> **Sumber yang diaudit:** implementasi aktif Figma Make pada `src/App.tsx` dan `src/index.css`

---

## 1. Project Overview

GEARORA adalah platform penyewaan dan penjualan perlengkapan outdoor. Homepage menggabungkan tampilan editorial modern, ilustrasi petualangan berbasis vektor, fotografi outdoor, serta komponen transaksional yang mengarahkan pengguna ke katalog rental, katalog pembelian, rekomendasi perjalanan, panduan, akun, dan keranjang.

Karakter visual utama:

- Modern outdoor editorial, bukan tampilan marketplace generik.
- Warna alam yang redup: forest green, mountain navy, sage, sand, dan cloud white.
- Headline tegas dengan satu aksen serif italic pada kata “alam.”
- Ilustrasi hero kartun/vector sebagai identitas utama.
- Bentuk komponen umumnya tegas dengan radius kecil; bentuk organik dipakai selektif pada logo, ikon kategori, dan ilustrasi.
- Fotografi hanya dipakai pada kartu produk dan banner trip, bukan menggantikan ilustrasi hero.
- Hierarki teks memakai Poppins untuk heading dan Inter untuk isi/UI.

### 1.1 Tujuan halaman

Homepage harus:

1. Menjelaskan proposisi nilai rental dan pembelian gear.
2. Mengarahkan pengguna ke katalog melalui CTA hero dan panel pencarian.
3. Menampilkan kategori populer dan produk unggulan.
4. Menjelaskan alur rental dalam tiga langkah.
5. Mengarahkan pengguna ke rekomendasi trip.
6. Menjaga pemisahan visual dan semantik antara harga **SEWA** dan **BELI**.

### 1.2 Status sumber kebenaran

Dokumen ini membedakan informasi sebagai berikut:

| Label                  | Arti                                                                                            |
| ---------------------- | ----------------------------------------------------------------------------------------------- |
| **IMPLEMENTED**        | Nilai atau perilaku dapat dibaca langsung dan persis dari source aktif.                         |
| **INFERENCE**          | Kesimpulan implementasi/responsive yang diturunkan dari aturan CSS, bukan metadata frame Figma. |
| **ESTIMATED**          | Perkiraan visual. Dokumen ini sebisa mungkin tidak menggunakannya karena nilai source tersedia. |
| **NEEDS VERIFICATION** | Tidak tersedia di source dan perlu diperiksa langsung pada file/node Figma.                     |

> **Catatan penting:** nama frame, nama layer Figma, konfigurasi Auto Layout asli, constraints Figma, component properties, variants, dan prototype links pada canvas tidak tersedia di repository. Jangan menyatakan metadata tersebut sebagai fakta sebelum memeriksa node Figma. Struktur DOM/CSS di bawah adalah fakta implementasi aktif.

---

## 2. Design Reference & Scope

### 2.1 Cakupan utama

Dokumen ini memprioritaskan Homepage, termasuk:

- Navigation bar.
- Hero copy.
- Hero illustration.
- Search & trip date panel.
- Kategori.
- Produk unggulan.
- Penjelasan alur rental.
- Banner trip planner.
- Footer.
- Design tokens dan reusable components yang dipakai Homepage.
- Perilaku responsive dan interaksi aktual.

Halaman katalog, detail, checkout, trip planner, panduan, akun, dan admin berada di implementasi yang sama, tetapi hanya dirujuk ketika diperlukan untuk menjelaskan routing atau reusable component.

### 2.2 Referensi implementasi

| Area                                            | Sumber                                               |
| ----------------------------------------------- | ---------------------------------------------------- |
| Struktur komponen dan konten                    | `src/App.tsx`                                        |
| Warna, ukuran, layout, responsive, state visual | `src/index.css`                                      |
| Font                                            | Google Fonts CSS import di `src/index.css`           |
| Ikon                                            | SVG path inline pada object `icons` di `src/App.tsx` |
| Hero illustration                               | SVG inline komponen `HeroArt` di `src/App.tsx`       |
| Foto produk/banner                              | URL Unsplash pada object `photos` di `src/App.tsx`   |
| Mount aplikasi                                  | `src/main.tsx` ke elemen `#root`                     |

### 2.3 Prinsip preservasi

- Jangan mengganti `HeroArt` dengan foto stok.
- Jangan menggambar ulang ilustrasi apabila komponen SVG asli tersedia.
- Jangan mengganti icon set dengan emoji.
- Jangan mengubah line break headline hero; line break merupakan bagian komposisi.
- Jangan menambahkan gradient dekoratif yang tidak ada.
- Jangan memperbesar radius seluruh interface; desain saat ini dominan 5–7 px.
- Jangan mencampur harga sewa per hari dengan harga beli per unit.
- Gunakan foto asli yang terdaftar pada Asset Inventory atau salinan lokal dari aset yang sama.

---

## 3. Page Structure

### 3.1 Hierarki halaman

```text
App
├── Header / Navigation Bar
│   └── container.header-inner
│       ├── Logo
│       ├── Primary Navigation
│       └── Header Actions
├── Home
│   ├── Hero
│   │   ├── container.hero-grid
│   │   │   ├── hero-copy
│   │   │   └── hero-visual
│   │   │       ├── circular brand stamp
│   │   │       ├── HeroArt SVG
│   │   │       └── floating information card
│   │   └── container.hero-search-wrap
│   │       └── Search & Trip Date Panel
│   ├── Popular Categories
│   ├── Featured Products / “Most Loved”
│   ├── Rental Process / “Rent With Confidence”
│   └── Trip Planner Banner
└── Footer
```

### 3.2 Urutan section dan konten

| Urutan | Section       | Eyebrow/Kicker         | Heading                                           |
| -----: | ------------- | ---------------------- | ------------------------------------------------- |
|      1 | Hero          | `OUTDOOR MADE SIMPLE`  | “Lebih dekat dengan alam. Lebih siap bertualang.” |
|      2 | Search panel  | —                      | Field pencarian + tanggal                         |
|      3 | Kategori      | `EXPLORE THE GEAR`     | “Mulai dari kebutuhanmu.”                         |
|      4 | Produk        | `MOST LOVED`           | “Gear favorit petualang.”                         |
|      5 | Proses rental | `RENT WITH CONFIDENCE` | “Petualangan hebat dimulai dari gear yang tepat.” |
|      6 | Trip planner  | `GEARORA TRIP PLANNER` | “Bingung harus bawa apa?”                         |
|      7 | Footer        | —                      | Logo, link, newsletter                            |

### 3.3 Layout global

- **IMPLEMENTED:** container utama memiliki `width: min(1180px, calc(100% - 40px))`.
- Pada viewport lebih lebar dari 1220 px, lebar konten berhenti pada 1180 px.
- Pada viewport ≤600 px, container menjadi `min(100% - 28px, 1180px)`, setara gutter horizontal 14 px.
- Background global adalah Cloud White `#F7F8F3`.
- Minimum lebar body adalah 320 px.
- Header sticky tetap berada di atas konten dengan `z-index: 50`.

---

## 4. Layout Specifications

## 4.A Navigation Bar

### 4.A.1 Container

| Properti              | Nilai                         |
| --------------------- | ----------------------------- |
| Tinggi desktop/tablet | `78px`                        |
| Tinggi mobile ≤600 px | `68px`                        |
| Position              | `sticky`, `top: 0`            |
| Z-index               | `50`                          |
| Background            | `#F7F8F3E8`                   |
| Backdrop blur         | `14px`                        |
| Border bawah          | `1px solid #DFE4DC`           |
| Alignment             | Flex, center, `space-between` |
| Lebar isi             | Global container 1180 px      |

Background menggunakan alpha hex delapan digit. Pertahankan nilai `#F7F8F3E8`, bukan opaque white.

### 4.A.2 Logo GEARORA

Logo bukan file gambar terpisah. Logo disusun dari:

1. Organic logo mark.
2. Inline tent icon.
3. Wordmark `GEARORA`.
4. Sub-label `OUTDOOR SUPPLY`.

| Elemen               | Spesifikasi                                                                                    |
| -------------------- | ---------------------------------------------------------------------------------------------- |
| Gap mark ke wordmark | `10px`                                                                                         |
| Wordmark             | Poppins 700, `19px`, line-height `1`, letter-spacing `0.05em`                                  |
| Sub-label            | Inter 600, `7px`, line-height `1.5`, letter-spacing `0.18em`, margin-top `3px`, opacity `0.65` |
| Logo mark            | `37 × 37px`                                                                                    |
| Logo mark radius     | `50% 50% 45% 55%`                                                                              |
| Logo mark fill       | Forest `#183D32`                                                                               |
| Logo mark foreground | Cloud `#F7F8F3`                                                                                |
| Logo mark rotation   | `-5deg`                                                                                        |
| Tent icon            | `22 × 22px`, diputar balik `5deg` agar tampak tegak                                            |

Footer menggunakan variant `logo-light`: wordmark putih, mark Warm Sand, tent icon Forest.

### 4.A.3 Primary navigation

Urutan item:

1. Sewa
2. Beli
3. Rekomendasi Trip
4. Panduan

| Properti           | Nilai                                                 |
| ------------------ | ----------------------------------------------------- |
| Display            | Flex horizontal                                       |
| Gap item           | `28px`                                                |
| Margin kiri nav    | `60px`                                                |
| Font               | Inter 600, `13px`                                     |
| Default color      | `#4E5B60`                                             |
| Hover/active color | Forest `#183D32`                                      |
| Active indicator   | Garis `2px`, full width label, bottom `-27px`, Forest |

**Catatan state aktual:** Sewa dan Beli sama-sama mengarah ke page state `catalog`. Karena active state dihitung dari target page, keduanya dapat tampak aktif bersamaan ketika page adalah katalog. Ini adalah fakta implementasi saat audit; **NEEDS VERIFICATION** apakah Figma prototype memang menghendaki perilaku tersebut.

### 4.A.4 Header actions

Urutan:

1. Search icon button.
2. Cart icon button dengan badge `2`.
3. Tombol Masuk.
4. Menu button, tersembunyi di desktop dan tampil ≤900 px.

| Komponen               | Spesifikasi                                                                         |
| ---------------------- | ----------------------------------------------------------------------------------- |
| Gap action             | `8px`                                                                               |
| Icon button            | `39 × 39px`, center                                                                 |
| Icon base              | SVG `24 × 24`, stroke currentColor, default icon size `20px`                        |
| Cart badge             | `16 × 16px`, circle, Alpine Blue, putih, Inter 700 `9px/16px`, top `1px`, right `0` |
| Account separator      | Border kiri `1px solid #DBE0D7`                                                     |
| Account margin/padding | margin-left `4px`, padding-left `16px`                                              |
| Account gap            | `8px`                                                                               |
| Account font           | Inter 600 `13px`                                                                    |
| User icon circle       | `30 × 30px`, Warm Sand, radius `50%`                                                |

---

## 4.B Hero Section

### 4.B.1 Section dan grid

| Properti            | Nilai             |
| ------------------- | ----------------- |
| Background          | `#EDF1E9`         |
| Padding             | `60px 0 0`        |
| Desktop grid        | `1.02fr 0.98fr`   |
| Alignment           | center            |
| Minimum tinggi grid | `555px`           |
| Kolom kiri          | Hero copy         |
| Kolom kanan         | Hero illustration |

Hero search panel berada di dalam section hero tetapi setelah `hero-grid`, lalu diturunkan `34px` sehingga overlap dengan section berikutnya.

### 4.B.2 Eyebrow

- Teks: `OUTDOOR MADE SIMPLE`.
- Font: Inter 700, `10px`.
- Letter spacing: `0.19em`.
- Warna: Forest.
- Display: flex horizontal, center.
- Gap ke decorative line: `12px`.
- Decorative line: `42 × 1px`, Sage `#A8B99A`.

### 4.B.3 Headline

Line break harus dipertahankan:

```text
Lebih dekat dengan
alam. Lebih siap
bertualang.
```

| Properti                      | Nilai                    |
| ----------------------------- | ------------------------ |
| Family utama                  | Poppins                  |
| Weight                        | 600                      |
| Size desktop                  | `clamp(43px, 5vw, 66px)` |
| Line-height                   | `1.06`                   |
| Color                         | Mountain Navy `#08213F`  |
| Margin                        | `20px 0 22px`            |
| Global heading letter-spacing | `-0.035em`               |

Kata `alam.`:

- Element semantic: `<em>`.
- Font family: Georgia, serif.
- Weight: 400.
- Style italic berasal dari elemen `<em>`/default browser.
- Color: Alpine Blue `#2377C9`.

Jangan mengganti aksen ini dengan Poppins italic. Kontras serif terhadap Poppins adalah bagian identitas.

### 4.B.4 Deskripsi

Teks:

> Sewa atau beli perlengkapan outdoor terkurasi. Berkualitas, aman, dan siap menemani cerita perjalananmu.

| Properti    | Nilai     |
| ----------- | --------- |
| Font        | Inter 400 |
| Size        | `16px`    |
| Line-height | `1.75`    |
| Color       | `#526267` |
| Max width   | `530px`   |

### 4.B.5 CTA row

- Display flex horizontal.
- Align center.
- Gap `30px`.
- Margin vertikal `30px`.

**CTA primer — “Mulai Sewa”**

- Reusable class: `.btn.primary`.
- Minimum height `48px`.
- Padding horizontal `22px`.
- Gap text/icon `11px`.
- Radius `6px`.
- Font Inter 700 `13px`.
- Fill Forest.
- Foreground putih.
- Shadow `0 8px 20px #183D3225`.
- Arrow icon `18px`.
- Hover: translateY `-2px` selama `0.2s`.

**CTA sekunder — “Bantu pilihkan gear”**

- Text-link, tanpa container fill.
- Inter 700 `13px`.
- Mountain Navy.
- Gap `8px`.
- Suffix berupa karakter `↗`, bukan SVG.
- Hover color Alpine Blue.

### 4.B.6 Trust indicators

Container:

- Border top `1px solid #D8DFD4`.
- Padding top `20px`.
- Flex row, gap `28px`.
- Font Inter 600 `11px`.
- Color `#586867`.

Item:

1. Shield check icon `19px` + `Gear terinspeksi`.
2. Star icon `18px` + `4.9 dari 2,000+ petualang`.

Gap icon ke teks adalah `8px`; icon berwarna Forest.

---

## 4.C Hero Illustration

### 4.C.1 Aturan utama

- Komponen asli bernama `HeroArt`.
- Format asli: inline SVG.
- ViewBox: `0 0 660 520`.
- Accessible role: `img`.
- Accessible label: `Ilustrasi petualang berkemah di pegunungan`.
- Container `.hero-visual`: relative, `min-height: 500px`, overflow hidden, stretch terhadap grid.
- SVG render: width `110%`, height `100%`, margin-left `-2%`.
- Jangan mengubah menjadi raster kecuali sistem target tidak mendukung SVG; jika dirasterisasi, gunakan rasio 660:520 dan resolusi minimal 2×.

### 4.C.2 Layer order SVG

Urutan di bawah adalah urutan paint SVG asli, dari belakang ke depan.

| Urutan | Elemen             | Geometri/posisi                                                | Fill/stroke              |
| -----: | ------------------ | -------------------------------------------------------------- | ------------------------ |
|      1 | Matahari           | Circle `cx=480`, `cy=90`, `r=54`                               | Sand `#E9DDC9`           |
|      2 | Massa gunung       | Path dari dasar kiri menuju puncak `(155,180)` dan `(330,130)` | Sage `#A8B99A`           |
|      3 | Salju puncak kiri  | Path di sekitar puncak `(155,180)`                             | Cloud `#F7F8F3`          |
|      4 | Salju puncak kanan | Path di sekitar puncak `(330,130)`                             | Cloud                    |
|      5 | Landscape tengah   | Organic wave mulai sekitar y=390                               | Forest `#183D32`         |
|      6 | Landscape depan    | Organic wave mulai sekitar y=433                               | Navy `#08213F`           |
|      7 | Tiga pohon         | Segitiga di kiri dan kanan                                     | Forest                   |
|      8 | Tenda outer        | Segitiga dari `(291,442)` ke apex `(379,310)`                  | Sand; stroke Navy 8      |
|      9 | Panel tenda kanan  | Segitiga/parallelogram                                         | Earth `#80664E`          |
|     10 | Bukaan tenda       | Segitiga dari sekitar `(332,442)`                              | Cloud; stroke Navy 7     |
|     11 | Pendaki            | Group translate `(178,290)`                                    | Lihat subbagian karakter |
|     12 | Ground accent      | Curved line sekitar x=246–334, y=470                           | Sage; stroke 8           |
|     13 | Dekorasi           | Dua titik dan satu bintang                                     | Sand                     |

### 4.C.3 Karakter pendaki

Karakter merupakan group dengan `transform="translate(178 290)"`.

| Bagian            | Spesifikasi                                   |
| ----------------- | --------------------------------------------- |
| Kepala            | Circle `(42,24)`, radius `18`, fill `#B98562` |
| Rambut/topi gelap | Path, fill Navy                               |
| Torso/jaket       | Path, fill Alpine Blue                        |
| Lengan            | Stroke `#B98562`, width `10`, round cap       |
| Kaki              | Stroke Navy, width `11`, round cap            |
| Carrier           | Fill Sand, stroke Navy `6`                    |

Karakter berada di foreground kiri tenda dan tidak boleh dipindahkan ke center. Asimetri karakter terhadap tenda menyeimbangkan komposisi.

### 4.C.4 Circular brand stamp

Stamp berada di HTML layer di atas SVG, bukan bagian dari SVG.

| Properti       | Nilai                      |
| -------------- | -------------------------- |
| Teks           | `GEAR UP · EXPLORE MORE ·` |
| Position       | absolute                   |
| Top/right      | `2% / 2%`                  |
| Size           | `94 × 94px`                |
| Border         | `1px solid #839589`        |
| Radius         | `50%`                      |
| Font           | Inter 700 `9px/1.6`        |
| Letter spacing | `0.16em`                   |
| Padding        | `15px`                     |
| Rotation       | `12deg`                    |
| Z-index        | `2`                        |

Teks saat ini berada sebagai blok yang dicenter di lingkaran, bukan text-on-path.

### 4.C.5 Floating information card

Konten:

- Tent icon.
- Label `Siap berangkat`.
- Nilai `1,240+ gear`.

| Properti    | Nilai                                      |
| ----------- | ------------------------------------------ |
| Position    | absolute, right `0`, bottom `58px`         |
| Background  | putih                                      |
| Padding     | `12px 18px 12px 12px`                      |
| Gap         | `12px`                                     |
| Radius      | `6px`                                      |
| Shadow      | `0 12px 35px #08213F24`                    |
| Icon circle | `40 × 40px`, Sand, Forest icon, 50% radius |
| Label       | `9px`, `#7A8585`                           |
| Value       | `13px`, margin-top `3px`                   |

---

## 4.D Search & Trip Date Panel

### 4.D.1 Posisi dan container

- Wrapper menggunakan global container.
- Wrapper dipindahkan ke bawah dengan `transform: translateY(34px)`.
- Position relative, `z-index: 4`.
- Panel putih, minimum height `86px`.
- Grid desktop: `1.15fr 1fr 150px`.
- Padding panel: `13px`.
- Radius: `7px`.
- Shadow: `0 14px 40px #102E2520`.
- Vertical alignment: center.

### 4.D.2 Search field

Struktur:

```text
label
├── search icon
└── span
    ├── small: “Mau cari apa?”
    └── input: placeholder “Tenda, carrier, kompor...”
```

Field group:

- Flex row, center.
- Gap `13px`.
- Padding horizontal `24px`.
- Border kanan `1px solid #DBE0D7`.
- Icon Forest.

Label kecil:

- Inter 700 `10px`.
- Mountain Navy.
- Margin-bottom `5px`.

Input:

- Inter `12px`.
- Width `100%`.
- Foreground/placeholder inherited implementation `#748080`.
- Background transparent.
- Outline `0`.
- Tidak memiliki border tersendiri.

### 4.D.3 Date field

Struktur identik dengan search field:

- Calendar icon.
- Label `Tanggal petualangan`.
- Input `type="text"` dengan placeholder `Pilih tanggal`.

**IMPLEMENTED:** field belum menggunakan native date input dan belum membuka date picker custom.

### 4.D.4 Tombol “Cari Gear”

| Properti   | Nilai                |
| ---------- | -------------------- |
| Width grid | `150px` pada desktop |
| Height     | `60px`               |
| Background | Alpine Blue          |
| Foreground | putih                |
| Font       | Inter 700 `13px`     |
| Radius     | `5px`                |

**IMPLEMENTED:** tombol langsung mengubah page state ke katalog; nilai search dan tanggal hero tidak diteruskan ke katalog.

---

## 4.E Popular Categories

### 4.E.1 Section

- Generic section padding-top `105px`, padding-bottom `30px`.
- Header section flex dengan `space-between`, align-end, margin-bottom `32px`.
- Heading section Poppins 600 `34px`; margin-top `8px`.
- CTA kanan: `Lihat semua kategori`, text-link + arrow 17 px.

### 4.E.2 Grid dan card

| Properti         | Nilai                                                          |
| ---------------- | -------------------------------------------------------------- |
| Grid desktop     | 4 kolom sama lebar                                             |
| Gap              | `14px`                                                         |
| Card min-height  | `108px`                                                        |
| Card padding     | `20px`                                                         |
| Background       | putih                                                          |
| Border           | `1px solid #E1E5DF`                                            |
| Internal display | Flex row, center                                               |
| Internal gap     | `15px`                                                         |
| Hover            | translateY `-4px`, border Sage, shadow `0 10px 30px #142E2510` |

Organic icon container:

- `54 × 58px`.
- Radius `50% 45% 55% 42%`.
- Icon size `31px`.

Variasi:

| Kategori        | Icon    | Background | Foreground  |
| --------------- | ------- | ---------- | ----------- |
| Tenda           | tent    | `#E7EEE5`  | Forest      |
| Tas & Carrier   | bag     | `#E3EBF2`  | Alpine Blue |
| Peralatan Masak | fire    | `#EEE5DC`  | Earth       |
| Tidur & Shelter | package | `#E1E6EC`  | Navy        |

Nama kategori: Poppins 600 `13px`. Count: Inter `10px`, `#84908F`, margin-top `5px`.

---

## 4.F Featured Product Cards

### 4.F.1 Section

- Background `#F0F2ED`.
- Margin-top `60px`.
- Padding-bottom `95px`.
- Grid 4 kolom, gap `18px`.

### 4.F.2 Card

| Properti   | Nilai                                             |
| ---------- | ------------------------------------------------- |
| Background | putih                                             |
| Border     | `1px solid #E0E4DE`                               |
| Radius     | tidak ditetapkan; sudut persegi                   |
| Overflow   | hidden                                            |
| Hover      | translateY `-5px`, shadow `0 16px 35px #09223F12` |
| Transition | `0.25s`                                           |

Foto:

- Height `225px`.
- Width 100%.
- `background-size: cover`.
- `background-position: center`.

Photo tag:

- Top/left `13px`.
- Background `#F7F8F3E8`.
- Forest text.
- Inter 700 `9px`.
- Padding `7px 10px`.
- Letter spacing `0.05em`.

Favorite control:

- Top/right `12px`.
- `31 × 31px`.
- White circle.
- Simbol saat ini adalah karakter `♡`, `21px`, Navy.

Body:

- Padding `17px`.
- Category/rating row: `9px`, 700, `0.08em`.
- Product title: Poppins 600 `15px`, margin `9px 0 13px`.
- Mode tabs: bottom border; gap `18px`.
- Tab text: `8px`, 800, `0.12em`.
- Active SEWA: Forest + bottom border `2px`.
- Active BELI: Alpine Blue + bottom border `2px`.
- Price: Poppins 600 `16px`.
- Unit: `10px`, `#84908D`.
- Arrow action: `31 × 31px`, Forest circle, white 17 px icon.

### 4.F.3 Produk dan mode

| Produk                  | Kategori      |          Sewa |           Beli | Tag             | Rating |
| ----------------------- | ------------- | ------------: | -------------: | --------------- | -----: |
| Tenda Arunika 4P        | Tenda         | Rp85.000/hari | Tidak tersedia | Paling diminati |    4.9 |
| Carrier Altitude 55L    | Tas & Carrier | Rp55.000/hari | Tidak tersedia | Ringan          |    4.8 |
| Sleeping Bag Bromo -5°C | Tidur         | Rp35.000/hari | Rp425.000/unit | Hangat          |    4.9 |
| Kompor Trail Mini       | Memasak       | Rp28.000/hari | Rp289.000/unit | Praktis         |    4.7 |

Tab BELI hanya dirender jika nilai harga beli lebih dari nol.

---

## 4.G Rental Process Panel

- Min-height `480px`.
- Grid desktop `1fr 1.15fr`.
- Background overall Forest; foreground putih.
- Left panel Navy, padding `65px`.
- Left panel clip-path `polygon(0 0, 92% 0, 100% 50%, 92% 100%, 0 100%)`.
- Heading `37px`, line-height `1.18`.
- Supporting text `13px/1.7`, `#B7C2C6`, max-width `390px`.
- CTA Sand.
- Right panel padding `58px 60px`.

Step:

- Grid `60px 1fr`.
- Gap `22px`.
- Padding vertical `25px`.
- Divider `1px solid #FFFFFF20`, kecuali item terakhir.
- Number Poppins 500 `29px`, Sage.
- Step heading `18px`.
- Description `12px`, `#B9C4BE`, margin-top `7px`.

Dekorasi ring:

- Pseudo-element `340 × 340px`.
- Posisi left `-120px`, bottom `-190px`.
- Border dan dua spread ring semi-transparan.

---

## 4.H Trip Planner Banner

- Global container.
- Margin-top `75px`, margin-bottom `80px`.
- Min-height `400px`.
- Grid `0.9fr 1.1fr`.
- Background Sand.
- Overflow hidden.
- Copy padding `62px`.
- Description `13px/1.7`, max-width `430px`.
- Foto di kanan menggunakan `photos.mountain`.
- Foto memakai `cover`, centered, clip-path diagonal `polygon(12% 0, 100% 0, 100% 100%, 0 100%)`.
- Mini recommendation card: absolute left `10%`, bottom `28px`, putih, padding `13px 16px`, gap `10px`, shadow `0 10px 28px #0002`.

---

## 4.I Footer

- Background Navy.
- Foreground putih.
- Padding `65px 0 25px`.
- Desktop grid `1.4fr 0.65fr 0.75fr 1.2fr`.
- Gap `45px`.
- Empat kolom: brand, Jelajahi, Bantuan, newsletter.
- Link font `11px`, color `#AEB9BF`.
- Heading kolom Poppins 600 `12px`.
- Footer copy `11px/1.8`, `#9FADB5`.
- Newsletter memakai input tanpa box; hanya border-bottom `#FFFFFF55`.
- Footer bottom memiliki top border `#FFFFFF18`, margin-top `50px`, padding-top `22px`, font `9px`, color `#819099`.

---

## 5. Component Specifications

### 5.1 Component map yang direkomendasikan

Nama berikut framework-agnostic. Implementasi dapat memakai web components, template server, native mobile, atau framework UI apa pun.

```text
Homepage
├── SiteHeader
│   ├── BrandLogo
│   ├── PrimaryNavigation
│   ├── IconButton
│   ├── CartButton
│   ├── AccountButton
│   └── MobileMenuButton
├── HeroSection
│   ├── Eyebrow
│   ├── HeroHeadline
│   ├── PrimaryButton
│   ├── TextLink
│   ├── TrustIndicator
│   ├── HeroIllustration
│   ├── BrandStamp
│   ├── FloatingInfoCard
│   └── HeroSearchPanel
│       ├── SearchField
│       ├── TripDateField
│       └── SearchButton
├── SectionHeading
├── CategoryGrid
│   └── CategoryCard
├── FeaturedProductGrid
│   └── ProductCard
│       ├── PhotoTag
│       ├── FavoriteButton
│       ├── ModeTabs
│       └── PriceDisplay
├── RentalProcessPanel
│   └── ProcessStep
├── TripPlannerBanner
│   └── RecommendationBadge
└── SiteFooter
    ├── FooterLinkGroup
    └── NewsletterField
```

### 5.2 Icon component

Semua icon memakai satu aturan:

```text
viewBox: 0 0 24 24
fill: none
stroke: currentColor
stroke-width: 1.8
stroke-linecap: round
stroke-linejoin: round
default rendered size: 20 × 20 px
```

Icon yang tersedia:

`search`, `user`, `cart`, `arrow`, `calendar`, `shield`, `star`, `tent`, `bag`, `fire`, `check`, `package`, `clock`, `chevron`, `menu`, `close`, `grid`, `chart`, `users`, `wallet`.

Jangan mencampur outline icon ini dengan icon fill berat. Pengecualian: star pada rating diberi `fill: Earth`.

### 5.3 Button variants

| Variant        | Fill        | Text      | Radius |    Height | Hover            |
| -------------- | ----------- | --------- | -----: | --------: | ---------------- |
| Primary        | Forest      | White     |   6 px | min 48 px | translateY -2 px |
| Sand           | Sand        | Navy      |   6 px | min 48 px | translateY -2 px |
| Outline        | Transparent | inherited |   6 px | min 48 px | translateY -2 px |
| Text link      | None        | Navy      |      0 |   content | Color Blue       |
| Search CTA     | Blue        | White     |   5 px |     60 px | Tidak ditetapkan |
| Circular arrow | Forest      | White     |    50% |     31 px | Mengikuti card   |

### 5.4 State visual yang tersedia

| Komponen       | Default     | Hover              | Active/selected     | Focus                  | Disabled  | Loading   |
| -------------- | ----------- | ------------------ | ------------------- | ---------------------- | --------- | --------- |
| Nav item       | Gray        | Forest             | Forest + underline  | **NEEDS VERIFICATION** | Tidak ada | Tidak ada |
| Primary button | Forest      | naik 2 px          | Tidak ada           | **NEEDS VERIFICATION** | Tidak ada | Tidak ada |
| Text link      | Navy        | Blue               | Tidak ada           | **NEEDS VERIFICATION** | Tidak ada | Tidak ada |
| Category card  | White       | naik 4 px + shadow | Tidak ada           | **NEEDS VERIFICATION** | Tidak ada | Tidak ada |
| Product card   | White       | naik 5 px + shadow | Mode tab selected   | **NEEDS VERIFICATION** | Tidak ada | Tidak ada |
| Hero inputs    | Transparent | Tidak ada          | Browser input state | Outline dihapus        | Tidak ada | Tidak ada |

> Focus-visible, disabled, dan loading states tidak didefinisikan di Homepage saat audit. Jangan mengklaim state tersebut berasal dari desain. Penambahan untuk accessibility harus mempertahankan visual dasar dan ditandai sebagai implementasi tambahan.

---

## 6. Design System & Tokens

### 6.1 Core palette

| Token semantic    | CSS token                    | HEX       | Penggunaan utama                             |
| ----------------- | ---------------------------- | --------- | -------------------------------------------- |
| Deep Forest Green | `--forest`, `--color-forest` | `#183D32` | CTA primer, logo mark, icon, landscape       |
| Mountain Navy     | `--navy`, `--color-navy`     | `#08213F` | Heading, footer, foreground utama, landscape |
| Alpine Blue       | `--blue`, `--color-blue`     | `#2377C9` | Aksen “alam.”, search CTA, active buy        |
| Sage Green        | `--sage`, `--color-sage`     | `#A8B99A` | Gunung, aksen, angka step                    |
| Warm Sand         | `--sand`, `--color-sand`     | `#E9DDC9` | Matahari, tenda, CTA sand, icon background   |
| Cloud White       | `--cloud`, `--color-cloud`   | `#F7F8F3` | Global background, foreground ilustrasi      |
| Earth Brown       | `--earth`, `--color-earth`   | `#80664E` | Rating, tenda, kategori masak                |
| Divider           | `--line`                     | `#DBE0D7` | Border dan separator umum                    |

### 6.2 Secondary colors yang terimplementasi

| Nilai     | Penggunaan                    |
| --------- | ----------------------------- |
| `#EDF1E9` | Background hero               |
| `#F0F2ED` | Background featured products  |
| `#526267` | Hero body text                |
| `#586867` | Trust text                    |
| `#4E5B60` | Default nav text              |
| `#839589` | Circular stamp border         |
| `#B98562` | Skin pendaki                  |
| `#DFE4DC` | Header bottom border          |
| `#D8DFD4` | Trust top border              |
| `#E1E5DF` | Category card border          |
| `#E0E4DE` | Product card border           |
| `#E7EEE5` | Forest-tinted icon background |
| `#E3EBF2` | Blue-tinted icon background   |
| `#EEE5DC` | Earth-tinted icon background  |
| `#E1E6EC` | Navy-tinted icon background   |

### 6.3 Spacing scale

Tidak ada named spacing token dalam source. Berikut nilai **IMPLEMENTED** yang berulang dan harus dipertahankan:

```text
3, 4, 5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18,
20, 22, 24, 25, 28, 30, 32, 34, 35, 40, 42, 45, 50, 58,
60, 62, 65, 70, 75, 78, 80, 86, 94, 95, 105 px
```

Pengelompokan praktis:

- Micro: 3–8 px.
- Component internal: 10–18 px.
- Content group: 20–35 px.
- Section/layout: 40–105 px.

Ini adalah pengelompokan dokumentasi, bukan nama token asli Figma.

### 6.4 Radius

|             Nilai | Penggunaan                                              |
| ----------------: | ------------------------------------------------------- |
|             `5px` | Search CTA                                              |
|             `6px` | Button umum, floating card                              |
|             `7px` | Search panel                                            |
|             `50%` | Badge, icon button circle, avatar                       |
| `50% 50% 45% 55%` | Logo mark                                               |
| `50% 45% 55% 42%` | Category icon container                                 |
|       `0` / unset | Category card, product card, process panel, trip banner |

### 6.5 Elevation

| Nama dokumentasi | Nilai exact             | Penggunaan          |
| ---------------- | ----------------------- | ------------------- |
| Button elevation | `0 8px 20px #183D3225`  | Primary CTA         |
| Floating card    | `0 12px 35px #08213F24` | Hero info card      |
| Search elevation | `0 14px 40px #102E2520` | Search/date panel   |
| Category hover   | `0 10px 30px #142E2510` | Category card       |
| Product hover    | `0 16px 35px #09223F12` | Product card        |
| Trip mini card   | `0 10px 28px #0002`     | Recommendation card |

### 6.6 Grid

| Area             | Desktop                 | Tablet ≤900       | Mobile ≤600                          |
| ---------------- | ----------------------- | ----------------- | ------------------------------------ |
| Global container | max 1180, 20 px gutters | sama              | 14 px gutters                        |
| Hero             | 1.02fr / 0.98fr         | 1 column          | flex column                          |
| Search panel     | 1.15fr / 1fr / 150px    | 1fr / 1fr / 130px | 1 column                             |
| Category         | 4 columns               | 2 columns         | 2 columns                            |
| Product          | 4 columns               | 2 columns         | horizontal snap row                  |
| Process          | 1fr / 1.15fr            | 1 column          | 1 column                             |
| Trip banner      | 0.9fr / 1.1fr           | 1 column          | 1 column                             |
| Footer           | 4 columns               | 2 columns         | 2 columns; brand/newsletter full row |

---

## 7. Typography System

### 7.1 Font loading

Font di-load dari Google Fonts:

```css
inter:
  400,
  500,
  600,
  700 Poppins: 500,
  600,
  700;
```

Base font: Inter.  
Heading `h1`, `h2`, `h3`: Poppins dengan letter-spacing `-0.035em`.

### 7.2 Type scale Homepage

| Style          | Family        |            Weight |           Size |     Line-height | Tracking | Color        |
| -------------- | ------------- | ----------------: | -------------: | --------------: | -------: | ------------ |
| Hero H1        | Poppins       |               600 | clamp 43–66 px |            1.06 | -0.035em | Navy         |
| Hero accent    | Georgia serif |        400 italic |        inherit |         inherit |  inherit | Blue         |
| Section H2     | Poppins       |               600 |          34 px | browser/default | -0.035em | Navy/inherit |
| Process H2     | Poppins       | default inherited |          37 px |            1.18 | -0.035em | White        |
| Hero body      | Inter         |               400 |          16 px |            1.75 |   normal | `#526267`    |
| Kicker/eyebrow | Inter         |               700 |          10 px |          normal |   0.19em | Forest       |
| Primary button | Inter         |               700 |          13 px |       inherited |   normal | White        |
| Text link      | Inter         |               700 |          13 px |       inherited |   normal | Navy         |
| Nav            | Inter         |               600 |          13 px |       inherited |   normal | `#4E5B60`    |
| Trust          | Inter         |               600 |          11 px |       inherited |   normal | `#586867`    |
| Product title  | Poppins       |               600 |          15 px |       inherited | -0.035em | Navy         |
| Price          | Poppins       |               600 |          16 px |       inherited | -0.035em | Navy         |
| Category title | Poppins       |               600 |          13 px |       inherited | -0.035em | Navy         |
| Footer heading | Poppins       |               600 |          12 px |       inherited | -0.035em | White        |
| Footer link    | Inter         |               400 |          11 px |       inherited |   normal | `#AEB9BF`    |

### 7.3 Mobile typography

- Hero H1 menjadi fixed `41px` pada ≤600 px.
- Section H2 menjadi `28px`.
- Process H2 menjadi `29px`.
- Tidak ada override mobile untuk body hero; tetap `16px/1.75`.

---

## 8. Asset Inventory

### 8.1 Brand dan vector assets

| Asset                     | Lokasi sumber           | Format             | Dimensi/viewBox                | Penggunaan                    |
| ------------------------- | ----------------------- | ------------------ | ------------------------------ | ----------------------------- |
| GEARORA logo              | `Logo` di `src/App.tsx` | HTML + inline SVG  | Mark 37×37; icon viewBox 24×24 | Header dan footer             |
| Tent logo icon            | `icons.tent`            | Inline SVG path    | viewBox 24×24                  | Logo, category, floating card |
| Hero illustration         | `HeroArt`               | Inline SVG         | viewBox 660×520                | Hero utama                    |
| Circular brand stamp      | `.adventure-stamp`      | HTML/CSS           | 94×94 desktop                  | Overlay hero                  |
| Decorative hero dots/star | Di dalam `HeroArt`      | Inline SVG         | Bagian viewBox 660×520         | Dekorasi hero                 |
| Process rings             | `.how-panel::before`    | CSS pseudo-element | Base 340×340                   | Dekorasi process section      |

**Nama layer Figma asli:** **NEEDS VERIFICATION**. Nama di atas adalah nama komponen/source aktif.

### 8.2 Foto

Semua foto berasal dari URL Unsplash dan saat ini tidak disimpan sebagai file lokal.

| Key        | Source URL ringkas                 | Request width | Penggunaan                             |
| ---------- | ---------------------------------- | ------------: | -------------------------------------- |
| `tent`     | `photo-1628087235616-4e146afcd061` |           900 | Tenda Arunika product card/detail      |
| `blueTent` | `photo-1597888490716-d388d6958931` |           900 | Sleeping Bag card/guide/detail thumb   |
| `camp`     | `photo-1660819731358-e197f235eeb7` |           900 | Carrier card/guide                     |
| `mountain` | `photo-1710373101239-090a9f072145` |          1200 | Trip planner banner/guide/detail thumb |
| `forest`   | `photo-1688599132131-fec54366b46e` |           900 | Kompor card/featured guide             |

Parameter umum: `auto=format&fit=crop&q=85`.

> Untuk implementasi produksi, aset dapat dilokalkan demi kestabilan, tetapi crop, subject, dan color impression harus sama. Jangan mengganti foto tanpa approval.

### 8.3 Icon inventory

| Icon         | Homepage usage                    |
| ------------ | --------------------------------- |
| search       | Header, hero search               |
| user         | Account button                    |
| cart         | Header cart                       |
| arrow        | CTA, product action, section link |
| calendar     | Hero date field                   |
| shield       | Trust indicator                   |
| star         | Trust/rating                      |
| tent         | Logo, category, floating card     |
| bag          | Category                          |
| fire         | Category                          |
| package      | Category                          |
| check        | Trip mini card                    |
| chevron      | Category card                     |
| menu / close | Responsive navigation             |

Icon lain (`clock`, `grid`, `chart`, `users`, `wallet`) dipakai di halaman non-Homepage.

### 8.4 File/layer metadata

Informasi berikut **NEEDS VERIFICATION** dari Figma:

- Figma file key dan node ID Homepage.
- Nama page/frame Figma.
- Nama layer untuk setiap subkomponen.
- Component set dan variant names.
- Aset export IDs.
- Format export resmi selain inline SVG.
- Dimensi frame desktop/tablet/mobile pada canvas.

---

## 9. Interaction Specifications

### 9.1 Routing aktual

Routing saat ini berupa client-side page state, bukan URL route.

| Control                   | Aksi aktual                                                      |
| ------------------------- | ---------------------------------------------------------------- |
| Logo/wrapper header       | Set page `home`, smooth scroll ke top                            |
| Nav Sewa                  | Set page `catalog`                                               |
| Nav Beli                  | Set page `catalog`                                               |
| Nav Rekomendasi Trip      | Set page `trip`                                                  |
| Nav Panduan               | Set page `guides`                                                |
| Header search             | Set page `catalog`                                               |
| Header cart               | Tidak memiliki handler                                           |
| Masuk                     | Set page `account`                                               |
| Mobile menu               | Toggle state open/closed                                         |
| Mulai Sewa                | Set page `catalog`                                               |
| Bantu pilihkan gear       | Set page `trip`                                                  |
| Cari Gear                 | Set page `catalog`                                               |
| Category card             | Set page `catalog`                                               |
| Product photo/title/arrow | Set page `detail`                                                |
| Product mode tab          | Toggle local state `sewa`/`beli`                                 |
| Coba sekarang             | Set page `catalog`                                               |
| Buat rekomendasi          | Set page `trip`                                                  |
| Footer links              | Sebagian mengubah page state; FAQ/Hubungi belum memiliki handler |

Setiap fungsi `go(page)`:

1. Mengubah page state.
2. Menjalankan `window.scrollTo({ top: 0, behavior: "smooth" })`.

### 9.2 Search dan date

**Search hero**

- Input menerima teks secara native.
- Tidak tersambung ke state.
- Tidak ada submit keyboard handler khusus.
- Nilai tidak dikirim ke katalog.
- Tidak ada suggestion/autocomplete.

**Date**

- Input bertipe text.
- Tidak ada date picker.
- Tidak ada validation tanggal.
- Tidak ada range atau participant selector pada Homepage.

**Status:** perilaku lanjutan **NEEDS VERIFICATION** terhadap prototype Figma/requirements.

### 9.3 Shopping cart

- Badge statis menampilkan `2`.
- Cart button memiliki accessible label `Keranjang`.
- Tidak ada click handler, drawer, modal, atau route.

### 9.4 Product card interaction

- Mode awal selalu `sewa`.
- Tab BELI hanya tersedia pada produk dengan `sell > 0`.
- Perubahan tab langsung mengganti price dan unit.
- Favorite heart tidak memiliki handler.
- Seluruh photo button, title, dan circular arrow membuka halaman detail.

### 9.5 Hover/active

Hover yang benar-benar didefinisikan:

- Navigation text → Forest.
- Text-link → Blue.
- Generic button → translateY -2 px.
- Category card → translateY -4 px, Sage border, shadow.
- Product card → translateY -5 px, shadow.

Active yang didefinisikan:

- Nav active underline.
- Product mode active underline/color.
- Mobile nav open/closed.

### 9.6 Validation, loading, error

Homepage tidak memiliki:

- Form validation.
- Error messaging.
- Loading skeleton/spinner.
- Disabled state.
- Search empty state.
- Date availability state.
- Newsletter submit state.

Semua hal di atas **NEEDS VERIFICATION** dan tidak boleh dideskripsikan sebagai bagian dari desain asli tanpa bukti tambahan.

---

## 10. Responsive Specifications

> Breakpoint di bawah adalah **IMPLEMENTED** dalam CSS. Ketersediaan frame tablet/mobile khusus pada Figma adalah **NEEDS VERIFICATION**.

### 10.1 Desktop: >900 px

- Header penuh, account button terlihat, menu button tersembunyi.
- Hero dua kolom 1.02/0.98.
- Illustration min-height 500 px.
- Search panel tiga kolom.
- Category dan product grid empat kolom.
- Rental process dua kolom.
- Trip banner dua kolom dengan diagonal photo edge.
- Footer empat kolom.

### 10.2 Tablet: ≤900 px dan >600 px

Navigation:

- Nav diposisikan absolute di bawah header (`top: 78px`).
- Default hidden; `.open` menjadi flex column.
- Background Cloud, padding 20 px.
- Active underline disembunyikan.
- Account desktop disembunyikan.
- Menu button dan mobile-only links tampil.

Hero:

- Grid menjadi satu kolom.
- Hero copy mendapat padding-top 25 px.
- Hero illustration min-height 450 px.
- Search panel tetap horizontal tiga kolom: `1fr 1fr 130px`.

Homepage sections:

- Category/product grid menjadi dua kolom.
- Process panel satu kolom; clip-path kiri dihapus.
- Trip banner satu kolom; photo min-height 320 px; clip-path dihapus.
- Footer menjadi dua kolom.

### 10.3 Mobile: ≤600 px

Global:

- Container gutter 14 px.
- Header height 68 px.
- Dropdown nav top 68 px.
- Header search icon disembunyikan; cart dan menu tetap terlihat.

Hero:

- Padding top 25 px.
- H1 fixed 41 px.
- Hero grid diubah menjadi flex column.
- Illustration min-height 355 px, width 100%.
- Stamp menjadi 70×70 px dengan font 6 px.
- Floating card bottom 20 px.
- Trust indicators menjadi column, gap 10 px.

Search panel:

- Overlap dihapus (`transform: none`).
- Width 100%.
- Margin-top 15 px.
- Grid satu kolom.
- Padding 12 px.
- Field padding 12 px.
- Divider berubah dari border-right menjadi border-bottom.
- Search button height 48 px.

Section:

- Generic section padding-top 70 px.
- Section H2 28 px.
- CTA kanan pada section header disembunyikan.

Categories:

- Tetap dua kolom.
- Gap 8 px.
- Card padding 13 px; min-height 95 px.
- Chevron akhir disembunyikan.
- Organic icon 44×48 px.

Products:

- Grid berubah menjadi horizontal scroll flex.
- `scroll-snap-type: x mandatory`.
- Card width minimum `78vw`.
- Card snap align start.
- Right overflow disengaja untuk memberi indikasi card berikutnya.

Process/trip/footer:

- Process copy padding 38×25 px.
- Process H2 29 px.
- Steps padding 25 px.
- Step grid `46px 1fr`.
- Trip copy padding 35×25 px.
- Footer tetap dua kolom; brand dan newsletter span penuh.
- Footer bottom menjadi column dengan gap 8 px.

### 10.4 Responsive details yang belum diverifikasi

- Frame width desain asli, misalnya 1440/1024/390: **NEEDS VERIFICATION**.
- Safe-area inset pada perangkat ber-notch: tidak didefinisikan.
- Landscape mobile: tidak memiliki breakpoint tersendiri.
- Hover suppression pada touch device: tidak didefinisikan.
- Maximum line length untuk tablet: mengikuti CSS flow, bukan constraint khusus.

---

## 11. Implementation Guidelines

### 11.1 Framework-agnostic section hierarchy

Gunakan struktur semantic:

```html
<header>
  <nav>...</nav>
</header>
<main>
  <section aria-labelledby="hero-title">...</section>
  <section aria-labelledby="categories-title">...</section>
  <section aria-labelledby="featured-title">...</section>
  <section aria-labelledby="rental-process-title">...</section>
  <section aria-labelledby="trip-planner-title">...</section>
</main>
<footer>...</footer>
```

### 11.2 Design token contract

Agent implementasi harus membuat token equivalent untuk:

```text
color.forest = #183D32
color.navy = #08213F
color.blue = #2377C9
color.sage = #A8B99A
color.sand = #E9DDC9
color.cloud = #F7F8F3
color.earth = #80664E
color.line = #DBE0D7

font.heading = Poppins
font.body = Inter
font.editorialAccent = Georgia

layout.container.max = 1180px
layout.gutter.desktop = 20px
layout.gutter.mobile = 14px
breakpoint.tablet = 900px
breakpoint.mobile = 600px
```

Nama token boleh mengikuti platform, tetapi nilai harus sama.

### 11.3 Asset usage

1. Prioritaskan komponen SVG `HeroArt` asli.
2. Pertahankan viewBox dan layer order.
3. Pertahankan alt text/accessible label.
4. Gunakan inline SVG icon paths asli bila memungkinkan.
5. Jika icon dipindah ke sprite/component library internal, pertahankan viewBox, stroke width, cap, dan join.
6. Pertahankan crop foto `cover` dan center.
7. Jangan mengganti hero illustration dengan foto produk.

### 11.4 Layout rules

- Header tidak menambah tinggi layout ketika sticky.
- Hero search panel harus overlap 34 px pada desktop/tablet, tetapi tidak pada mobile.
- Hero copy dan illustration harus berbagi grid yang sama pada desktop.
- SVG boleh melebar 110% dan sengaja ter-crop oleh `.hero-visual`.
- Brand stamp dan floating card harus diposisikan relatif terhadap hero visual, bukan viewport.
- Section berikut hero perlu mempertahankan padding 105 px agar overlap search panel tidak menabrak heading.
- Product card pada mobile wajib horizontal snap, bukan grid satu kolom.
- Card tidak boleh diberi radius besar secara global.

### 11.5 Content rules

- Teks headline, line break, dan punctuation harus identik.
- Format harga sewa: `Rp{harga}` + `/ hari`.
- Format harga beli: `Rp{harga}` + `/ unit`.
- Label SEWA dan BELI menggunakan uppercase.
- Produk tanpa harga beli tidak menampilkan tab BELI.
- Badge cart Homepage tetap `2` untuk fidelity statis; aplikasi nyata boleh mengambil data dinamis.

### 11.6 Accessibility notes

Yang sudah tersedia:

- Hero SVG memiliki role dan aria-label.
- Header search/cart dan product arrow memiliki aria-label.
- Elemen aksi utama menggunakan button.

Yang perlu dipastikan saat implementasi:

- Tambahkan focus-visible yang jelas tanpa mengubah default visual non-focus.
- Tetapkan label programatik untuk input hero; struktur visual `<small>` saat ini belum otomatis menjadi accessible label.
- Pastikan menu mobile memiliki `aria-expanded`.
- Pastikan active page memakai `aria-current`.
- Pastikan contrast alpha text tetap memenuhi target.

Perbaikan accessibility merupakan implementasi teknis yang diperbolehkan selama visual fidelity tidak berubah.

### 11.7 Jangan lakukan

- Jangan menambah glassmorphism.
- Jangan menambah gradient pada hero/button.
- Jangan mengganti Navy dengan hitam murni.
- Jangan menjadikan semua card rounded.
- Jangan menghapus aksen Georgia pada “alam.”
- Jangan meratakan semua elemen ilustrasi ke tengah.
- Jangan menghapus whitespace di sekitar copy hero.
- Jangan memindahkan search panel sepenuhnya ke luar hero pada desktop.
- Jangan menggunakan emoji untuk shield, star, tent, cart, atau menu.

---

## 12. Pixel-Perfect Checklist

### 12.1 Global

- [ ] Background halaman `#F7F8F3`.
- [ ] Container max-width tepat 1180 px.
- [ ] Gutter desktop 20 px per sisi dan mobile 14 px.
- [ ] Poppins hanya untuk heading/brand/title; Inter untuk body/UI.
- [ ] Heading tracking `-0.035em`.
- [ ] Tidak ada radius besar yang tidak terdokumentasi.

### 12.2 Header

- [ ] Header 78 px desktop dan 68 px mobile.
- [ ] Background header memakai alpha dan blur 14 px.
- [ ] Logo mark 37×37 px dengan organic radius dan rotation.
- [ ] Nav gap 28 px; margin-left 60 px.
- [ ] Cart badge tepat 16×16 px dan menampilkan `2`.
- [ ] Active nav line 2 px berada 27 px di bawah label.
- [ ] Account button memiliki separator kiri.

### 12.3 Hero copy

- [ ] Hero background `#EDF1E9`.
- [ ] Desktop grid ratio 1.02/0.98.
- [ ] H1 memakai line break 3 baris yang didokumentasikan.
- [ ] Kata “alam.” memakai Georgia italic berwarna `#2377C9`.
- [ ] H1 maksimum 66 px, line-height 1.06.
- [ ] Body max-width 530 px dan line-height 1.75.
- [ ] CTA primary 48 px minimum, radius 6 px.
- [ ] Trust row memiliki border top dan dua item.

### 12.4 Hero illustration

- [ ] SVG asli dengan viewBox 660×520 dipakai.
- [ ] Layer order matahari → gunung → landscape → tenda → karakter → dekorasi tidak berubah.
- [ ] SVG width 110% dan crop container dipertahankan.
- [ ] Karakter tetap di kiri tenda.
- [ ] Circular stamp 94 px desktop / 70 px mobile.
- [ ] Stamp berotasi 12 derajat.
- [ ] Floating card berada kanan bawah.
- [ ] Tidak ada foto stok di dalam hero visual.

### 12.5 Search panel

- [ ] Overlap 34 px pada desktop/tablet.
- [ ] Panel min-height 86 px, radius 7 px.
- [ ] Shadow exact `0 14px 40px #102E2520`.
- [ ] Grid desktop `1.15fr 1fr 150px`.
- [ ] Search dan date field memiliki divider kanan.
- [ ] Blue CTA height 60 px.
- [ ] Pada mobile panel satu kolom, tidak overlap, CTA 48 px.

### 12.6 Homepage sections

- [ ] Section padding top 105 px desktop / 70 px mobile.
- [ ] Category grid 4/2/2 kolom.
- [ ] Product grid 4/2/horizontal-scroll.
- [ ] Product photo 225 px pada desktop.
- [ ] Mode sewa/beli tidak tercampur.
- [ ] Process panel minimum 480 px dan memakai Forest/Navy.
- [ ] Trip photo memakai diagonal clip desktop dan tanpa clip tablet/mobile.
- [ ] Footer 4 kolom desktop, 2 kolom tablet/mobile.

### 12.7 Interaction

- [ ] CTA dan navigation target mengikuti tabel routing.
- [ ] Button hover bergerak -2 px.
- [ ] Category hover bergerak -4 px.
- [ ] Product hover bergerak -5 px.
- [ ] Product mode tabs mengganti harga dan unit.
- [ ] Mobile menu benar-benar toggle.
- [ ] Smooth scroll ke top terjadi saat page state berubah.

---

## 13. Unverified Details & Assumptions

### 13.1 NEEDS VERIFICATION — metadata Figma

- [ ] Nama Figma page dan frame Homepage.
- [ ] File key dan node ID.
- [ ] Frame dimensions desktop, tablet, dan mobile.
- [ ] Auto Layout settings asli.
- [ ] Hug/fill/fixed sizing setiap layer.
- [ ] Constraints/pinning asli.
- [ ] Component set, instance, variant, dan property names.
- [ ] Prototype transition/easing/duration pada canvas.
- [ ] Export settings untuk logo/illustration/icon.
- [ ] Variable collections/modes asli.
- [ ] Apakah source implementation sudah 1:1 dengan canvas terbaru.

### 13.2 NEEDS VERIFICATION — interaction intent

- [ ] Apakah Sewa dan Beli seharusnya membuka filter/mode katalog berbeda.
- [ ] Tujuan cart button.
- [ ] Search query handoff ke katalog.
- [ ] Date picker dan format tanggal.
- [ ] Favorite product behavior.
- [ ] Newsletter submit.
- [ ] Focus, loading, error, disabled, dan validation states.
- [ ] Apakah hero logo click hanya scroll atau route home.

### 13.3 INFERENCE yang tidak boleh dianggap metadata Figma

- Breakpoint 900 px dan 600 px merupakan fakta CSS aktif, tetapi bukan bukti adanya frame Figma pada ukuran tersebut.
- Pengelompokan spacing menjadi micro/component/layout dibuat untuk membantu implementasi; source tidak memiliki named spacing scale.
- Nama elevation seperti “Button elevation” adalah label dokumentasi; source hanya menyimpan nilai box-shadow.
- Nama semantic token mengikuti brief/source; nama variable Figma asli belum tersedia.

### 13.4 Known implementation characteristics

Karakteristik berikut harus diketahui agent sebelum mengubah routing atau interaksi:

1. Current navigation memakai React local state, bukan URL.
2. Sewa dan Beli menuju page state yang sama.
3. Search hero dan date belum stateful.
4. Cart dan favorite belum aktif.
5. Mobile menu memiliki dua item tambahan: Akun Saya dan Admin Console.
6. Logo memiliki scroll-to-top internal dan wrapper header juga mengarahkan ke home.
7. Homepage tidak memiliki loading/error/empty state.

---

## Ringkasan Fidelity Non-Negotiable

Untuk reproduksi paling dekat, agent wajib mempertahankan:

1. Palet tujuh warna inti.
2. Poppins + Inter + Georgia accent.
3. Container 1180 px.
4. Header sticky translucent 78 px.
5. Hero dua kolom dengan background `#EDF1E9`.
6. Headline tiga baris dan aksen “alam.”.
7. SVG hero asli 660×520 beserta layering-nya.
8. Search panel putih yang overlap 34 px.
9. Card dengan radius minimal dan whitespace editorial.
10. Product mobile horizontal snap.
11. Breakpoint 900 px dan 600 px.
12. Pemisahan mode dan unit harga SEWA/BELI.

Dokumen ini adalah acuan implementasi teknis Homepage GEARORA berdasarkan desain aktif yang dapat diaudit. Jika ditemukan perbedaan antara dokumen dan node Figma terbaru, lakukan verifikasi visual, catat perbedaannya, lalu perbarui bagian `NEEDS VERIFICATION` tanpa melakukan redesign.
