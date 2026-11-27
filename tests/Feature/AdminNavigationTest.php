<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use App\Support\Navigation\AdminNavigation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_sees_all_navigation_groups(): void
    {
        $nav = $this->navFor($this->staff(RoleName::Owner));

        foreach (['Katalog &amp; Inventaris', 'Operasional Rental', 'Penjualan Retail', 'Keuangan', 'Pelanggan', 'Konten &amp; Rekomendasi', 'Laporan', 'Sistem'] as $label) {
            $this->assertStringContainsString($label, $nav);
        }
    }

    public function test_admin_sees_report_and_finance_groups(): void
    {
        $nav = $this->navFor($this->staff(RoleName::Admin));

        $this->assertStringContainsString('Laporan', $nav);
        $this->assertStringContainsString('Keuangan', $nav);
        $this->assertStringContainsString('Pembayaran', $nav);
    }

    public function test_warehouse_sees_only_permitted_groups(): void
    {
        $nav = $this->navFor($this->staff(RoleName::WarehouseStaff));

        $this->assertStringContainsString('Katalog &amp; Inventaris', $nav);
        $this->assertStringContainsString('Inventaris', $nav);
        $this->assertStringContainsString('Pickup', $nav);
        $this->assertStringContainsString('Biaya Tambahan', $nav);
        $this->assertStringContainsString('Log Aktivitas', $nav);

        foreach (['Penjualan Retail', 'Pelanggan', 'Konten &amp; Rekomendasi', 'Laporan', 'Pembayaran', 'Deposit'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $nav);
        }
    }

    public function test_customer_cannot_open_admin_dashboard(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_active_parent_opens_and_child_is_marked(): void
    {
        $owner = $this->staff(RoleName::Owner);
        $this->actingAs($owner)->get(route('admin.inventory.index'))->assertOk();

        $nav = $this->navForUser($owner, route('admin.inventory.index'));

        $this->assertStringContainsString('data-group-active="true"', $nav);
        $this->assertStringContainsString('aria-current="page"', $nav);
        $this->assertStringContainsString('id="admin-nav-catalog"', $nav);
        $this->assertStringContainsString('aria-controls="admin-nav-catalog"', $nav);
    }

    public function test_sidebar_mobile_controls_and_escape_handler_present(): void
    {
        $html = $this->actingAs($this->staff(RoleName::Owner))->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="admin-sidebar"', $html);
        $this->assertStringContainsString('aria-controls="admin-sidebar"', $html);
        $this->assertStringContainsString('@keydown.escape.window="sidebarOpen = false"', $html);
        $this->assertStringContainsString("document.body.classList.toggle('nav-open', sidebarOpen)", $html);
    }

    public function test_admin_topbar_renders_context_and_user_chip(): void
    {
        $html = $this->actingAs($this->staff(RoleName::Owner))->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="admin-header"', $html);
        $this->assertStringContainsString('Admin Console · GEARORA', $html);
        $this->assertStringContainsString('admin-user-chip', $html);
        $this->assertStringContainsString('admin-subtitle', $html);
    }

    public function test_dashboard_renders_real_kpi_and_notice_regions(): void
    {
        $html = $this->actingAs($this->staff(RoleName::Owner))->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="stat-grid"', $html);
        $this->assertStringContainsString('admin-content', $html);
        $this->assertStringContainsString('id="admin-content"', $html);
    }

    public function test_every_navigation_route_exists(): void
    {
        foreach (AdminNavigation::groups() as $group) {
            if (! empty($group['standalone'])) {
                $this->assertTrue(Route::has($group['route']), "Route {$group['route']} tidak ditemukan.");

                continue;
            }

            foreach ($group['children'] as $child) {
                $this->assertTrue(Route::has($child['route']), "Route {$child['route']} tidak ditemukan.");
            }
        }
    }

    private function navFor(User $user): string
    {
        return $this->navForUser($user, route('admin.dashboard'));
    }

    private function navForUser(User $user, string $url): string
    {
        $html = $this->actingAs($user)->get($url)->assertOk()->getContent();

        preg_match('/<nav class="admin-nav".*?<\/nav>/s', $html, $matches);

        return $matches[0] ?? '';
    }

    private function staff(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
