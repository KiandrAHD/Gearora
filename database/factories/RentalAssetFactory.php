<?php

namespace Database\Factories;

use App\Enums\RentalAssetCondition;
use App\Enums\RentalAssetStatus;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RentalAsset>
 */
class RentalAssetFactory extends Factory
{
    protected $model = RentalAsset::class;

    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'asset_code' => 'AST-'.strtoupper(Str::random(6)),
            'condition' => RentalAssetCondition::Good,
            'status' => RentalAssetStatus::Available,
            'purchase_date' => now()->subMonths(6)->toDateString(),
            'notes' => null,
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['status' => RentalAssetStatus::Maintenance]);
    }
}
