<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SalesCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetailCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_uses_dedicated_session_key(): void
    {
        session([SalesCartService::SESSION_KEY => [1 => 2]]);

        $cart = app(SalesCartService::class);

        $this->assertSame([1 => 2], $cart->raw());
        $this->assertSame(2, $cart->count());
        $this->assertNull(session('rental_booking_draft'));
    }

    public function test_add_to_cart_stores_variant_and_quantity(): void
    {
        $variant = $this->saleVariant(stock: 5);

        $this->post(route('retail.cart.store'), [
            '_token' => csrf_token(),
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('retail.cart.index'));

        $this->assertSame([$variant->id => 2], session(SalesCartService::SESSION_KEY));
    }

    public function test_server_recomputes_price_not_client(): void
    {
        $variant = $this->saleVariant(stock: 5, price: 120000);

        session([SalesCartService::SESSION_KEY => [$variant->id => 3]]);

        $cart = app(SalesCartService::class);

        $this->assertSame(360000, $cart->subtotal());
        $this->assertSame(120000, $cart->lines()->first()['unit_price']);
    }

    public function test_adding_rental_only_product_is_rejected(): void
    {
        $product = Product::factory()->create(['type' => ProductType::Rental, 'sale_price' => null]);
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);

        $this->post(route('retail.cart.store'), [
            '_token' => csrf_token(),
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertSessionHas('error');

        $this->assertEmpty(session(SalesCartService::SESSION_KEY, []));
    }

    public function test_quantity_cannot_exceed_available_stock(): void
    {
        $variant = $this->saleVariant(stock: 2);

        $this->post(route('retail.cart.store'), [
            '_token' => csrf_token(),
            'variant_id' => $variant->id,
            'quantity' => 5,
        ])->assertSessionHas('error');

        $this->assertEmpty(session(SalesCartService::SESSION_KEY, []));
    }

    public function test_update_and_remove_and_clear(): void
    {
        $variant = $this->saleVariant(stock: 5);
        $cart = app(SalesCartService::class);

        $cart->add($variant, 2);
        $cart->update($variant, 4);
        $this->assertSame([$variant->id => 4], $cart->raw());

        $cart->remove($variant->id);
        $this->assertTrue($cart->isEmpty());

        $cart->add($variant, 1);
        $cart->clear();
        $this->assertTrue($cart->isEmpty());
    }

    public function test_unavailable_variant_filtered_from_lines(): void
    {
        $variant = $this->saleVariant(stock: 5);
        session([SalesCartService::SESSION_KEY => [$variant->id => 2]]);

        $variant->product->update(['status' => ProductStatus::Draft]);

        $this->assertTrue(app(SalesCartService::class)->lines()->isEmpty());
    }

    private function saleVariant(int $stock, int $price = 100000): ProductVariant
    {
        $product = Product::factory()->sale()->create(['sale_price' => $price]);

        return ProductVariant::factory()->for($product)->create([
            'sale_price' => $price,
            'stock_quantity' => $stock,
            'is_active' => true,
        ]);
    }
}
