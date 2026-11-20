<?php

namespace Tests\Feature;

use App\Support\ProductionReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — konfigurasi produksi & deployment gate.
 */
class ProductionConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function secureConfig(): void
    {
        config()->set('app.debug', false);
        config()->set('app.url', 'https://gearora.example');
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('session.secure', true);
        config()->set('session.http_only', true);
        config()->set('session.same_site', 'lax');
        config()->set('gearora.midtrans.fake', false);
    }

    public function test_secure_configuration_has_no_violations(): void
    {
        $this->secureConfig();

        $this->assertSame([], ProductionReadiness::violations());
        $this->assertTrue(ProductionReadiness::isReady());
    }

    public function test_debug_http_and_insecure_session_are_detected(): void
    {
        $this->secureConfig();
        config()->set('app.debug', true);
        config()->set('app.url', 'http://gearora.example');
        config()->set('session.secure', false);
        config()->set('session.http_only', false);

        $violations = ProductionReadiness::violations();

        $this->assertNotEmpty($violations);
        $this->assertContains('APP_DEBUG harus false pada production.', $violations);
        $this->assertContains('APP_URL harus memakai https:// pada production.', $violations);
        $this->assertContains('SESSION_SECURE_COOKIE harus true pada HTTPS.', $violations);
    }

    public function test_fake_gateway_flag_is_detected(): void
    {
        $this->secureConfig();
        config()->set('gearora.midtrans.fake', true);

        $this->assertContains('MIDTRANS_FAKE harus false pada production.', ProductionReadiness::violations());
    }

    public function test_command_passes_with_secure_configuration(): void
    {
        $this->secureConfig();

        $this->artisan('gearora:production-check')->assertSuccessful();
    }

    public function test_command_fails_with_insecure_configuration(): void
    {
        $this->secureConfig();
        config()->set('app.debug', true);

        $this->artisan('gearora:production-check')->assertFailed();
    }
}
