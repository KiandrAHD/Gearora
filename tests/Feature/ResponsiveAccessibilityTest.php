<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filter_drawer_exposes_accessible_toggle_state(): void
    {
        $this->get(route('catalog.index', ['mode' => 'sewa']))
            ->assertOk()
            ->assertSee('aria-controls="catalog-filters"', false)
            ->assertSee(':aria-expanded="filtersOpen.toString()"', false)
            ->assertSee('aria-label="Tutup filter"', false)
            ->assertSee('aria-label="Filter katalog"', false);
    }

    public function test_payment_picker_is_a_labelled_radio_group(): void
    {
        $product = Product::factory()->create(['name' => 'Tenda Aksesibel', 'rental_price_per_day' => 75000]);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 75000]);

        $this->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('<fieldset class="payment-methods">', false)
            ->assertSee('<legend class="payment-methods-legend">Metode pembayaran</legend>', false)
            ->assertSee('type="radio" name="method"', false);
    }
}
