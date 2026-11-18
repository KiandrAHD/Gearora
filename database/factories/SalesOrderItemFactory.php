<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrderItem>
 */
class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(50, 500) * 1000;
        $quantity = fake()->numberBetween(1, 3);

        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => null,
            'product_variant_id' => ProductVariant::factory(),
            'product_name_snapshot' => fake()->words(2, true),
            'variant_name_snapshot' => 'Standar',
            'sku_snapshot' => 'SKU-'.fake()->unique()->numerify('######'),
            'unit_price_snapshot' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $unitPrice * $quantity,
        ];
    }
}
