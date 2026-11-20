<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerLoginRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerLoginController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function create(Request $request): View
    {
        // Hanya path internal yang diizinkan sebagai tujuan setelah login.
        // Ini mempertahankan draft paket trip tanpa membuka open redirect.
        $redirect = $request->query('redirect');

        if (is_string($redirect)
            && str_starts_with($redirect, '/')
            && ! str_starts_with($redirect, '//')
            && ! str_contains($redirect, '\\')) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.login');
    }

    public function store(CustomerLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $throttleKey = $this->throttleKey($credentials['email'], $request->ip());
        $maxAttempts = (int) config('gearora.login.max_attempts');
        $decaySeconds = (int) config('gearora.login.decay_seconds');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '
                    .RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (! Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            (bool) ($credentials['remember'] ?? false),
        )) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'email' => 'Akun ini tidak aktif. Hubungi admin GEARORA.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        $this->logger->log('auth.login', $user, 'Pelanggan masuk.', ['email' => $user->email], $user);

        return redirect()->intended(route('customer.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->logger->log('auth.logout', $user, 'Pelanggan keluar.', [], $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Kamu telah keluar dari akun.');
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return 'login:'.Str::transliterate(Str::lower($email)).'|'.$ip;
    }
}
