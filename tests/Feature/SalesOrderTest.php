<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesReservationStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\SalesOrderService;
use App\Services\SalesPaymentService;
use App\Services\SalesStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_checkout_creates_order_items_payment_and_reservation(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 10, price: 120000);

        $order = $this->orders()->checkout($customer, [['variant_id' => $variant->id, 'quantity' => 2]], PaymentMethod::Transfer);

        $this->assertSame(SalesOrderStatus::PendingPayment, $order->order_status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame(240000, $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertDatabaseHas('sales_payments', ['sales_order_id' => $order->id, 'method' => 'transfer', 'status' => 'unpaid']);
        $this->assertDatabaseHas('sales_stock_reservations', [
            'sales_order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'status' => SalesReservationStatus::Reserved->value,
        ]);
    }

    public function test_stock_quantity_is_not_reduced_before_payment(): void
    {
        $variant = $this->saleVariant(stock: 10);

        $this->orders()->checkout($this->customer(), [['variant_id' => $variant->id, 'quantity' => 4]]);

        $this->assertSame(10, (int) $variant->fresh()->stock_quantity);
        $this->assertSame(6, $this->stock()->availableQuantity($variant->fresh()));
    }

    public function test_overselling_is_prevented_within_transaction(): void
    {
        $variant = $this->saleVariant(stock: 3);

        $this->orders()->checkout($this->customer(), [['variant_id' => $variant->id, 'quantity' => 3]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->orders()->checkout($this->customer(), [['variant_id' => $variant->id, 'quantity' => 1]]);
    }

    public function test_rental_only_product_cannot_be_sold(): void
    {
        $product = Product::factory()->create(['type' => ProductType::Rental, 'sale_price' => null]);
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);

        $this->expectException(RuntimeException::class);
        $this->orders()->checkout($this->customer(), [['variant_id' => $variant->id, 'quantity' => 1]]);
    }

    public function test_confirm_requires_verified_payment(): void
    {
        $order = $this->orders()->checkout($this->customer(), [['variant_id' => $this->saleVariant()->id, 'quantity' => 1]]);

        $this->expectException(InvalidOrderTransitionException::class);
        $this->orders()->transition($order, SalesOrderStatus::Confirmed);
    }

    public function test_full_forward_transition_after_payment(): void
    {
        $customer = $this->customer();
        $order = $this->orders()->checkout($customer, [['variant_id' => $this->saleVariant()->id, 'quantity' => 1]]);

        $payment = $order->payments()->firstOrFail();
        app(SalesPaymentService::class)->markSettled($payment, $customer, 'manual');

        $order->refresh();
        $this->assertSame(SalesOrderStatus::Confirmed, $order->order_status);
        $this->assertSame(PaymentStatus::Verified, $order->payment_status);

        $this->orders()->transition($order, SalesOrderStatus::ReadyForPickup, $customer);
        $this->orders()->transition($order->refresh(), SalesOrderStatus::Completed, $customer);

        $this->assertSame(SalesOrderStatus::Completed, $order->refresh()->order_status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_cancellation_releases_reservations_without_touching_stock(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 5);
        $order = $this->orders()->checkout($customer, [['variant_id' => $variant->id, 'quantity' => 2]]);

        $this->orders()->cancel($order, $customer, 'Batal.');

        $this->assertSame(SalesOrderStatus::Cancelled, $order->refresh()->order_status);
        $this->assertSame(5, (int) $variant->fresh()->stock_quantity);
        $this->assertSame(0, $this->stock()->reservedQuantity($variant->id));
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $order = $this->orders()->checkout($this->customer(), [['variant_id' => $this->saleVariant()->id, 'quantity' => 1]]);

        $this->expectException(InvalidOrderTransitionException::class);
        $this->orders()->transition($order, SalesOrderStatus::Completed);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Customer);

        return $user;
    }

    private function saleVariant(int $stock = 10, int $price = 100000): ProductVariant
    {
        $product = Product::factory()->sale()->create(['sale_price' => $price]);

        return ProductVariant::factory()->for($product)->create([
            'sale_price' => $price,
            'stock_quantity' => $stock,
            'is_active' => true,
        ]);
    }

    private function orders(): SalesOrderService
    {
        return app(SalesOrderService::class);
    }

    private function stock(): SalesStockService
    {
        return app(SalesStockService::class);
    }
}
