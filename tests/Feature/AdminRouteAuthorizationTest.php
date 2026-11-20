<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\RentalOrder;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — otorisasi route admin per role (bukan sekadar menu tersembunyi).
 */
class AdminRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoCatalogSeeder::class);
    }

    public function test_anonymous_is_redirected_from_admin(): void
    {
        // Middleware `auth` berjalan lebih dulu sehingga tamu diarahkan ke login.
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.customers.index'))->assertRedirect(route('login'));
    }

    public function test_customer_is_forbidden_from_admin_area(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_warehouse_staff_can_access_operations_but_not_finance_or_reports(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole(RoleName::WarehouseStaff);

        // Diizinkan.
        $this->actingAs($staff)->get(route('admin.inventory.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.operations.pickups'))->assertOk();

        // Ditolak: tidak punya permission terkait.
        $this->actingAs($staff)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.products.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.sales.index'))->assertForbidden();
    }

    public function test_inactive_staff_is_forbidden(): void
    {
        $staff = User::factory()->create(['is_active' => false]);
        $staff->assignRole(RoleName::Admin);

        $this->actingAs($staff)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_warehouse_staff_cannot_verify_payment_via_direct_post(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole(RoleName::WarehouseStaff);

        $order = RentalOrder::factory()->create();
        $order->customer->assignRole(RoleName::Customer);
        $payment = $order->payments()->create([
            'method' => 'transfer',
            'amount' => $order->total,
            'rental_amount' => $order->subtotal,
            'deposit_amount' => $order->deposit_total,
            'status' => 'waiting_verification',
            'proof_path' => 'payment-proofs/x.jpg',
        ]);

        $this->actingAs($staff)
            ->post(route('admin.payments.decide', $payment), ['decision' => 'verified'])
            ->assertForbidden();
    }
}
