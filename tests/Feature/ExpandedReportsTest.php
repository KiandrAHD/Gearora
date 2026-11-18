<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\RoleName;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpandedReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_sees_summary_report(): void
    {
        $this->confirmedSales(120000, 2);

        $this->actingAs($this->staff(RoleName::Owner))->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Laporan')
            ->assertSee('Penjualan retail')
            ->assertSee('240.000');
    }

    public function test_sales_report_only_counts_verified_sales(): void
    {
        $this->confirmedSales(100000, 1);
        SalesOrder::factory()->create(['order_status' => 'pending_payment', 'payment_status' => 'unpaid']);

        $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.index', ['report' => 'sales']))
            ->assertOk()
            ->assertSee('Data Sales')
            ->assertSee('Items');
    }

    public function test_each_report_type_renders(): void
    {
        $owner = $this->staff(RoleName::Owner);

        foreach (['summary', 'rental', 'sales', 'payments', 'deposits', 'charges', 'inventory', 'operations'] as $type) {
            $this->actingAs($owner)
                ->get(route('admin.reports.index', ['report' => $type]))
                ->assertOk();
        }
    }

    public function test_unknown_report_type_falls_back_to_summary(): void
    {
        $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.index', ['report' => 'does-not-exist']))
            ->assertOk();
    }

    public function test_date_filter_validation_rejects_inverted_range(): void
    {
        $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.index', [
                'start_date' => '2026-02-10',
                'end_date' => '2026-02-01',
            ]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_warehouse_cannot_view_business_reports(): void
    {
        $this->actingAs($this->staff(RoleName::WarehouseStaff))
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_customer_cannot_view_reports(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.reports.index'))->assertForbidden();
    }

    private function confirmedSales(int $price, int $quantity): SalesOrder
    {
        $product = Product::factory()->create([
            'type' => ProductType::Sale,
            'rental_price_per_day' => null,
            'sale_price' => $price,
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'sale_price' => $price,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $order = SalesOrder::factory()->confirmed()->create([
            'subtotal' => $price * $quantity,
            'total' => $price * $quantity,
            'confirmed_at' => now(),
        ]);

        SalesOrderItem::factory()->for($order, 'order')->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => $product->name,
            'unit_price_snapshot' => $price,
            'quantity' => $quantity,
            'line_total' => $price * $quantity,
        ]);

        return $order;
    }

    private function staff(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
