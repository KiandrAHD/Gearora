<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya staf aktif (Owner, Admin, Petugas Gudang) yang boleh masuk area Admin.
 * Customer yang sudah login tetap ditolak (403), bukan diarahkan ke dashboard customer.
 */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->is_active || ! $user->isStaff()) {
            abort(403, 'Akun ini tidak memiliki akses ke Admin Console.');
        }

        return $next($request);
    }
}
