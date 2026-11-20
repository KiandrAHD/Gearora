<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * PHASE 8 — CSRF.
 *
 * Laravel melewati VerifyCsrfToken ketika APP_ENV=testing. Test ini memaksa
 * environment non-testing agar proteksi benar-benar dievaluasi.
 */
class CsrfSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_changing_web_routes_use_web_group(): void
    {
        foreach (['register.store', 'login.store', 'customer.sales.checkout'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} tidak ditemukan.");
            $this->assertContains('web', $route->gatherMiddleware(), "Route {$name} harus berada di grup web (CSRF aktif).");
        }
    }

    public function test_webhook_route_is_outside_web_group(): void
    {
        $route = Route::getRoutes()->getByName('payments.midtrans.notification');

        $this->assertNotNull($route);
        $this->assertNotContains('web', $route->gatherMiddleware());
    }

    public function test_web_post_without_token_is_rejected(): void
    {
        $this->app['env'] = 'local';

        $this->post(route('register.store'), [
            'name' => 'Tanpa Token',
            'email' => 'no-token@gearora-test.invalid',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertStatus(419);

        $this->assertDatabaseMissing('users', ['email' => 'no-token@gearora-test.invalid']);
    }

    public function test_webhook_endpoint_is_not_csrf_protected(): void
    {
        $this->app['env'] = 'local';

        $this->postJson(route('payments.midtrans.notification'), [])->assertStatus(400);
    }
}
