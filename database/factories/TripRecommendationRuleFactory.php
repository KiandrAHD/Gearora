<?php

namespace Database\Factories;

use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Models\TripRecommendationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripRecommendationRule>
 */
class TripRecommendationRuleFactory extends Factory
{
    protected $model = TripRecommendationRule::class;

    public function definition(): array
    {
        return [
            'name' => 'Aturan '.fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'activity' => TripActivity::Camping->value,
            'min_duration_days' => 1,
            'max_duration_days' => null,
            'min_participants' => 1,
            'max_participants' => null,
            'required_special_needs' => null,
            'product_category_id' => null,
            'product_id' => null,
            'product_variant_id' => null,
            'service_mode' => TripServiceMode::Semua,
            'recommended_quantity' => 1,
            'quantity_per_participant' => false,
            'priority' => 100,
            'reason_template' => null,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forActivity(TripActivity|string $activity): static
    {
        return $this->state(fn () => [
            'activity' => $activity instanceof TripActivity ? $activity->value : $activity,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
