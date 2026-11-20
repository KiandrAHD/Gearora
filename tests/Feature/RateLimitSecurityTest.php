<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — rate limiting login pelanggan & staf.
 */
class RateLimitSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config()->set('gearora.login.max_attempts', 2);
        config()->set('gearora.login.decay_seconds', 60);
    }

    public function test_customer_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create(['email' => 'customer@gearora-test.invalid']);

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak',
            (string) session('errors')->first('email'),
        );
    }

    public function test_admin_login_is_throttled(): void
    {
        $staff = User::factory()->create(['email' => 'staff@gearora-test.invalid']);
        $staff->assignRole(RoleName::Admin);

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('admin.login.store'), [
                'email' => $staff->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('admin.login.store'), [
            'email' => $staff->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Terlalu banyak',
            (string) session('errors')->first('email'),
        );
    }

    public function test_successful_login_clears_throttle_counter(): void
    {
        $user = User::factory()->create([
            'email' => 'fresh@gearora-test.invalid',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
    }
}
