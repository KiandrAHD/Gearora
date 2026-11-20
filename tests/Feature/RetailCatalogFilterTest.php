<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\SalesReservationStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesStockReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter katalog mode BELI. Harga memakai harga jual per unit; ketersediaan
 * memakai stok fisik dikurangi reservasi aktif (SalesStockService).
 */
class RetailCatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_retail_mode_shows_sale_capable_products_only(): void
    {
        $this->saleProduct('Kompor Portable', 300000, 4);
        Product::factory()->create(['name' => 'Tenda Sewa Saja', 'type' => ProductType::Rental])
            ->variants()->create(['name' => 'Std', 'sku' => 'TND-1', 'rental_price_per_day' => 80000, 'is_active' => true]);

        $this->get(route('catalog.index', ['mode' => 'beli']))
            ->assertOk()
            ->assertSee('Kompor Portable')
            ->assertDontSee('Tenda Sewa Saja')
            ->assertSee('KATALOG BELI');
    }

    public function test_price_filter_uses_sale_price(): void
    {
        $this->saleProduct('Lampu Murah', 50000, 5);
        $this->saleProduct('Headlamp Premium', 750000, 5);

        $this->get(route('catalog.index', ['mode' => 'beli', 'min_price' => 500000]))
            ->assertOk()
            ->assertSee('Headlamp Premium')
            ->assertDontSee('Lampu Murah');
    }

    public function test_stock_filter_excludes_fully_reserved_variant(): void
    {
        $available = $this->saleProduct('Matras Ready', 90000, 3);

        $reserved = $this->saleProduct('Matras Ludes', 90000, 3);
        SalesStockReservation::factory()->create([
            'sales_order_id' => SalesOrder::factory(),
            'product_variant_id' => $reserved->getKey(),
            'quantity' => 3,
            'status' => SalesReservationStatus::Reserved,
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(60),
        ]);

        $this->get(route('catalog.index', ['mode' => 'beli', 'stock' => 'tersedia']))
            ->assertOk()
            ->assertSee('Matras Ready')
            ->assertDontSee('Matras Ludes');

        $this->assertSame(3, $available->stock_quantity);
    }

    public function test_expired_reservation_does_not_block_stock(): void
    {
        $variant = $this->saleProduct('Matras Kadaluarsa', 90000, 3);
        SalesStockReservation::factory()->create([
            'sales_order_id' => SalesOrder::factory(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 3,
            'status' => SalesReservationStatus::Reserved,
            'reserved_at' => now()->subHours(4),
            'expires_at' => now()->subHours(1),
        ]);

        $this->get(route('catalog.index', ['mode' => 'beli', 'stock' => 'tersedia']))
            ->assertOk()
            ->assertSee('Matras Kadaluarsa');
    }

    public function test_retail_page_has_no_rental_date_fields(): void
    {
        $this->saleProduct('Gear Retail', 120000, 2);

        $this->get(route('catalog.index', ['mode' => 'beli']))
            ->assertOk()
            ->assertDontSee('name="start_date"', false)
            ->assertDontSee('name="end_date"', false)
            ->assertSee('name="stock"', false);
    }

    public function test_sort_by_lowest_sale_price(): void
    {
        $this->saleProduct('Produk Mahal', 900000, 2);
        $this->saleProduct('Produk Murah', 15000, 2);

        $html = $this->get(route('catalog.index', ['mode' => 'beli', 'sort' => 'harga_terendah']))
            ->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Produk Mahal'), strpos($html, 'Produk Murah'));
    }

    public function test_price_unit_label_shown(): void
    {
        $this->saleProduct('Sepatu Trail', 425000, 6);

        $this->get(route('catalog.index', ['mode' => 'beli']))
            ->assertOk()
            ->assertSee('425.000')
            ->assertSee('/ unit');
    }

    private function saleProduct(string $name, int $price, int $stock): ProductVariant
    {
        $product = Product::factory()->sale()->create([
            'name' => $name,
            'slug' => str()->slug($name),
            'sale_price' => $price,
        ]);

        return ProductVariant::factory()->for($product)->create([
            'sale_price' => $price,
            'stock_quantity' => $stock,
            'is_active' => true,
        ]);
    }
}
