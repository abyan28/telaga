<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * T9.1 — buatkan akun wali siswa lama + paksa ganti password (wali & guru).
 */
class WaliAccountTest extends TestCase
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

    private function siswa(string $nik, string $nama): Student
    {
        $kelas = SchoolClass::firstOrCreate(['id_academic_year' => $this->ta->id_academic_years, 'nama_kelas' => 'A']);

        return Student::create([
            'id_class' => $kelas->id_classes, 'id_academic_year' => $this->ta->id_academic_years,
            'nik' => $nik, 'nama_lengkap' => $nama, 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'BANDUNG', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);
    }

    /** Akun wali dibuat: username=password=no_hp, flag paksa-ganti, anak tertaut. */
    public function test_admin_creates_wali_account(): void
    {
        $ahmad = $this->siswa('3200000000000001', 'AHMAD');

        $this->actingAs($this->admin())
            ->post("/portal/admin/data/students/{$ahmad->slug}/ortu-account", ['nama' => 'BUDI', 'no_hp' => '081200000001'])
            ->assertRedirect();

        $user = User::where('no_hp', '6281200000001')->first();
        $this->assertNotNull($user);
        $this->assertSame('ortu', $user->role);
        $this->assertSame('6281200000001', $user->username);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('3200000000000001', $user->password)); // L8.1: sandi awal = NIK anak
        $this->assertSame($user->ortu->id_parents, $ahmad->fresh()->id_parent);
    }

    /** Anak kedua dg no_hp sama → tertaut ke akun wali yang sama (bukan akun baru). */
    public function test_sibling_same_no_hp_links_to_same_account(): void
    {
        $admin = $this->admin();
        $ahmad = $this->siswa('3200000000000001', 'AHMAD');
        $siti = $this->siswa('3200000000000002', 'SITI');

        $this->actingAs($admin)->post("/portal/admin/data/students/{$ahmad->slug}/ortu-account", ['nama' => 'BUDI', 'no_hp' => '081200000001']);
        $this->actingAs($admin)->post("/portal/admin/data/students/{$siti->slug}/ortu-account", ['nama' => 'BUDI', 'no_hp' => '081200000001']);

        $this->assertSame(1, User::where('no_hp', '6281200000001')->count());
        $g = User::where('no_hp', '6281200000001')->first()->ortu;
        $this->assertSame($g->id_parents, $ahmad->fresh()->id_parent);
        $this->assertSame($g->id_parents, $siti->fresh()->id_parent);
    }

    /** Wali dg flag true dipaksa ke form ganti-password; setelah ganti, flag clear & akses normal. */
    public function test_wali_forced_to_change_password(): void
    {
        $wali = User::create(['username' => 'wali9', 'no_hp' => '081200000009', 'email' => 'wali9@test.id', 'role' => 'ortu', 'password' => Hash::make('081200000009'), 'must_change_password' => true]);
        $this->completeOrtu($wali->id_users); // K2.1: profil lengkap agar gate profil tak ikut nge-block

        // Akses dashboard → diblok ke form ganti-password.
        $this->actingAs($wali)->get('/portal/ortu')->assertRedirect(route('ortu.password'));

        // Ganti password → flag clear, redirect ke dashboard.
        $this->actingAs($wali)->post('/portal/ortu/password', [
            'current_password' => '081200000009', 'password' => 'rahasia-baru', 'password_confirmation' => 'rahasia-baru',
        ])->assertRedirect(route('ortu.dashboard'));
        $this->assertFalse($wali->fresh()->must_change_password);

        // Sekarang dashboard bisa diakses.
        $this->actingAs($wali->fresh())->get('/portal/ortu')->assertOk();
    }

    /** Guru dg flag true juga dipaksa ganti password. */
    public function test_guru_forced_to_change_password(): void
    {
        $guru = User::create(['username' => '081300000001', 'no_hp' => '081300000001', 'role' => 'guru', 'password' => Hash::make('1234567890123456'), 'must_change_password' => true]);

        $this->actingAs($guru)->get('/portal/guru')->assertRedirect(route('guru.password'));
    }

    /** K2.1: wali profil belum lengkap → diblok ke form profil; setelah lengkap → akses normal. */
    public function test_wali_forced_to_complete_profile(): void
    {
        $wali = User::create(['username' => 'wali_baru', 'email' => 'wb@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        \App\Models\OrangTua::create(['id_user' => $wali->id_users, 'ada_ibu' => false]); // profil belum lengkap

        // Akses register → diblok ke form profil.
        $this->actingAs($wali)->get('/portal/ortu/register')->assertRedirect(route('ortu.profile'));

        // Lengkapi profil (skema L1.1: ibu saja, ada_ayah=false)
        $this->actingAs($wali)->post('/portal/ortu/profile', [
            'ada_ibu' => '1',
            'ibu_nama' => 'BUDI SANTOSO',
            'ibu_no_hp' => '081234567890',
            'ibu_pekerjaan' => 'GURU',
            'ibu_tempat_lahir' => 'BANDUNG',
            'ibu_tanggal_lahir' => '1985-05-05',
            'ibu_agama' => 'ISLAM',
            'ibu_pendidikan' => 'S1',
            'ibu_penghasilan' => '1 - 2 JUTA',
            'alamat' => 'JL. TEST 1',
        ])->assertRedirect(route('ortu.dashboard'));

        $this->assertDatabaseHas('parents', ['id_user' => $wali->id_users, 'ibu_nama' => 'BUDI SANTOSO']);
        $this->actingAs($wali->fresh())->get('/portal/ortu/register')->assertOk();
    }

    /** L8.1: tambah murid + no_hp_ortu → akun ortu auto-dibuat (username=no_hp, sandi=NIK anak). Adik no_hp sama → 1 akun. */
    public function test_add_student_with_ortu_hp_creates_account(): void
    {
        $admin = $this->admin();
        $base = ['jenis_kelamin' => 'L', 'tempat_lahir' => 'X', 'tanggal_lahir' => '2020-01-01', 'agama' => 'ISLAM',
            'anak_ke' => 1, 'jumlah_saudara' => 0, 'warga_negara' => 'INDONESIA', 'bahasa_keseharian' => 'INDONESIA',
            'kondisi_kesehatan' => 'TIDAK ADA', 'ukuran_baju' => 'M', 'sudah_mengaji' => 'Belum', 'pernah_belajar' => 'Belum', 'status' => 'aktif'];

        // Kakak → akun baru, sandi = NIK kakak.
        $this->actingAs($admin)->post('/portal/admin/data/students',
            $base + ['nama_lengkap' => 'KAKAK', 'nama_panggilan' => 'KAK', 'nik' => '3200000000000001', 'no_hp_ortu' => '081200000001'])->assertRedirect();
        $user = User::where('no_hp', '6281200000001')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('3200000000000001', $user->password));
        $this->assertTrue($user->must_change_password);

        // Adik, no_hp sama → tautkan ke akun yang sama (bukan akun baru, sandi tak berubah).
        $this->actingAs($admin)->post('/portal/admin/data/students',
            $base + ['nama_lengkap' => 'ADIK', 'nama_panggilan' => 'DIK', 'nik' => '3200000000000002', 'no_hp_ortu' => '081200000001'])->assertRedirect();
        $this->assertSame(1, User::where('no_hp', '6281200000001')->count());
        $this->assertTrue(Hash::check('3200000000000001', $user->fresh()->password)); // masih NIK kakak

        // Tanpa no_hp_ortu → murid saja, tak ada akun.
        $this->actingAs($admin)->post('/portal/admin/data/students',
            $base + ['nama_lengkap' => 'YATIM', 'nama_panggilan' => 'YT', 'nik' => '3200000000000003'])->assertRedirect();
        $this->assertNull(Student::where('nik', '3200000000000003')->first()->id_parent);
    }
}
