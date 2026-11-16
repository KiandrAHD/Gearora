<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_is_reachable(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_homepage_shows_brand_and_tagline(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('GEARORA')
            ->assertSee('Gear Up. Explore More.');
    }

    public function test_homepage_route_is_named(): void
    {
        $this->assertSame(url('/'), route('home'));
    }

    public function test_unknown_route_returns_branded_not_found_page(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertDontSee('Whoops');
    }
}
