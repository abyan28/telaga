<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
        // Guard T1.1: email wajib diverifikasi via OTP sebelum daftar.
        if (session('otp_verified_email') !== $request->input('email')) {
            return back()->withErrors(['email' => 'Email belum diverifikasi. Silakan kirim kode verifikasi terlebih dahulu.'])->withInput();
        }

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

        // Login otomatis setelah registrasi berhasil — bersihkan OTP session
        $request->session()->forget(['otp_code', 'otp_email', 'otp_expires_at', 'otp_verified_email']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('ortu.dashboard');
    }

    /**
     * T1.1: Kirim OTP 6 digit ke email via Brevo SMTP.
     * Simpan OTP + expiry di session (5 menit).
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Cegah OTP ke email yang sudah terdaftar.
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['error' => 'Email sudah terdaftar. Silakan login atau gunakan email lain.'], 422);
        }

        // Resend cooldown: 60 detik sejak OTP terakhir dikirim
        $lastSent = $request->session()->get('otp_sent_at', 0);
        if (now()->getTimestamp() - $lastSent < 60) {
            return response()->json(['error' => 'Tunggu sebelum mengirim ulang kode.'], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Mail::to($request->email)->send(new \App\Mail\OtpVerification($code));

        $request->session()->put([
            'otp_code'       => $code,
            'otp_email'      => $request->email,
            'otp_expires_at' => now()->addMinutes(5)->getTimestamp(),
            'otp_sent_at'    => now()->getTimestamp(),
        ]);

        return response()->json(['sent' => true]);
    }

    /**
     * T1.1: Verifikasi OTP dari session.
     * Bila cocok & belum expired → set session otp_verified_email.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'string', 'size:6'],
        ]);

        $session = $request->session();

        if ($session->get('otp_email') !== $request->email) {
            return response()->json(['verified' => false, 'error' => 'Email tidak sesuai.'], 422);
        }
        if (now()->getTimestamp() > (int) $session->get('otp_expires_at', 0)) {
            return response()->json(['verified' => false, 'error' => 'Kode sudah kedaluwarsa. Silakan kirim ulang.'], 410);
        }
        if ($session->get('otp_code') !== $request->code) {
            return response()->json(['verified' => false, 'error' => 'Kode salah.'], 422);
        }

        $session->put('otp_verified_email', $request->email);
        $session->forget(['otp_code', 'otp_expires_at']);

        return response()->json(['verified' => true]);
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
