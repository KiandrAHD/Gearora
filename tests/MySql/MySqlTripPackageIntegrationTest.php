<?php

namespace Tests\MySql;

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\TripPackage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bukti integrasi MySQL nyata untuk Paket Trip: skema InnoDB + foreign key,
 * dan checkout mixed sewa+beli yang memakai nested transaction (savepoint).
 *
 * Jalankan hanya dengan database test:
 *   php artisan test --configuration=phpunit.mysql.xml tests/MySql
 */
class MySqlTripPackageIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();

        if (DB::connection()->getDriverName() !== 'mysql' || $database !== 'gearora_test') {
            $this->fail("Refusing destructive MySQL test on database [{$database}]. Use gearora_test.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_trip_packages_schema_is_innodb_with_foreign_keys(): void
    {
        $engine = DB::selectOne(
            'select ENGINE from information_schema.TABLES where TABLE_SCHEMA = database() and TABLE_NAME = ?',
            ['trip_packages'],
        );

        $this->assertNotNull($engine, 'Tabel trip_packages tidak ditemukan.');
        $this->assertSame('InnoDB', $engine->ENGINE);

        foreach (['customer_id' => 'users', 'rental_order_id' => 'rental_orders', 'sales_order_id' => 'sales_orders'] as $column => $parent) {
            $foreignKeys = DB::select(
                'select CONSTRAINT_NAME from information_schema.KEY_COLUMN_USAGE
                 where TABLE_SCHEMA = database() and TABLE_NAME = ? and COLUMN_NAME = ? and REFERENCED_TABLE_NAME = ?',
                ['trip_packages', $column, $parent],
            );

            $this->assertNotEmpty($foreignKeys, "Foreign key {$column} -> {$parent} tidak ada.");
        }

        $jsonType = DB::selectOne(
            "select DATA_TYPE from information_schema.COLUMNS where TABLE_SCHEMA = database() and TABLE_NAME = 'trip_packages' and COLUMN_NAME = 'plan_snapshot'",
        );
        $this->assertSame('json', strtolower((string) $jsonType->DATA_TYPE));
    }

    public function test_mixed_package_checkout_commits_both_orders_through_savepoints(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $rental = Product::factory()->create(['name' => 'Tenda MySQL', 'rental_price_per_day' => 90000]);
        $rentalVariant = ProductVariant::factory()->for($rental)->create(['rental_price_per_day' => 90000]);
        RentalAsset::factory()->for($rentalVariant, 'variant')->create();

        $sale = Product::factory()->sale()->create(['name' => 'Headlamp MySQL', 'sale_price' => 185000]);
        $saleVariant = ProductVariant::factory()->for($sale)->create(['sale_price' => 185000, 'stock_quantity' => 4]);

        $this->withSession(['trip_package_draft' => [
            'activity' => 'hiking',
            'destination' => 'Gunung Prau',
            'location' => 'Dieng',
            'duration_days' => 2,
            'participants' => 2,
            'experience' => 'pemula',
            'mode' => 'sewa',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'special_needs' => [],
            'items' => [
                ['variant_id' => $rentalVariant->id, 'quantity' => 1, 'fulfillment' => 'sewa'],
                ['variant_id' => $saleVariant->id, 'quantity' => 2, 'fulfillment' => 'beli'],
            ],
        ]]);

        $this->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect();

        $package = TripPackage::query()->firstOrFail();

        $this->assertNotNull($package->rental_order_id);
        $this->assertNotNull($package->sales_order_id);
        $this->assertDatabaseHas('rental_orders', ['id' => $package->rental_order_id]);
        $this->assertDatabaseHas('sales_orders', ['id' => $package->sales_order_id]);
        $this->assertDatabaseHas('sales_stock_reservations', [
            'sales_order_id' => $package->sales_order_id,
            'product_variant_id' => $saleVariant->id,
            'quantity' => 2,
        ]);

        $this->assertSame('Gunung Prau', $package->plan_snapshot['destination'] ?? null);
    }
}
