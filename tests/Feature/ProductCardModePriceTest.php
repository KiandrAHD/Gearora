<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kartu produk harus menampilkan harga sesuai mode halaman. Produk tipe `both`
 * TIDAK boleh menampilkan harga sewa pada halaman Beli (dan sebaliknya).
 */
class ProductCardModePriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_product_shows_rental_price_on_rental_page(): void
    {
        $product = Product::factory()->both()->create(['name' => 'Tenda Serbaguna', 'rental_price_per_day' => 85000]);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 85000, 'sale_price' => 450000]);

        $this->get(route('catalog.index', ['mode' => 'sewa']))
            ->assertOk()
            ->assertSee('85.000')
            ->assertSee('/ hari');
    }

    public function test_both_product_shows_sale_price_on_retail_page(): void
    {
        $product = Product::factory()->both()->create(['name' => 'Tenda Serbaguna', 'rental_price_per_day' => 85000]);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 85000, 'sale_price' => 450000]);

        $this->get(route('catalog.index', ['mode' => 'beli']))
            ->assertOk()
            ->assertSee('450.000')
            ->assertSee('/ unit')
            ->assertDontSee('85.000');
    }

    public function test_card_uses_img_with_alt_and_lazy_loading(): void
    {
        $product = Product::factory()->create(['name' => 'Carrier Uji Foto']);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 60000]);
        ProductImage::factory()->create(['product_id' => $product->getKey(), 'path' => 'products/'.$product->getKey().'/x.jpg', 'is_primary' => true]);

        $html = $this->get(route('catalog.index', ['mode' => 'sewa']))->assertOk()->getContent();

        $this->assertStringContainsString('alt="Carrier Uji Foto"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function test_missing_image_shows_local_placeholder_not_remote_hotlink(): void
    {
        $product = Product::factory()->create(['name' => 'Produk Tanpa Foto']);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 60000]);

        $html = $this->get(route('catalog.index', ['mode' => 'sewa']))->assertOk()->getContent();

        $this->assertStringContainsString('product-placeholder.svg', $html);
        $this->assertStringContainsString('Foto belum tersedia', $html);
    }
}
