<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter & sorting katalog mode SEWA. Harga memakai harga rental per hari dan
 * ketersediaan memakai RentalAvailabilityService (bukan stok fisik).
 */
class RentalCatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_mode_shows_only_rental_capable_products(): void
    {
        $rental = $this->rentalProduct('Tenda Rental', 85000);
        $both = Product::factory()->both()->create(['name' => 'Carrier Both']);
        ProductVariant::factory()->for($both)->create(['rental_price_per_day' => 60000, 'sale_price' => 400000]);
        $saleOnly = Product::factory()->sale()->create(['name' => 'Kompor Jual']);
        ProductVariant::factory()->for($saleOnly)->create(['sale_price' => 300000]);

        $response = $this->get(route('catalog.index', ['mode' => 'sewa']));

        $response->assertOk()
            ->assertSee('Tenda Rental')
            ->assertSee('Carrier Both')
            ->assertDontSee('Kompor Jual');
    }

    public function test_price_filter_uses_rental_price_per_day(): void
    {
        $cheap = $this->rentalProduct('Sleeping Bag Murah', 40000);
        $expensive = $this->rentalProduct('Tenda Mahal', 250000);

        $response = $this->get(route('catalog.index', [
            'mode' => 'sewa',
            'min_price' => 100000,
        ]));

        $response->assertOk()
            ->assertSee('Tenda Mahal')
            ->assertDontSee('Sleeping Bag Murah');

        $this->assertTrue($cheap->exists);
    }

    public function test_keyword_searches_name_brand_and_description(): void
    {
        $product = $this->rentalProduct('Tenda Dome Andalan', 90000);
        $other = $this->rentalProduct('Matras Tipis', 25000);
        $product->product->update(['brand' => 'Arunika', 'description' => 'Tahan angin kencang']);
        $product->product->refresh();
        $this->assertSame('Arunika', $product->product->brand);
        $this->assertSame('Tahan angin kencang', $product->product->description);

        $this->get(route('catalog.index', ['mode' => 'sewa', 'q' => 'Dome']))
            ->assertOk()->assertSee('Tenda Dome Andalan')->assertDontSee('Matras Tipis');

        $this->get(route('catalog.index', ['mode' => 'sewa', 'q' => 'Arunika']))
            ->assertOk()->assertSee('Tenda Dome Andalan');

        $this->get(route('catalog.index', ['mode' => 'sewa', 'q' => 'angin']))
            ->assertOk()->assertSee('Tenda Dome Andalan');

        $this->assertTrue($other->exists);
    }

    public function test_category_filter(): void
    {
        $tenda = Category::factory()->create(['name' => 'Tenda', 'slug' => 'tenda']);
        $carrier = Category::factory()->create(['name' => 'Carrier', 'slug' => 'carrier']);

        $this->rentalProduct('Tenda Besar', 120000, $tenda);
        $this->rentalProduct('Carrier 60L', 70000, $carrier);

        $this->get(route('catalog.index', ['mode' => 'sewa', 'kategori' => 'tenda']))
            ->assertOk()->assertSee('Tenda Besar')->assertDontSee('Carrier 60L');
    }

    public function test_sort_by_lowest_rental_price(): void
    {
        $this->rentalProduct('Tenda Termahal', 300000);
        $this->rentalProduct('Tenda Termurah', 20000);

        $html = $this->get(route('catalog.index', ['mode' => 'sewa', 'sort' => 'harga_terendah']))
            ->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'Tenda Termahal'),
            strpos($html, 'Tenda Termurah'),
        );
    }

    public function test_date_filter_hides_product_without_rentable_asset(): void
    {
        $variant = $this->rentalProduct('Tenda Tanpa Unit', 90000);

        $response = $this->get(route('catalog.index', [
            'mode' => 'sewa',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]));

        $response->assertOk()
            ->assertDontSee('Tenda Tanpa Unit')
            ->assertSee('Tidak ada unit rental');

        $this->assertNotNull($variant);
    }

    public function test_date_filter_shows_product_with_rentable_asset(): void
    {
        $variant = $this->rentalProduct('Tenda Ada Unit', 90000);
        RentalAsset::factory()->for($variant, 'variant')->create();

        $response = $this->get(route('catalog.index', [
            'mode' => 'sewa',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]));

        $response->assertOk()->assertSee('Tenda Ada Unit');
    }

    public function test_invalid_mode_falls_back_to_rental(): void
    {
        $this->rentalProduct('Tenda Mode Default', 90000);

        $this->get(route('catalog.index', ['mode' => 'hacked']))
            ->assertOk()
            ->assertSee('Tenda Mode Default')
            ->assertSee('KATALOG SEWA');
    }

    public function test_product_without_active_rental_variant_is_hidden(): void
    {
        $product = Product::factory()->create(['name' => 'Tenda Nonaktif', 'type' => ProductType::Rental]);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 90000, 'is_active' => false]);

        $this->get(route('catalog.index', ['mode' => 'sewa']))
            ->assertOk()
            ->assertDontSee('Tenda Nonaktif');
    }

    public function test_draft_product_is_hidden(): void
    {
        $product = Product::factory()->draft()->create(['name' => 'Tenda Draft']);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 90000]);

        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->get(route('catalog.index', ['mode' => 'sewa']))->assertOk()->assertDontSee('Tenda Draft');
    }

    private function rentalProduct(string $name, int $price, ?Category $category = null): ProductVariant
    {
        $product = Product::factory()->create([
            'name' => $name,
            'slug' => str()->slug($name),
            'type' => ProductType::Rental,
            'category_id' => $category?->getKey() ?? Category::factory(),
            'rental_price_per_day' => $price,
        ]);

        return ProductVariant::factory()->for($product)->create(['rental_price_per_day' => $price]);
    }
}
