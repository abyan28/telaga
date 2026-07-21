<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureUserHasRole — Role-Based Access Control (rules.md §1.5).
 *
 * Membatasi akses route hanya untuk user dengan role yang diizinkan. Dipakai
 * pada route dengan sintaks `role:admin` atau `role:admin,guru` (beberapa role).
 * User tanpa role sesuai akan ditolak (403); yang belum login diarahkan ke login.
 */
class EnsureUserHasRole
{
    /**
     * Menangani request masuk dan memeriksa role user.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     * @param  string  ...$roles  Daftar role yang diizinkan mengakses route.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Belum login -> arahkan ke halaman login
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // Role user tidak termasuk yang diizinkan -> tolak akses (403)
        if (! in_array(Auth::user()->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }

        return $next($request);
    }
}
