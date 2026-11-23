<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use Illuminate\Database\Seeder;

/**
 * Data katalog produk yang dikelompokkan sesuai kategori alat outdoor.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('DemoCatalogSeeder dilewati: bukan environment lokal.');

            return;
        }

        $jsonPath = database_path('seeders/data/alat_outdoor.json');

        if (! is_file($jsonPath)) {
            $this->command?->warn('File alat_outdoor.json tidak ditemukan.');

            return;
        }

        /** @var array<int, array<string, mixed>> $catalog */
        $rawJson = ltrim((string) file_get_contents($jsonPath), "\xEF\xBB\xBF");
        $catalog = json_decode($rawJson, true) ?? [];

        foreach ($catalog as $index => $group) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $group['slug']],
                [
                    'name' => $group['name'],
                    'icon' => $group['icon'] ?? 'bag',
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );

            foreach ($group['products'] ?? [] as $entry) {
                $product = Product::query()->updateOrCreate(
                    ['slug' => $entry['slug']],
                    [
                        'category_id' => $category->getKey(),
                        'name' => $entry['name'],
                        'description' => 'Perlengkapan outdoor kategori '.$group['name'].' yang terkurasi, bersih, dan siap digunakan untuk kegiatan alam terbuka maupun pendakian.',
                        'brand' => 'GEARORA Select',
                        'type' => ProductType::Both,
                        'status' => ProductStatus::Active,
                        'specifications' => [
                            'Kategori' => $group['name'],
                            'Kondisi' => 'Terinspeksi & Siap Pakai',
                        ],
                        'requires_deposit' => false,
                        'rental_price_per_day' => $entry['rental'],
                        'sale_price' => $entry['sale'],
                        'is_featured' => (bool) ($entry['featured'] ?? false),
                    ],
                );

                if (filled($entry['image'] ?? null)) {
                    ProductImage::query()->updateOrCreate(
                        ['product_id' => $product->getKey(), 'is_primary' => true],
                        [
                            'path' => $entry['image'],
                            'alt' => $entry['name'],
                            'sort_order' => 0,
                        ],
                    );
                }

                $variant = ProductVariant::query()->updateOrCreate(
                    ['sku' => 'GEAR-'.$product->getKey().'-STD'],
                    [
                        'product_id' => $product->getKey(),
                        'name' => 'Standar',
                        'sale_price' => $entry['sale'],
                        'stock_quantity' => 8,
                        'is_active' => true,
                    ],
                );

                for ($unit = 1; $unit <= 3; $unit++) {
                    RentalAsset::query()->updateOrCreate(
                        ['asset_code' => 'GEAR-'.$variant->getKey().'-'.str_pad((string) $unit, 2, '0', STR_PAD_LEFT)],
                        [
                            'product_variant_id' => $variant->getKey(),
                            'condition' => 'good',
                            'status' => 'available',
                            'notes' => 'Unit siap sewa',
                        ],
                    );
                }
            }
        }
    }
}
