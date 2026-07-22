<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\MonthlySppBill;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * L8.2 — export CSV/PDF data siswa + laporan SPP dengan filter.
 */
class ReportExportTest extends TestCase
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

    private function siswa(string $nik, string $nama, ?string $nis = null): Student
    {
        return Student::create([
            'id_academic_year' => $this->ta->id_academic_years,
            'nik' => $nik, 'nama_lengkap' => $nama, 'nama_panggilan' => $nama,
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'BANDUNG',
            'tanggal_lahir' => '2019-07-15', 'status' => 'aktif', 'nis' => $nis,
        ]);
    }

    public function test_students_report_renders(): void
    {
        $this->siswa('3201010101010001', 'AHMAD');
        $this->actingAs($this->admin())->get('/portal/admin/reports/students')->assertOk()->assertSee('AHMAD');
    }

    public function test_students_csv_has_data(): void
    {
        $this->siswa('3201010101010001', 'AHMAD');
        $resp = $this->actingAs($this->admin())->get('/portal/admin/reports/students/export/csv');
        $resp->assertOk();
        $this->assertStringContainsString('AHMAD', $resp->streamedContent());
        $this->assertStringContainsString('Nama Lengkap', $resp->streamedContent());
    }

    /** Filter "murid baru" = NIS prefix-tahun (YY=26) — NSM 12 digit + 26 + urut. */
    public function test_students_new_filter_by_nis_prefix(): void
    {
        $this->siswa('3201010101010001', 'BARU', '12345678901226001'); // YY=26 → baru
        $this->siswa('3201010101010002', 'LAMA', '12345678901225001'); // YY=25 → lama
        $resp = $this->actingAs($this->admin())->get('/portal/admin/reports/students?murid_baru=1');
        $resp->assertOk()->assertSee('BARU')->assertDontSee('LAMA');
    }

    public function test_spp_report_and_status_filter(): void
    {
        $s = $this->siswa('3201010101010001', 'AHMAD');
        MonthlySppBill::create(['id_student' => $s->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'bulan' => '2026-07', 'nominal' => 100000, 'jumlah_terbayar' => 100000, 'status' => 'lunas']);
        MonthlySppBill::create(['id_student' => $s->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'bulan' => '2026-08', 'nominal' => 100000, 'jumlah_terbayar' => 0, 'status' => 'belum_lunas']);

        $admin = $this->admin();
        $admin->getConnection(); // noop
        $this->actingAs($admin)->get('/portal/admin/reports/spp')->assertOk();
        // Filter belum → hanya bulan 2026-08
        $resp = $this->actingAs($admin)->get('/portal/admin/reports/spp/export/csv?status_bayar=belum');
        $resp->assertOk();
        $csv = $resp->streamedContent();
        $this->assertStringContainsString('2026-08', $csv);
        $this->assertStringNotContainsString('2026-07', $csv);
    }

    public function test_spp_pdf_renders(): void
    {
        $s = $this->siswa('3201010101010001', 'AHMAD');
        MonthlySppBill::create(['id_student' => $s->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'bulan' => '2026-07', 'nominal' => 100000, 'jumlah_terbayar' => 0, 'status' => 'belum_lunas']);
        $resp = $this->actingAs($this->admin())->get('/portal/admin/reports/spp/export/pdf');
        $resp->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_rbac_guru_blocked(): void
    {
        $guru = User::create(['username' => 'Guru', 'email' => 'g@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        $this->actingAs($guru)->get('/portal/admin/reports/students')->assertForbidden();
    }
}
