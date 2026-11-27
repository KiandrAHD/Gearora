<?php

namespace Database\Seeders;

use App\Enums\GuideStatus;
use App\Models\EquipmentGuide;
use App\Models\GuideCategory;
use Illuminate\Database\Seeder;

/**
 * Panduan editorial untuk local/testing. Tidak pernah di-seed ke produksi.
 * updateOrCreate by slug membuat seeder aman dijalankan berulang kali.
 */
class DemoGuidesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('DemoGuidesSeeder dilewati: bukan environment lokal.');

            return;
        }

        $categories = [
            ['name' => 'Dasar Camping', 'description' => 'Mulai dari memilih tenda sampai mendirikan shelter.', 'sort_order' => 1],
            ['name' => 'Persiapan Pendakian', 'description' => 'Rencana perjalanan, pakaian, dan logistik sebelum naik gunung.', 'sort_order' => 2],
            ['name' => 'Perawatan Gear', 'description' => 'Cara merawat dan menyimpan perlengkapan.', 'sort_order' => 3],
            ['name' => 'Keselamatan', 'description' => 'Persiapan dan keselamatan di alam terbuka.', 'sort_order' => 4],
        ];

        $categoryModels = [];

        foreach ($categories as $entry) {
            $categoryModels[$entry['name']] = GuideCategory::query()->updateOrCreate(
                ['slug' => str($entry['name'])->slug()->value()],
                [
                    'name' => $entry['name'],
                    'description' => $entry['description'].' (Demo lokal)',
                    'sort_order' => $entry['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $guides = [
            [
                'title' => 'Cara Memilih Tenda untuk Pendakian dan Camping',
                'category' => 'Dasar Camping',
                'featured' => true,
                'excerpt' => 'Kapasitas, musim, ruang depan, dan bobot adalah empat hal yang perlu dibaca sebelum memilih tenda.',
                'content' => <<<'MD'
# Cara memilih tenda

Tenda bukan hanya tempat tidur. Ia adalah perlindungan dari embun, angin, hujan, dan perubahan suhu malam. Mulai dari rencana trip, bukan dari warna atau bentuk tenda.

## Tentukan kapasitas realistis

Label dua orang berarti dua matras standar dapat masuk. Untuk pendakian dengan carrier besar, pilih kapasitas satu tingkat lebih besar agar ruang tidur dan barang tetap nyaman.

- Solo atau ultralight: tenda 1–2 orang
- Dua orang dengan carrier: tenda 3 orang
- Camping keluarga: pilih ruang vestibule dan tinggi duduk yang cukup

## Baca spesifikasi cuaca

Periksa rainfly, ketahanan air lantai, ventilasi, dan jumlah pasak. Tenda tiga musim cocok untuk hujan dan angin normal, namun bukan alasan mengabaikan prakiraan cuaca.

> Jangan memasang tenda di cekungan, jalur air, bawah pohon rapuh, atau terlalu dekat api unggun.

## Cek sebelum berangkat

Bentangkan tenda di rumah. Pastikan pasak, pole, flysheet, footprint, dan repair kit lengkap. Latihan singkat membuat pemasangan lebih cepat saat tiba di camp.
MD,
            ],
            [
                'title' => 'Layering Pakaian Gunung: Tetap Hangat Tanpa Gerah',
                'category' => 'Persiapan Pendakian',
                'featured' => true,
                'excerpt' => 'Sistem tiga lapis membantu tubuh tetap nyaman saat ritme jalan, angin, hujan, dan suhu berubah.',
                'content' => <<<'MD'
# Layering pakaian gunung

Suhu tubuh berubah cepat saat mendaki. Sistem layering memungkinkan kamu menambah atau mengurangi lapisan tanpa mengganti semua pakaian.

## Lapisan dasar

Gunakan bahan yang mengalirkan keringat seperti polyester, wool, atau campuran teknis. Hindari katun untuk aktivitas dingin dan basah karena lambat kering.

## Lapisan penghangat

Fleece atau insulasi sintetis dipakai ketika berhenti, di area berangin, atau menjelang malam. Simpan lapisan ini dalam dry bag agar tetap kering.

## Lapisan pelindung

Jaket luar melindungi dari angin dan hujan. Ventilasi ketiak dan hood yang bisa diatur membantu mengontrol panas.

- Buka lapisan sebelum terlalu berkeringat
- Ganti baju basah sesegera mungkin
- Bawa kaus kaki cadangan dalam kemasan kedap air
MD,
            ],
            [
                'title' => 'Packing Carrier yang Seimbang untuk Pendakian',
                'category' => 'Persiapan Pendakian',
                'featured' => true,
                'excerpt' => 'Susunan beban yang tepat membuat carrier stabil, punggung tidak cepat lelah, dan barang penting mudah dijangkau.',
                'content' => <<<'MD'
# Packing carrier yang seimbang

Carrier yang baik tidak hanya besar. Beban harus dekat dengan punggung, terkunci, dan tidak bergerak ketika kamu melangkah.

## Susun berdasarkan berat dan akses

Letakkan sleeping bag dan pakaian tidur di bagian bawah. Taruh barang berat seperti logistik dan tenda di tengah dekat punggung. Jas hujan, P3K, headlamp, air, dan snack harus mudah diambil.

## Lindungi dari air

Gunakan liner bag atau dry bag di dalam carrier. Rain cover membantu, tetapi bukan satu-satunya perlindungan saat hujan deras.

## Atur fit carrier

Kencangkan hip belt lebih dulu, lalu shoulder strap, load lifter, dan sternum strap. Sebagian besar beban harus bertumpu di pinggul, bukan bahu.

> Bila carrier menarik ke belakang atau bergoyang, berhenti dan susun ulang. Jangan memaksakan diri sampai nyeri.
MD,
            ],
            [
                'title' => 'Checklist Pendakian Gunung Prau untuk Pemula',
                'category' => 'Keselamatan',
                'featured' => false,
                'excerpt' => 'Daftar ringkas untuk memastikan dokumen, perlengkapan pribadi, navigasi, dan rencana pulang tidak tertinggal.',
                'content' => <<<'MD'
# Checklist Gunung Prau untuk pemula

Gunung Prau populer, tetapi tetap membutuhkan persiapan. Jalur ramai bukan jaminan kondisi selalu aman.

## Sebelum berangkat

- Cek aturan dan kuota basecamp
- Pantau cuaca dan rute transportasi
- Beri tahu keluarga rencana pulang
- Simpan nomor darurat dan kontak teman satu grup

## Gear pribadi

Bawa jaket hangat, jas hujan, headlamp, botol minum, obat pribadi, makan darurat, dan kantong sampah. Gunakan sepatu yang sudah dipakai berjalan sebelumnya, bukan sepatu baru pada hari pendakian.

## Di jalur

Jaga ritme grup. Jangan meninggalkan peserta paling lambat sendirian. Bila cuaca memburuk atau kondisi tubuh menurun, putar balik adalah keputusan yang benar.
MD,
            ],
            [
                'title' => 'Cara Merawat Sleeping Bag Setelah Trip',
                'category' => 'Perawatan Gear',
                'featured' => false,
                'excerpt' => 'Keringkan, angin-anginkan, lalu simpan longgar agar insulasi sleeping bag tetap mengembang dan hangat.',
                'content' => <<<'MD'
# Merawat sleeping bag

Sleeping bag menyimpan kelembapan dari tubuh dan udara malam. Menyimpannya dalam keadaan lembap dapat memunculkan bau dan menurunkan performa insulasi.

## Setelah pulang

Keluarkan sleeping bag dari compression sack. Angin-anginkan di tempat teduh hingga benar-benar kering. Hindari panas matahari langsung terlalu lama karena dapat merusak kain dan isian.

## Cuci seperlunya

Cuci hanya bila kotor atau berbau. Ikuti label perawatan, gunakan deterjen lembut, dan pastikan seluruh bagian kering sebelum disimpan.

## Simpan longgar

Gunakan storage sack besar atau gantung di lemari kering. Compression sack dipakai saat perjalanan, bukan untuk penyimpanan jangka panjang.
MD,
            ],
            [
                'title' => 'Memasak Aman di Camp: Kompor, Gas, dan Ventilasi',
                'category' => 'Keselamatan',
                'featured' => false,
                'excerpt' => 'Api kecil tetap berisiko. Pilih permukaan stabil, jauhkan bahan mudah terbakar, dan jangan menyalakan kompor di dalam tenda tertutup.',
                'content' => <<<'MD'
# Memasak aman di camp

Kompor portabel memudahkan logistik, namun api dan gas butuh disiplin. Persiapan sederhana mencegah luka bakar dan kebakaran.

## Pilih area memasak

Cari tanah datar, terbuka, dan terlindung dari angin kuat. Jauhkan kompor dari tenda, dry grass, botol bahan bakar, dan barang sintetis.

## Gunakan gas dengan benar

Periksa seal dan ulir sebelum memasang cartridge. Jangan memakai cartridge yang bocor atau penyok. Matikan kompor sebelum memindahkan panci.

> Jangan pernah memasak dengan kompor menyala di dalam tenda tertutup. Risiko karbon monoksida dan kebakaran tetap ada meski cuaca dingin.

## Setelah selesai

Pastikan api mati total, kompor dingin sebelum disimpan, dan semua sampah makanan dibawa turun.
MD,
            ],
            [
                'title' => 'Etika Leave No Trace untuk Pendaki Indonesia',
                'category' => 'Keselamatan',
                'featured' => false,
                'excerpt' => 'Jalur yang indah bertahan karena setiap pendaki membawa pulang sampah, menghormati satwa, dan mengikuti aturan kawasan.',
                'content' => <<<'MD'
# Etika Leave No Trace

Meninggalkan alam seperti semula adalah bagian dari keterampilan outdoor. Prinsip ini berlaku di gunung, pantai, hutan kota, dan bumi perkemahan.

## Rencanakan dan patuhi aturan

Cek kuota, zona camp, larangan api, serta kebijakan sampah pengelola. Bawa wadah sampah sendiri sejak dari rumah.

## Jaga jalur dan air

Tetap di jalur yang ada agar vegetasi tidak rusak. Jangan mencuci alat makan atau memakai sabun langsung di sumber air.

## Bawa turun semuanya

Sampah makanan, tisu, plastik, dan puntung rokok harus kembali bersama kamu. Jangan mengubur atau membakar sampah.

- Hormati pendaki lain dengan suara seperlunya
- Jangan memberi makan satwa liar
- Hindari mengambil bunga, batu, atau tanaman sebagai suvenir
MD,
            ],
            [
                'title' => 'Pertolongan Pertama Dasar untuk Aktivitas Outdoor',
                'category' => 'Keselamatan',
                'featured' => false,
                'excerpt' => 'P3K membantu menangani masalah awal, bukan menggantikan tenaga medis. Kenali batas kemampuan dan kapan harus meminta evakuasi.',
                'content' => <<<'MD'
# P3K dasar di alam terbuka

Bawa kotak P3K sesuai ukuran grup dan risiko perjalanan. Isinya perlu mudah diakses, kedap air, dan diperiksa sebelum berangkat.

## Isi minimal

Sertakan plester, kasa steril, perban, antiseptik, sarung tangan sekali pakai, pinset, obat pribadi, oralit, dan catatan kondisi medis peserta bila diperlukan.

## Saat ada cedera

Amankan lokasi lebih dulu. Nilai kesadaran, napas, perdarahan, dan kemungkinan cedera serius. Jangan memindahkan orang yang dicurigai mengalami cedera kepala, leher, atau tulang belakang kecuali ada bahaya langsung.

## Minta bantuan segera

Hentikan perjalanan dan hubungi petugas bila muncul sesak napas, penurunan kesadaran, perdarahan berat, nyeri dada, hipotermia berat, atau cedera yang tidak bisa ditangani dengan P3K dasar.

> Ikuti pelatihan pertolongan pertama resmi untuk kemampuan praktis. Artikel ini hanya pengingat persiapan awal.
MD,
            ],
        ];

        foreach ($guides as $position => $entry) {
            EquipmentGuide::query()->updateOrCreate(
                ['slug' => str($entry['title'])->slug()->value()],
                [
                    'guide_category_id' => $categoryModels[$entry['category']]->getKey(),
                    'title' => $entry['title'],
                    'excerpt' => $entry['excerpt'],
                    'content' => $entry['content'],
                    'content_format' => 'markdown',
                    'status' => GuideStatus::Published,
                    'is_featured' => $entry['featured'],
                    'published_at' => now()->subDays($position),
                    'seo_title' => $entry['title'].' | GEARORA Field Notes',
                    'seo_description' => $entry['excerpt'],
                ],
            );
        }
    }
}
