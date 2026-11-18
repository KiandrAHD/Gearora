<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\SalesOrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSalesOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_sees_sales_orders_and_can_filter_by_status(): void
    {
        $owner = $this->staff(RoleName::Owner);
        $pending = $this->order();
        $confirmed = $this->order();
        $confirmed->forceFill([
            'order_status' => SalesOrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Verified,
            'confirmed_at' => now(),
        ])->save();

        $this->actingAs($owner)->get(route('admin.sales.index'))
            ->assertOk()
            ->assertSee($pending->order_code)
            ->assertSee($confirmed->order_code);

        $this->actingAs($owner)->get(route('admin.sales.index', ['status' => SalesOrderStatus::Confirmed->value]))
            ->assertOk()
            ->assertSee($confirmed->order_code)
            ->assertDontSee($pending->order_code);
    }

    public function test_owner_can_open_sales_order_detail(): void
    {
        $owner = $this->staff(RoleName::Owner);
        $order = $this->order();

        $this->actingAs($owner)->get(route('admin.sales.show', $order))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee($order->customer->name);
    }

    public function test_recording_cash_payment_confirms_order_and_reduces_stock_once(): void
    {
        $owner = $this->staff(RoleName::Owner);
        $variant = $this->variant(stock: 6);
        $order = $this->order($variant, 2);
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($owner)->post(route('admin.sales.payments.cash', $payment), ['note' => 'Tunai di toko'])
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Verified, $payment->refresh()->status);
        $this->assertSame(SalesOrderStatus::Confirmed, $order->refresh()->order_status);
        $this->assertSame(4, (int) $variant->fresh()->stock_quantity);

        // Aksi ganda tidak mengurangi stok lagi.
        $this->actingAs($owner)->post(route('admin.sales.payments.cash', $payment->refresh()));
        $this->assertSame(4, (int) $variant->fresh()->stock_quantity);
    }

    public function test_transition_rejects_invalid_target_with_error_flash(): void
    {
        $owner = $this->staff(RoleName::Owner);
        $order = $this->order();

        $this->actingAs($owner)->post(route('admin.sales.transition', $order), [
            'status' => SalesOrderStatus::Completed->value,
        ])->assertSessionHas('error');

        $this->assertSame(SalesOrderStatus::PendingPayment, $order->refresh()->order_status);
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $owner = $this->staff(RoleName::Owner);
        $order = $this->order();

        $this->actingAs($owner)->post(route('admin.sales.transition', $order), [
            'status' => 'unknown_status',
        ])->assertSessionHasErrors('status');
    }

    public function test_warehouse_cannot_access_sales_admin(): void
    {
        $warehouse = $this->staff(RoleName::WarehouseStaff);

        $this->actingAs($warehouse)->get(route('admin.sales.index'))->assertForbidden();
    }

    public function test_customer_cannot_access_sales_admin(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.sales.index'))->assertForbidden();
    }

    public function test_admin_can_view_but_not_complete_without_permission_matrix(): void
    {
        $admin = $this->staff(RoleName::Admin);
        $order = $this->order();

        $this->actingAs($admin)->get(route('admin.sales.show', $order))->assertOk();
    }

    private function staff(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function order(?ProductVariant $variant = null, int $quantity = 1): SalesOrder
    {
        $variant ??= $this->variant();
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        return app(SalesOrderService::class)->checkout($customer, [
            ['variant_id' => $variant->id, 'quantity' => $quantity],
        ], PaymentMethod::Cash);
    }

    private function variant(int $stock = 10): ProductVariant
    {
        $product = Product::factory()->sale()->create(['sale_price' => 100000]);

        return ProductVariant::factory()->for($product)->create([
            'sale_price' => 100000,
            'stock_quantity' => $stock,
            'is_active' => true,
        ]);
    }
}
