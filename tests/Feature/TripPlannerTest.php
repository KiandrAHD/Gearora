<?php

namespace Tests\Feature;

use App\Enums\TripActivity;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TripRecommendationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_shows_form_and_safety_disclaimer(): void
    {
        $response = $this->get('/rekomendasi-trip');

        $response->assertOk()
            ->assertSee('Ke mana langkahmu')
            ->assertSee('Gunung Prau')
            ->assertSee('Catatan keselamatan:')
            ->assertSee('kondisi jalur');
    }

    public function test_post_with_valid_data_returns_results(): void
    {
        $this->seedDemoTrip();

        $response = $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Camping->value,
            'destination' => 'Bromo',
            'duration_days' => 3,
            'participants' => 2,
            'experience' => 'pemula',
            'mode' => 'sewa',
        ]);

        $response->assertOk()
            ->assertSee('gear direkomendasikan')
            ->assertSee('Gunung Prau');
    }

    public function test_post_invalid_activity_returns_validation(): void
    {
        $response = $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => 'invalid_activity',
            'destination' => 'Bromo',
            'duration_days' => 3,
            'participants' => 2,
            'experience' => 'pemula',
            'mode' => 'sewa',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors('activity');
    }

    public function test_post_missing_fields_returns_validation(): void
    {
        $response = $this->post('/rekomendasi-trip', ['_token' => csrf_token()]);

        $response->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_results_preserve_input_on_revalidation(): void
    {
        $response = $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Merbabu',
            'duration_days' => 5,
            'participants' => 4,
            'mode' => 'sewa',
        ]);

        $response->assertOk()
            ->assertSee('Gunung Prau')
            ->assertSee('value="hiking" selected', false);
    }

    public function test_safety_disclaimer_always_visible(): void
    {
        $response = $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Camping->value,
            'destination' => 'Rinca',
            'duration_days' => 7,
            'participants' => 10,
            'mode' => 'sewa',
        ]);

        $response->assertOk()
            ->assertSee('Catatan keselamatan');
    }

    private function seedDemoTrip(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000, 'stock_quantity' => 10]);
        TripRecommendationRule::factory()->create([
            'activity' => TripActivity::Camping->value,
            'product_category_id' => $category->id,
            'priority' => 100,
        ]);
    }
}
