<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Standar',
            'sku' => 'SKU-'.strtoupper(Str::random(8)),
            'attributes' => null,
            'rental_price_per_day' => null,
            'sale_price' => null,
            'stock_quantity' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
