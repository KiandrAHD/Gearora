<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripActivity;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TripRecommendationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wizard 5 langkah Trip Planner. Level pengalaman memfilter aturan
 * `required_experience_levels` (data-driven, bukan if-else di Blade).
 */
class TripPlannerWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_shows_five_step_wizard_and_experience_options(): void
    {
        $this->get('/rekomendasi-trip')
            ->assertOk()
            ->assertSee('Bingung harus bawa apa?')
            ->assertSee('Langkah 1 · Aktivitas')
            ->assertSee('Langkah 2 · Durasi')
            ->assertSee('Langkah 3 · Mode')
            ->assertSee('Langkah 4 · Ringkasan')
            ->assertSee('name="experience"', false)
            ->assertSee('Catatan keselamatan:');
    }

    public function test_post_with_experience_returns_results(): void
    {
        $this->seedRule();

        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
            'experience' => ExperienceLevel::Pemula->value,
        ])
            ->assertOk()
            ->assertSee('Langkah 5 · Pilih gear')
            ->assertSee('Lanjut ke pratinjau paket');
    }

    public function test_experience_filter_hides_rule_requiring_other_level(): void
    {
        $rule = $this->seedRule();
        $rule->update(['required_experience_levels' => [ExperienceLevel::Mahir->value]]);

        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
            'experience' => ExperienceLevel::Pemula->value,
        ])
            ->assertOk()
            ->assertSee('Belum ada aturan cocok');
    }

    public function test_rule_with_empty_experience_levels_matches_any_level(): void
    {
        $rule = $this->seedRule();
        $rule->update(['required_experience_levels' => null]);

        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
            'experience' => ExperienceLevel::Mahir->value,
        ])
            ->assertOk()
            ->assertSee('Langkah 5 · Pilih gear');
    }

    public function test_experience_defaults_to_pemula_when_missing(): void
    {
        $this->seedRule();

        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
        ])
            ->assertOk()
            ->assertSee('Langkah 5 · Pilih gear');
    }

    public function test_invalid_experience_is_rejected(): void
    {
        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
            'experience' => 'hacked',
        ])
            ->assertSessionHasErrors('experience');
    }

    private function seedRule(): TripRecommendationRule
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000, 'stock_quantity' => 10]);

        return TripRecommendationRule::factory()->create([
            'activity' => TripActivity::Hiking->value,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'priority' => 100,
        ]);
    }
}
