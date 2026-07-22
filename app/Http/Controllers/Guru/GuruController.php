<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\DocumentUploadService;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * GuruController — dashboard guru dengan pembatasan ketat per kelas (PRD §5.3/§7.9).
 *
 * KEAMANAN (PRD §13 — anti kebocoran antar-kelas): setiap query siswa dibatasi
 * ke id_classes yang diampu guru (Teacher::ampuClassIds). Guru mengakses siswa
 * lewat method guardStudent() yang memanggil abort(403) bila siswa di luar
 * kelas ampuannya.
 */
class GuruController extends Controller
{
    /**
     * Menampilkan dashboard guru: kelas yang diampu + siswa di kelas tersebut.
     */
    public function index(): View
    {
        $teacher = $this->teacher();
        $classIds = $teacher->ampuClassIds();

        // Hanya siswa di kelas yang diampu (guard di level query)
        $students = Student::whereIn('id_class', $classIds)
            ->with(['schoolClass.homeroomTeacher'])
            ->get();

        $classes = $teacher->classes()->get();
        // K8.1: kelas yang guru ini wali kelasnya (hak tambah/edit siswa).
        $homeroomIds = $teacher->homeroomClass()->pluck('id_classes')->all();

        return view('guru.dashboard', compact('classes', 'students', 'homeroomIds'));
    }

    /**
     * Profil siswa (read-only) untuk guru: biodata.
     * Hanya siswa di kelas yang diampu (guardStudent). Reuse view wali/child-profile.
     */
    public function showStudent(Student $student): View
    {
        $this->guardStudent($student);
        $student->load(['schoolClass.homeroomTeacher', 'ortu', 'academicYear']);

        return view('ortu.child-profile', [
            'student' => $student,
            'backUrl' => route('guru.dashboard'),
            'backLabel' => 'Kembali ke Dashboard',
        ]);
    }

    /**
     * Memperbarui profil siswa (hanya siswa di kelas yang diampu guru).
     */
    public function updateStudent(Request $request, Student $student): RedirectResponse
    {
        $this->guardHomeroom($student); // K8.1: hanya wali kelas boleh edit

        $rules = Student::profilRules();
        // update: nik ignore diri sendiri
        $rules['nik'] = ['required', 'digits:16', \Illuminate\Validation\Rule::unique('students','nik')->ignore($student->id_students,'id_students')];
        $rules['nis'] = ['nullable', 'digits_between:15,18', \Illuminate\Validation\Rule::unique('students','nis')->ignore($student->id_students,'id_students')];

        $data = $request->validate($rules, ValidationRules::messages());
        $student->update($data);

        return back()->with('success', 'Profil murid diperbarui.');
    }

