<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('customer.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['nullable', 'string', 'min:8', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->name = $data['name'];
            $user->phone = $data['phone'] ?? null;
            $user->address = $data['address'] ?? null;

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            app(ActivityLogger::class)->log(
                'profile.updated',
                $user,
                'Pelanggan memperbarui profil.',
                ['password_changed' => ! empty($data['password'])],
                $user,
            );
        });

        return back()->with('status', 'Profil diperbarui.');
    }
}
