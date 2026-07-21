<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\MonthlySppBill;
use App\Models\ReRegistrationPayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji skenario multi-anak per wali (Tahap 3): satu wali dengan beberapa anak,
 * termasuk lintas tahun ajaran (kakak tahun lalu, adik tahun ini). Memastikan
 * dashboard menampilkan SEMUA anak dan total tunggakan dihitung benar (PRD §13).
 */
class MultiChildTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $taLalu;
    private AcademicYear $taAktif;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taLalu = AcademicYear::create(['tahun' => '2025/2026', 'is_aktif' => false]);
        $this->taAktif = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
    }

    /**
     * Membuat wali + ortu.
     */
    private function wali(string $email = 'w@test.id'): User
    {
        $u = User::create(['username' => 'Wali', 'email' => $email, 'role' => 'ortu', 'password' => Hash::make('x')]);
        $this->completeOrtu($u->id_users);

        return $u;
    }

    /**
     * Membuat anak milik wali di tahun ajaran tertentu.
     */
    private function anak(User $wali, AcademicYear $ta, string $nik, string $nama): Student
    {
        return Student::create([
            'id_parent' => $wali->ortu->id_parents,
            'id_academic_year' => $ta->id_academic_years,
            'nik' => $nik, 'nama_lengkap' => $nama, 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);
    }

    /**
     * Dashboard menampilkan SEMUA anak wali (2 anak sekaligus di tahun aktif).
     */
    public function test_dashboard_shows_all_children(): void
    {
        $wali = $this->wali();
        $this->anak($wali, $this->taAktif, '3273010000000001', 'Anak Satu');
        $this->anak($wali, $this->taAktif, '3273010000000002', 'Anak Dua');

        $response = $this->actingAs($wali)->get('/portal/ortu');

        $response->assertOk();
        $response->assertViewHas('students', fn ($students) => $students->count() === 2);
        $response->assertSee('Anak Satu');
        $response->assertSee('Anak Dua');
    }

    /**
     * Total tunggakan dijumlah dari SEMUA anak (SPP + daftar ulang).
     */
    public function test_total_tunggakan_aggregates_all_children(): void
    {
        $wali = $this->wali();
        $a1 = $this->anak($wali, $this->taAktif, '3273010000000001', 'Anak Satu');
        $a2 = $this->anak($wali, $this->taAktif, '3273010000000002', 'Anak Dua');

        // Anak 1: SPP kurang 150rb + daftar ulang kurang 200rb = 350rb
        MonthlySppBill::create(['id_student' => $a1->id_students, 'id_academic_year' => $this->taAktif->id_academic_years, 'bulan' => '2026-07', 'nominal' => 250000, 'jumlah_terbayar' => 100000, 'status' => 'kurang']);
        ReRegistrationPayment::create(['id_student' => $a1->id_students, 'id_academic_year' => $this->taAktif->id_academic_years, 'total_biaya' => 1500000, 'jumlah_terbayar' => 1300000, 'status' => 'kurang']);
        // Anak 2: SPP kurang 250rb
        MonthlySppBill::create(['id_student' => $a2->id_students, 'id_academic_year' => $this->taAktif->id_academic_years, 'bulan' => '2026-07', 'nominal' => 250000, 'jumlah_terbayar' => 0, 'status' => 'belum_lunas']);

        // Total = 150rb + 200rb + 250rb = 600rb
        $response = $this->actingAs($wali)->get('/portal/ortu');
        $response->assertViewHas('totalTunggakan', 600000.0);
    }

    /**
     * Skenario lintas tahun: kakak (tahun lalu) + adik (tahun ini) sama-sama tampil.
     */
    public function test_siblings_across_academic_years(): void
    {
        $wali = $this->wali();
        $kakak = $this->anak($wali, $this->taLalu, '3273010000000003', 'Kakak');
        $adik = $this->anak($wali, $this->taAktif, '3273010000000004', 'Adik');

        $response = $this->actingAs($wali)->get('/portal/ortu');

        $response->assertViewHas('students', fn ($students) => $students->count() === 2);
        $response->assertSee('Kakak');
        $response->assertSee('Adik');
    }

    /**
     * Wali tanpa anak melihat empty-state (ajakan mendaftar), bukan error.
     */
    public function test_wali_without_children_sees_empty_state(): void
    {
        $wali = $this->wali();

        $response = $this->actingAs($wali)->get('/portal/ortu');

        $response->assertOk();
        $response->assertViewHas('students', fn ($students) => $students->isEmpty());
        $response->assertSee('Daftar Murid Baru');
    }
}
