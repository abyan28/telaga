<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DocumentUploadService;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * MasterDataController (Admin) — manajemen data siswa, guru, dan kelas (PRD §7.9/§7.10).
 *
 * Termasuk pembuatan akun guru (password awal = nomor induk, rules.md §1.5) dan
 * penugasan guru ke banyak kelas (many-to-many via pivot class_teacher).
 */
class MasterDataController extends Controller
{
    /**
     * Menampilkan halaman manajemen (legacy gabungan — dipertahankan sbg alias).
     */
    public function index(Request $request): View
    {
        return $this->students($request);
    }

    /**
     * Tab Data Siswa (T5.1/T5.2): daftar siswa + filter kelas/status + pencarian.
     */
    public function students(Request $request): View
    {
        $q = Student::with(['schoolClass.homeroomTeacher', 'ortu.user']);

        // Pencarian nama / NIK / NISN
        if ($cari = trim((string) $request->query('cari'))) {
            $q->where(fn ($w) => $w
                ->where('nama_lengkap', 'like', "%{$cari}%")
                ->orWhere('nik', 'like', "%{$cari}%")
                ->orWhere('nisn', 'like', "%{$cari}%"));
        }
        // Filter kelas — nilai khusus 'none' = siswa belum dapat kelas (K8.1)
        if ($kelas = $request->query('id_class')) {
            $kelas === 'none' ? $q->whereNull('id_class') : $q->where('id_class', $kelas);
        }
        // Filter status (default 'aktif'; 'semua' = tanpa filter; lulus/nonaktif = arsip)
        $status = $request->query('status', 'aktif');
        if ($status !== 'semua') {
            $q->where('status', $status);
        }

        // K5.1: siswa hasil PPDB (punya formulir pendaftaran) baru muncul di Data
        // Siswa SETELAH ada pembayaran daftar ulang (nominal berapapun → terbayar>0).
        // Siswa lama manual (tanpa formulir) tampil apa adanya.
        $q->where(fn ($w) => $w
            ->whereDoesntHave('registrationForms')
            ->orWhereHas('reRegistrationPayments', fn ($r) => $r->where('jumlah_terbayar', '>', 0)));

        $students = $q->orderBy('nama_lengkap')->paginate(25)->withQueryString();
        $classes = SchoolClass::orderBy('nama_kelas')->get();

        return view('admin.data.students', compact('students', 'classes', 'status'));
    }

    /**
     * Tab Data Guru (T5.1): daftar guru + kelas diampu. `classes` untuk modal tambah.
     * K5.4: default hanya guru aktif; ?status=nonaktif untuk lihat ex-guru.
     */
    public function teachers(Request $request): View
    {
        $q = Teacher::with(['user', 'classes.students', 'homeroomClass']);
        // Pencarian nama / NUPTK / email / HP — dibungkus closure agar tak bocor ke filter is_aktif.
        if ($cari = trim((string) $request->query('cari'))) {
            $q->where(fn ($w) => $w
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('nuptk', 'like', "%{$cari}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$cari}%")->orWhere('no_hp', 'like', "%{$cari}%")));
        }
        // K5.4: filter status (default aktif; nonaktif = ex-guru diarsipkan).
        $status = $request->query('status', 'aktif');
        if ($status === 'nonaktif') {
            $q->where('is_aktif', false);
        } elseif ($status !== 'semua') {
            $q->where('is_aktif', true);
        }
        $teachers = $q->orderBy('nama')->paginate(25)->withQueryString();
        $classes = SchoolClass::with('academicYear')->get();

