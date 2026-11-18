<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationAndMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_no_catalog_active_state(): void
    {
        $response = $this->get('/')->assertOk();

        $this->assertSame(0, substr_count($this->primaryNav($response->getContent()), 'aria-current="page"'));
    }

    public function test_rental_mode_marks_only_sewa_active(): void
    {
        $response = $this->get('/katalog?mode=sewa')->assertOk();
        $nav = $this->primaryNav($response->getContent());

        $this->assertSame(1, substr_count($nav, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/href="[^"]*mode=sewa"[^>]*aria-current="page"/', $nav);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*mode=beli"[^>]*aria-current="page"/', $nav);
    }

    public function test_sale_mode_marks_only_beli_active(): void
    {
        $response = $this->get('/katalog?mode=beli')->assertOk();
        $nav = $this->primaryNav($response->getContent());

        $this->assertSame(1, substr_count($nav, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/href="[^"]*mode=beli"[^>]*aria-current="page"/', $nav);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*mode=sewa"[^>]*aria-current="page"/', $nav);
    }

    public function test_catalog_without_mode_defaults_to_sewa_only(): void
    {
        $response = $this->get('/katalog')->assertOk();
        $nav = $this->primaryNav($response->getContent());

        $this->assertSame(1, substr_count($nav, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/href="[^"]*mode=sewa"[^>]*aria-current="page"/', $nav);
    }

    public function test_trip_page_marks_only_trip_active(): void
    {
        $response = $this->get('/rekomendasi-trip')->assertOk();
        $nav = $this->primaryNav($response->getContent());

        $this->assertSame(1, substr_count($nav, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/href="[^"]*rekomendasi-trip"[^>]*aria-current="page"/', $nav);
    }

    public function test_guide_page_marks_only_guide_active(): void
    {
        $response = $this->get('/panduan')->assertOk();
        $nav = $this->primaryNav($response->getContent());

        $this->assertSame(1, substr_count($nav, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/href="[^"]*panduan"[^>]*aria-current="page"/', $nav);
    }

    public function test_mobile_navigation_has_controls_backdrop_and_escape_handler(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="mobile-navigation"', $html);
        $this->assertStringContainsString('aria-controls="mobile-navigation"', $html);
        $this->assertStringContainsString('class="mobile-nav"', $html);
        $this->assertStringContainsString('mobile-nav-backdrop', $html);
        $this->assertStringContainsString('@keydown.escape.window="open = false"', $html);
        $this->assertStringContainsString('x-effect="document.body.classList.toggle(\'nav-open\', open)"', $html);
    }

    public function test_mobile_navigation_css_has_explicit_display_override(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/@media\(max-width:900px\).*?\.mobile-nav\{display:block/', $css);
        $this->assertStringContainsString('.mobile-nav-backdrop', $css);
        $this->assertStringContainsString('body.nav-open{overflow:hidden}', $css);
    }

    public function test_hero_svg_has_valid_driver_translation(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('transform="translate(178 290)"', $html);
        $this->assertStringNotContainsString('transform="translate(178 290">', $html);
        $this->assertStringContainsString('viewBox="0 0 660 520"', $html);
    }

    private function primaryNav(string $html): string
    {
        preg_match('/<nav class="primary-nav".*?<\/nav>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
