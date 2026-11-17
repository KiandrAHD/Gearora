<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder utama. Tidak membuat akun produksi atau password default.
     * Akun Owner dibuat eksplisit lewat `php artisan gearora:provision-owner`.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call(DemoCatalogSeeder::class);
        }
    }
}
