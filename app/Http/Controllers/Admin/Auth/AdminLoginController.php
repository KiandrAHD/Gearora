<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Login staf Admin Console. Owner, Admin, dan Petugas Gudang memakai route ini.
 * Customer tidak dapat masuk ke area admin melalui form ini.
 */
class AdminLoginController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function create(): View
    {
        if (Auth::check() && Auth::user()->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
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

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active || ! $user->isStaff()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'email' => 'Akun ini tidak memiliki akses ke Admin Console.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        $this->logger->log('auth.admin_login', $user, 'Staf masuk ke Admin Console.', [], $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->logger->log('auth.admin_logout', $user, 'Staf keluar dari Admin Console.', [], $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Kamu telah keluar dari Admin Console.');
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return 'admin-login:'.Str::transliterate(Str::lower($email)).'|'.$ip;
    }
}
