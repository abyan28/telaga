<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa wali melengkapi profil (nama, no_hp, pekerjaan, TTL) sebelum boleh
 * mendaftar/bayar (K2.1). Selama profil belum lengkap, wali diarahkan ke form
 * profil. Rute yang tetap boleh diakses: form profil itu sendiri, pengaturan
 * akun, ganti password, dan logout (hindari loop redirect & user tak terkunci).
 */
class RequireOrtuProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->role === 'ortu') {
            $route   = $request->route()?->getName();
            $alwaysOk = ['ortu.password', 'ortu.password.update', 'logout'];

            // Langkah 2: wajib isi username + email (akun auto-create T9.1).
            if ($user->needsAccountSetup()) {
                $allowed = [...$alwaysOk, 'ortu.account', 'ortu.account.update'];
                if (str_starts_with((string) $route, 'ortu.') && ! in_array($route, $allowed, true)) {
                    return redirect()->route('ortu.account')
                        ->with('info', 'Lengkapi username dan email akun Anda terlebih dahulu.');
                }
            }

            // Langkah 3: wajib lengkapi profil orang tua.
            if (! $user->ortu?->isComplete()) {
                $allowed = [...$alwaysOk, 'ortu.account', 'ortu.account.update',
                    'ortu.profile', 'ortu.profile.update'];
                if (str_starts_with((string) $route, 'ortu.') && ! in_array($route, $allowed, true)) {
                    return redirect()->route('ortu.profile')
                        ->with('success', 'Lengkapi profil Anda terlebih dahulu sebelum mendaftar.');
                }
            }
        }

        return $next($request);
    }
}
