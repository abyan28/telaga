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

        // L2.1: siswa hasil PPDB (punya formulir pendaftaran) baru RESMI masuk Data
        // Murid setelah dapat NIS (generate). Belum ber-NIS = masih calon (tab Calon Murid).
        // Siswa lama manual (tanpa formulir) tampil apa adanya.
        $q->where(fn ($w) => $w
            ->whereDoesntHave('registrationForms')
            ->orWhereNotNull('nis'));

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
     * L3.3: Data Orang Tua — daftar ortu (punya ≥1 anak) + filter kelas/status murid + search.
     * Rowspan per-anak di view; ortu diulang bila >1 anak.
     */
    public function parents(Request $request): View
    {
        $cari   = trim((string) $request->query('cari'));
        $kelas  = $request->query('kelas');
        $status = $request->query('status'); // ''=semua, aktif/nonaktif/lulus/dropout

        $parents = OrangTua::with([
                'students.schoolClass.homeroomTeacher',
            ])
            ->whereHas('students') // hanya ortu yang punya anak
            ->when($cari, fn ($q) => $q->where(fn ($w) => $w
                ->where('ayah_nama', 'like', "%{$cari}%")
                ->orWhere('ibu_nama', 'like', "%{$cari}%")
                ->orWhereHas('students', fn ($s) => $s->where('nama_lengkap', 'like', "%{$cari}%"))))
            ->when($kelas, fn ($q) => $q->whereHas('students', fn ($s) => $s->where('id_class', $kelas)))
            ->when($status, fn ($q) => $q->whereHas('students', fn ($s) => $s->where('status', $status)))
            ->orderBy('ibu_nama')
            ->paginate(25)
            ->withQueryString();

        $classes = SchoolClass::orderBy('nama_kelas')->get(['id_classes', 'nama_kelas']);

        return view('admin.data.parents', compact('parents', 'classes', 'cari', 'kelas', 'status'));
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
        $nis = Rule::unique('students', 'nis');
        if ($ignoreId) {
            $nik->ignore($ignoreId, 'id_students');
            $nisn->ignore($ignoreId, 'id_students');
            $nis->ignore($ignoreId, 'id_students');
        }

        // L1.2: profil murid DRY dari Student::profilRules(); override nik unique + nisn/nis/kelas/status.
        $rules = Student::profilRules();
        $rules['nik'] = ['required', 'digits:16', $nik];
        $rules['nisn'] = ['nullable', 'digits:10', $nisn];
        $rules['nis'] = ['nullable', 'digits_between:15,18', $nis];
        $rules['angkatan'] = ['nullable', 'integer', 'min:2000', 'max:2099'];
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
            'no_hp_ortu' => ['nullable', ...ValidationRules::noHp(false)], // L8.1: opsional; isi → buat akun ortu
        ], [
            ...ValidationRules::messages(),
            'nik.digits' => 'NIK harus tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'nisn.digits' => 'NISN harus tepat 10 digit angka.',
            'nisn.unique' => 'NISN ini sudah terdaftar.',
            'nis.digits_between' => 'NIS harus 15-18 digit angka.',
            'nis.unique' => 'NIS ini sudah terdaftar.',
        ]);
        $data['id_academic_year'] = AcademicYear::where('is_aktif', true)->value('id_academic_years');
        $data['nisn'] = $data['nisn'] ?? null;
        $data['nis'] = $data['nis'] ?? null;
        // L8.3: angkatan auto dari prefix NIS bila NIS diisi; else pakai input manual.
        $data['angkatan'] = Student::nisToAngkatan($data['nis']) ?? ($data['angkatan'] ?? null);
        $foto = $request->file('foto'); // simpan setelah create agar folder pakai PK (nama kembar)
        unset($data['foto']);

        $student = Student::create($data);
        // Foto opsional (siswa lama boleh belum punya) → profil/siswa/{Nama-id}/Foto_{Nama-id}.
        if ($foto) {
            $student->update(['foto_path' => $uploader->storeProfilePhoto($foto, 'siswa', $data['nama_lengkap'], $student->id_students)]);
        }
        // L8.1: no HP ortu diisi → buat/tautkan akun ortu (username & sandi awal = NIK anak).
        if ($noHpOrtu = ValidationRules::normalizeNoHp((string) $request->input('no_hp_ortu'))) {
            $this->linkOrtu($student, $noHpOrtu);
        }
        AuditLogService::record('tambah_siswa', 'Student', null, ['nik' => $data['nik']]);

        return back()->with('success', 'Data murid berhasil ditambahkan.'
            .($noHpOrtu ? ' Akun orang tua dibuat (login & sandi awal = NIK anak).' : ''));
    }

    /**
     * L8.1/T9.1: buat atau tautkan akun ortu ke siswa. Merge kakak-adik by users.no_hp
     * (nomor sama → 1 akun; password tak diubah). Akun baru: username=no_hp, password=NIK
     * anak, must_change_password. no_hp disimpan sebagai kontak Ibu.
     */
    private function linkOrtu(Student $student, string $noHp, ?string $namaIbu = null): void
    {
        DB::transaction(function () use ($student, $noHp, $namaIbu) {
            $ortu = OrangTua::whereHas('user', fn ($q) => $q->where('no_hp', $noHp))->first();

            if (! $ortu) {
                $user = User::create([
                    'username' => $noHp,
                    'no_hp' => $noHp,
                    'role' => 'ortu',
                    'password' => Hash::make($student->nik), // sandi awal = NIK anak (kakak bila kakak-adik)
                    'must_change_password' => true,
                ]);
                $ortu = OrangTua::create([
                    'id_user' => $user->id_users,
                    'ada_ayah' => false, 'ada_ibu' => true,
                    'ibu_nama' => $namaIbu, 'ibu_no_hp' => $noHp,
                ]);
            }

            $student->update(['id_parent' => $ortu->id_parents]);
        });
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
            'nis.unique' => 'NIS ini sudah terdaftar.',
            'nis.digits_between' => 'NIS harus 15-18 digit angka.',
        ]);
        $data['nisn'] = $data['nisn'] ?? null;
        $data['nis'] = $data['nis'] ?? null;
        // L8.3: angkatan auto dari NIS; fallback ke input manual; fallback ke nilai lama.
        $data['angkatan'] = Student::nisToAngkatan($data['nis']) ?? ($data['angkatan'] ?? $student->angkatan);
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

        $this->linkOrtu($student, $data['no_hp'], $data['nama']);

        AuditLogService::record('buat_akun_wali', 'Student#'.$student->id_students, null, ['no_hp' => $data['no_hp']]);

        return back()->with('success', 'Akun orang tua dibuat/ditautkan. Login = No. HP, sandi awal = NIK anak.');
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

    // Kolom CSV import murid lama (L8.1). Urutan = urutan kolom di file.
    private const IMPORT_COLUMNS = [
        'nama_lengkap', 'nama_panggilan', 'nik', 'nis', 'nisn',
        'jenis_kelamin', 'agama', 'tempat_lahir', 'tanggal_lahir',
        'anak_ke', 'jumlah_saudara', 'warga_negara', 'bahasa_keseharian',
        'kondisi_kesehatan', 'tahun_ajaran', 'status', 'angkatan', 'no_hp_ortu',
    ];

    /**
     * L8.1: halaman import CSV murid lama (upload + hasil).
     */
    public function importForm(): View
    {
        return view('admin.data.import');
    }

    /**
     * L8.1: unduh template CSV (header + 1 baris contoh) dari database/data.
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return response()->download(database_path('data/template-import-murid.csv'), 'template-import-murid.csv');
    }

    /**
     * L8.1: import massal murid lama dari CSV.
     *
     * Opsi B (skip+report): baris valid diinsert, NIK yang sudah ada dilewati
     * (append-only — admin cukup upload ulang file yang diperbaiki), baris
     * gagal dikumpulkan beserta alasan. TA & status default = aktif bila kosong.
     * no_hp_ortu diisi → auto-akun ortu via linkOrtu (username=no_hp, sandi=NIK).
     */
    public function importStudents(Request $request): RedirectResponse|View
    {
        $request->validate(
            ['file' => ['required', 'file', 'max:5120']],
            ['file.max' => 'Ukuran file maksimal 5 MB.']
        );

        $taAktif = AcademicYear::where('is_aktif', true)->value('id_academic_years');
        $handle = fopen($request->file('file')->getRealPath(), 'r');

        // Baca & petakan header → indeks kolom (case-insensitive, buang BOM/spasi).
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'File CSV kosong.']);
        }
        $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]); // buang BOM Excel
        $map = [];
        foreach ($header as $i => $name) {
            $map[strtolower(trim((string) $name))] = $i;
        }

        $ok = 0;
        $skip = 0; // NIK sudah ada
        $errors = []; // ["Baris N: pesan", ...]
        $baris = 1; // header = baris 1

        while (($row = fgetcsv($handle)) !== false) {
            $baris++;
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // baris kosong
            }

            // Ambil nilai per kolom lewat header map (kolom hilang → '').
            $data = [];
            foreach (self::IMPORT_COLUMNS as $col) {
                $data[$col] = isset($map[$col]) ? trim((string) ($row[$map[$col]] ?? '')) : '';
            }

            // Normalkan string kosong ke null untuk kolom opsional unik.
            foreach (['nis', 'nisn', 'nama_panggilan'] as $col) {
                if ($data[$col] === '') {
                    $data[$col] = null;
                }
            }

            // Default: agama Islam, TA aktif, status aktif (sesuai kesepakatan).
            $data['agama'] = $data['agama'] ?: 'ISLAM';
            $data['status'] = $data['status'] ?: 'aktif';

            // Field non-form yang dipakai insert (bukan bagian profilRules).
            $noHpOrtu = ValidationRules::normalizeNoHp($data['no_hp_ortu']) ?: null;
            $tahunAjaran = $data['tahun_ajaran'];
            unset($data['no_hp_ortu'], $data['tahun_ajaran']);

            // Skip NIK duplikat (append-only re-import).
            if ($data['nik'] !== '' && Student::where('nik', $data['nik'])->exists()) {
                $skip++;

                continue;
            }

            // Validasi per baris: profil murid + NIS/NISN opsional. Field cabang
            // (ngaji/belajar) tak di CSV → beri default agar required_if tak gagal.
            $data['sudah_mengaji'] = $data['sudah_mengaji'] ?? '';
            $rules = Student::profilRules();
            $rules['sudah_mengaji'] = ['nullable', 'in:Sudah,Belum'];
            $rules['pernah_belajar'] = ['nullable', 'in:PAUD,Les,Belum'];
            $rules['nama_panggilan'] = ['required', 'string', 'max:100'];
            $rules['nisn'] = ['nullable', 'digits:10', 'unique:students,nisn'];
            $rules['nis'] = ['nullable', 'digits_between:15,18', 'unique:students,nis'];
            $rules['nik'] = ['required', 'digits:16', 'unique:students,nik'];
            unset($rules['ukuran_baju'], $rules['ngaji_dimana'], $rules['ngaji_metode'],
                $rules['ngaji_jilid'], $rules['belajar_keterangan']);

            $v = \Validator::make($data, $rules, ValidationRules::messages());
            if ($v->fails()) {
                $errors[] = 'Baris '.$baris.': '.$v->errors()->first();

                continue;
            }
            $valid = $v->validated();

            // Normalkan enum kosong → null agar SQLite CHECK constraint tidak gagal.
            foreach (['sudah_mengaji', 'pernah_belajar', 'ukuran_baju'] as $col) {
                if (isset($valid[$col]) && $valid[$col] === '') {
                    $valid[$col] = null;
                }
            }

            // Resolve tahun ajaran (nama "2026/2027" → id); kosong/invalid → TA aktif.
            $valid['id_academic_year'] = $tahunAjaran
                ? (AcademicYear::where('tahun', $tahunAjaran)->value('id_academic_years') ?: $taAktif)
                : $taAktif;
            $valid['nisn'] = $valid['nisn'] ?? null;
            $valid['nis'] = $valid['nis'] ?? null;
            // L8.3: angkatan auto dari NIS; fallback ke kolom angkatan di CSV bila ada.
            $valid['angkatan'] = Student::nisToAngkatan($valid['nis']) ?? (($data['angkatan'] ?? '') !== '' ? (int) $data['angkatan'] : null);

            $student = Student::create($valid);
            if ($noHpOrtu) {
                $this->linkOrtu($student, $noHpOrtu);
            }
            $ok++;
        }
        fclose($handle);

        AuditLogService::record('import_siswa_csv', 'Student', null, ['ok' => $ok, 'skip' => $skip, 'gagal' => count($errors)]);

        // Tampilkan hasil di halaman yang sama (ringkasan + daftar error).
        return view('admin.data.import', compact('ok', 'skip', 'errors'));
    }
}
