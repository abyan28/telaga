<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa ganti password (T9.1) untuk akun yang dibuat admin dengan password awal
 * tebakable (wali: no_hp, guru: NUPTK). Selama users.must_change_password true,
 * user diarahkan ke form ganti-password role-nya sampai diganti.
 */
class ForceChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // L4.1: user dinonaktifkan — logout paksa.
        // Strict false (bukan null) agar user tanpa is_aktif eksplisit (default DB) tidak kena logout.
        if ($user && $user->is_aktif === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => 'Akun ini sudah nonaktif. Hubungi admin.']);
        }

        if ($user && $user->must_change_password) {
            // Izinkan akses form ganti-password itu sendiri + logout (hindari loop redirect).
            $target = $user->role === 'guru' ? 'guru.password' : 'ortu.password';
            $allowed = ["{$target}", "{$target}.update", 'logout'];

            if (! in_array($request->route()?->getName(), $allowed, true)) {
                return redirect()->route($target)
                    ->with('success', 'Silakan ganti password awal Anda terlebih dahulu.');
            }
        }

        return $next($request);
    }
}
