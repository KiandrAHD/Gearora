<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
