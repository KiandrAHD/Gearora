<?php

namespace Tests\Feature;

use App\Data\TripPlanData;
use App\Enums\ProductStatus;
use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Enums\TripSpecialNeed;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TripRecommendationRule;
use App\Services\TripRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripRecommendationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_matches_selected_activity_only(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->forActivity(TripActivity::Camping)->create(['product_id' => $product->id]);
        TripRecommendationRule::factory()->forActivity(TripActivity::Trekking)->create(['product_id' => $product->id]);

        $results = $this->service()->recommend($this->plan(['activity' => TripActivity::Camping->value]));

        $this->assertCount(1, $results);
        $this->assertSame(TripActivity::Camping->value, $results[0]['rule']->activity);
    }

    public function test_any_activity_rule_applies_to_all(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->forActivity(TripActivity::ANY)->create(['product_id' => $product->id]);

        $results = $this->service()->recommend($this->plan(['activity' => TripActivity::Overlanding->value]));

        $this->assertCount(1, $results);
    }

    public function test_duration_outside_range_excluded(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->create([
            'product_id' => $product->id,
            'activity' => TripActivity::ANY,
            'min_duration_days' => 3,
            'max_duration_days' => 5,
        ]);

        $this->assertCount(0, $this->service()->recommend($this->plan(['duration_days' => 2])));
        $this->assertCount(1, $this->service()->recommend($this->plan(['duration_days' => 4])));
        $this->assertCount(0, $this->service()->recommend($this->plan(['duration_days' => 6])));
    }

    public function test_participant_range_respected(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->create([
            'product_id' => $product->id,
            'activity' => TripActivity::ANY,
            'min_participants' => 2,
            'max_participants' => 4,
        ]);

        $this->assertCount(0, $this->service()->recommend($this->plan(['participants' => 1])));
        $this->assertCount(1, $this->service()->recommend($this->plan(['participants' => 3])));
        $this->assertCount(0, $this->service()->recommend($this->plan(['participants' => 5])));
    }

    public function test_required_special_needs_enforced(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->create([
            'product_id' => $product->id,
            'activity' => TripActivity::ANY,
            'required_special_needs' => [TripSpecialNeed::RainProtection->value],
        ]);

        $without = $this->service()->recommend($this->plan(['special_needs' => []]));
        $with = $this->service()->recommend($this->plan(['special_needs' => [TripSpecialNeed::RainProtection->value]]));

        $this->assertCount(0, $without);
        $this->assertCount(1, $with);
    }

    public function test_priority_orders_results_and_deduplicates_variant(): void
    {
        $category = Category::factory()->create();
        $high = Product::factory()->sale()->for($category)->create();
        $low = Product::factory()->sale()->for($category)->create();

        $highVariant = ProductVariant::factory()->for($high)->create(['sale_price' => 100000, 'stock_quantity' => 5]);
        $lowVariant = ProductVariant::factory()->for($low)->create(['sale_price' => 100000, 'stock_quantity' => 5]);

        // Rule kategori prioritas tinggi akan menangkap varian high lebih dulu.
        TripRecommendationRule::factory()->create([
            'product_category_id' => $category->id,
            'activity' => TripActivity::ANY,
            'priority' => 500,
            'service_mode' => TripServiceMode::Beli,
        ]);
        TripRecommendationRule::factory()->create([
            'product_id' => $low->id,
            'activity' => TripActivity::ANY,
            'priority' => 10,
            'service_mode' => TripServiceMode::Beli,
        ]);

        $results = $this->service()->recommend($this->plan(['mode' => 'beli']));

        // Varian high muncul lebih dulu (menang dedup) karena rule prioritas tinggi.
        $this->assertNotEmpty($results);
        $this->assertSame($highVariant->id, $results[0]['variant']->id);
        $this->assertSame($lowVariant->id, $results[1]['variant']->id);
    }

    public function test_inactive_rule_ignored(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->forActivity(TripActivity::ANY)->inactive()->create(['product_id' => $product->id]);

        $this->assertCount(0, $this->service()->recommend($this->plan()));
    }

    public function test_sale_mode_excludes_rental_only_product(): void
    {
        $rentalOnly = Product::factory()->create(['sale_price' => null]);
        ProductVariant::factory()->for($rentalOnly)->create(['rental_price_per_day' => 50000]);

        TripRecommendationRule::factory()->forActivity(TripActivity::ANY)->create([
            'product_id' => $rentalOnly->id,
            'service_mode' => TripServiceMode::Beli,
        ]);

        $this->assertCount(0, $this->service()->recommend($this->plan(['mode' => 'beli'])));
    }

    public function test_inactive_product_excluded(): void
    {
        $product = Product::factory()->sale()->create(['status' => ProductStatus::Draft]);
        ProductVariant::factory()->for($product)->create(['sale_price' => 90000, 'stock_quantity' => 3]);

        TripRecommendationRule::factory()->forActivity(TripActivity::ANY)->create([
            'product_id' => $product->id,
            'service_mode' => TripServiceMode::Beli,
        ]);

        $this->assertCount(0, $this->service()->recommend($this->plan(['mode' => 'beli'])));
    }

    public function test_quantity_per_participant_multiplies(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->forActivity(TripActivity::ANY)->create([
            'product_id' => $product->id,
            'recommended_quantity' => 2,
            'quantity_per_participant' => true,
            'service_mode' => TripServiceMode::Beli,
        ]);

        $results = $this->service()->recommend($this->plan(['participants' => 3, 'mode' => 'beli']));

        $this->assertSame(6, $results[0]['quantity']);
    }

    public function test_reason_template_placeholders_substituted(): void
    {
        $product = $this->saleProduct();
        TripRecommendationRule::factory()->forActivity(TripActivity::ANY)->create([
            'product_id' => $product->id,
            'service_mode' => TripServiceMode::Beli,
            'reason_template' => 'Cocok untuk :destination dengan :participants peserta.',
        ]);

        $results = $this->service()->recommend($this->plan(['mode' => 'beli', 'destination' => 'Bromo', 'participants' => 4]));

        $this->assertSame('Cocok untuk Bromo dengan 4 peserta.', $results[0]['reason']);
    }

    private function service(): TripRecommendationService
    {
        return app(TripRecommendationService::class);
    }

    /** @param array<string, mixed> $overrides */
    private function plan(array $overrides = []): TripPlanData
    {
        return TripPlanData::fromArray(array_merge([
            'activity' => TripActivity::Camping->value,
            'destination' => 'Bromo',
            'duration_days' => 3,
            'participants' => 2,
            'special_needs' => [],
            'mode' => 'sewa',
        ], $overrides));
    }

    private function saleProduct(): Product
    {
        $product = Product::factory()->both()->create();
        ProductVariant::factory()->for($product)->create(['sale_price' => 150000, 'stock_quantity' => 10]);

        return $product;
    }
}
