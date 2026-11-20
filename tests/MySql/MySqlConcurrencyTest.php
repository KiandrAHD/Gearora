<?php

namespace Tests\MySql;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PHASE 8 — bukti row-level locking InnoDB dengan dua koneksi independen.
 *
 * Windows runner ini tidak menyediakan pcntl/test-parallel. Test tetap memakai
 * dua koneksi MySQL nyata: koneksi kedua harus timeout saat koneksi pertama
 * memegang `SELECT ... FOR UPDATE` pada stok yang sama.
 *
 * Jalankan hanya dengan database test:
 *   php artisan test --configuration=phpunit.mysql.xml tests/MySql
 */
class MySqlConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        if ($connection->getDriverName() !== 'mysql' || $database !== 'gearora_test') {
            $this->fail("Refusing destructive MySQL test on database [{$database}]. Use gearora_test.");
        }
    }

    public function test_innodb_row_lock_blocks_second_connection_until_release(): void
    {
        // RefreshDatabase membuka transaksi wrapper. Fixture untuk koneksi kedua
        // harus committed terlebih dahulu supaya terlihat lintas koneksi.
        DB::commit();
        config()->set('database.connections.mysql_secondary', config('database.connections.mysql'));

        $primary = DB::connection('mysql');
        $secondary = DB::connection('mysql_secondary');
        $secondary->disconnect();
        $secondary->reconnect();

        $variantId = $primary->table('product_variants')->insertGetId([
            'product_id' => $this->productId($primary),
            'name' => 'PHASE8 LOCK TEST',
            'sku' => 'P8-LOCK-'.strtoupper(str()->random(8)),
            'rental_price_per_day' => 0,
            'sale_price' => 100000,
            'stock_quantity' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondary->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $primary->beginTransaction();
        $primary->table('product_variants')->where('id', $variantId)->lockForUpdate()->first();

        $secondary->beginTransaction();

        try {
            $secondary->table('product_variants')->where('id', $variantId)->lockForUpdate()->first();
            $this->fail('Koneksi kedua berhasil mengambil lock saat koneksi pertama masih memegangnya.');
        } catch (QueryException $exception) {
            $this->assertStringContainsStringIgnoringCase('lock wait timeout', $exception->getMessage());
        } finally {
            if ($secondary->transactionLevel() > 0) {
                $secondary->rollBack();
            }

            if ($primary->transactionLevel() > 0) {
                $primary->commit();
            }
        }

        $secondary->beginTransaction();
        $row = $secondary->table('product_variants')->where('id', $variantId)->lockForUpdate()->first();
        $secondary->rollBack();

        $this->assertNotNull($row);
        $primary->table('product_variants')->where('id', $variantId)->delete();
    }

    private function productId($connection): int
    {
        $categoryId = $connection->table('categories')->insertGetId([
            'name' => 'PHASE8 Lock',
            'slug' => 'phase8-lock-'.str()->lower(str()->random(8)),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $connection->table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'PHASE8 Lock Product',
            'slug' => 'phase8-lock-product-'.str()->lower(str()->random(8)),
            'description' => 'Temporary MySQL concurrency fixture.',
            'type' => 'sale',
            'status' => 'active',
            'rental_price_per_day' => 0,
            'sale_price' => 100000,
            'requires_deposit' => false,
            'is_featured' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
