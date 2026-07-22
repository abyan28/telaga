<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentProgressNote;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji Fase 6 (Alur Guru) — fokus pada query guard anti-kebocoran antar-kelas
 * (PRD §13): guru hanya bisa melihat/mengubah/mencatat siswa di kelas yang diampu.
 */
class GuruTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
    }

    /**
     * Membuat guru (user+teacher) yang mengampu kelas tertentu.
     */
    private function makeGuru(string $email, string $nig, array $classIds = []): array
    {
        $user = User::create(['username' => 'Guru', 'email' => $email, 'role' => 'guru', 'password' => Hash::make('x')]);
        $teacher = Teacher::create(['id_user' => $user->id_users, 'nama' => 'Guru', 'nuptk' => $nig, 'no_hp' => '08'.crc32($email)]);
        if ($classIds) {
            $teacher->classes()->sync($classIds);
        }

        return [$user, $teacher];
    }

    /**
     * T6.3/K8.1: WALI KELAS tambah siswa ke kelasnya OK; kelas lain ditolak.
     */
    public function test_guru_add_student_scoped_to_own_class(): void
    {
        $kelasA = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'A']);
        $kelasB = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'B']);
        [, $teacher] = $this->makeGuru('gs@test.id', 'G-ST', [$kelasA->id_classes]);
        $kelasA->update(['id_homeroom_teacher' => $teacher->id_teachers]); // K8.1: jadikan wali kelas A
        $user = $teacher->user;

        $base = ['nama_lengkap' => 'Anak', 'nama_panggilan' => 'Anak', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01',
            'agama' => 'ISLAM', 'anak_ke' => 1, 'jumlah_saudara' => 0, 'warga_negara' => 'WNI', 'bahasa_keseharian' => 'INDONESIA',
            'kondisi_kesehatan' => 'SEHAT', 'ukuran_baju' => 'S', 'sudah_mengaji' => 'Belum', 'pernah_belajar' => 'Belum', 'status' => 'aktif'];

        // Ke kelas yang diwalikan → sukses.
        $this->actingAs($user)->post('/portal/guru/students', $base + ['nik' => '3200000000000001', 'id_class' => $kelasA->id_classes])->assertRedirect();
        $this->assertDatabaseHas('students', ['nik' => '3200000000000001', 'id_class' => $kelasA->id_classes]);

        // Ke kelas yang TIDAK diwalikan → ditolak validasi.
        $this->actingAs($user)->post('/portal/guru/students', $base + ['nik' => '3200000000000002', 'id_class' => $kelasB->id_classes])->assertSessionHasErrors('id_class');
    }

    /**
     * K8.1: guru BIASA (mengajar, bukan wali kelas) tak boleh tambah/edit siswa.
     */
    public function test_plain_teacher_cannot_add_or_edit_student(): void
    {
        $kelasA = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'A']);
        [$user] = $this->makeGuru('gp2@test.id', 'G-PL', [$kelasA->id_classes]); // ampu, TANPA wali kelas
        $siswa = $this->makeStudentInClass($kelasA, '3200000000000009');
        $base = ['nama_lengkap' => 'Anak', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01'];

        // Tambah siswa → 403.
        $this->actingAs($user)->post('/portal/guru/students', $base + ['nik' => '3200000000000010', 'id_class' => $kelasA->id_classes])->assertForbidden();
        // Edit siswa → 403.
        $this->actingAs($user)->put("/portal/guru/students/{$siswa->slug}", ['nama_lengkap' => 'X', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2021-01-01'])->assertForbidden();
        // Tapi tetap boleh lihat profil.
        $this->actingAs($user)->get("/portal/guru/students/{$siswa->slug}")->assertOk();
    }

    /**
     * Guru lihat profil siswa: kelas diampu OK, kelas lain ditolak (guardStudent).
     */
    public function test_guru_view_student_profile_scoped(): void
    {
        $kelasA = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'A']);
        $kelasB = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'B']);
        [$user] = $this->makeGuru('gv@test.id', 'G-VW', [$kelasA->id_classes]);
        $base = ['nama_lengkap' => 'ANAK', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01', 'id_academic_year' => $this->ta->id_academic_years];
        $mine = Student::create($base + ['nik' => '3300000000000001', 'id_class' => $kelasA->id_classes]);
        $other = Student::create($base + ['nik' => '3300000000000002', 'id_class' => $kelasB->id_classes]);

        $this->actingAs($user)->get('/portal/guru/students/'.$mine->slug)->assertOk()->assertSee('ANAK');
        $this->actingAs($user)->get('/portal/guru/students/'.$other->slug)->assertForbidden();
    }

    /**
     * T6.1: guru ganti password — password lama salah ditolak, benar diterima.
     */
    public function test_guru_change_password(): void
    {
        [$user] = $this->makeGuru('gp@test.id', 'G-PW');

        $this->actingAs($user)->post('/portal/guru/password', [
            'username' => 'pak_guru', 'email' => 'gp@test.id', 'current_password' => 'salah', 'password' => 'barubaru8', 'password_confirmation' => 'barubaru8',
        ])->assertSessionHasErrors('current_password');

        // username < 6 ditolak
        $this->actingAs($user)->post('/portal/guru/password', [
            'username' => 'abc', 'email' => 'gp@test.id', 'current_password' => 'x', 'password' => 'barubaru8', 'password_confirmation' => 'barubaru8',
        ])->assertSessionHasErrors('username');

        $this->actingAs($user)->post('/portal/guru/password', [
            'username' => 'pak_guru', 'email' => 'gp@test.id', 'current_password' => 'x', 'password' => 'barubaru8', 'password_confirmation' => 'barubaru8',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('barubaru8', $user->fresh()->password));
        $this->assertSame('pak_guru', $user->fresh()->username);
        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    /**
     * Membuat kelas + siswa di dalamnya.
     */
    private function makeStudentInClass(SchoolClass $class, string $nik): Student
    {
        return Student::create([
            'id_class' => $class->id_classes, 'id_academic_year' => $this->ta->id_academic_years,
            'nik' => $nik, 'nama_lengkap' => 'Siswa', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);
    }

    /**
     * Dashboard guru hanya menampilkan siswa di kelas yang diampu.
     */
    public function test_guru_dashboard_only_shows_own_class_students(): void
    {
        $kelasA = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'A']);
        $kelasB = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'B']);
        $siswaA = $this->makeStudentInClass($kelasA, '3273010101010001');
        $siswaB = $this->makeStudentInClass($kelasB, '3273010101010002');

        [$user] = $this->makeGuru('guru@test.id', 'G-1', [$kelasA->id_classes]);

        $response = $this->actingAs($user)->get('/portal/guru');
        $response->assertOk();
        // Hanya siswa kelas A yang tampil (bukan kelas B)
        $response->assertViewHas('students', function ($students) use ($siswaA, $siswaB) {
            $ids = $students->pluck('id_students')->all();

            return in_array($siswaA->id_students, $ids, true)
                && ! in_array($siswaB->id_students, $ids, true);
        });
    }

    /**
     * ANTI-KEBOCORAN: guru DILARANG mengubah profil siswa kelas lain (403).
     */
    public function test_guru_cannot_edit_other_class_student(): void
    {
        $kelasA = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'A']);
        $kelasB = SchoolClass::create(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'B']);
        $siswaB = $this->makeStudentInClass($kelasB, '3273010101010002');
        [$user] = $this->makeGuru('guru@test.id', 'G-1', [$kelasA->id_classes]);

        $this->actingAs($user)->put("/portal/guru/students/{$siswaB->slug}", [
            'nama_lengkap' => 'Diretas', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2021-01-01',
        ])->assertForbidden();

        $this->assertDatabaseMissing('students', ['nama_lengkap' => 'Diretas']);
    }

    /**
     * RBAC: wali tidak boleh mengakses portal guru.
     */
    public function test_wali_cannot_access_guru_portal(): void
    {
        $wali = User::create(['username' => 'W', 'email' => 'w@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $this->actingAs($wali)->get('/portal/guru')->assertForbidden();
    }

    /**
     * Guru dapat memperbarui email & no. HP sendiri (T2.3); sinkron users & teachers.
     */
    public function test_guru_can_update_own_account(): void
    {
        [$user, $teacher] = $this->makeGuru('guru@test.id', 'G-1');

        $this->actingAs($user)->post('/portal/guru/account', [
            'email' => 'guru.baru@test.id',
            'no_hp' => '081777666555',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id_users' => $user->id_users, 'email' => 'guru.baru@test.id', 'no_hp' => '6281777666555']);
        $this->assertDatabaseHas('teachers', ['id_teachers' => $teacher->id_teachers, 'no_hp' => '6281777666555']);
    }

    /**
     * Guru dapat memperbarui profil sendiri (T6.4): nama, TTL, riwayat.
     */
    public function test_guru_can_update_own_profile(): void
    {
        [$user, $teacher] = $this->makeGuru('guru@test.id', 'G-1');

        $this->actingAs($user)->post('/portal/guru/profile', [
            'nama' => 'Ustadz Baru',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1990-01-01',
            'riwayat_pendidikan' => 'S1 PGRA',
        ])->assertRedirect();

        $this->assertDatabaseHas('teachers', ['id_teachers' => $teacher->id_teachers, 'nama' => 'USTADZ BARU', 'tempat_lahir' => 'BANDUNG']); // T2.4: uppercase
    }
}