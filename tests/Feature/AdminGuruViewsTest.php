<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\RegistrationForm;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Smoke test halaman admin & guru yang di-bind di Tahap 3: memastikan tiap
 * halaman merender (HTTP 200) dengan data asli, bukan sekadar compile.
 */
class AdminGuruViewsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
    }

    private function admin(): User
    {
        return User::create(['username' => 'Admin', 'email' => 'a@test.id', 'role' => 'admin', 'password' => Hash::make('x')]);
    }

    /**
     * Menyiapkan 1 pendaftaran lengkap (wali+anak+form) untuk halaman admin.
     */
    private function seedRegistration(): RegistrationForm
    {
        $wali = User::create(['username' => 'Wali', 'email' => 'w@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($wali->id_users);
        $s = Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $this->ta->id_academic_years,
            'nik' => '3273010101010001', 'nama_lengkap' => 'Budi Uji', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);

        return RegistrationForm::create([
            'id_user' => $wali->id_users, 'id_student' => $s->id_students,
            'id_academic_year' => $this->ta->id_academic_years, 'status' => 'menunggu_verifikasi',
        ]);
    }

    public function test_admin_registrations_list_renders(): void
    {
        $this->seedRegistration();
        $this->actingAs($this->admin())->get('/portal/admin/registrations')
            ->assertOk()->assertSee('Budi Uji');
    }

    public function test_registrations_filtered_by_academic_year(): void
    {
        $this->seedRegistration(); // TA aktif → tampil default
        // Form TA lain (nonaktif) → tersembunyi saat filter default (TA aktif)
        $taLama = \App\Models\AcademicYear::create(['tahun' => '2020/2021', 'is_aktif' => false]);
        $wali = User::create(['username' => 'W2', 'email' => 'w2@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($wali->id_users);
        $s = Student::create(['id_parent' => $g->id_parents, 'id_academic_year' => $taLama->id_academic_years, 'nik' => '3273010101019999', 'nama_lengkap' => 'Siswa Lama', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2019-01-01', 'status' => 'aktif']);
        RegistrationForm::create(['id_user' => $wali->id_users, 'id_student' => $s->id_students, 'id_academic_year' => $taLama->id_academic_years, 'status' => 'menunggu_verifikasi']);

        $this->actingAs($admin = $this->admin())->get('/portal/admin/registrations')
            ->assertOk()->assertSee('Budi Uji')->assertDontSee('Siswa Lama');
        $this->actingAs($admin)->get('/portal/admin/registrations?tahun='.$taLama->id_academic_years)
            ->assertOk()->assertSee('Siswa Lama')->assertDontSee('Budi Uji');
    }

    public function test_admin_registration_detail_renders(): void
    {
        $form = $this->seedRegistration();
        $this->actingAs($this->admin())->get("/portal/admin/registrations/{$form->slug}")
            ->assertOk()->assertSee('Budi Uji');
    }

    public function test_admin_data_pages_render(): void
    {
        $u = User::create(['username' => 'Ustadz X', 'email' => 'g@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        Teacher::create(['id_user' => $u->id_users, 'nama' => 'Ustadz X', 'nuptk' => '9000000000000001', 'no_hp' => '08222']);
        SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'Kelas Uji']);

        $admin = $this->admin();
        // Tab terpisah (T5.1): guru & kelas punya halaman masing-masing.
        $this->actingAs($admin)->get('/portal/admin/data/teachers')
            ->assertOk()->assertSee('Ustadz X');
        $this->actingAs($admin)->get('/portal/admin/data/classes')
            ->assertOk()->assertSee('Kelas Uji');
        $this->actingAs($admin)->get('/portal/admin/data/students')->assertOk();

        // Profil siswa (T5.3) render dengan biodata.
        $siswa = Student::create([
            'id_academic_year' => $this->ta->id_academic_years, 'nik' => '3273010101013333',
            'nama_lengkap' => 'Profil Anak', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01',
        ]);
        $this->actingAs($admin)->get("/portal/admin/data/students/{$siswa->slug}")
            ->assertOk()->assertSee('Profil Anak')->assertSee('Riwayat Pembayaran');
    }

    /**
     * T5.2: CRUD siswa — tambah (NISN opsional), edit isi NISN, tolak NIK duplikat.
     */
    public function test_student_crud(): void
    {
        $admin = $this->admin();
        $kelas = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'A']);

        $l12 = ['nama_panggilan' => 'Siti', 'agama' => 'ISLAM', 'anak_ke' => 1, 'jumlah_saudara' => 0,
            'warga_negara' => 'WNI', 'bahasa_keseharian' => 'INDONESIA', 'kondisi_kesehatan' => 'SEHAT',
            'ukuran_baju' => 'S', 'sudah_mengaji' => 'Belum', 'pernah_belajar' => 'Belum'];
        // Tambah siswa lama tanpa NISN
        $this->actingAs($admin)->post('/portal/admin/data/students', $l12 + [
            'nama_lengkap' => 'Siti Lama', 'nik' => '3273010101012222',
            'jenis_kelamin' => 'P', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2020-05-01',
            'id_class' => $kelas->id_classes, 'status' => 'aktif',
        ])->assertRedirect();
        $siswa = Student::where('nik', '3273010101012222')->first();
        $this->assertNotNull($siswa);
        $this->assertNull($siswa->nisn);

        // Edit: isi NISN
        $this->actingAs($admin)->put("/portal/admin/data/students/{$siswa->slug}", $l12 + [
            'nama_lengkap' => 'Siti Lama', 'nik' => '3273010101012222', 'nisn' => '0011223344',
            'jenis_kelamin' => 'P', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2020-05-01',
            'status' => 'aktif',
        ])->assertRedirect();
        $this->assertSame('0011223344', $siswa->fresh()->nisn);

        // Tolak NIK duplikat
        $this->actingAs($admin)->post('/portal/admin/data/students', $l12 + [
            'nama_lengkap' => 'Dobel', 'nik' => '3273010101012222',
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01', 'status' => 'aktif',
        ])->assertSessionHasErrors('nik');
    }

    /**
     * P4.2: tab Set Pembayaran render + simpan semua nominal (bukan cuma pendaftaran).
     */
    public function test_admin_settings_page_and_save_all_nominals(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/portal/admin/settings')->assertOk()->assertSee('Biaya Daftar Ulang');

        $this->actingAs($admin)->post('/portal/admin/settings', [
            'nominal_pendaftaran' => 150000, 'nominal_spp' => 250000,
            'nominal_daftar_ulang' => 1500000, 'tanggal_generate_spp' => 5, 'kuota_pendaftaran' => 40,
        ])->assertRedirect();

        $this->assertSame('250000', \App\Models\Setting::get('nominal_spp'));
        $this->assertSame('1500000', \App\Models\Setting::get('nominal_daftar_ulang'));
    }

    /**
     * P4.1: kartu dashboard mengambil nilai dari DB (lulus count + kuota %).
     */
    public function test_admin_dashboard_cards_from_db(): void
    {
        $admin = $this->admin();
        \App\Models\Setting::set('kuota_pendaftaran', 10);
        // 2 form lulus + 1 masih diproses.
        foreach (['lulus', 'lulus', 'diproses_seleksi'] as $st) {
            RegistrationForm::create([
                'id_user' => $admin->id_users, 'id_academic_year' => $this->ta->id_academic_years, 'status' => $st,
            ]);
        }

        $this->actingAs($admin)->get('/portal/admin')
            ->assertOk()
            ->assertSee('20% dari kuota 10'); // 2/10
    }

    /**
     * K6.3/K6.1: dashboard memisah pending PPDB vs Pembayaran (breakdown jenis),
     * dan badge sidebar menampilkan jumlah per tab (bukti bayar + dokumen pending).
     */
    public function test_dashboard_split_and_sidebar_badge(): void
    {
        $admin = $this->admin();
        $form = $this->seedRegistration();
        $sid = $form->id_student;

        // 1 bukti bayar pendaftaran + 1 daftar ulang + 1 SPP, semua pending.
        foreach ([['pendaftaran', 150000], ['daftar_ulang', 200000], ['spp', 100000]] as [$jenis, $jml]) {
            \App\Models\PaymentTransaction::create([
                'id_student' => $sid, 'id_user' => $form->id_user, 'jenis' => $jenis,
                'jumlah' => $jml, 'bukti_path' => 'x.jpg', 'status' => 'pending', 'tanggal_bayar' => '2026-08-01',
            ]);
        }
        // 1 dokumen pendaftaran belum diverifikasi → masuk badge/card PPDB.
        \App\Models\RegistrationDocument::create([
            'id_registration_form' => $form->id_registration_forms, 'jenis' => 'kk',
            'path' => 'kk.jpg', 'status' => 'pending',
        ]);

        $res = $this->actingAs($admin)->get('/portal/admin')->assertOk();
        // Card Pembayaran breakdown: 1 daftar ulang, 1 SPP.
        $res->assertSee('Pending Pembayaran')->assertSee('1 daftar ulang, 1 SPP');
        // Card PPDB = 1 bayar pendaftaran + 1 dokumen = 2.
        $res->assertSee('Pending PPDB');
        // Badge sidebar: PPDB=2, SPP=1 harus tampil.
        $res->assertSee('bg-rose-500', false);
    }

    /**
     * T5.4: edit guru (bio + kelas).
     */
    public function test_teacher_update(): void
    {
        $admin = $this->admin();
        $kelas = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'B']);
        $u = User::create(['username' => 'Guru Lama', 'email' => 'gl@test.id', 'no_hp' => '628111', 'role' => 'guru', 'password' => Hash::make('x')]);
        $t = Teacher::create(['id_user' => $u->id_users, 'nama' => 'Guru Lama', 'nuptk' => '7700000000000001', 'no_hp' => '628111']);

        $this->actingAs($admin)->put("/portal/admin/teachers/{$t->slug}", [
            'nama' => 'Guru Baru', 'email' => 'gl@test.id', 'nuptk' => '7700000000000001', 'no_hp' => '081100000001',
            'tempat_lahir' => 'Solo', 'tanggal_lahir' => '1990-01-01', 'riwayat_pendidikan' => 'S1 PGSD',
            'alamat' => 'Jl. Guru 1', 'provinsi_id' => '33', 'provinsi_nama' => 'Jawa Tengah',
            'class_ids' => [$kelas->id_classes],
        ])->assertRedirect();
        $t->refresh();
        $this->assertSame('GURU BARU', $t->nama);
        $this->assertSame('SOLO', $t->tempat_lahir);
        $this->assertSame('JAWA TENGAH', $t->provinsi_nama); // T2.1+T2.4: alamat wilayah tersimpan uppercase via admin CMS
        $this->assertCount(1, $t->classes);
    }

    /**
     * T5.5: edit & hapus kelas.
     */
    public function test_class_update_and_destroy(): void
    {
        $admin = $this->admin();
        $k = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'Lama']);

        $this->actingAs($admin)->put("/portal/admin/classes/{$k->id_classes}", ['nama_kelas' => 'Baru'])->assertRedirect();
        $this->assertSame('BARU', $k->fresh()->nama_kelas); // T2.4: uppercase

        $this->actingAs($admin)->delete("/portal/admin/classes/{$k->id_classes}")->assertRedirect();
        $this->assertNull(SchoolClass::find($k->id_classes));
    }

    /**
     * T5.6a/b: buka/tutup PPDB, buat tahun ajaran baru, halaman daftar ulang.
     */
    public function test_ppdb_controls_and_daftar_ulang(): void
    {
        $admin = $this->admin();

        // Tutup PPDB
        $this->actingAs($admin)->post('/portal/admin/ppdb/toggle', ['dibuka' => 0])->assertRedirect();
        $this->assertSame('0', \App\Models\Setting::get('pendaftaran_dibuka'));

        // L2.4: navigasi TA PPDB "next" buat TA berikutnya & set target (TIDAK aktifkan).
        $this->actingAs($admin)->post('/portal/admin/settings/academic-year', ['scope' => 'ppdb', 'arah' => 'next'])->assertRedirect();
        $ta2 = AcademicYear::where('tahun', '2027/2028')->first();
        $this->assertNotNull($ta2);
        $this->assertFalse((bool) $ta2->is_aktif);              // bukan TA sistem
        $this->assertTrue((bool) $this->ta->fresh()->is_aktif); // TA aktif tak berubah
        $this->assertSame((string) $ta2->id_academic_years, \App\Models\Setting::get('ta_ppdb'));

        $this->actingAs($admin)->get('/portal/admin/daftar-ulang')->assertOk();
        $this->actingAs($admin)->get('/portal/admin/spp')->assertOk(); // T5.6h
    }

    /**
     * T5.6a: gate — wali tak bisa buka form pendaftaran saat PPDB ditutup (403).
     */
    public function test_wali_blocked_when_ppdb_closed(): void
    {
        \App\Models\Setting::set('pendaftaran_dibuka', '0');
        $wali = User::create(['username' => 'W', 'email' => 'w@t.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $this->completeOrtu($wali->id_users);

        // K2.7: GET form saat ditutup → halaman info dalam layout (bukan 403 telanjang).
        $this->actingAs($wali)->get('/portal/ortu/register')
            ->assertOk()->assertSee('Pendaftaran Sedang Ditutup');
        // Guard POST tetap 403 (tak bisa dilewati via submit langsung).
        $this->actingAs($wali)->post('/portal/ortu/register', [])->assertForbidden();
    }

    /**
     * T5.6i: CRUD akun admin — tambah, tolak hapus diri sendiri, hapus admin lain.
     */
    public function test_admin_account_crud(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/portal/admin/accounts')->assertOk();
        // Buat admin baru
        $this->actingAs($admin)->post('/portal/admin/accounts', [
            'name' => 'Admin_Dua', 'email' => 'admin2@test.id', 'password' => 'rahasia8',
        ])->assertRedirect();
        $baru = User::where('email', 'admin2@test.id')->first();
        $this->assertSame('admin', $baru->role);

        // Tolak hapus diri sendiri.
        $this->actingAs($admin)->delete("/portal/admin/accounts/{$admin->id_users}")->assertSessionHasErrors('akun');
        $this->assertNotNull(User::find($admin->id_users));

        // Hapus admin lain.
        $this->actingAs($admin)->delete("/portal/admin/accounts/{$baru->id_users}")->assertRedirect();
        $this->assertNull(User::find($baru->id_users));
    }

    public function test_admin_reports_page_renders(): void
    {
        // K0.3: audit log dari DB (bukan mock statik).
        \App\Services\AuditLogService::record('uji_audit', 'Test#1');
        $this->actingAs($this->admin())->get('/portal/admin/reports')
            ->assertOk()->assertSee('uji audit');
    }

    public function test_guru_dashboard_renders_with_students(): void
    {
        $gu = User::create(['username' => 'Guru', 'email' => 'guru@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        $teacher = Teacher::create(['id_user' => $gu->id_users, 'nama' => 'Guru', 'nuptk' => '1000000000000001', 'no_hp' => '08333']);
        $kelas = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'Kelas A']);
        $teacher->classes()->sync([$kelas->id_classes]);
        Student::create([
            'id_class' => $kelas->id_classes, 'id_academic_year' => $this->ta->id_academic_years,
            'nik' => '3273010101019999', 'nama_lengkap' => 'Murid Guru', 'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);

        $this->actingAs($gu)->get('/portal/guru')
            ->assertOk()->assertSee('Murid Guru')->assertSee('Kelas A');
    }

    /**
     * K5.1: siswa PPDB (punya formulir) tersembunyi dari Data Siswa sampai bayar
     * daftar ulang; siswa lama manual (tanpa formulir) selalu tampil. K5.4: toggle
     * status guru (nonaktif = keluar) + tersembunyi dari list default.
     */
    public function test_k5_student_filter_and_teacher_toggle(): void
    {
        $admin = $this->admin();
        $wali = User::create(['username' => 'WaliK5', 'email' => 'wk5@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($wali->id_users);

        // Siswa PPDB belum bayar daftar ulang → tersembunyi.
        $ppdb = Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $this->ta->id_academic_years,
            'nik' => '3273010101015001', 'nama_lengkap' => 'PPDB Belum Bayar', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'X', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);
        RegistrationForm::create(['id_user' => $wali->id_users, 'id_student' => $ppdb->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'status' => 'lulus']);
        \App\Models\ReRegistrationPayment::create(['id_student' => $ppdb->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'total_biaya' => 500000, 'jumlah_terbayar' => 0, 'status' => 'belum_lunas']);

        // Siswa lama manual (tanpa formulir) → selalu tampil.
        Student::create([
            'id_academic_year' => $this->ta->id_academic_years, 'nik' => '3273010101015002',
            'nama_lengkap' => 'Siswa Lama Manual', 'jenis_kelamin' => 'P', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01', 'status' => 'aktif',
        ]);

        $this->actingAs($admin)->get('/portal/admin/data/students')
            ->assertOk()->assertSee('Siswa Lama Manual')->assertDontSee('PPDB Belum Bayar');

        // L2.1: bayar DU saja (terbayar>0) TIDAK cukup — masih calon, belum tampil di Data Murid.
        \App\Models\ReRegistrationPayment::where('id_student', $ppdb->id_students)->update(['jumlah_terbayar' => 100000, 'status' => 'kurang']);
        $this->actingAs($admin)->get('/portal/admin/data/students')->assertDontSee('PPDB Belum Bayar');

        // Setelah dapat NIS (resmi jadi murid) → muncul di Data Murid.
        $ppdb->update(['nis' => '101234567890260001']);
        $this->actingAs($admin)->get('/portal/admin/data/students')->assertSee('PPDB Belum Bayar');

        // K5.4: toggle guru → nonaktif, hilang dari list default, muncul di filter nonaktif.
        $gu = User::create(['username' => '081200000099', 'no_hp' => '081200000099', 'role' => 'guru', 'password' => Hash::make('x')]);
        $t = Teacher::create(['id_user' => $gu->id_users, 'nama' => 'GURU TOGGLE', 'nuptk' => '5000000000000001', 'no_hp' => '081200000099']);
        $this->actingAs($admin)->post("/portal/admin/teachers/{$t->slug}/toggle-status")->assertRedirect();
        $this->assertFalse($t->fresh()->is_aktif);
        $this->actingAs($admin)->get('/portal/admin/data/teachers')->assertDontSee('GURU TOGGLE');
        $this->actingAs($admin)->get('/portal/admin/data/teachers?status=nonaktif')->assertSee('GURU TOGGLE');
    }

    /**
     * K9.3: pagination server-side Data Siswa (25/halaman). Siswa lama manual
     * (tanpa formulir) selalu tampil → cukup untuk uji paginasi.
     */
    public function test_students_paginated_25_per_page(): void
    {
        // 26 siswa manual bernama urut → yg ke-26 (Zzz) jatuh di halaman 2.
        for ($i = 1; $i <= 26; $i++) {
            $nama = $i === 26 ? 'ZZZ SISWA AKHIR' : 'AAA Siswa '.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            Student::create([
                'id_academic_year' => $this->ta->id_academic_years,
                'nik' => '32730101010'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'nama_lengkap' => $nama, 'jenis_kelamin' => 'L',
                'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01', 'status' => 'aktif',
            ]);
        }
        $admin = $this->admin();
        // Halaman 1: 25 baris, siswa ke-26 belum tampil.
        $this->actingAs($admin)->get('/portal/admin/data/students')
            ->assertOk()->assertDontSee('ZZZ SISWA AKHIR')->assertSee('page=2');
        // Halaman 2: siswa ke-26 muncul.
        $this->actingAs($admin)->get('/portal/admin/data/students?page=2')
            ->assertOk()->assertSee('ZZZ SISWA AKHIR');
    }

    /**
     * Foto profil: admin upload pas foto saat tambah siswa & guru → foto_path tersimpan.
     */
    public function test_admin_can_upload_photo_for_student_and_teacher(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/portal/admin/data/students', [
            'nama_lengkap' => 'Siswa Foto', 'nama_panggilan' => 'Siswa', 'nik' => '3273010101017777',
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01', 'status' => 'aktif',
            'agama' => 'ISLAM', 'anak_ke' => 1, 'jumlah_saudara' => 0, 'warga_negara' => 'WNI',
            'bahasa_keseharian' => 'INDONESIA', 'kondisi_kesehatan' => 'SEHAT', 'ukuran_baju' => 'S',
            'sudah_mengaji' => 'Belum', 'pernah_belajar' => 'Belum',
            'foto' => UploadedFile::fake()->image('pas.jpg'),
        ])->assertRedirect();
        $siswa = Student::where('nik', '3273010101017777')->first();
        $this->assertNotNull($siswa->foto_path);
        Storage::disk('public')->assertExists($siswa->foto_path);

        $this->actingAs($admin)->post('/portal/admin/teachers', [
            'nama' => 'Guru Foto', 'no_hp' => '081277778888', 'nuptk' => '7000000000000001',
            'foto' => UploadedFile::fake()->image('guru.jpg'),
        ])->assertRedirect();
        $guru = Teacher::where('nuptk', '7000000000000001')->first();
        $this->assertNotNull($guru->foto_path);
        Storage::disk('public')->assertExists($guru->foto_path);
    }

    /**
     * Foto guru via EDIT (updateTeacher): dulu diabaikan (tak divalidasi/disimpan) — regresi.
     * Sekaligus verifikasi nama kembar tak saling menimpa (folder di-suffix PK guru).
     */
    public function test_admin_can_upload_photo_when_editing_teacher_and_twins_dont_collide(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        // Dua guru bernama SAMA persis.
        $this->actingAs($admin)->post('/portal/admin/teachers', [
            'nama' => 'AHMAD FAUZI', 'no_hp' => '081200000001', 'nuptk' => '7100000000000001',
        ])->assertRedirect();
        $this->actingAs($admin)->post('/portal/admin/teachers', [
            'nama' => 'AHMAD FAUZI', 'no_hp' => '081200000002', 'nuptk' => '7100000000000002',
        ])->assertRedirect();

        $g1 = Teacher::where('nuptk', '7100000000000001')->first();
        $g2 = Teacher::where('nuptk', '7100000000000002')->first();

        // Upload foto guru pertama via EDIT (PUT) — inti bug yang diperbaiki.
        $this->actingAs($admin)->put('/portal/admin/teachers/'.$g1->slug, [
            'nama' => 'AHMAD FAUZI', 'no_hp' => '081200000001', 'nuptk' => '7100000000000001',
            'foto' => UploadedFile::fake()->image('a.jpg'),
        ])->assertRedirect();

        // Upload foto guru kedua via EDIT.
        $this->actingAs($admin)->put('/portal/admin/teachers/'.$g2->slug, [
            'nama' => 'AHMAD FAUZI', 'no_hp' => '081200000002', 'nuptk' => '7100000000000002',
            'foto' => UploadedFile::fake()->image('b.jpg'),
        ])->assertRedirect();

        $g1->refresh();
        $g2->refresh();
        // Keduanya tersimpan…
        $this->assertNotNull($g1->foto_path);
        $this->assertNotNull($g2->foto_path);
        Storage::disk('public')->assertExists($g1->foto_path);
        Storage::disk('public')->assertExists($g2->foto_path);
        // …dan TIDAK ke path yang sama (nama kembar dibedakan oleh PK).
        $this->assertNotSame($g1->foto_path, $g2->foto_path);
    }

    /**
     * NUPTK wajib tepat 16 digit — non-16 ditolak validasi (store & update).
     */
    public function test_nuptk_must_be_16_digits(): void
    {
        $admin = $this->admin();

        // Store: 10 digit ditolak.
        $this->actingAs($admin)->post('/portal/admin/teachers', [
            'nama' => 'GURU NUPTK', 'no_hp' => '081299990001', 'nuptk' => '1234567890',
        ])->assertSessionHasErrors('nuptk');
        $this->assertDatabaseMissing('teachers', ['nuptk' => '1234567890']);

        // Store 16 digit lolos, lalu update ke non-16 ditolak.
        $this->actingAs($admin)->post('/portal/admin/teachers', [
            'nama' => 'GURU NUPTK', 'no_hp' => '081299990002', 'nuptk' => '1234567890123456',
        ])->assertRedirect();
        $t = Teacher::where('nuptk', '1234567890123456')->first();
        $this->actingAs($admin)->put('/portal/admin/teachers/'.$t->slug, [
            'nama' => 'GURU NUPTK', 'no_hp' => '081299990002', 'nuptk' => '999',
        ])->assertSessionHasErrors('nuptk');
    }

    /**
     * Filter status siswa default 'aktif': siswa nonaktif tersembunyi kecuali ?status=semua.
     */
    public function test_student_status_filter_defaults_to_aktif(): void
    {
        $admin = $this->admin();
        $ta = \App\Models\AcademicYear::where('is_aktif', true)->first();
        $base = ['jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01', 'id_academic_year' => $ta->id_academic_years];
        Student::create($base + ['nama_lengkap' => 'SISWA AKTIF', 'nik' => '3400000000000001', 'status' => 'aktif']);
        Student::create($base + ['nama_lengkap' => 'SISWA KELUAR', 'nik' => '3400000000000002', 'status' => 'nonaktif']);

        // Default (tanpa query): hanya aktif.
        $this->actingAs($admin)->get('/portal/admin/data/students')->assertSee('SISWA AKTIF')->assertDontSee('SISWA KELUAR');
        // ?status=semua: keduanya tampil.
        $this->actingAs($admin)->get('/portal/admin/data/students?status=semua')->assertSee('SISWA AKTIF')->assertSee('SISWA KELUAR');
    }

    /**
     * L2.1: alur calon murid — batal (refund) & generate NIS (gated PPDB tutup).
     */
    public function test_calon_murid_cancel_and_generate_nis(): void
    {
        $admin = $this->admin();
        $wali = User::create(['username' => 'WaliCalon', 'email' => 'wc@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        \App\Models\Setting::set('nsm_sekolah', '101234567890');
        \App\Models\Setting::set('persen_refund', '20'); // denda 20%

        // A lunas (1jt) → refund = 1jt−200k = 800k.
        // B cicil 500k (denda 200k, di atas denda) → refund = 500k−200k = 300k.
        $mk = function (string $nama, string $nik, float $bayar) use ($wali): Student {
            $s = Student::create([
                'id_academic_year' => $this->ta->id_academic_years, 'nik' => $nik,
                'nama_lengkap' => $nama, 'jenis_kelamin' => 'L', 'tempat_lahir' => 'X',
                'tanggal_lahir' => '2020-01-01', 'status' => 'aktif',
            ]);
            RegistrationForm::create(['id_user' => $wali->id_users, 'id_student' => $s->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'status' => 'lulus']);
            \App\Models\ReRegistrationPayment::create(['id_student' => $s->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'total_biaya' => 1000000, 'jumlah_terbayar' => $bayar, 'status' => 'kurang']);
            return $s;
        };
        $a = $mk('AAA CALON', '3400000000009001', 1000000);
        $b = $mk('BBB CALON', '3400000000009002', 500000);

        // Halaman calon murid tampil keduanya.
        $this->actingAs($admin)->get('/portal/admin/calon-murid')->assertOk()->assertSee('AAA CALON')->assertSee('BBB CALON');

        // Batal A (lunas 1jt, denda 200k → refund 800k tercatat).
        $this->actingAs($admin)->post("/portal/admin/calon-murid/{$a->slug}/cancel")->assertRedirect();
        $this->assertDatabaseHas('registration_forms', ['id_student' => $a->id_students, 'status' => 'dibatalkan']);
        $this->assertDatabaseHas('payment_transactions', ['id_student' => $a->id_students, 'jenis' => 'refund', 'jumlah' => 800000]);

        // Generate NIS gagal saat PPDB masih buka.
        $this->actingAs($admin)->post('/portal/admin/calon-murid/generate-nis')->assertSessionHasErrors('nis');

        // Tutup PPDB → generate NIS untuk sisa calon (B). Format NSM+YY+urut.
        \App\Models\Setting::set('pendaftaran_dibuka', '0');
        $this->actingAs($admin)->post('/portal/admin/calon-murid/generate-nis')->assertRedirect();
        $this->assertSame('101234567890' . '26' . '001', $b->fresh()->nis);
        $this->assertSame('aktif', $b->fresh()->status);

        // B kini resmi di Data Murid; halaman calon murid kosong dari B.
        $this->actingAs($admin)->get('/portal/admin/data/students')->assertSee('BBB CALON');
        $this->actingAs($admin)->get('/portal/admin/calon-murid')->assertDontSee('BBB CALON');
    }
}