<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\MonthlySppBill;
use App\Models\ReRegistrationPayment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menguji Fase 5 (Keuangan/Cicilan): generate SPP idempotent, pembayaran cicilan
 * wali (pending), tolak overpay & lintas-pemilik, dan pembukaan daftar ulang saat lulus.
 */
class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ta;

    /**
     * Menyiapkan tahun ajaran aktif + setting nominal.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
        Setting::set('nominal_spp', '250000');
        Setting::set('nominal_daftar_ulang', '1500000');
    }

    /**
     * Membuat wali + anak (siswa aktif). Mengembalikan [user wali, student].
     *
     * @return array{0: User, 1: Student}
     */
    private function makeWaliWithChild(string $email = 'w@test.id', string $nik = '3273010101010001'): array
    {
        $user = User::create(['username' => 'Wali', 'email' => $email, 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($user->id_users);
        $student = Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $this->ta->id_academic_years,
            'nik' => $nik, 'nama_lengkap' => 'Anak', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);

        return [$user, $student];
    }

    /**
     * Command spp:generate membuat 1 tagihan per siswa aktif dan idempotent.
     */
    public function test_spp_generate_is_idempotent(): void
    {
        $this->makeWaliWithChild();

        $this->artisan('spp:generate --bulan=2026-08')->assertSuccessful();
        $this->assertDatabaseCount('monthly_spp_bills', 1);

        // Jalan lagi: tidak membuat tagihan dobel
        $this->artisan('spp:generate --bulan=2026-08')->assertSuccessful();
        $this->assertDatabaseCount('monthly_spp_bills', 1);

        $bill = MonthlySppBill::first();
        $this->assertEquals(250000, (int) $bill->nominal);
        $this->assertSame('belum_lunas', $bill->status);
    }

    /**
     * K10.4: SPP harus dibayar LUNAS (tidak dapat dicicil); transaksi 'pending'.
     */
    public function test_wali_can_pay_spp_in_full(): void
    {
        Storage::fake('public');
        [$user, $student] = $this->makeWaliWithChild();
        $bill = MonthlySppBill::create([
            'id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'bulan' => '2026-08', 'nominal' => 250000,
        ]);

        $this->actingAs($user)->post('/portal/ortu/payments', [
            'jenis' => 'spp',
            'referensi_id' => $bill->id_monthly_spp_bills,
            'jumlah' => 250000,
            'tanggal_bayar' => '2026-08-03',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('ortu.payments'));

        $this->assertDatabaseHas('payment_transactions', [
            'id_student' => $student->id_students, 'jenis' => 'spp',
            'referensi_id' => $bill->id_monthly_spp_bills, 'jumlah' => 250000, 'status' => 'pending',
        ]);
    }

    /**
     * K10.4: SPP dibayar kurang dari sisa (dicicil) DITOLAK.
     */
    public function test_partial_spp_payment_is_rejected(): void
    {
        Storage::fake('public');
        [$user, $student] = $this->makeWaliWithChild();
        $bill = MonthlySppBill::create([
            'id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'bulan' => '2026-08', 'nominal' => 250000,
        ]);

        $this->actingAs($user)->post('/portal/ortu/payments', [
            'jenis' => 'spp', 'referensi_id' => $bill->id_monthly_spp_bills,
            'jumlah' => 100000, 'tanggal_bayar' => '2026-08-03',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertDatabaseCount('payment_transactions', 0);
    }

    /**
     * K10.4: Daftar ulang TETAP boleh dicicil (bayar sebagian diterima).
     */
    public function test_daftar_ulang_can_be_paid_partially(): void
    {
        Storage::fake('public');
        [$user, $student] = $this->makeWaliWithChild();
        $daftar = \App\Models\ReRegistrationPayment::create([
            'id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'total_biaya' => 500000,
        ]);

        $this->actingAs($user)->post('/portal/ortu/payments', [
            'jenis' => 'daftar_ulang',
            'referensi_id' => $daftar->id_re_registration_payments,
            'jumlah' => 200000,
            'tanggal_bayar' => '2026-08-03',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('ortu.payments'));

        $this->assertDatabaseHas('payment_transactions', [
            'id_student' => $student->id_students, 'jenis' => 'daftar_ulang',
            'referensi_id' => $daftar->id_re_registration_payments, 'jumlah' => 200000, 'status' => 'pending',
        ]);
    }

    /**
     * L2.3: SPP duplikat upload (pending lama) ditolak.
     */
    public function test_duplicate_spp_upload_is_rejected(): void
    {
        Storage::fake('public');
        [$user, $student] = $this->makeWaliWithChild();
        $bill = MonthlySppBill::create(['id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'bulan' => '2026-08', 'nominal' => 250000]);

        $this->actingAs($user)->post('/portal/ortu/payments', ['jenis' => 'spp', 'referensi_id' => $bill->id_monthly_spp_bills, 'jumlah' => 250000, 'tanggal_bayar' => '2026-08-03', 'bukti' => UploadedFile::fake()->image('b1.jpg')])->assertRedirect();
        $this->actingAs($user)->post('/portal/ortu/payments', ['jenis' => 'spp', 'referensi_id' => $bill->id_monthly_spp_bills, 'jumlah' => 250000, 'tanggal_bayar' => '2026-08-03', 'bukti' => UploadedFile::fake()->image('b2.jpg')])->assertSessionHasErrors('bukti');
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    /**
     * L2.2: PPDB ditutup + calon lulus belum bayar DU → kelulusan dibatalkan.
     */
    public function test_ppdb_closed_cancels_lulus_for_unpaid(): void
    {
        Setting::set('pendaftaran_dibuka', '0');
        [$user, $student] = $this->makeWaliWithChild();
        $form = \App\Models\RegistrationForm::create([
            'id_user' => $user->id_users, 'id_student' => $student->id_students,
            'id_academic_year' => $this->ta->id_academic_years, 'status' => 'lulus',
        ]);

        // Panggil lazy cancel (via method model langsung — yang dipanggil controller)
        $cancelled = $form->cancelLulusIfPpdbClosedAndUnpaid();
        $form->refresh();

        $this->assertTrue($cancelled);
        $this->assertSame('gagal', $form->status);
        $this->assertSame('nonaktif', $form->student->status);
    }

    /**
     * L2.2: PPDB ditutup tapi sudah bayar DU → tetap lulus.
     */
    public function test_ppdb_closed_does_not_cancel_if_already_paid(): void
    {
        Setting::set('pendaftaran_dibuka', '0');
        [$user, $student] = $this->makeWaliWithChild();
        $form = \App\Models\RegistrationForm::create([
            'id_user' => $user->id_users, 'id_student' => $student->id_students,
            'id_academic_year' => $this->ta->id_academic_years, 'status' => 'lulus',
        ]);
        // Sudah bayar DU sebagian (mis. 100rb)
        \App\Models\ReRegistrationPayment::create([
            'id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'total_biaya' => 500000, 'jumlah_terbayar' => 100000,
        ]);

        $cancelled = $form->cancelLulusIfPpdbClosedAndUnpaid();
        $form->refresh();

        $this->assertFalse($cancelled);
        $this->assertSame('lulus', $form->status);
    }

    /**
     * L2.2: upload bukti DU ditolak bila PPDB tutup + belum bayar.
     */
    public function test_du_upload_rejected_when_ppdb_closed_and_unpaid(): void
    {
        Storage::fake('public');
        Setting::set('pendaftaran_dibuka', '0');
        [$user, $student] = $this->makeWaliWithChild();
        $daftar = \App\Models\ReRegistrationPayment::create([
            'id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'total_biaya' => 500000,
        ]);

        $this->actingAs($user)->post('/portal/ortu/payments', [
            'jenis' => 'daftar_ulang',
            'referensi_id' => $daftar->id_re_registration_payments,
            'jumlah' => 200000,
            'tanggal_bayar' => '2026-08-03',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasErrors('bukti');

        $this->assertDatabaseCount('payment_transactions', 0);
    }

    /**
     * L2.2: upload DU tetap jalan saat PPDB tutup tapi sudah bayar sebagian.
     */
    public function test_du_upload_allowed_when_ppdb_closed_but_partially_paid(): void
    {
        Storage::fake('public');
        Setting::set('pendaftaran_dibuka', '0');
        [$user, $student] = $this->makeWaliWithChild();
        $daftar = \App\Models\ReRegistrationPayment::create([
            'id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'total_biaya' => 500000, 'jumlah_terbayar' => 100000,
        ]);

        $this->actingAs($user)->post('/portal/ortu/payments', [
            'jenis' => 'daftar_ulang',
            'referensi_id' => $daftar->id_re_registration_payments,
            'jumlah' => 200000,
            'tanggal_bayar' => '2026-08-03',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('ortu.payments'));

        $this->assertDatabaseHas('payment_transactions', [
            'jenis' => 'daftar_ulang', 'jumlah' => 200000, 'status' => 'pending',
        ]);
    }

    /**
     * L2.3: daftar ulang duplikat upload ditolak.
     */
    public function test_duplicate_daftar_ulang_upload_is_rejected(): void
    {
        Storage::fake('public');
        [$user, $student] = $this->makeWaliWithChild();
        $daftar = \App\Models\ReRegistrationPayment::create(['id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years, 'total_biaya' => 500000]);

        $this->actingAs($user)->post('/portal/ortu/payments', ['jenis' => 'daftar_ulang', 'referensi_id' => $daftar->id_re_registration_payments, 'jumlah' => 200000, 'tanggal_bayar' => '2026-08-03', 'bukti' => UploadedFile::fake()->image('b1.jpg')])->assertRedirect();
        $this->actingAs($user)->post('/portal/ortu/payments', ['jenis' => 'daftar_ulang', 'referensi_id' => $daftar->id_re_registration_payments, 'jumlah' => 200000, 'tanggal_bayar' => '2026-08-03', 'bukti' => UploadedFile::fake()->image('b2.jpg')])->assertSessionHasErrors('bukti');
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    /**
     * Pembayaran melebihi sisa tunggakan ditolak.
     */
    public function test_overpayment_is_rejected(): void
    {
        Storage::fake('public');
        [$user, $student] = $this->makeWaliWithChild();
        $bill = MonthlySppBill::create([
            'id_student' => $student->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'bulan' => '2026-08', 'nominal' => 250000, 'jumlah_terbayar' => 200000,
        ]);

        // Sisa 50.000; bayar 100.000 harus ditolak
        $this->actingAs($user)->post('/portal/ortu/payments', [
            'jenis' => 'spp', 'referensi_id' => $bill->id_monthly_spp_bills,
            'jumlah' => 100000, 'tanggal_bayar' => '2026-08-03',
            'bukti' => UploadedFile::fake()->image('b.jpg'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertDatabaseCount('payment_transactions', 0);
    }

    /**
     * Wali tidak boleh membayar tagihan milik anak wali lain (403).
     */
    public function test_wali_cannot_pay_other_childs_bill(): void
    {
        Storage::fake('public');
        [$waliA] = $this->makeWaliWithChild('a@test.id', '3273010101010001');
        [, $childB] = $this->makeWaliWithChild('b@test.id', '3273010101010002');
        $billB = MonthlySppBill::create([
            'id_student' => $childB->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'bulan' => '2026-08', 'nominal' => 250000,
        ]);

        // Wali A mencoba bayar tagihan anak Wali B
        $this->actingAs($waliA)->post('/portal/ortu/payments', [
            'jenis' => 'spp', 'referensi_id' => $billB->id_monthly_spp_bills,
            'jumlah' => 50000, 'tanggal_bayar' => '2026-08-03',
            'bukti' => UploadedFile::fake()->image('b.jpg'),
        ])->assertForbidden();
    }

    /**
     * Keputusan lulus membuka tagihan daftar ulang dengan nominal dinamis.
     */
    public function test_lulus_opens_re_registration_bill(): void
    {
        [$user, $student] = $this->makeWaliWithChild();
        $admin = User::create(['username' => 'A', 'email' => 'admin@test.id', 'role' => 'admin', 'password' => Hash::make('x')]);
        $form = \App\Models\RegistrationForm::create([
            'id_user' => $user->id_users, 'id_student' => $student->id_students,
            'id_academic_year' => $this->ta->id_academic_years, 'status' => 'diproses_seleksi',
        ]);

        $this->actingAs($admin)
            ->post("/portal/admin/registrations/{$form->slug}/decide", ['keputusan' => 'lulus'])
            ->assertRedirect();

        $daftar = ReRegistrationPayment::where('id_student', $student->id_students)->first();
        $this->assertNotNull($daftar);
        $this->assertEquals(1500000, (int) $daftar->total_biaya);
    }
}
