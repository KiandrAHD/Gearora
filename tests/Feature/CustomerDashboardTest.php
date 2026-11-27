<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_account_nav_and_empty_state(): void
    {
        $user = User::factory()->create(['name' => 'Rani Pendaki']);

        $this->actingAs($user)
            ->get('/akun')
            ->assertOk()
            ->assertSee('Ringkasan akun')
            ->assertSee('Sewa saya')
            ->assertSee('Pembelian saya')
            ->assertSee('Profil &amp; keamanan', false)
            ->assertSee('Rani Pendaki')
            ->assertSee('Belum ada pesanan sewa');
    }

    public function test_account_nav_only_links_to_existing_routes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/akun')->assertOk();

        $response->assertSee(route('customer.orders.index'), false);
        $response->assertSee(route('customer.sales.index'), false);
        $response->assertSee(route('customer.profile.edit'), false);
    }
}
