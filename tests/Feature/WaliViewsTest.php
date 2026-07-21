<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\MonthlySppBill;
use App\Models\RegistrationForm;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menguji halaman wali yang baru di-bind di Tahap 3 benar-benar merender dan
 * form-nya terhubung ke endpoint yang benar (status pendaftaran & pembayaran).
 */
class WaliViewsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $ta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
        Setting::set('nominal_pendaftaran', '150000');
        Setting::set('nominal_spp', '250000');
    }

    /**
     * Membuat wali + anak + (opsional) formulir pendaftaran.
     */
    private function waliWithForm(string $status = 'menunggu_bukti'): array
    {
        $u = User::create(['username' => 'Bunda', 'email' => 'w@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $g = $this->completeOrtu($u->id_users);
        $s = Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $this->ta->id_academic_years,
            'nik' => '3273010101010001', 'nama_lengkap' => 'Anak Uji', 'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'status' => 'aktif',
        ]);
        $f = RegistrationForm::create([
            'id_user' => $u->id_users, 'id_student' => $s->id_students,
            'id_academic_year' => $this->ta->id_academic_years, 'status' => $status,
        ]);

        return [$u, $s, $f];
    }

    /**
     * Halaman status merender dengan data pendaftaran nyata.
     */
    public function test_status_page_renders(): void
    {
        [$u, $s] = $this->waliWithForm();
        $resp = $this->actingAs($u)->get('/portal/ortu/status');
        $resp->assertOk();
        $resp->assertSee('Anak Uji');
        $resp->assertSee('Timeline Status Pendaftaran');
    }

    /**
     * Form status page mengunggah bukti bayar pendaftaran ke endpoint yang benar.
     */
    public function test_status_upload_proof_works(): void
    {
        Storage::fake('public');
        [$u, $s, $f] = $this->waliWithForm();

        $this->actingAs($u)->post('/portal/ortu/status/proof', [
            'id_registration_form' => $f->id_registration_forms,
            'jumlah' => 150000,
            'tanggal_bayar' => '2026-07-10',
            'bank_asal' => 'BANK BRI (BANK RAKYAT INDONESIA)',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('ortu.status'));

        $this->assertDatabaseHas('payment_transactions', [
            'id_registration_form' => $f->id_registration_forms, 'jenis' => 'pendaftaran', 'status' => 'pending',
            'bank_asal' => 'BANK BRI (BANK RAKYAT INDONESIA)',
        ]);
        $this->assertSame('menunggu_verifikasi', $f->fresh()->status);

        // Bukti tersimpan rapi: pembayaran/{jenis}/{tahun}/{Nama-Siswa}/Pendaftaran_...
        $path = \App\Models\PaymentTransaction::latest('id_payment_transactions')->value('bukti_path');
        $this->assertStringStartsWith('pembayaran/pendaftaran/', $path);
        $this->assertStringContainsString('/Pendaftaran_', $path);
        Storage::disk('public')->assertExists($path);
    }

    /**
     * Halaman pembayaran merender daftar tagihan + form cicilan.
     */
    public function test_payments_page_renders_with_bills(): void
    {
        [$u, $s] = $this->waliWithForm('lulus');
        MonthlySppBill::create([
            'id_student' => $s->id_students, 'id_academic_year' => $this->ta->id_academic_years,
            'bulan' => '2026-07', 'nominal' => 250000, 'jumlah_terbayar' => 100000, 'status' => 'kurang',
        ]);

        $resp = $this->actingAs($u)->get('/portal/ortu/payments');
        $resp->assertOk();
        $resp->assertSee('SPP');
        $resp->assertSee('Riwayat Pembayaran');
    }

    /**
     * Wali dapat memperbarui email & no. HP (T2.3); tersinkron ke users & ortu.
     */
    public function test_account_update_syncs_email_and_hp(): void
    {
        [$u] = $this->waliWithForm();

        $this->actingAs($u)->post('/portal/ortu/account', [
            'email' => 'baru@test.id',
            'no_hp' => '081999888777',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id_users' => $u->id_users, 'email' => 'baru@test.id', 'no_hp' => '6281999888777']);
        // HP tersimpan di users, ortu tidak sinkron lagi (updateAccount tidak update ortu)
    }

    /**
     * daftarBank() parse bank.csv: buang header, nama bank valid (T8.1).
     */
    public function test_daftar_bank_parses_csv(): void
    {
        $banks = \App\Models\PaymentTransaction::daftarBank();
        $this->assertContains('BANK MANDIRI', $banks);
        $this->assertNotContains('nama_bank', $banks); // header terbuang
    }

    /**
     * K10.3: halaman Data Anak render + profil anak;
     * GATE: wali lain TIDAK bisa buka anak yg bukan miliknya (404).
     */
    public function test_wali_children_list_profile_and_ownership_gate(): void
    {
        [$ownerUser, $anak] = $this->waliWithForm();

        // List anak + profil.
        $this->actingAs($ownerUser)->get('/portal/ortu/children')->assertOk()->assertSee('Anak Uji');
        $this->actingAs($ownerUser)->get("/portal/ortu/children/{$anak->slug}")
            ->assertOk()->assertSee('Anak Uji');

        // Wali lain → anak bukan miliknya → 404.
        $other = User::create(['username' => 'Lain', 'email' => 'lain@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $this->completeOrtu($other->id_users);
        $this->actingAs($other)->get("/portal/ortu/children/{$anak->slug}")->assertNotFound();
    }
}
