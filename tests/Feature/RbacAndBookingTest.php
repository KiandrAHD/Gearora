<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\RentalOrderStatus;
use App\Enums\RoleName;
use App\Exceptions\RentalAvailabilityException;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;
use App\Services\RentalOrderService;
use App\Support\RentalPeriod;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAndBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_owner_can_access_admin_dashboard(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_customer_can_only_view_own_order(): void
    {
        $first = User::factory()->create();
        $first->assignRole(RoleName::Customer);
        $second = User::factory()->create();
        $second->assignRole(RoleName::Customer);
        $order = RentalOrder::factory()->for($first, 'customer')->create();

        $this->actingAs($second)->get(route('customer.orders.show', $order))->assertForbidden();
    }

    public function test_booking_creates_order_and_blocks_same_asset(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);
        $variant = $this->rentalVariant(1);
        $period = RentalPeriod::from(now()->addDays(3)->toDateString(), now()->addDays(5)->toDateString());

        $order = app(RentalOrderService::class)->create($customer, [['variant_id' => $variant->id, 'quantity' => 1]], $period, PaymentMethod::Transfer);

        $this->assertSame(RentalOrderStatus::PendingPayment, $order->order_status);
        $this->assertCount(1, $order->assignments);
        $this->assertDatabaseHas('payments', ['rental_order_id' => $order->id, 'method' => 'transfer', 'status' => 'unpaid']);

        $other = User::factory()->create();
        $other->assignRole(RoleName::Customer);
        $this->expectException(RentalAvailabilityException::class);
        app(RentalOrderService::class)->create($other, [['variant_id' => $variant->id, 'quantity' => 1]], $period, PaymentMethod::Transfer);
    }

    public function test_adjacent_half_open_period_can_reuse_asset(): void
    {
        $variant = $this->rentalVariant(1);
        $first = User::factory()->create();
        $first->assignRole(RoleName::Customer);
        $second = User::factory()->create();
        $second->assignRole(RoleName::Customer);
        $start = now()->addDays(8)->startOfDay();

        app(RentalOrderService::class)->create($first, [['variant_id' => $variant->id, 'quantity' => 1]], RentalPeriod::from($start, $start->addDays(2)));
        $secondOrder = app(RentalOrderService::class)->create($second, [['variant_id' => $variant->id, 'quantity' => 1]], RentalPeriod::from($start->addDays(2), $start->addDays(4)));

        $this->assertNotNull($secondOrder->id);
    }

    private function rentalVariant(int $assets): ProductVariant
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);
        RentalAsset::factory()->count($assets)->for($variant, 'variant')->create();

        return $variant;
    }
}
