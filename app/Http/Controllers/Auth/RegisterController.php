<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * RegisterController — registrasi mandiri (self-signup) wali murid.
 *
 * Sesuai rules.md §1.5, HANYA wali murid yang boleh mendaftar sendiri. Role
 * dikunci menjadi 'ortu' oleh sistem (tidak dapat dipilih user) untuk mencegah
 * eskalasi hak akses. Profil wali disimpan di tabel parents (rules.md §1.6).
 */
class RegisterController extends Controller
{
    /**
     * Memproses registrasi akun wali murid baru.
     *
     * Memvalidasi input, membuat user (role dikunci 'ortu') beserta baris
     * profil ortu dalam satu transaksi, lalu otomatis login.
     */
    public function register(Request $request): RedirectResponse
    {
        // Validasi input registrasi. Username: unik, tanpa spasi/tanda hubung/simbol
        // (hanya huruf, angka, underscore, titik). Nama wali diisi nanti di CMS wali.
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[a-zA-Z0-9_.]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'no_hp' => [...\App\Support\ValidationRules::noHp(false), 'unique:users,no_hp'],
        ], [
            ...\App\Support\ValidationRules::messages(),
            'username.regex' => 'Username hanya boleh huruf, angka, titik, dan underscore (tanpa spasi/tanda hubung).',
            'username.min' => 'Username minimal 6 karakter.',
            'username.unique' => 'Username ini sudah digunakan.',
            'no_hp.unique' => 'Nomor HP ini sudah digunakan.',
        ]);

        // Buat user + profil ortu secara atomik
        $user = DB::transaction(function () use ($validated) {
            // Role dikunci 'ortu' — TIDAK diambil dari input (rules.md §1.5).
            // Nama asli ortu dikosongkan; diisi wali sendiri lewat CMS (§1.6).
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'no_hp' => $validated['no_hp'] ?? null,
                'role' => 'ortu',
                'password' => Hash::make($validated['password']),
            ]);

            // Buat profil wali (ortu) tertaut ke user. Kolom ortu diisi belakangan
            // lewat CMS wali (K2.1 gate). Default ibu aktif; no_hp signup di users saja.
            OrangTua::create([
                'id_user' => $user->id_users,
                'ada_ayah' => false, 'ada_ibu' => true,
            ]);

            return $user;
        });

        // Login otomatis setelah registrasi berhasil
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('ortu.dashboard');
    }

    /**
     * Cek ketersediaan username (AJAX). Dipakai form signup untuk feedback live.
     * Kembalikan {available: bool}. Sama-persis dengan rule unique:users,username.
     */
    public function checkUsername(Request $request): \Illuminate\Http\JsonResponse
    {
        $username = (string) $request->query('username', '');
        $min = max(3, (int) $request->query('min', 3)); // guru pakai min 6
        $ignore = (int) $request->query('ignore', 0); // edit: abaikan diri sendiri
        $valid = preg_match("/^[a-zA-Z0-9_.]{{$min},30}$/", $username) === 1;

        return response()->json([
            'available' => $valid && ! User::where('username', $username)
                ->when($ignore, fn ($q) => $q->where('id_users', '!=', $ignore))
                ->exists(),
        ]);
    }
}
