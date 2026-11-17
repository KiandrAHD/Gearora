<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

class UiFoundationTest extends TestCase
{
    public function test_catalog_skeleton_renders_filter_and_empty_state(): void
    {
        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Temukan gear untuk perjalananmu.')
            ->assertSee('Cari gear')
            ->assertSee('name="q"', false)
            ->assertSee('Belum ada produk di katalog')
            ->assertSee('Terapkan filter');
    }

    public function test_guides_skeleton_renders_empty_state(): void
    {
        $this->get('/panduan')
            ->assertOk()
            ->assertSee('Belum ada panduan yang dipublikasikan')
            ->assertSee('Konten draft tidak akan ditampilkan');
    }

    public function test_customer_layout_renders_navigation_and_empty_orders(): void
    {
        $this->get('/akun')
            ->assertOk()
            ->assertSee('Ringkasan akun')
            ->assertSee('Navigasi akun')
            ->assertSee('Pesanan saya')
            ->assertSee('Belum ada pesanan');
    }

    public function test_admin_layout_renders_skeleton_dashboard(): void
    {
        $html = View::make('admin.dashboard')->render();

        $this->assertStringContainsString('Admin Dashboard', $html);
        $this->assertStringContainsString('Navigasi admin', $html);
        $this->assertStringContainsString('Belum ada pesanan pada database.', $html);
        $this->assertStringContainsString('Phase 2', $html);
        $this->assertStringContainsString('Lewati ke konten utama', $html);
    }

    public function test_all_skeletons_expose_accessible_main_landmark(): void
    {
        foreach (['/', '/katalog', '/panduan', '/akun'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('id="main-content"', false);
        }
    }
}
