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

        if ($user && $user->role === 'ortu' && ! $user->ortu?->isComplete()) {
            // Hanya gate rute wali (daftar/bayar/dll). Rute guru/admin dibiarkan
            // ke middleware role: (403) — jangan telan RBAC jadi redirect profil.
            $route = $request->route()?->getName();
            $allowed = ['ortu.profile', 'ortu.profile.update', 'ortu.account', 'ortu.account.update',
                'ortu.password', 'ortu.password.update', 'logout'];

            if (str_starts_with((string) $route, 'ortu.') && ! in_array($route, $allowed, true)) {
                return redirect()->route('ortu.profile')
                    ->with('success', 'Lengkapi profil Anda terlebih dahulu sebelum mendaftar.');
            }
        }

        return $next($request);
    }
}
