<?php

namespace App\Providers;

use App\Contracts\FakePaymentGateway;
use App\Contracts\MidtransPaymentGateway;
use App\Contracts\PaymentGateway;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            // Mode "fake" (tanpa jaringan) dipakai pada test/local tanpa kredensial.
            if (app()->environment('testing') || config('gearora.midtrans.fake')) {
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
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Webhook provider: batas wajar, cukup untuk retry Midtrans.
        RateLimiter::for('midtrans-webhook', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
