<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\OrangTua;
use App\Models\Student;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * DashboardController (Wali) — ringkasan status & tagihan SEMUA anak wali (PRD §6).
 *
 * Mendukung wali dengan banyak anak (mis. 2 anak sekaligus, atau kakak/adik
 * lintas tahun ajaran): menampilkan satu blok ringkasan per anak. Sisa tunggakan
 * dihitung dinamis (PRD §13).
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard ringkasan untuk seluruh anak milik wali yang login.
     */
    public function index(): View
    {
        $ortuId = Auth::user()->ortu?->id_parents;

        // Seluruh anak (siswa) milik wali + relasi yang dibutuhkan dashboard.
        $students = Student::where('id_parent', $ortuId)
            ->with([
                'schoolClass.academicYear',
                'schoolClass.teachers.user',
                'academicYear',
                'registrationForms' => fn ($q) => $q->latest('id_registration_forms'),
                'monthlySppBills',
                'reRegistrationPayments',
            ])
            ->orderByDesc('id_students')
            ->get();

        // Total sisa tunggakan seluruh anak (SPP + daftar ulang), dihitung dinamis.
        $totalTunggakan = $students->sum(function ($s) {
            return $s->monthlySppBills->sum(fn ($b) => $b->sisa())
                + $s->reRegistrationPayments->sum(fn ($r) => $r->sisa());
        });

        return view('ortu.dashboard', compact('students', 'totalTunggakan'));
    }

    /**
     * K10.3: halaman daftar anak wali (read-only) → pintu ke profil & catatan guru.
     */
    public function children(): View
    {
        $students = Student::where('id_parent', Auth::user()->ortu?->id_parents)
            ->with(['schoolClass', 'academicYear'])
            ->orderByDesc('id_students')
            ->get();

        return view('ortu.children', compact('students'));
    }

    /**
     * K10.3: profil biodata read-only. Gate: anak ini WAJIB milik wali yang
     * login — where id_parent, else 404.
     */
    public function childProfile(Student $student): View
    {
        abort_unless($student->id_parent === Auth::user()->ortu?->id_parents, 404);

        $student->load(['schoolClass.homeroomTeacher', 'ortu', 'academicYear']);

        return view('ortu.child-profile', compact('student'));
    }

    /**
     * Menampilkan form pengaturan akun wali (update email & no. HP) — T2.3.
     */
    public function accountForm(): View
    {
        return view('ortu.account', ['ortu' => Auth::user()->ortu, 'user' => Auth::user()]);
    }

    /**
     * Memperbarui email, no. HP, & username akun wali (T2.3).
     *
     * Email & no_hp unik lintas users; no_hp juga disimpan di profil ortu
     * (unique index terpisah). Keduanya diselaraskan dalam satu transaksi;
     * validasi unique mengabaikan baris milik wali ini sendiri.
     */
    public function updateAccount(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_.]+$/',
                Rule::unique('users', 'username')->ignore($user->id_users, 'id_users')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id_users, 'id_users')],
            'no_hp' => [
                ...ValidationRules::noHp(false),
                Rule::unique('users', 'no_hp')->ignore($user->id_users, 'id_users'),
            ],
        ], [
            ...ValidationRules::messages(),
            'username.regex' => 'Username hanya boleh huruf, angka, underscore (_) dan titik (.), tanpa spasi.',
            'username.unique' => 'Username ini sudah digunakan.',
            'email.unique' => 'Email ini sudah digunakan.',
            'no_hp.unique' => 'Nomor HP ini sudah digunakan.',
        ]);

        $user->update($data);

        return back()->with('success', 'Pengaturan akun berhasil diperbarui.');
    }

    /**
     * Form ganti password wali (T9.1: akun dibuat admin, password awal = no_hp).
     */
    public function passwordForm(): View
    {
        return view('ortu.password');
    }

    /**
     * Ganti password wali. Clear must_change_password agar tak lagi dipaksa (T9.1).
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        Auth::user()->update(['password' => Hash::make($request->password), 'must_change_password' => false]);

        return redirect()->route('ortu.dashboard')->with('success', 'Password berhasil diperbarui.');
    }

    /**
     * Form profil wali (K2.1: data ayah/ibu wajib lengkap + alamat).
     */
    public function profileForm(): View
    {
        return view('ortu.profile', ['ortu' => Auth::user()->ortu]);
    }

    /**
     * Simpan profil wali (L1.1): data ayah + ibu (flat) + alamat keluarga.
     * Guard: minimal 1 ortu aktif (ada_ayah || ada_ibu).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $ortu = $user->ortu;

        $adaAyah = (bool) $request->input('ada_ayah', false);
        $adaIbu  = (bool) $request->input('ada_ibu', false);

        if (! $adaAyah && ! $adaIbu) {
            return back()->withErrors(['ada_ortu' => 'Minimal satu data orang tua (Ayah atau Ibu) harus diisi.']);
        }

        $rules = ['ada_ayah' => ['boolean'], 'ada_ibu' => ['boolean'], ...OrangTua::alamatRules()];

        foreach ([['ayah', $adaAyah], ['ibu', $adaIbu]] as [$p, $ada]) {
            $req = $ada ? 'required' : 'nullable';
            $rules["{$p}_nama"]          = $ada ? ValidationRules::nama() : ValidationRules::nama(false);
            $rules["{$p}_tempat_lahir"]  = [$req, 'string', 'max:100'];
            $rules["{$p}_tanggal_lahir"] = [$req, 'date'];
            $rules["{$p}_agama"]         = [$req, 'string', 'max:50'];
            $rules["{$p}_pendidikan"]    = [$req, 'string', 'max:20'];
            $rules["{$p}_pekerjaan"]     = [$req, 'string', 'max:50'];
            $rules["{$p}_pekerjaan_lain"]= ['nullable', "required_if:{$p}_pekerjaan,LAINNYA", 'string', 'max:100'];
            $rules["{$p}_penghasilan"]   = [$req, 'string', 'max:20'];
            $rules["{$p}_no_hp"]         = $ada ? ValidationRules::noHp() : ['nullable'];
        }

        $data = $request->validate($rules, \App\Support\ValidationRules::messages());

        // Kolom ortu yang tidak aktif → null semua (ponytail: null 9 col per ortu, 1 loop).
        foreach ([['ayah', $adaAyah], ['ibu', $adaIbu]] as [$p, $ada]) {
            if (! $ada) {
                foreach (['nama','tempat_lahir','tanggal_lahir','agama','pendidikan','pekerjaan','pekerjaan_lain','penghasilan','no_hp'] as $c) {
                    $data["{$p}_{$c}"] = null;
                }
            }
        }
        $data['ada_ayah'] = $adaAyah;
        $data['ada_ibu']  = $adaIbu;

        $ortu->update($data);

        return redirect()->route('ortu.dashboard')->with('success', 'Profil berhasil dilengkapi.');
    }
}
