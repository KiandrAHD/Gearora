<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_open_every_report_type(): void
    {
        $owner = $this->staff(RoleName::Owner);

        foreach (['summary', 'rental', 'sales', 'payments', 'deposits', 'charges', 'inventory', 'operations'] as $type) {
            $this->actingAs($owner)->get(route('admin.reports.index', ['report' => $type]))->assertOk();
        }
    }

    public function test_sales_only_permission_cannot_open_other_reports(): void
    {
        $user = $this->userWith(['reports.sales.view']);

        $this->actingAs($user)->get(route('admin.reports.index', ['report' => 'sales']))->assertOk();

        foreach (['rental', 'payments', 'deposits', 'charges', 'inventory', 'operations'] as $type) {
            $this->actingAs($user)->get(route('admin.reports.index', ['report' => $type]))->assertForbidden();
        }
    }

    public function test_summary_hides_unauthorized_groups(): void
    {
        $user = $this->userWith(['reports.sales.view']);

        $response = $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();

        $response->assertSee('Penjualan retail')
            ->assertDontSee('Payment collected')
            ->assertDontSee('Deposit held');
    }

    public function test_charges_report_requires_charges_permission(): void
    {
        $user = $this->userWith(['reports.rentals.view']);

        $this->actingAs($user)->get(route('admin.reports.index', ['report' => 'charges']))->assertForbidden();
    }

    public function test_export_requires_view_and_export_permission(): void
    {
        $viewOnly = $this->userWith(['reports.sales.view']);

        $this->actingAs($viewOnly)
            ->get(route('admin.reports.export', ['report' => 'sales']))
            ->assertForbidden();

        $withExport = $this->userWith(['reports.sales.view', 'reports.export']);

        $this->actingAs($withExport)
            ->get(route('admin.reports.export', ['report' => 'sales']))
            ->assertOk();

        $this->actingAs($withExport)
            ->get(route('admin.reports.export', ['report' => 'deposits']))
            ->assertForbidden();
    }

    public function test_export_permission_alone_is_not_enough(): void
    {
        $user = $this->userWith(['reports.export']);

        $this->actingAs($user)->get(route('admin.reports.export', ['report' => 'sales']))->assertForbidden();
    }

    public function test_owner_can_export_all_formats(): void
    {
        $owner = $this->staff(RoleName::Owner);

        foreach (['csv', 'pdf', 'xlsx'] as $format) {
            $this->actingAs($owner)
                ->get(route('admin.reports.export', ['report' => 'sales', 'format' => $format]))
                ->assertOk();
        }
    }

    public function test_customer_cannot_access_reports(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.reports.export', ['report' => 'sales']))->assertForbidden();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $role = Role::factory()->create(['name' => 'reporter_'.uniqid(), 'label' => 'Reporter']);
        $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id')->all());

        $user = User::factory()->create();
        $user->assignRole(RoleName::WarehouseStaff);
        $user->assignRole($role->name);

        return $user;
    }

    private function staff(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
