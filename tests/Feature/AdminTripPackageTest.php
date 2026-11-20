<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\TripPackage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pemantauan paket trip oleh staf: daftar, detail, dan batas akses pelanggan.
 */
class AdminTripPackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_see_trip_package_list_and_detail(): void
    {
        $package = TripPackage::factory()->create();

        $this->actingAs($this->owner())
            ->get(route('admin.trip-packages.index'))
            ->assertOk()
            ->assertSee($package->code)
            ->assertSee('Paket Trip Planner');

        $this->actingAs($this->owner())
            ->get(route('admin.trip-packages.show', $package))
            ->assertOk()
            ->assertSee($package->code)
            ->assertSee($package->destination);
    }

    public function test_package_list_can_be_filtered_by_status(): void
    {
        $visible = TripPackage::factory()->create(['destination' => 'Gunung Prau']);
        TripPackage::factory()->create(['destination' => 'Gunung Rinjani']);

        $this->actingAs($this->owner())
            ->get(route('admin.trip-packages.index', ['q' => 'Prau']))
            ->assertOk()
            ->assertSee($visible->code)
            ->assertDontSee('Gunung Rinjani');
    }

    public function test_customer_cannot_access_admin_package_monitor(): void
    {
        $package = TripPackage::factory()->create();
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.trip-packages.index'))
            ->assertForbidden();
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Owner);

        return $user;
    }
}
