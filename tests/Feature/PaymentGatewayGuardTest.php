<?php

namespace Tests\Feature;

use App\Contracts\FakePaymentGateway;
use App\Contracts\MidtransPaymentGateway;
use App\Contracts\PaymentGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * PHASE 8 — guard provider pembayaran palsu tidak boleh aktif di production.
 */
class PaymentGatewayGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_testing_environment_uses_fake_gateway(): void
    {
        $this->assertInstanceOf(FakePaymentGateway::class, app(PaymentGateway::class));
    }

    public function test_production_environment_uses_real_gateway(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config()->set('gearora.midtrans.fake', false);
        $this->app->forgetInstance(PaymentGateway::class);

        $this->assertInstanceOf(MidtransPaymentGateway::class, $this->app->make(PaymentGateway::class));
    }

    public function test_production_environment_rejects_fake_flag(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config()->set('gearora.midtrans.fake', true);
        $this->app->forgetInstance(PaymentGateway::class);

        $this->expectException(LogicException::class);

        $this->app->make(PaymentGateway::class);
    }
}
