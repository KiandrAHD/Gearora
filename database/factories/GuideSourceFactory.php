<?php

namespace Database\Factories;

use App\Models\EquipmentGuide;
use App\Models\GuideSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuideSource>
 */
class GuideSourceFactory extends Factory
{
    protected $model = GuideSource::class;

    public function definition(): array
    {
        return [
            'equipment_guide_id' => EquipmentGuide::factory(),
            'source_title' => fake()->sentence(4),
            'publisher' => fake()->company(),
            'source_url' => fake()->url(),
            'accessed_at' => now()->toDateString(),
            'notes' => fake()->sentence(),
        ];
    }
}
