<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesReservationStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SalesOrderService;
use App\Services\SalesStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SalesStockReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_available_quantity_subtracts_active_reservations(): void
    {
        $variant = $this->saleVariant(stock: 10);
        $this->order($variant, 4);

        $stock = $this->stock();
        $this->assertSame(4, $stock->reservedQuantity($variant->id));
        $this->assertSame(6, $stock->availableQuantity($variant->fresh()));
    }

    public function test_reserving_beyond_stock_is_rejected(): void
    {
        $variant = $this->saleVariant(stock: 2);

        $this->expectException(InvalidArgumentException::class);
        $this->order($variant, 3);
    }

    public function test_consume_reduces_stock_exactly_once_and_is_idempotent(): void
    {
        $variant = $this->saleVariant(stock: 10);
        $order = $this->order($variant, 4);

        $stock = $this->stock();
        $this->assertSame(1, $stock->consumeForOrder($order));
        $this->assertSame(6, (int) $variant->fresh()->stock_quantity);

        // Pemanggilan kedua tidak mengurangi stok lagi.
        $this->assertSame(0, $stock->consumeForOrder($order->fresh()));
        $this->assertSame(6, (int) $variant->fresh()->stock_quantity);

        $this->assertDatabaseHas('sales_stock_reservations', [
            'sales_order_id' => $order->id,
            'status' => SalesReservationStatus::Consumed->value,
        ]);
        $this->assertSame(1, StockMovement::query()->where('type', 'sale')->count());
    }

    public function test_release_restores_availability_without_consuming(): void
    {
        $variant = $this->saleVariant(stock: 5);
        $order = $this->order($variant, 2);

        $stock = $this->stock();
        $this->assertSame(1, $stock->releaseForOrder($order, SalesReservationStatus::Released));
        $this->assertSame(5, (int) $variant->fresh()->stock_quantity);
        $this->assertSame(0, $stock->reservedQuantity($variant->id));

        // Idempoten.
        $this->assertSame(0, $stock->releaseForOrder($order->fresh(), SalesReservationStatus::Released));
    }

    public function test_release_rejects_invalid_status(): void
    {
        $order = $this->order($this->saleVariant(), 1);

        $this->expectException(InvalidArgumentException::class);
        $this->stock()->releaseForOrder($order, SalesReservationStatus::Consumed);
    }

    public function test_expired_order_releases_reservations(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 5);
        $order = $this->order($variant, 2);

        app(SalesOrderService::class)->transition($order, SalesOrderStatus::Expired, $customer);

        $this->assertSame(0, $this->stock()->reservedQuantity($variant->id));
        $this->assertDatabaseHas('sales_stock_reservations', [
            'sales_order_id' => $order->id,
            'status' => SalesReservationStatus::Expired->value,
        ]);
    }

    private function order(ProductVariant $variant, int $quantity)
    {
        return app(SalesOrderService::class)->checkout($this->customer(), [
            ['variant_id' => $variant->id, 'quantity' => $quantity],
        ], PaymentMethod::Cash);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Customer);

        return $user;
    }

    private function saleVariant(int $stock = 10): ProductVariant
    {
        $product = Product::factory()->sale()->create(['sale_price' => 100000]);

        return ProductVariant::factory()->for($product)->create([
            'sale_price' => 100000,
            'stock_quantity' => $stock,
            'is_active' => true,
        ]);
    }

    private function stock(): SalesStockService
    {
        return app(SalesStockService::class);
    }
}
