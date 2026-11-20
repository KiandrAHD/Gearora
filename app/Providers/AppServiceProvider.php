<?php

namespace App\Providers;

use App\Contracts\FakePaymentGateway;
use App\Contracts\MidtransPaymentGateway;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Support\ProductionReadiness;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $environment = app()->environment();
            $fakeEnabled = (bool) config('gearora.midtrans.fake');

            // Production tidak boleh pernah memakai provider palsu, termasuk
            // saat salah konfigurasi environment flag.
            if ($environment === 'production') {
                if ($fakeEnabled) {
                    throw new LogicException('Fake Midtrans gateway is forbidden in production.');
                }

                return new MidtransPaymentGateway;
            }

            // Otomated test selalu offline; local/development perlu opt-in eksplisit.
            if ($environment === 'testing' || ($environment === 'local' && $fakeEnabled)) {
                return new FakePaymentGateway;
            }

            return new MidtransPaymentGateway;
        });
    }

    public function boot(): void
    {
        // Owner memegang kontrol tertinggi aplikasi.
        Gate::before(function (User $user): ?bool {
            return $user->isOwner() ? true : null;
        });

        Gate::define('permission', function (User $user, string $permission): bool {
            return $user->hasPermission($permission);
        });

        Password::defaults(function () {
            return Password::min(8)->letters()->numbers();
        });

        $this->configureRateLimiters();

        if ($this->app->environment('production')) {
            $this->assertProductionSecurityConfiguration();
            URL::forceScheme('https');
        }
    }

    private function assertProductionSecurityConfiguration(): void
    {
        $violations = ProductionReadiness::violations();

        if ($violations !== []) {
            throw new LogicException('Unsafe production configuration: '.implode(' ', $violations));
        }
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Webhook provider: batas wajar, cukup untuk retry Midtrans.
        RateLimiter::for('midtrans-webhook', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
