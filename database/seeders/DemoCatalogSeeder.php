<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use Illuminate\Database\Seeder;

/**
 * Data contoh katalog untuk pengembangan lokal. Tidak dijalankan di produksi.
 * Label "Demo" dipakai agar tidak disalahartikan sebagai data nyata.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('DemoCatalogSeeder dilewati: bukan environment lokal.');

            return;
        }

        $catalog = [
            ['name' => 'Tenda', 'icon' => 'tent', 'products' => [
                ['name' => 'Tenda Arunika 4P (Demo)', 'rental' => 85000, 'sale' => null, 'units' => 4, 'featured' => true],
                ['name' => 'Tenda Rimba 2P (Demo)', 'rental' => 65000, 'sale' => null, 'units' => 3],
            ]],
            ['name' => 'Tas & Carrier', 'icon' => 'bag', 'products' => [
                ['name' => 'Carrier Altitude 55L (Demo)', 'rental' => 55000, 'sale' => null, 'units' => 4, 'featured' => true],
            ]],
            ['name' => 'Tidur & Shelter', 'icon' => 'package', 'products' => [
                ['name' => 'Sleeping Bag Bromo -5C (Demo)', 'rental' => 35000, 'sale' => 425000, 'units' => 5, 'featured' => true],
            ]],
            ['name' => 'Peralatan Masak', 'icon' => 'fire', 'products' => [
                ['name' => 'Kompor Trail Mini (Demo)', 'rental' => 28000, 'sale' => 289000, 'units' => 6, 'featured' => true],
            ]],
        ];

        foreach ($catalog as $index => $group) {
            $category = Category::query()->updateOrCreate(
                ['slug' => str($group['name'])->slug()->value()],
                ['name' => $group['name'], 'icon' => $group['icon'], 'is_active' => true, 'sort_order' => $index],
            );

            foreach ($group['products'] as $entry) {
                $product = Product::query()->updateOrCreate(
                    ['slug' => str($entry['name'])->slug()->value()],
                    [
                        'category_id' => $category->getKey(),
                        'name' => $entry['name'],
                        'description' => 'Produk contoh untuk pengembangan. Ganti dengan data nyata sebelum produksi.',
                        'brand' => 'GEARORA Demo',
                        'type' => $entry['sale'] === null ? ProductType::Rental : ProductType::Both,
                        'status' => ProductStatus::Active,
                        'specifications' => ['Catatan' => 'Data demo'],
                        'requires_deposit' => false,
                        'rental_price_per_day' => $entry['rental'],
                        'sale_price' => $entry['sale'],
                        'is_featured' => (bool) ($entry['featured'] ?? false),
                    ],
                );

                $variant = ProductVariant::query()->updateOrCreate(
                    ['sku' => 'DEMO-'.$product->getKey().'-STD'],
                    [
                        'product_id' => $product->getKey(),
                        'name' => 'Standar',
                        'sale_price' => $entry['sale'],
                        'stock_quantity' => $entry['sale'] !== null ? 10 : 0,
                        'is_active' => true,
                    ],
                );

                for ($unit = 1; $unit <= $entry['units']; $unit++) {
                    RentalAsset::query()->updateOrCreate(
                        ['asset_code' => 'DEMO-'.$variant->getKey().'-'.str_pad((string) $unit, 2, '0', STR_PAD_LEFT)],
                        [
                            'product_variant_id' => $variant->getKey(),
                            'condition' => 'good',
                            'status' => 'available',
                            'notes' => 'Unit demo',
                        ],
                    );
                }
            }
        }
    }
}
