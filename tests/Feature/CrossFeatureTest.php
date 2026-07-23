<?php

namespace Tests\Feature;

use App\Mail\RegistrationNotification;
use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\PaymentTransaction;
use App\Models\RegistrationForm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Menguji Fase 7 (Lintas-Fitur): audit log login, email notifikasi (PRD §7.13),
 * dan export laporan CSV/PDF (PRD §7.12).
 */
class CrossFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Login berhasil tercatat di audit_logs.
     */
    public function test_login_is_audited(): void
    {
        User::create(['username' => 'W', 'email' => 'w@test.id', 'role' => 'ortu', 'password' => Hash::make('password123')]);

        $this->post('/login', ['login' => 'w@test.id', 'password' => 'password123']);

        $this->assertDatabaseHas('audit_logs', ['aksi' => 'login']);
    }

    /**
     * Keputusan lulus mengirim email notifikasi ke wali (PRD §7.13).
     */
    public function test_kelulusan_sends_email(): void
    {
        Mail::fake();
        $ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
        $admin = User::create(['username' => 'A', 'email' => 'admin@test.id', 'role' => 'admin', 'password' => Hash::make('x')]);
        $wali = User::create(['username' => 'W', 'email' => 'wali@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($wali->id_users);
        $student = Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $ta->id_academic_years,
            'nik' => '3273010101010001', 'nama_lengkap' => 'Budi', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01',
        ]);
        $form = RegistrationForm::create([
            'id_user' => $wali->id_users, 'id_student' => $student->id_students,
            'id_academic_year' => $ta->id_academic_years, 'status' => 'diproses_seleksi',
        ]);

        $this->actingAs($admin)
            ->post("/portal/admin/registrations/{$form->slug}/decide", ['keputusan' => 'lulus']);

        Mail::assertQueued(RegistrationNotification::class);
    }

    /**
     * Verifikasi pembayaran SPP berhasil mengirim email berisi detail transaksi (PRD §7.13).
     */
    public function test_payment_verification_sends_detailed_email(): void
    {
        Mail::fake();
        $ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
        $admin = User::create(['username' => 'A', 'email' => 'admin@test.id', 'role' => 'admin', 'password' => Hash::make('x')]);
        $wali = User::create(['username' => 'W', 'email' => 'wali@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($wali->id_users);
        $student = Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $ta->id_academic_years,
            'nik' => '3273010101010001', 'nama_lengkap' => 'Budi', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01',
        ]);
        $bill = \App\Models\MonthlySppBill::create([
            'id_student' => $student->id_students, 'id_academic_year' => $ta->id_academic_years,
            'bulan' => '2026-08', 'nominal' => 250000, 'jumlah_terbayar' => 0, 'status' => 'belum_lunas',
        ]);
        $trx = PaymentTransaction::create([
            'id_student' => $student->id_students, 'id_user' => $wali->id_users, 'jenis' => 'spp',
            'referensi_id' => $bill->id_monthly_spp_bills, 'jumlah' => 250000,
            'bukti_path' => 'a.jpg', 'status' => 'pending', 'tanggal_bayar' => '2026-08-01',
        ]);

        $this->actingAs($admin)
            ->post("/portal/admin/payments/{$trx->id_payment_transactions}/verify", ['status' => 'diverifikasi']);

        // Email terkirim dengan tabel detail terisi (nama siswa, bulan, jumlah).
        Mail::assertQueued(RegistrationNotification::class, function ($mail) {
            return $mail->detail['Nama Siswa'] === 'Budi'
                && ($mail->detail['Bulan'] ?? '') === 'Agustus 2026'
                && ! empty($mail->detail['Jumlah Dibayar']);
        });
    }

    /**
     * Membuat admin + beberapa transaksi untuk uji export.
     */
    private function seedTransactions(): User
    {
        $ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
        $admin = User::create(['username' => 'A', 'email' => 'admin@test.id', 'role' => 'admin', 'password' => Hash::make('x')]);
        $wali = User::create(['username' => 'W', 'email' => 'wali@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($wali->id_users);
        $student = Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $ta->id_academic_years,
            'nik' => '3273010101010001', 'nama_lengkap' => 'Budi', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01',
        ]);
        PaymentTransaction::create([
            'id_student' => $student->id_students, 'id_user' => $wali->id_users, 'jenis' => 'spp',
            'jumlah' => 250000, 'bukti_path' => 'a.jpg', 'status' => 'diverifikasi', 'tanggal_bayar' => '2026-08-01',
        ]);

        return $admin;
    }

    /**
     * Export CSV mengembalikan file text/csv berisi data transaksi.
     */
    public function test_export_csv(): void
    {
        $admin = $this->seedTransactions();

        $response = $this->actingAs($admin)->get('/portal/admin/reports/export/csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Budi', $response->streamedContent());
    }

    /**
     * Export PDF mengembalikan file application/pdf.
     */
    public function test_export_pdf(): void
    {
        $admin = $this->seedTransactions();

        $response = $this->actingAs($admin)->get('/portal/admin/reports/export/pdf');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    /**
     * Filter status mempersempit hasil laporan.
     */
    public function test_report_filter_by_status(): void
    {
        $admin = $this->seedTransactions();

        $response = $this->actingAs($admin)->get('/portal/admin/reports?status=pending');
        $response->assertOk();
        // Tidak ada transaksi 'pending' (satu-satunya berstatus diverifikasi)
        $response->assertViewHas('transactions', fn ($t) => $t->count() === 0);
    }
}
