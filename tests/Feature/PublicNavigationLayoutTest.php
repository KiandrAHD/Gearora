<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi layout navbar: grid seimbang, navigasi tengah, aksi kanan,
 * underline aktif tidak hardcoded offset, mobile drawer focus management.
 */
class PublicNavigationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_uses_grid_with_three_columns(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('grid-template-columns:minmax(0,1fr) auto minmax(0,1fr)', $css);
        $this->assertMatchesRegularExpression('/\.brand\{[^}]*grid-column:1/', $css);
        $this->assertMatchesRegularExpression('/\.primary-nav\{[^}]*grid-column:2/', $css);
        $this->assertMatchesRegularExpression('/\.header-actions\{[^}]*grid-column:3/', $css);
    }

    public function test_primary_nav_is_centered_with_justify_self(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/\.primary-nav\{[^}]*justify-self:center/', $css);
        $this->assertStringNotContainsString('margin-left:35px', $css);
        $this->assertStringNotContainsString('margin-right:auto', $css);
    }

    public function test_header_actions_are_right_aligned(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/\.header-actions\{[^}]*justify-self:end/', $css);
    }

    public function test_active_nav_underline_bottoms_at_zero(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // bottom:0 agar underline sejajar header bottom tanpa hardcoded offset
        $this->assertStringContainsString('bottom:0', $css);
        $this->assertStringNotContainsString('bottom:-28px', $css);
    }

    public function test_primary_nav_stretches_to_fill_header_height(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/\.primary-nav\{[^}]*align-self:stretch/', $css);
        $this->assertMatchesRegularExpression('/\.primary-nav a\{[^}]*display:flex;align-items:center/', $css);
    }

    public function test_mobile_drawer_has_close_button(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('mobile-nav-close', $html);
        $this->assertStringContainsString('aria-label="Tutup menu navigasi"', $html);
    }

    public function test_mobile_drawer_has_focus_trap(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('@keydown.tab="trap($event)"', $html);
        $this->assertStringContainsString('x-ref="drawer"', $html);
    }

    public function test_mobile_menu_toggle_uses_toggle_function(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('@click="toggle()"', $html);
        $this->assertStringNotContainsString('@click="open = !open"', $html);
    }

    public function test_alpine_mobile_nav_data_registered(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("Alpine.data('mobileNav'", $js);
        $this->assertStringContainsString('show()', $js);
        $this->assertStringContainsString('close()', $js);
        $this->assertStringContainsString('trap(event)', $js);
    }
}