        return view('admin.data.teachers', compact('teachers', 'classes', 'status'));
    }

    /**
     * K5.4: ubah status keluar/aktif guru (ganti hard-delete). Guru nonaktif
     * disembunyikan dari list & diblok login (AuthController). Data tetap tersimpan.
     */
    public function toggleTeacherStatus(Teacher $teacher): RedirectResponse
    {
        $teacher->update(['is_aktif' => ! $teacher->is_aktif]);
        // L4.1: sinkron is_aktif user agar login guard (users.is_aktif) konsisten.
        $teacher->user?->update(['is_aktif' => $teacher->is_aktif]);
        AuditLogService::record('ubah_status_guru', 'Teacher#'.$teacher->id_teachers, null, ['is_aktif' => $teacher->is_aktif]);

        return back()->with('success', 'Status guru diubah menjadi '.($teacher->is_aktif ? 'AKTIF' : 'NONAKTIF (keluar)').'.');
    }

    /**
     * Tab Data Kelas (T5.1): daftar kelas + tahun ajaran.
     */
    public function classes(): View
    {
        $classes = SchoolClass::with(['academicYear', 'teachers', 'students', 'homeroomTeacher'])->get();
        // Guru aktif untuk dropdown wali kelas.
        $teachers = Teacher::where('is_aktif', true)->orderBy('nama')->get(['id_teachers', 'nama']);

        return view('admin.data.classes', compact('classes', 'teachers'));
    }

    /**
     * Profil siswa (T5.3): biodata + riwayat tagihan SPP & daftar ulang + transaksi.
     */
    public function showStudent(Student $student): View
    {
        $student->load([
            'schoolClass.academicYear', 'schoolClass.homeroomTeacher', 'ortu.user', 'academicYear',
            'monthlySppBills', 'reRegistrationPayments',
            'paymentTransactions' => fn ($q) => $q->latest('id_payment_transactions'),
        ]);

        return view('admin.data.student-profile', compact('student'));
    }

    /**
     * Profil guru: biodata + akun + kelas yang diampu (beserta jumlah siswa).
     */
    public function showTeacher(Teacher $teacher): View
    {
        $teacher->load(['user', 'classes.students', 'classes.academicYear']);

        return view('admin.data.teacher-profile', compact('teacher'));
    }

    /**
     * Aturan validasi siswa dipakai bersama store & update (DRY).
     * $ignoreId: id_students yang diabaikan saat cek unique (mode update).
     */
    private function studentRules(?int $ignoreId = null): array
    {
        $nik = Rule::unique('students', 'nik');
        $nisn = Rule::unique('students', 'nisn');
        if ($ignoreId) {
            $nik->ignore($ignoreId, 'id_students');
            $nisn->ignore($ignoreId, 'id_students');
        }

        // L1.2: profil murid DRY dari Student::profilRules(); override nik unique + nisn/kelas/status.
        $rules = Student::profilRules();
        $rules['nik'] = ['required', 'digits:16', $nik];
        $rules['nisn'] = ['nullable', 'digits:10', $nisn];
        $rules['id_class'] = ['nullable', 'exists:classes,id_classes'];
        $rules['status'] = ['required', 'in:aktif,lulus,nonaktif'];

        return $rules; // alamat kini di ortu (L1) — tak divalidasi di sini
    }

    /**
     * Menambah siswa (T5.2) — untuk input siswa lama. Terikat tahun ajaran aktif.
     */
    public function storeStudent(Request $request, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validate($this->studentRules() + [
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            ...ValidationRules::messages(),
            'nik.digits' => 'NIK harus tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'nisn.digits' => 'NISN harus tepat 10 digit angka.',
            'nisn.unique' => 'NISN ini sudah terdaftar.',
        ]);
        $data['id_academic_year'] = AcademicYear::where('is_aktif', true)->value('id_academic_years');
        $data['nisn'] = $data['nisn'] ?? null;
        $foto = $request->file('foto'); // simpan setelah create agar folder pakai PK (nama kembar)
        unset($data['foto']);

        $student = Student::create($data);
        // Foto opsional (siswa lama boleh belum punya) → profil/siswa/{Nama-id}/Foto_{Nama-id}.
        if ($foto) {
            $student->update(['foto_path' => $uploader->storeProfilePhoto($foto, 'siswa', $data['nama_lengkap'], $student->id_students)]);
        }
        AuditLogService::record('tambah_siswa', 'Student', null, ['nik' => $data['nik']]);

        return back()->with('success', 'Data murid berhasil ditambahkan.');
    }

    /**
     * Memperbarui data siswa (T5.2) — termasuk mengisi NISN yang baru terbit.
     */
    public function updateStudent(Request $request, Student $student, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validate($this->studentRules($student->id_students) + [
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            ...ValidationRules::messages(),
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'nisn.unique' => 'NISN ini sudah terdaftar.',
        ]);
        $data['nisn'] = $data['nisn'] ?? null;
        // Ganti foto (hapus lama agar tak menyampah); kosong = pertahankan foto lama.
        if ($request->hasFile('foto')) {
            if ($student->foto_path) {
                \Storage::disk('public')->delete($student->foto_path);
            }
            $data['foto_path'] = $uploader->storeProfilePhoto($request->file('foto'), 'siswa', $data['nama_lengkap'], $student->id_students);
        }
        unset($data['foto']);

        $student->update($data);
        AuditLogService::record('ubah_siswa', 'Student#'.$student->id_students);

        return back()->with('success', 'Data murid diperbarui.');
    }

    /**
     * Buatkan/tautkan akun wali untuk siswa lama (T9.1).
     *
     * Admin mengetik no_hp wali. firstOrCreate ortu by no_hp: nomor sama
     * (kakak-adik) menautkan ke akun wali yang sudah ada; nomor baru membuat
     * akun (username=password=no_hp, must_change_password=true → dipaksa ganti
     * saat login pertama). Lalu tautkan siswa ke ortu tsb.
     */
    public function createOrtuAccount(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ValidationRules::nama(),
            'no_hp' => ValidationRules::noHp(),
        ], ValidationRules::messages());

        if ($student->ortu?->user) {
            return back()->withErrors(['no_hp' => 'Murid ini sudah tertaut ke akun orang tua.']);
        }

        DB::transaction(function () use ($data, $student) {
            // Cari wali by no_hp; buat akun+profil bila belum ada (kakak-adik → 1 akun).
            // Lookup: users.no_hp (bukan parents — kolom parents.no_hp sudah dihapus).
            $ortu = OrangTua::whereHas('user', fn ($q) => $q->where('no_hp', $data['no_hp']))->first();

            if (! $ortu) {
                $user = User::create([
                    'username' => $data['no_hp'],
                    'no_hp' => $data['no_hp'],
                    'role' => 'ortu',
                    'password' => Hash::make($data['no_hp']),
                    'must_change_password' => true,
                ]);
                // L1.1: minimal ortu — default ibu (isi profil lengkap via wali.profile gate).
                $ortu = OrangTua::create([
                    'id_user' => $user->id_users,
                    'ada_ayah' => false, 'ada_ibu' => true,
                    'ibu_nama' => $data['nama'], 'ibu_no_hp' => $data['no_hp'],
                ]);
            }

            $student->update(['id_parent' => $ortu->id_parents]);
        });

        AuditLogService::record('buat_akun_wali', 'Student#'.$student->id_students, null, ['no_hp' => $data['no_hp']]);

        return back()->with('success', 'Akun orang tua dibuat/ditautkan. Login & password awal = No. HP orang tua.');
    }

    /**
     * Membuat akun guru baru (user role 'guru' + profil teacher + penugasan kelas).
     *
     * Auto-akun (T5.10): password awal = NUPTK (rules.md §1.5). Login guru pakai
     * no_hp (wajib) atau email (opsional — guru isi sendiri belakangan). username
     * = email bila ada, selain itu no_hp (fallback display). Dibungkus transaksi.
     */
    public function storeTeacher(Request $request, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ValidationRules::nama(),
            'email' => ['nullable', 'email', 'unique:users,email'],
            'no_hp' => [...ValidationRules::noHp(), 'unique:users,no_hp', 'unique:teachers,no_hp'],
            'nuptk' => [...ValidationRules::nuptk(), 'unique:teachers,nuptk'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'tanggal_mulai_mengajar' => ['nullable', 'date'],
            'riwayat_pendidikan' => ['nullable', 'string'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'provinsi_id' => ['nullable', 'string', 'max:10'], 'provinsi_nama' => ['nullable', 'string', 'max:255'],
            'kota_id' => ['nullable', 'string', 'max:10'], 'kota_nama' => ['nullable', 'string', 'max:255'],
            'kecamatan_id' => ['nullable', 'string', 'max:10'], 'kecamatan_nama' => ['nullable', 'string', 'max:255'],
            'kelurahan_id' => ['nullable', 'string', 'max:10'], 'kelurahan_nama' => ['nullable', 'string', 'max:255'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,id_classes'],
            'homeroom_class_id' => ['nullable', 'exists:classes,id_classes'],
        ], [
            ...ValidationRules::messages(),
            'no_hp.required' => 'Nomor HP wajib diisi (dipakai untuk login guru).',
            'no_hp.unique' => 'Nomor HP ini sudah digunakan.',
            'nuptk.unique' => 'NUPTK ini sudah terdaftar.',
        ]);

        // Foto guru opsional — disimpan setelah Teacher dibuat (folder pakai PK, nama kembar).
        $foto = $request->file('foto');

        DB::transaction(function () use ($data, $foto, $uploader) {
            // Akun login guru — password awal = NUPTK (rules.md §1.5).
            // username = email bila ada, selain itu no_hp (fallback display).
            $user = User::create([
                'username' => $data['email'] ?? $data['no_hp'],
                'email' => $data['email'] ?? null,
                'no_hp' => $data['no_hp'],
                'role' => 'guru',
                'password' => Hash::make($data['nuptk']),
                'must_change_password' => true, // T9.1: paksa ganti password awal (NUPTK)
            ]);

            // Profil guru (nama asli disimpan di sini — rules.md §1.6)
            $teacher = Teacher::create([
                'id_user' => $user->id_users,
                'nama' => $data['nama'],
                'nuptk' => $data['nuptk'],
                'no_hp' => $data['no_hp'],
                'jabatan' => $data['jabatan'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'tanggal_mulai_mengajar' => $data['tanggal_mulai_mengajar'] ?? null,
                'riwayat_pendidikan' => $data['riwayat_pendidikan'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ] + collect($data)->only([
                'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
                'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama',
            ])->all());

            // Foto → profil/guru/{Nama-id}/Foto_{Nama-id} (PK pembeda nama kembar).
            if ($foto) {
                $teacher->update(['foto_path' => $uploader->storeProfilePhoto($foto, 'guru', $data['nama'], $teacher->id_teachers)]);
            }

            // Penugasan ke kelas yang diampu (multi-kelas)
            if (! empty($data['class_ids'])) {
                $teacher->classes()->sync($data['class_ids']);
            }

            // K8.1: set kelas yang diwalikelaskan guru ini (bila dipilih).
            $this->setTeacherHomeroom($teacher->id_teachers, $data['homeroom_class_id'] ?? null);
        });

        AuditLogService::record('tambah_guru', 'Teacher', null, ['nuptk' => $data['nuptk']]);

        return back()->with('success', 'Akun guru berhasil dibuat. Password awal = NUPTK. Login pakai No. HP atau email.');
    }

    /**
     * Memperbarui data guru (T5.4): akun (email/HP) + profil (nama, NUPTK, TTL, riwayat).
     */
    public function updateTeacher(Request $request, Teacher $teacher, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ValidationRules::nama(),
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($teacher->id_user, 'id_users')],
            'no_hp' => [...ValidationRules::noHp(), Rule::unique('users', 'no_hp')->ignore($teacher->id_user, 'id_users'), Rule::unique('teachers', 'no_hp')->ignore($teacher->id_teachers, 'id_teachers')],
            'nuptk' => [...ValidationRules::nuptk(), Rule::unique('teachers', 'nuptk')->ignore($teacher->id_teachers, 'id_teachers')],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'tanggal_mulai_mengajar' => ['nullable', 'date'],
            'riwayat_pendidikan' => ['nullable', 'string'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'provinsi_id' => ['nullable', 'string', 'max:10'], 'provinsi_nama' => ['nullable', 'string', 'max:255'],
            'kota_id' => ['nullable', 'string', 'max:10'], 'kota_nama' => ['nullable', 'string', 'max:255'],
            'kecamatan_id' => ['nullable', 'string', 'max:10'], 'kecamatan_nama' => ['nullable', 'string', 'max:255'],
            'kelurahan_id' => ['nullable', 'string', 'max:10'], 'kelurahan_nama' => ['nullable', 'string', 'max:255'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,id_classes'],
            'homeroom_class_id' => ['nullable', 'exists:classes,id_classes'],
        ], [
            ...ValidationRules::messages(),
            'no_hp.required' => 'Nomor HP wajib diisi (dipakai untuk login guru).',
            'nuptk.unique' => 'NUPTK ini sudah terdaftar.',
        ]);

        DB::transaction(function () use ($data, $teacher, $request, $uploader) {
            // Ganti foto guru bila diunggah (hapus lama agar tak menyampah). Kosong = pertahankan.
            if ($request->hasFile('foto')) {
                if ($teacher->foto_path) {
                    \Storage::disk('public')->delete($teacher->foto_path);
                }
                $data['foto_path'] = $uploader->storeProfilePhoto($request->file('foto'), 'guru', $data['nama'], $teacher->id_teachers);
            }

            // Akun: email/HP (username selaras — email bila ada, else HP)
            $teacher->user->update([
                'username' => $data['email'] ?? $data['no_hp'],
                'email' => $data['email'] ?? null,
                'no_hp' => $data['no_hp'],
            ]);
            $teacher->update([
                'nama' => $data['nama'],
                'nuptk' => $data['nuptk'],
                'no_hp' => $data['no_hp'],
                'jabatan' => $data['jabatan'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'tanggal_mulai_mengajar' => $data['tanggal_mulai_mengajar'] ?? null,
                'riwayat_pendidikan' => $data['riwayat_pendidikan'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ] + collect($data)->only([
                'foto_path',
                'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
                'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama',
            ])->all());
            $teacher->classes()->sync($data['class_ids'] ?? []);
            // K8.1: set/lepas kelas yang diwalikelaskan guru ini.
            $this->setTeacherHomeroom($teacher->id_teachers, $data['homeroom_class_id'] ?? null);
        });

        AuditLogService::record('ubah_guru', 'Teacher#'.$teacher->id_teachers);

        return back()->with('success', 'Data guru diperbarui.');
    }

    /**
     * Memperbarui data tampilan web seorang guru (jabatan, foto, tampil_di_web)
     * untuk halaman Profil publik (rules.md §1.8).
     */
    public function updateTeacherWeb(Request $request, Teacher $teacher, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validate([
            'jabatan' => ['nullable', 'string', 'max:255'],
            'tampil_di_web' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $teacher->jabatan = $data['jabatan'] ?? null;
        $teacher->tampil_di_web = $request->boolean('tampil_di_web');

        // Simpan foto guru bila diunggah (hapus foto lama agar tak menyampah)
        if ($request->hasFile('foto')) {
            if ($teacher->foto_path) {
                \Storage::disk('public')->delete($teacher->foto_path);
            }
            $teacher->foto_path = $uploader->storeProfilePhoto($request->file('foto'), 'guru', $teacher->nama, $teacher->id_teachers);
        }
        $teacher->save();

        AuditLogService::record('update_guru_web', 'Teacher#'.$teacher->id_teachers);

        return back()->with('success', 'Data tampilan web guru diperbarui.');
    }

    /**
     * Membuat kelas baru pada tahun ajaran aktif.
     */
    public function storeClass(Request $request): RedirectResponse
    {
        $tahunAjaran = AcademicYear::where('is_aktif', true)->firstOrFail();

        // nama_kelas unik dalam satu tahun ajaran aktif (cegah "Kelas A" ganda).
        $data = $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classes', 'nama_kelas')->where(
                    fn ($q) => $q->where('id_academic_year', $tahunAjaran->id_academic_years),
                ),
            ],
            'id_homeroom_teacher' => ['nullable', 'exists:teachers,id_teachers'],
        ], [
            'nama_kelas.unique' => 'Nama kelas ini sudah ada pada tahun ajaran aktif.',
        ]);

        $class = SchoolClass::create([
            'id_academic_year' => $tahunAjaran->id_academic_years,
            'nama_kelas' => $data['nama_kelas'],
        ]);
        $this->setHomeroom($class, $data['id_homeroom_teacher'] ?? null);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    /**
     * Set wali kelas sebuah kelas + tegakkan "1 guru maks 1 kelas jadi wali":
     * kosongkan jabatan wali guru tsb di kelas lain sebelum menetapkan di sini.
     */
    private function setHomeroom(SchoolClass $class, ?int $teacherId): void
    {
        // Lepas jabatan wali guru lama di kelas ini (bila diganti guru lain).
        if ($class->id_homeroom_teacher && $class->id_homeroom_teacher !== $teacherId) {
            $this->clearWaliKelasJabatan($class->id_homeroom_teacher);
        }
        if ($teacherId) {
            SchoolClass::where('id_homeroom_teacher', $teacherId)
                ->where('id_classes', '!=', $class->id_classes)
                ->update(['id_homeroom_teacher' => null]);
            Teacher::where('id_teachers', $teacherId)->update(['jabatan' => 'WALI KELAS']);
        }
        $class->update(['id_homeroom_teacher' => $teacherId]);
    }

    /**
     * Set kelas yang diwalikelaskan seorang guru (dipakai dari form Guru).
     * Lepas jabatan wali guru ini di semua kelas, lalu pasang di $classId (bila ada).
     * Jabatan auto: "WALI KELAS" saat dipasang, dikosongkan saat dilepas.
     */
    private function setTeacherHomeroom(int $teacherId, ?int $classId): void
    {
        SchoolClass::where('id_homeroom_teacher', $teacherId)->update(['id_homeroom_teacher' => null]);
        if ($classId) {
            // Guru lama kelas tujuan kehilangan jabatan wali kelasnya (diganti guru ini).
            $lama = SchoolClass::where('id_classes', $classId)->value('id_homeroom_teacher');
            if ($lama && $lama !== $teacherId) {
                $this->clearWaliKelasJabatan($lama);
            }
            SchoolClass::where('id_classes', $classId)->update(['id_homeroom_teacher' => $teacherId]);
            Teacher::where('id_teachers', $teacherId)->update(['jabatan' => 'WALI KELAS']);
        } else {
            $this->clearWaliKelasJabatan($teacherId);
        }
    }

    /**
     * Kosongkan jabatan guru HANYA bila masih "WALI KELAS" (jangan hapus jabatan
     * manual seperti "Kepala Sekolah"). Dipanggil saat guru dilepas dari wali kelas.
     */
    private function clearWaliKelasJabatan(int $teacherId): void
    {
        Teacher::where('id_teachers', $teacherId)
            ->where('jabatan', 'WALI KELAS')
            ->update(['jabatan' => null]);
    }

    /**
     * Memperbarui nama kelas (T5.5) — unik per tahun ajaran, ignore diri sendiri.
     */
    public function updateClass(Request $request, SchoolClass $class): RedirectResponse
    {
        $data = $request->validate([
            'nama_kelas' => [
                'required', 'string', 'max:255',
                Rule::unique('classes', 'nama_kelas')
                    ->where(fn ($q) => $q->where('id_academic_year', $class->id_academic_year))
                    ->ignore($class->id_classes, 'id_classes'),
            ],
            'id_homeroom_teacher' => ['nullable', 'exists:teachers,id_teachers'],
        ], ['nama_kelas.unique' => 'Nama kelas ini sudah ada pada tahun ajaran aktif.']);

        $class->update(['nama_kelas' => $data['nama_kelas']]);
        $this->setHomeroom($class, $data['id_homeroom_teacher'] ?? null);
        AuditLogService::record('ubah_kelas', 'SchoolClass#'.$class->id_classes);

        return back()->with('success', 'Kelas diperbarui.');
    }

    /**
     * Menghapus kelas (T5.5). Siswa & pivot guru terlepas (FK nullOnDelete/cascade).
     */
    public function destroyClass(SchoolClass $class): RedirectResponse
    {
        $class->delete();
        AuditLogService::record('hapus_kelas', 'SchoolClass#'.$class->id_classes);

        return back()->with('success', 'Kelas dihapus.');
    }

    /**
     * Memperbarui penugasan kelas seorang guru (sinkron many-to-many).
     */
    public function assignClasses(Request $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validate([
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,id_classes'],
        ]);

        $teacher->classes()->sync($data['class_ids'] ?? []);

        AuditLogService::record('ubah_penugasan_guru', 'Teacher#'.$teacher->id_teachers, null, $data);

        return back()->with('success', 'Penugasan kelas guru diperbarui.');
    }
}
