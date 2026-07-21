<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AuthController — menangani login & logout untuk semua role.
 *
 * Sistem memakai satu tabel users + kolom role (rules.md §1.5). Setelah login
 * berhasil, user diarahkan ke dashboard sesuai rolenya masing-masing.
 */
class AuthController extends Controller
{
    /**
     * Menampilkan halaman login/signup. L5.4: bila sesi masih aktif, langsung
     * arahkan ke dashboard sesuai role (jangan tampilkan form login lagi).
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('auth.login');
    }

    /**
     * Memproses percobaan login.
     *
     * Identifier login boleh email, nomor HP, ATAU username (T3.3 — semuanya unik).
     * Email dideteksi lewat format; input lain dicocokkan ke no_hp atau username.
     * Berhasil -> redirect per role.
     */
    public function login(Request $request): RedirectResponse
    {
        // Validasi input login: satu field 'login' = email / no. HP / username
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ], [], ['login' => 'Email / No. HP / Username']);

        $login = $request->input('login');

        // Email → kolom email. Selain itu cocokkan no_hp ATAU username (T3.3).
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $credentials = ['email' => $login, 'password' => $request->input('password')];
        } else {
            // K1.1: no_hp dicocokkan dalam format ternormalisasi (081… → 6281…),
            // username tetap dicocokkan apa adanya. normalizeNoHp aman untuk
            // input username karena kolom no_hp tak pernah menyimpan username.
            $noHp = \App\Support\ValidationRules::normalizeNoHp($login);
            $user = \App\Models\User::where('no_hp', $noHp)->orWhere('username', $login)->first();
            $credentials = ['id_users' => $user?->id_users, 'password' => $request->input('password')];
        }

        // Coba autentikasi; parameter kedua = "remember me"
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // L4.1: akun nonaktif (guru keluar / wali/admin dinonaktifkan) diblok — 1 guard semua role.
            if (! $user->is_aktif) {
                Auth::logout();

                return back()->withErrors(['login' => 'Akun ini sudah nonaktif. Hubungi admin.'])->onlyInput('login');
            }

            // L2.1: blokir ortu jika PPDB tutup & tak punya anak aktif/calon-lulus/utang-DU-berjalan.
            // PPDB buka → gate mati → akun otomatis hidup lagi (tanpa kolom flag baru).
            if ($user->role === 'ortu' && Setting::get('pendaftaran_dibuka', '1') === '0') {
                $punyaAnakHidup = $user->ortu?->students()
                    ->where(function ($q) {
                        $q->where('status', 'aktif')
                          ->orWhereHas('registrationForms', fn ($f) => $f->where('status', 'lulus'))
                          ->orWhere(function ($q2) {
                              // Dibatalkan tapi masih utang denda (terbayar < denda) → akun tetap hidup.
                              $persenDenda = (int) Setting::get('persen_refund', 30);
                              $q2->whereHas('registrationForms', fn ($f) => $f->where('status', 'dibatalkan'))
                                 ->whereHas('reRegistrationPayments', fn ($r) =>
                                     $r->whereRaw('jumlah_terbayar < ROUND(total_biaya * ? / 100, 2)', [$persenDenda]));
                          });
                    })->exists() ?? false;

                if (! $punyaAnakHidup) {
                    Auth::logout();

                    return back()->withErrors(['login' => 'Akun Anda dinonaktifkan karena tidak ada anak yang aktif. Hubungi pihak sekolah.'])->onlyInput('login');
                }
            }

            $request->session()->regenerate();

            return $this->redirectByRole();
        }

        // Kredensial salah — kembali dengan pesan error pada field login
        return back()->withErrors([
            'login' => 'Email/No. HP/Username atau kata sandi salah.',
        ])->onlyInput('login');
    }

    /**
     * Logout user dan invalidasi session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Mengarahkan user ke dashboard sesuai role setelah login.
     */
    private function redirectByRole(): RedirectResponse
    {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'guru' => redirect()->route('guru.dashboard'),
            default => redirect()->route('ortu.dashboard'), // wali
        };
    }
}
