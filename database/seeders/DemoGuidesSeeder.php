<?php

namespace Database\Seeders;

use App\Enums\GuideStatus;
use App\Models\EquipmentGuide;
use App\Models\GuideCategory;
use Illuminate\Database\Seeder;

/**
 * Kategori & artikel panduan contoh untuk pengembangan lokal.
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
            ['name' => 'Perawatan Gear', 'description' => 'Cara merawat dan menyimpan perlengkapan.', 'sort_order' => 2],
            ['name' => 'Keselamatan', 'description' => 'Persiapan dan keselamatan di alam terbuka.', 'sort_order' => 3],
        ];

        $categoryModels = [];

        foreach ($categories as $entry) {
            $categoryModels[$entry['name']] = GuideCategory::query()->updateOrCreate(
                ['slug' => str($entry['name'])->slug()->value()],
                [
                    'name' => $entry['name'],
                    'description' => $entry['description'].' (Demo)',
                    'sort_order' => $entry['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $guides = [
            [
                'title' => 'Memilih Tenda Sesuai Kondisi (Demo)',
                'category' => 'Dasar Camping',
                'excerpt' => 'Panduan ringkas membaca spesifikasi tenda sebelum menyewa.',
                'content' => "# Memilih Tenda\n\nPerhatikan jumlah orang, cuaca, dan bobot.\n\n## Tips\n\n- Sesuaikan kapasitas dengan peserta\n- Cek lapisan anti air\n",
            ],
            [
                'title' => 'Merawat Sleeping Bag (Demo)',
                'category' => 'Perawatan Gear',
                'excerpt' => 'Langkah sederhana menjaga kehangatan dan kebersihan.',
                'content' => "# Merawat Sleeping Bag\n\nKeringkan sebelum disimpan dan hindari sinar matahari langsung.\n",
            ],
        ];

        foreach ($guides as $entry) {
            EquipmentGuide::query()->updateOrCreate(
                ['slug' => str($entry['title'])->slug()->value()],
                [
                    'guide_category_id' => $categoryModels[$entry['category']]->getKey(),
                    'title' => $entry['title'],
                    'excerpt' => $entry['excerpt'],
                    'content' => $entry['content'],
                    'content_format' => 'markdown',
                    'status' => GuideStatus::Published,
                    'published_at' => now(),
                ],
            );
        }
    }
}