    /**
     * Menambah siswa lama (T6.3) — hanya ke kelas yang diampu guru (guard).
     */
    public function storeStudent(Request $request, DocumentUploadService $uploader): RedirectResponse
    {
        // K8.1: hanya wali kelas boleh tambah siswa, & hanya ke kelas yang diwalikan.
        $classIds = $this->teacher()->homeroomClass()->pluck('id_classes')->all();
        abort_if(empty($classIds), 403, 'Hanya wali kelas yang dapat menambah murid.');

        $rules = Student::profilRules();
        $rules['nik'] = ['required', 'digits:16', 'unique:students,nik'];
        $rules['nisn'] = ['nullable', 'digits:10', 'unique:students,nisn'];
        $rules['nis'] = ['nullable', 'digits_between:15,18', 'unique:students,nis'];
        $rules['id_class'] = ['required', Rule::in($classIds)];
        $rules['foto'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

        $data = $request->validate($rules, [
            ...ValidationRules::messages(),
            'nik.digits' => 'NIK harus tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'id_class.in' => 'Anda hanya bisa menambah murid ke kelas yang Anda ampu.',
        ]);
        $data['nisn'] = $data['nisn'] ?? null;
        $data['nis'] = $data['nis'] ?? null;
        $data['id_academic_year'] = \App\Models\AcademicYear::where('is_aktif', true)->value('id_academic_years');
        $foto = $request->file('foto'); // simpan setelah create agar folder pakai PK (nama kembar)
        unset($data['foto']);

        $student = Student::create($data);
        // Foto opsional (siswa lama boleh belum punya) → profil/siswa/{Nama-id}/Foto_{Nama-id}.
        if ($foto) {
            $student->update(['foto_path' => $uploader->storeProfilePhoto($foto, 'siswa', $data['nama_lengkap'], $student->id_students)]);
        }

        return back()->with('success', 'Murid berhasil ditambahkan ke kelas Anda.');
    }

    /**
     * Form ganti password guru (T6.1: password awal = nomor induk, wajib diganti).
     */
    public function passwordForm(): View
    {
        return view('guru.password', ['teacher' => Auth::user()->teacher]);
    }

    /**
     * Menyimpan password baru guru (verifikasi password lama dulu).
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[a-zA-Z0-9_.]+$/', Rule::unique('users', 'username')->ignore(Auth::id(), 'id_users')],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(Auth::id(), 'id_users')],
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'username.regex'  => 'Username hanya boleh huruf, angka, titik, dan underscore (tanpa spasi/tanda hubung).',
            'username.min'    => 'Username minimal 6 karakter.',
            'username.unique' => 'Username ini sudah digunakan.',
            'email.required'  => 'Email wajib diisi.',
            'email.unique'    => 'Email ini sudah digunakan.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        Auth::user()->update([
            'username'             => $request->username,
            'email'                => $request->email,
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return back()->with('success', 'Username & password berhasil diperbarui.');
    }

    /**
     * Memperbarui email & no. HP akun guru sendiri (T2.3, T6.4).
     *
     * Guru boleh mengisi/mengubah email (opsional) & no. HP (wajib — identifier
     * login). no_hp disinkron ke profil teachers (unique index terpisah).
     * Validasi unique mengabaikan baris milik guru ini sendiri.
     */
    public function updateAccount(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $teacher = $user->teacher;

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id_users, 'id_users')],
            'no_hp' => [
                ...ValidationRules::noHp(),
                Rule::unique('users', 'no_hp')->ignore($user->id_users, 'id_users'),
                Rule::unique('teachers', 'no_hp')->ignore($teacher?->id_teachers, 'id_teachers'),
            ],
        ], [
            ...ValidationRules::messages(),
            'no_hp.required' => 'Nomor HP wajib diisi (dipakai untuk login).',
            'email.unique' => 'Email ini sudah digunakan.',
            'no_hp.unique' => 'Nomor HP ini sudah digunakan.',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $teacher, $data) {
            $user->update(['email' => $data['email'] ?? null, 'no_hp' => $data['no_hp']]);
            $teacher?->update(['no_hp' => $data['no_hp']]);
        });

        return back()->with('success', 'Pengaturan akun berhasil diperbarui.');
    }

    /**
     * Memperbarui profil guru sendiri (T6.4): nama, TTL, riwayat pendidikan.
     * NUPTK tidak diubah di sini — identitas resmi & password awal, dikelola admin.
     */
    public function updateProfile(Request $request, DocumentUploadService $uploader): RedirectResponse
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher !== null, 403);

        // Jabatan guru wali kelas dikelola otomatis (auto "WALI KELAS") — tak boleh
        // diubah manual di sini; guru biasa boleh mengisi jabatannya sendiri.
        $isWaliKelas = $teacher->homeroomClass()->exists();

        $data = $request->validate([
            'nama' => ValidationRules::nama(),
            'jabatan' => ['nullable', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'tanggal_mulai_mengajar' => ['nullable', 'date'],
            'riwayat_pendidikan' => ['nullable', 'string'],
            'alamat' => ['nullable', 'string', 'max:500'],
            // Alamat wilayah berjenjang (T2.1) — nullable; id kode BPS + nama snapshot.
            'provinsi_id' => ['nullable', 'string', 'max:10'],
            'provinsi_nama' => ['nullable', 'string', 'max:255'],
            'kota_id' => ['nullable', 'string', 'max:10'],
            'kota_nama' => ['nullable', 'string', 'max:255'],
            'kecamatan_id' => ['nullable', 'string', 'max:10'],
            'kecamatan_nama' => ['nullable', 'string', 'max:255'],
            'kelurahan_id' => ['nullable', 'string', 'max:10'],
            'kelurahan_nama' => ['nullable', 'string', 'max:255'],
        ], ValidationRules::messages());

        // Wali kelas: jangan biarkan guru menimpa jabatan otomatis.
        if ($isWaliKelas) {
            unset($data['jabatan']);
        }

        // Ganti foto bila diunggah (hapus lama agar tak menyampah).
        if ($request->hasFile('foto')) {
            if ($teacher->foto_path) {
                \Storage::disk('public')->delete($teacher->foto_path);
            }
            $data['foto_path'] = $uploader->storeProfilePhoto($request->file('foto'), 'guru', $data['nama'], $teacher->id_teachers);
        }
        unset($data['foto']);

        $teacher->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Mengambil profil Teacher milik user yang login.
     */
    private function teacher(): Teacher
    {
        return Auth::user()->teacher()->firstOrFail();
    }

    /**
     * Query guard: pastikan siswa berada di salah satu kelas yang diampu guru.
     * Bila tidak, tolak akses (403) — mencegah kebocoran data antar-kelas (PRD §13).
     */
    private function guardStudent(Student $student): void
    {
        abort_unless(
            in_array($student->id_class, $this->teacher()->ampuClassIds(), true),
            403,
            'Murid ini berada di luar kelas yang Anda ampu.',
        );
    }

    /**
     * Guard wali kelas (K8.1): hanya wali kelas siswa yang boleh tambah/edit
     * profil. Guru biasa (cuma mengajar) → 403.
     */
    private function guardHomeroom(Student $student): void
    {
        abort_unless(
            $this->teacher()->isHomeroomOf($student),
            403,
            'Hanya wali kelas yang dapat mengubah data murid ini.',
        );
    }
}
