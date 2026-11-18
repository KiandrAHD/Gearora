<?php

namespace Database\Factories;

use App\Enums\SalesReservationStatus;
use App\Models\ProductVariant;
use App\Models\SalesOrderItem;
use App\Models\SalesStockReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesStockReservation>
 */
class SalesStockReservationFactory extends Factory
{
    protected $model = SalesStockReservation::class;

    public function definition(): array
    {
        return [
            'sales_order_id' => null,
            'sales_order_item_id' => SalesOrderItem::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'quantity' => 1,
            'status' => SalesReservationStatus::Reserved,
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(120),
        ];
    }
}
