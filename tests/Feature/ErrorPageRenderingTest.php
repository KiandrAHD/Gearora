<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — halaman error harus menampilkan kode HTTP yang benar.
 *
 * Bug temuan QA browser: sebelum Phase 8, respons 403 dirender memakai view
 * errors/500 (headline "Terjadi kesalahan") sehingga membingungkan pengguna dan
 * menyamarkan status aslinya. View errors/403 + fallback errors/4xx memperbaiki ini.
 */
class ErrorPageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_forbidden_renders_403_view_not_generic_500(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('Akses ditolak')
            ->assertDontSee('Terjadi kesalahan');
    }

    public function test_not_found_renders_404_view(): void
    {
        $this->get('/rute-yang-tidak-ada-'.uniqid())
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Halaman tidak ditemukan');
    }
}
