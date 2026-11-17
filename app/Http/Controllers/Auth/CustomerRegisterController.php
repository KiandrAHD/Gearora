<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerRegisterController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(CustomerRegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            // Role tidak pernah diterima dari request registrasi.
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole(RoleName::Customer);

            $this->logger->log(
                'auth.registered',
                $user,
                'Akun pelanggan baru terdaftar.',
                ['email' => $user->email],
                $user,
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('customer.dashboard'))
            ->with('status', 'Akun berhasil dibuat. Selamat datang di GEARORA.');
    }
}
