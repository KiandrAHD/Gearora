<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'brand' => fake()->company(),
            'type' => ProductType::Rental,
            'status' => ProductStatus::Active,
            'specifications' => ['Berat' => fake()->numberBetween(1, 5).' kg'],
            'requires_deposit' => false,
            'rental_price_per_day' => fake()->numberBetween(30, 150) * 1000,
            'sale_price' => null,
            'is_featured' => false,
        ];
    }

    public function sale(): static
    {
        return $this->state(fn () => [
            'type' => ProductType::Sale,
            'rental_price_per_day' => null,
            'sale_price' => fake()->numberBetween(200, 900) * 1000,
        ]);
    }

    public function both(): static
    {
        return $this->state(fn () => [
            'type' => ProductType::Both,
            'sale_price' => fake()->numberBetween(200, 900) * 1000,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft]);
    }
}
