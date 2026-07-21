<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * L8.1 — import CSV massal murid lama (skip+report, auto-akun ortu).
 */
class ImportCsvTest extends TestCase
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

    public function test_import_page_renders(): void
    {
        $this->actingAs($this->admin())
            ->get('/portal/admin/data/students/import')
            ->assertOk();
    }

    public function test_template_download(): void
    {
        $this->actingAs($this->admin())
            ->get('/portal/admin/data/students/import/template')
            ->assertOk();
    }

    public function test_import_valid_csv(): void
    {
        $csv = "nama_lengkap,nama_panggilan,nik,nis,nisn,jenis_kelamin,agama,tempat_lahir,tanggal_lahir,anak_ke,jumlah_saudara,warga_negara,bahasa_keseharian,kondisi_kesehatan,tahun_ajaran,status,no_hp_ortu\n"
            ."AHMAD FAIZAL,FAIZAL,3201010101010001,,,L,ISLAM,BANDUNG,2019-07-15,1,2,INDONESIA,SUNDA,TIDAK ADA,2026/2027,aktif,\n"
            ."SITI AISYAH,AISYAH,3201010101010002,,,P,ISLAM,BANDUNG,2020-03-10,2,1,INDONESIA,SUNDA,TIDAK ADA,2026/2027,aktif,081234567890\n";

        $file = UploadedFile::fake()->createWithContent('murid.csv', $csv);

        $this->actingAs($this->admin())
            ->post('/portal/admin/data/students/import', ['file' => $file])
            ->assertSessionHasNoErrors()
            ->assertSee('2 sukses');

        $this->assertDatabaseHas('students', ['nik' => '3201010101010001', 'nama_lengkap' => 'AHMAD FAIZAL', 'status' => 'aktif']);
        $this->assertDatabaseHas('students', ['nik' => '3201010101010002', 'nama_lengkap' => 'SITI AISYAH']);

        // Baris kedua punya no_hp_ortu → akun ortu dibuat
        $this->assertDatabaseHas('users', ['role' => 'ortu']);
    }

    public function test_import_skip_duplicate_nik(): void
    {
        Student::create([
            'id_academic_year' => $this->ta->id_academic_years,
            'nik' => '3201010101010001', 'nama_lengkap' => 'AHMAD FAIZAL',
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'BANDUNG',
            'tanggal_lahir' => '2019-07-15', 'status' => 'aktif',
        ]);

        $csv = "nama_lengkap,nama_panggilan,nik,jenis_kelamin,tempat_lahir,tanggal_lahir,anak_ke,jumlah_saudara,warga_negara,bahasa_keseharian,kondisi_kesehatan\n"
            ."AHMAD FAIZAL,FAIZAL,3201010101010001,L,BANDUNG,2019-07-15,1,2,INDONESIA,SUNDA,TIDAK ADA\n"
            ."BUDI BARU,BUDI,3201010101010002,L,BANDUNG,2020-01-01,2,1,INDONESIA,SUNDA,SEHAT\n";

        $file = UploadedFile::fake()->createWithContent('murid.csv', $csv);

        $this->actingAs($this->admin())
            ->post('/portal/admin/data/students/import', ['file' => $file])
            ->assertSee('1 sukses')
            ->assertSee('1 sudah ada');
    }

    public function test_import_shows_validation_errors(): void
    {
        $csv = "nama_lengkap,nama_panggilan,nik,jenis_kelamin,tempat_lahir,tanggal_lahir,anak_ke,jumlah_saudara,warga_negara,bahasa_keseharian,kondisi_kesehatan\n"
            ."BUDI BARU,BUDI,abcnot16digit,L,BANDUNG,2020-01-01,2,1,INDONESIA,SUNDA,SEHAT\n";

        $file = UploadedFile::fake()->createWithContent('murid.csv', $csv);

        $this->actingAs($this->admin())
            ->post('/portal/admin/data/students/import', ['file' => $file])
            ->assertSee('1 gagal');
    }

    public function test_import_rbac_guru_blocked(): void
    {
        $guru = User::create(['username' => 'Guru', 'email' => 'g@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        $file = UploadedFile::fake()->create('murid.csv');

        $this->actingAs($guru)
            ->post('/portal/admin/data/students/import', ['file' => $file])
            ->assertForbidden();
    }
}
