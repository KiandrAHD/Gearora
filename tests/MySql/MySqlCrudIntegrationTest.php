<?php

namespace Tests\MySql;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Opt-in integration test untuk MySQL nyata.
 *
 * Jalankan hanya dengan database test:
 *   php artisan test --configuration=phpunit.mysql.xml
 */
class MySqlCrudIntegrationTest extends TestCase
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

    public function test_mysql_schema_is_innodb_with_foreign_keys(): void
    {
        $engine = DB::selectOne(
            'select ENGINE from information_schema.TABLES where TABLE_SCHEMA = database() and TABLE_NAME = ?',
            ['rental_assets'],
        );

        $this->assertSame('InnoDB', $engine->ENGINE);

        $foreignKeys = DB::select(
            'select CONSTRAINT_NAME from information_schema.KEY_COLUMN_USAGE
             where TABLE_SCHEMA = database() and TABLE_NAME = ? and COLUMN_NAME = ? and REFERENCED_TABLE_NAME = ?',
            ['product_variants', 'product_id', 'products'],
        );

        $this->assertNotEmpty($foreignKeys);
    }

    public function test_mysql_phase5_payment_and_operations_schema(): void
    {
        $tables = [
            'payments', 'payment_attempts', 'payment_webhook_events', 'payment_proofs',
            'refunds', 'deposits', 'deposit_transactions',
            'rental_handovers', 'rental_handover_items', 'rental_returns', 'rental_return_items',
            'inspections', 'inspection_photos', 'additional_charges',
        ];

        foreach ($tables as $table) {
            $engine = DB::selectOne(
                'select ENGINE from information_schema.TABLES where TABLE_SCHEMA = database() and TABLE_NAME = ?',
                [$table],
            );

            $this->assertNotNull($engine, "Tabel {$table} tidak ditemukan.");
            $this->assertSame('InnoDB', $engine->ENGINE, "Tabel {$table} bukan InnoDB.");
        }

        // Kolom status/metode kini string (kompatibel MySQL + SQLite), bukan ENUM.
        $methodType = DB::selectOne(
            "select DATA_TYPE from information_schema.COLUMNS where TABLE_SCHEMA = database() and TABLE_NAME = 'payments' and COLUMN_NAME = 'method'",
        );
        $this->assertSame('varchar', $methodType->DATA_TYPE);

        // Uang disimpan sebagai integer Rupiah penuh.
        $depositType = DB::selectOne(
            "select DATA_TYPE from information_schema.COLUMNS where TABLE_SCHEMA = database() and TABLE_NAME = 'deposits' and COLUMN_NAME = 'required_amount'",
        );
        $this->assertContains($depositType->DATA_TYPE, ['int', 'bigint']);

        // Deduplikasi webhook dijaga oleh unique index.
        $unique = DB::select(
            "select INDEX_NAME from information_schema.STATISTICS
             where TABLE_SCHEMA = database() and TABLE_NAME = 'payment_webhook_events'
               and COLUMN_NAME = 'event_hash' and NON_UNIQUE = 0",
        );
        $this->assertNotEmpty($unique);

        $payment = DB::table('payments')->insertGetId([
            'rental_order_id' => RentalOrder::factory()->create()->id,
            'method' => 'midtrans',
            'amount' => 150000,
            'rental_amount' => 100000,
            'deposit_amount' => 50000,
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('midtrans', DB::table('payments')->where('id', $payment)->value('method'));
        $this->assertSame(50000, (int) DB::table('payments')->where('id', $payment)->value('deposit_amount'));
    }

    public function test_mysql_product_and_variant_crud_persists_to_database(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);
        $category = Category::factory()->create();

        $this->actingAs($owner)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Produk MySQL Integrasi',
            'slug' => 'produk-mysql-integrasi',
            'description' => 'Dibuat oleh integration test.',
            'brand' => 'MySQL Test',
            'type' => 'both',
            'status' => 'active',
            'rental_price_per_day' => 80000,
            'sale_price' => 700000,
            'requires_deposit' => 0,
            'is_featured' => 0,
            'specifications' => [
                ['key' => 'Kapasitas', 'value' => '2 orang'],
            ],
        ])->assertRedirect();

        $product = Product::query()->where('slug', 'produk-mysql-integrasi')->firstOrFail();
        $this->assertSame('Produk MySQL Integrasi', DB::table('products')->where('id', $product->id)->value('name'));

        $this->actingAs($owner)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Produk MySQL Diperbarui',
            'slug' => 'produk-mysql-diperbarui',
            'description' => 'Sudah diperbarui.',
            'brand' => 'MySQL Test Updated',
            'type' => 'both',
            'status' => 'active',
            'rental_price_per_day' => 85000,
            'sale_price' => 725000,
            'requires_deposit' => 0,
            'is_featured' => 1,
            'return_to' => 'index',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produk MySQL Diperbarui',
            'slug' => 'produk-mysql-diperbarui',
            'rental_price_per_day' => 85000,
        ]);

        $product->refresh();

        $this->actingAs($owner)->post(route('admin.products.variants.store', $product), [
            'name' => 'Varian MySQL',
            'sku' => 'MYSQL-INT-01',
            'rental_price_per_day' => 80000,
            'sale_price' => 725000,
            'stock_quantity' => 7,
            'is_active' => 1,
        ])->assertRedirect(route('admin.products.edit', $product));

        $variant = ProductVariant::query()->where('sku', 'MYSQL-INT-01')->firstOrFail();
        $this->assertSame($product->id, $variant->product_id);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'initial',
            'quantity_after' => 7,
        ]);
    }

    public function test_mysql_safe_delete_and_deactivation_rules(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        $safeVariant = ProductVariant::factory()->create();
        $safeVariantId = $safeVariant->id;

        $this->actingAs($owner)
            ->delete(route('admin.products.variants.destroy', [$safeVariant->product, $safeVariant]))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_variants', ['id' => $safeVariantId]);

        $rentedVariant = ProductVariant::factory()->create(['is_active' => true]);
        RentalAsset::factory()->count(2)->for($rentedVariant, 'variant')->create();

        $this->actingAs($owner)
            ->delete(route('admin.products.variants.destroy', [$rentedVariant->product, $rentedVariant]))
            ->assertRedirect();

        $this->assertDatabaseHas('product_variants', ['id' => $rentedVariant->id, 'is_active' => 0]);
        $this->assertSame(2, RentalAsset::query()->where('product_variant_id', $rentedVariant->id)->count());

        $safeProduct = Product::factory()->create();
        $safeProductId = $safeProduct->id;
        $this->actingAs($owner)->delete(route('admin.products.destroy', $safeProduct))->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $safeProductId]);

        $historicProduct = Product::factory()->create();
        $historicVariant = ProductVariant::factory()->for($historicProduct)->create();
        RentalAsset::factory()->for($historicVariant, 'variant')->create();

        $this->actingAs($owner)
            ->delete(route('admin.products.destroy', $historicProduct))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $historicProduct->id]);

        $this->actingAs($owner)
            ->post(route('admin.products.archive', $historicProduct))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['id' => $historicProduct->id, 'status' => 'archived']);
    }
}
