<?php

namespace Database\Seeders;

use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Models\Product;
use App\Models\TripRecommendationRule;
use Illuminate\Database\Seeder;

/**
 * Aturan Trip Planner contoh. Mengacu kategori produk demo bila tersedia,
 * sehingga engine tetap deterministik tanpa data hardcoded di kode.
 */
class DemoTripRulesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('DemoTripRulesSeeder dilewati: bukan environment lokal.');

            return;
        }

        $tentCategoryId = Product::query()->where('name', 'like', '%Tenda%')->value('category_id');

        $rules = [
            [
                'name' => 'Tenda untuk camping',
                'activity' => TripActivity::Camping->value,
                'min_duration_days' => 1,
                'min_participants' => 1,
                'product_category_id' => $tentCategoryId,
                'service_mode' => TripServiceMode::Sewa,
                'recommended_quantity' => 1,
                'priority' => 200,
                'reason_template' => 'Tenda utama untuk :participants peserta selama :duration hari di :destination.',
            ],
            [
                'name' => 'Perlengkapan tidur',
                'activity' => TripActivity::ANY,
                'min_duration_days' => 1,
                'min_participants' => 1,
                'product_category_id' => Product::query()->where('name', 'like', '%Sleeping%')->value('category_id'),
                'service_mode' => TripServiceMode::Semua,
                'recommended_quantity' => 1,
                'quantity_per_participant' => true,
                'priority' => 100,
                'reason_template' => 'Satu set perlengkapan tidur per peserta (:participants orang).',
            ],
        ];

        foreach ($rules as $rule) {
            TripRecommendationRule::query()->updateOrCreate(
                [
                    'name' => $rule['name'],
                    'activity' => $rule['activity'],
                ],
                array_merge($rule, ['is_active' => true]),
            );
        }
    }
}
