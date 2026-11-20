<?php

namespace Tests\Feature;

use App\Enums\SalesReservationStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\SalesOrder;
use App\Models\SalesStockReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman detail katalog: harga mode-aware, tombol keranjang retail, kuota
 * rental, dan fallback gambar lokal. Tidak ada hotlink gambar eksternal.
 */
class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_product_shows_booking_form_and_daily_price(): void
    {
        $variant = $this->rentalVariant('Tenda Detail', 95000);
        RentalAsset::factory()->for($variant, 'variant')->create();

        $this->get(route('catalog.show', $variant->product))
            ->assertOk()
            ->assertSee('95.000')
            ->assertSee('/ hari sewa')
            ->assertSee('Lanjutkan ke ringkasan')
            ->assertSee('name="start_date"', false);
    }

    public function test_rental_availability_is_shown_for_requested_dates(): void
    {
        $variant = $this->rentalVariant('Tenda Tanggal', 80000);
        RentalAsset::factory()->count(2)->for($variant, 'variant')->create();

        $this->get(route('catalog.show', [
            'product' => $variant->product,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('tersedia 2');
    }

    public function test_sale_product_shows_cart_button_with_server_stock(): void
    {
        $variant = $this->saleVariant('Headlamp Detail', 450000, 4);

        $this->get(route('catalog.show', $variant->product))
            ->assertOk()
            ->assertSee('Beli langsung')
            ->assertSee('+ Keranjang')
            ->assertSee('Stok tersedia: 4')
            ->assertSee('action="'.route('retail.cart.store').'"', false)
            ->assertDontSee('Pembelian online belum tersedia');
    }

    public function test_sale_panel_hides_button_when_stock_fully_reserved(): void
    {
        $variant = $this->saleVariant('Headlamp Ludes', 450000, 2);
        SalesStockReservation::factory()->create([
            'sales_order_id' => SalesOrder::factory(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
            'status' => SalesReservationStatus::Reserved,
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(60),
        ]);

        $this->get(route('catalog.show', $variant->product))
            ->assertOk()
            ->assertSee('Stok habis')
            ->assertDontSee('+ Keranjang');
    }

    public function test_retail_detail_does_not_show_rental_booking_form(): void
    {
        $variant = $this->saleVariant('Sepatu Detail', 300000, 3);

        $this->get(route('catalog.show', $variant->product))
            ->assertOk()
            ->assertDontSee('Lanjutkan ke ringkasan')
            ->assertDontSee('/ hari sewa');
    }

    public function test_missing_image_uses_local_placeholder(): void
    {
        $variant = $this->rentalVariant('Tanpa Foto Detail', 70000);

        $this->get(route('catalog.show', $variant->product))
            ->assertOk()
            ->assertSee('product-placeholder.svg')
            ->assertSee('Foto belum tersedia');
    }

    public function test_draft_product_returns_not_found(): void
    {
        $product = Product::factory()->draft()->create(['name' => 'Produk Draft']);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);

        $this->get(route('catalog.show', $product))->assertNotFound();
    }

    private function rentalVariant(string $name, int $price): ProductVariant
    {
        $product = Product::factory()->create([
            'name' => $name,
            'slug' => str()->slug($name),
            'rental_price_per_day' => $price,
        ]);

        return ProductVariant::factory()->for($product)->create(['rental_price_per_day' => $price]);
    }

    private function saleVariant(string $name, int $price, int $stock): ProductVariant
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
