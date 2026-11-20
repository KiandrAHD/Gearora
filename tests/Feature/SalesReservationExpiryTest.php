<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesReservationStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesPaymentAttempt;
use App\Models\SalesStockReservation;
use App\Models\User;
use App\Services\SalesOrderExpiryService;
use App\Services\SalesOrderService;
use App\Services\SalesPaymentService;
use App\Services\SalesStockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — kedaluwarsa reservasi stok retail & late settlement.
 */
class SalesReservationExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_expired_reservation_does_not_hold_stock(): void
    {
        $variant = $this->saleVariant(stock: 10);
        $order = $this->order($variant, 4);

        $this->expireReservationRows($order);

        $stock = app(SalesStockService::class);
        $this->assertSame(0, $stock->reservedQuantity($variant->id));
        $this->assertSame(10, $stock->availableQuantity($variant->fresh()));
    }

    public function test_expire_service_transitions_order_and_releases_stock_without_consuming(): void
    {
        $variant = $this->saleVariant(stock: 5);
        $order = $this->order($variant, 2);

        $this->ageOrder($order);

        $count = app(SalesOrderExpiryService::class)->expireStale();

        $this->assertSame(1, $count);
        $this->assertSame(SalesOrderStatus::Expired, $order->fresh()->order_status);
        $this->assertSame(5, (int) $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('sales_stock_reservations', [
            'sales_order_id' => $order->id,
            'status' => SalesReservationStatus::Expired->value,
        ]);
    }

    public function test_expire_service_is_idempotent(): void
    {
        $order = $this->order($this->saleVariant(stock: 3), 1);
        $this->ageOrder($order);

        $service = app(SalesOrderExpiryService::class);

        $this->assertTrue($service->expireOrder($order->id));
        $this->assertFalse($service->expireOrder($order->id));
    }

    public function test_late_settlement_after_reservation_expiry_does_not_consume_or_confirm(): void
    {
        $variant = $this->saleVariant(stock: 4);
        $customer = $this->customer();
        $order = app(SalesOrderService::class)->checkout($customer, [
            ['variant_id' => $variant->id, 'quantity' => 2],
        ], PaymentMethod::Transfer);

        // Reservasi habis waktu, tetapi job expiry belum dijalankan.
        $this->expireReservationRows($order);

        $payment = $order->payments()->firstOrFail();
        app(SalesPaymentService::class)->markSettled($payment, $customer, 'manual');

        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);
        $this->assertSame(SalesOrderStatus::PendingPayment, $order->fresh()->order_status);
        $this->assertSame(4, (int) $variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_settlement_before_expiry_consumes_stock_and_confirms(): void
    {
        $variant = $this->saleVariant(stock: 4);
        $customer = $this->customer();
        $order = app(SalesOrderService::class)->checkout($customer, [
            ['variant_id' => $variant->id, 'quantity' => 2],
        ], PaymentMethod::Cash);

        $payment = $order->payments()->firstOrFail();
        app(SalesPaymentService::class)->markSettled($payment, $customer, 'cash');

        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertSame(SalesOrderStatus::Confirmed, $order->fresh()->order_status);
        $this->assertSame(2, (int) $variant->fresh()->stock_quantity);
    }

    public function test_expire_service_skips_order_with_successful_attempt(): void
    {
        $variant = $this->saleVariant(stock: 3);
        $order = $this->order($variant, 1);
        $this->ageOrder($order);

        $payment = $order->payments()->firstOrFail();
        SalesPaymentAttempt::factory()->for($payment, 'payment')->create([
            'status' => 'settlement',
        ]);

        $this->assertFalse(app(SalesOrderExpiryService::class)->expireOrder($order->id));
    }

    private function order(ProductVariant $variant, int $quantity): SalesOrder
    {
        return app(SalesOrderService::class)->checkout($this->customer(), [
            ['variant_id' => $variant->id, 'quantity' => $quantity],
        ], PaymentMethod::Cash);
    }

    private function ageOrder(SalesOrder $order): void
    {
        $order->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->expireReservationRows($order);
    }

    private function expireReservationRows(SalesOrder $order): void
    {
        SalesStockReservation::query()
            ->where('sales_order_id', $order->getKey())
            ->update(['expires_at' => now()->subMinute()]);
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
}
