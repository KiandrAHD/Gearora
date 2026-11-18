<?php

namespace Database\Factories;

use App\Enums\GuideStatus;
use App\Models\EquipmentGuide;
use App\Models\GuideCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EquipmentGuide>
 */
class EquipmentGuideFactory extends Factory
{
    protected $model = EquipmentGuide::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'guide_category_id' => GuideCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'excerpt' => fake()->sentence(),
            'content' => "# Panduan\n\n".fake()->paragraph(),
            'content_format' => 'markdown',
            'cover_image_path' => null,
            'status' => GuideStatus::Draft,
            'author_id' => User::factory(),
            'publisher_id' => null,
            'published_at' => null,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => GuideStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => GuideStatus::Archived]);
    }
}
