<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_database_connection_is_not_production(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_baseline_migrations_create_expected_tables(): void
    {
        foreach (['users', 'cache', 'cache_locks', 'jobs', 'sessions'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel {$table} tidak ditemukan.");
        }
    }
}
