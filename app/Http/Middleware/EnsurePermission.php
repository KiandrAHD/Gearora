<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Otorisasi berbasis permission di server. Menu yang disembunyikan di Blade
 * bukan kontrol akses; setiap route sensitif harus melewati middleware ini.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('admin.login'));
        }

        // Owner memegang kontrol tertinggi (lihat Gate::before di AppServiceProvider).
        if (! $user->hasPermission($permission) && ! $user->isOwner()) {
            abort(403, "Anda tidak memiliki izin '{$permission}'.");
        }

        return $next($request);
    }
}
