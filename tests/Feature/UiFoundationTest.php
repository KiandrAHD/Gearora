<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_renders_filter_and_empty_state(): void
    {
        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Sewa gear untuk perjalananmu.')
            ->assertSee('Cari nama, merek, atau deskripsi gear')
            ->assertSee('name="q"', false)
            ->assertSee('Belum ada gear yang cocok');
    }

    public function test_guides_page_renders(): void
    {
        $this->get('/panduan')
            ->assertOk()
            ->assertSee('Panduan sebelum bertualang.')
            ->assertSee('Belum ada panduan dipublikasikan.');
    }

    public function test_customer_route_requires_login(): void
    {
        $this->get('/akun')->assertRedirect(route('login'));
    }

    public function test_admin_route_requires_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_all_skeletons_expose_accessible_main_landmark(): void
    {
        foreach (['/', '/katalog', '/panduan'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('id="main-content"', false);
        }
    }

    public function test_customer_dashboard_exposes_main_landmark_after_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/akun')
            ->assertOk()
            ->assertSee('id="main-content"', false);
    }
}
