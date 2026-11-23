<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TripRecommendationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            ->assertSee('Ke mana langkahmu')
            ->assertSee('LANGKAH 01 / 04')
            ->assertSee('Ceritakan rencana perjalananmu.')
            ->assertSee('Siapa yang ikut bertualang?')
            ->assertSee('Apa yang dibutuhkan selama perjalanan?')
            ->assertSee('Periksa kembali rencana perjalananmu.')
            ->assertSee('name="departure_time"', false)
            ->assertSee('name="experience"', false)
            ->assertSee('name="mode"', false)
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
            ->assertSee('LANGKAH 05 / 05')
            ->assertSee('Pilih perlengkapan petualanganmu.')
            ->assertSee('Pratinjau paket');
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
            ->assertSee('Belum ada aturan aktif yang cocok');
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
            ->assertSee('LANGKAH 05 / 05');
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
            ->assertSee('LANGKAH 05 / 05');
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

    public function test_departure_time_must_use_hh_mm_format(): void
    {
        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
            'experience' => ExperienceLevel::Pemula->value,
            'departure_time' => 'five-am',
        ])->assertSessionHasErrors('departure_time');

        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'sewa',
            'experience' => ExperienceLevel::Pemula->value,
            'departure_time' => '04:30',
        ])->assertSessionDoesntHaveErrors('departure_time');
    }

    public function test_return_date_is_derived_from_start_date_and_duration(): void
    {
        $this->seedRule();

        $start = now()->addDays(6)->toDateString();
        $expected = Carbon::parse($start)->addDays(3)->toDateString();

        // Tanggal kembali tampil pada halaman pratinjau paket (server-side).
        $this->post(route('trip-planner.package.store'), [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Bromo',
            'location' => 'Dieng',
            'duration_days' => 3,
            'participants' => 2,
            'experience' => ExperienceLevel::Pemula->value,
            'mode' => 'sewa',
            'start_date' => $start,
            'end_date' => now()->addDays(90)->toDateString(),
            'special_needs' => [],
            'items' => [
                ['variant_id' => Product::query()->firstOrFail()->variants()->firstOrFail()->getKey(), 'quantity' => 1, 'fulfillment' => 'sewa'],
            ],
        ])->assertRedirect(route('trip-planner.package.review'));

        $this->get(route('trip-planner.package.review'))
            ->assertOk()
            ->assertSee($start.' → '.$expected);
    }

    public function test_fleksibel_mode_recommends_both_rental_and_sale_rules(): void
    {
        $rentalCategory = Category::factory()->create();
        $rentalProduct = Product::factory()->for($rentalCategory)->create();
        ProductVariant::factory()->for($rentalProduct)->create(['rental_price_per_day' => 50000, 'stock_quantity' => 10]);
        TripRecommendationRule::factory()->create([
            'activity' => TripActivity::ANY,
            'product_id' => $rentalProduct->id,
            'service_mode' => TripServiceMode::Sewa,
            'priority' => 200,
        ]);

        $saleCategory = Category::factory()->create();
        $saleProduct = Product::factory()->sale()->for($saleCategory)->create();
        ProductVariant::factory()->for($saleProduct)->create(['sale_price' => 120000, 'stock_quantity' => 5]);
        TripRecommendationRule::factory()->create([
            'activity' => TripActivity::ANY,
            'product_id' => $saleProduct->id,
            'service_mode' => TripServiceMode::Beli,
            'priority' => 200,
        ]);

        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'fleksibel',
            'experience' => ExperienceLevel::Pemula->value,
        ])
            ->assertOk()
            ->assertSee($rentalProduct->name)
            ->assertSee($saleProduct->name);
    }

    public function test_unknown_mode_is_rejected(): void
    {
        $this->post('/rekomendasi-trip', [
            '_token' => csrf_token(),
            'activity' => TripActivity::Hiking->value,
            'destination' => 'Gunung Prau',
            'duration_days' => 2,
            'participants' => 2,
            'mode' => 'gratis',
            'experience' => ExperienceLevel::Pemula->value,
        ])->assertSessionHasErrors('mode');
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
