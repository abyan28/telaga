<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\RegistrationForm;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji alur admin (Fase 4): verifikasi pembayaran + sinkron saldo cicilan
 * (PRD §13), keputusan lulus/gagal, setting nominal, dan pembuatan guru.
 */
class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat admin untuk actingAs.
     */
    private function admin(): User
    {
        return User::create([
            'username' => 'Admin', 'email' => 'admin@test.id',
            'role' => 'admin', 'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Membuat siswa + tahun ajaran untuk konteks pembayaran.
     */
    private function makeStudent(): Student
    {
        $ta = AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
        $wali = User::create([
            'username' => 'W', 'email' => 'w@test.id', 'role' => 'ortu', 'password' => Hash::make('x'),
        ]);
        $g = $this->completeOrtu($wali->id_users);

        return Student::create([
            'id_parent' => $g->id_parents, 'id_academic_year' => $ta->id_academic_years,
            'nik' => '3273010101010001', 'nama_lengkap' => 'Budi',
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01',
        ]);
    }

    /**
     * Service: menyetujui cicilan SPP menyinkronkan saldo & status 'kurang'
     * lalu 'lunas' saat total tercukupi (PRD §13).
     */
    public function test_payment_verification_syncs_spp_balance(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $service = app(PaymentVerificationService::class);

        // Tagihan SPP 250.000
        $bill = MonthlySppBill::create([
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'bulan' => '2026-08', 'nominal' => 250000,
        ]);

        // Cicilan 1: 100.000 -> status 'kurang'
        $trx1 = PaymentTransaction::create([
            'id_student' => $student->id_students, 'id_user' => $admin->id_users,
            'jenis' => 'spp', 'referensi_id' => $bill->id_monthly_spp_bills,
            'jumlah' => 100000, 'bukti_path' => 'a.jpg', 'status' => 'pending', 'tanggal_bayar' => '2026-08-02',
        ]);
        $service->approve($trx1, $admin->id_users);
        $bill->refresh();
        $this->assertEquals(100000, (int) $bill->jumlah_terbayar);
        $this->assertSame('kurang', $bill->status);
        $this->assertEquals(150000.0, $bill->sisa());

        // Cicilan 2: 150.000 -> lunas
        $trx2 = PaymentTransaction::create([
            'id_student' => $student->id_students, 'id_user' => $admin->id_users,
            'jenis' => 'spp', 'referensi_id' => $bill->id_monthly_spp_bills,
            'jumlah' => 150000, 'bukti_path' => 'b.jpg', 'status' => 'pending', 'tanggal_bayar' => '2026-08-10',
        ]);
        $service->approve($trx2, $admin->id_users);
        $bill->refresh();
        $this->assertEquals(250000, (int) $bill->jumlah_terbayar);
        $this->assertSame('lunas', $bill->status);
    }

    /**
     * P3.1 (regression): verifikasi bukti pembayaran PENDAFTARAN menggerakkan
     * status form: menunggu_verifikasi -> pembayaran_diverifikasi (diverifikasi),
     * dan kembali ke menunggu_bukti bila ditolak (wali unggah ulang).
     */
    public function test_registration_payment_verification_advances_form(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $service = app(PaymentVerificationService::class);
        $form = RegistrationForm::create([
            'id_user' => $student->ortu->id_user,
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'status' => 'menunggu_verifikasi',
        ]);
        $trx = PaymentTransaction::create([
            'id_student' => $student->id_students,
            'id_registration_form' => $form->id_registration_forms,
            'id_user' => $admin->id_users,
            'jenis' => 'pendaftaran', 'jumlah' => 150000,
            'bukti_path' => 'p.jpg', 'status' => 'pending', 'tanggal_bayar' => '2026-07-01',
        ]);

        $service->approve($trx, $admin->id_users);
        $this->assertSame('pembayaran_diverifikasi', $form->fresh()->status);

        // Tolak transaksi (skenario upload ulang): form mundur ke menunggu_bukti.
        $trx2 = PaymentTransaction::create([
            'id_student' => $student->id_students,
            'id_registration_form' => $form->id_registration_forms,
            'id_user' => $admin->id_users,
            'jenis' => 'pendaftaran', 'jumlah' => 150000,
            'bukti_path' => 'p2.jpg', 'status' => 'pending', 'tanggal_bayar' => '2026-07-02',
        ]);
        // Reset status di DB (bukan lewat instance lama yang atribut-nya tak dirty).
        RegistrationForm::where('id_registration_forms', $form->id_registration_forms)
            ->update(['status' => 'menunggu_verifikasi']);
        $service->reject($trx2, $admin->id_users, 'Bukti buram');
        $this->assertSame('menunggu_bukti', $form->fresh()->status);
    }

    /**
     * Menolak transaksi mengeluarkannya dari perhitungan saldo.
     */
    public function test_rejected_payment_not_counted(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $service = app(PaymentVerificationService::class);
        $bill = MonthlySppBill::create([
            'id_student' => $student->id_students, 'id_academic_year' => $student->id_academic_year,
            'bulan' => '2026-09', 'nominal' => 250000,
        ]);
        $trx = PaymentTransaction::create([
            'id_student' => $student->id_students, 'id_user' => $admin->id_users,
            'jenis' => 'spp', 'referensi_id' => $bill->id_monthly_spp_bills,
            'jumlah' => 100000, 'bukti_path' => 'a.jpg', 'status' => 'pending', 'tanggal_bayar' => '2026-09-02',
        ]);
        $service->reject($trx, $admin->id_users, 'Bukti buram');
        $bill->refresh();
        $this->assertEquals(0, (int) $bill->jumlah_terbayar);
        $this->assertSame('belum_lunas', $bill->status);
    }

    /**
     * Admin dapat menetapkan keputusan lulus; status form & siswa diperbarui.
     */
    public function test_admin_can_decide_lulus(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $form = RegistrationForm::create([
            'id_user' => $student->ortu->id_user,
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'status' => 'diproses_seleksi',
        ]);

        $this->actingAs($admin)
            ->post("/portal/admin/registrations/{$form->slug}/decide", ['keputusan' => 'lulus'])
            ->assertRedirect();

        $this->assertSame('lulus', $form->fresh()->status);
        $this->assertSame('aktif', $student->fresh()->status);
    }

    /**
     * K3.1: keputusan ditolak bila form belum mencapai 'diproses_seleksi'
     * (mis. berkas belum tuntas). Status tidak berubah.
     */
    public function test_decide_blocked_before_selection_stage(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $form = RegistrationForm::create([
            'id_user' => $student->ortu->id_user,
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'status' => 'pembayaran_diverifikasi', // belum diproses_seleksi
        ]);

        $this->actingAs($admin)
            ->post("/portal/admin/registrations/{$form->slug}/decide", ['keputusan' => 'lulus'])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame('pembayaran_diverifikasi', $form->fresh()->status);
    }

    /**
     * K3.3: admin dapat membuka kembali keputusan lulus/gagal → form kembali
     * ke 'diproses_seleksi' & siswa 'aktif' (siap ditetapkan ulang).
     */
    public function test_admin_can_reopen_decision(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $form = RegistrationForm::create([
            'id_user' => $student->ortu->id_user,
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'status' => 'gagal',
        ]);
        $student->update(['status' => 'nonaktif']);

        $this->actingAs($admin)
            ->post("/portal/admin/registrations/{$form->slug}/reopen-decision")
            ->assertRedirect();

        $this->assertSame('diproses_seleksi', $form->fresh()->status);
        $this->assertSame('aktif', $student->fresh()->status);
    }

    /**
     * Admin dapat memperbarui nominal biaya dinamis.
     */
    public function test_admin_can_update_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/portal/admin/settings', [
            'nominal_pendaftaran' => 200000,
            'nominal_spp' => 300000,
            'nominal_daftar_ulang' => 1750000,
            'tanggal_generate_spp' => 5,
            'kuota_pendaftaran' => 40,
        ])->assertRedirect();

        $this->assertSame('200000', Setting::get('nominal_pendaftaran'));
        $this->assertSame('300000', Setting::get('nominal_spp'));

        // Simpan sebagian saja (form dropdown kirim 1 field) — tak butuh field lain.
        $this->actingAs($admin)->post('/portal/admin/settings', ['nominal_spp' => 99000])->assertRedirect();
        $this->assertSame('99000', Setting::get('nominal_spp'));
        $this->assertSame('200000', Setting::get('nominal_pendaftaran')); // tak tersentuh
    }

    /**
     * Admin dapat membuat akun guru (password = nomor induk) + tugaskan kelas.
     */
    public function test_admin_can_create_teacher_with_classes(): void
    {
        $admin = $this->admin();
        $ta = AcademicYear::create(['tahun' => '2027/2028', 'is_aktif' => true]);
        $kelas = SchoolClass::create(['id_academic_year' => $ta->id_academic_years, 'nama_kelas' => 'A']);

        $this->actingAs($admin)->post('/portal/admin/teachers', [
            'nama' => 'Ustadz Ahmad',
            'email' => 'ahmad@test.id',
            'nuptk' => '2027000000000001',
            'no_hp' => '081300000099',
            'class_ids' => [$kelas->id_classes],
        ])->assertRedirect();

        $teacher = Teacher::where('nuptk', '2027000000000001')->first();
        $this->assertNotNull($teacher);
        $this->assertSame('guru', $teacher->user->role);
        // Password awal = NUPTK
        $this->assertTrue(Hash::check('2027000000000001', $teacher->user->password));
        $this->assertCount(1, $teacher->classes);
    }

    /**
     * P2.3: verifikasi berkas ditolak bila pembayaran belum diverifikasi.
     * P3.2: setelah bayar diverifikasi & semua dokumen diterima, form maju ke seleksi.
     */
    public function test_document_verification_gated_and_advances_to_selection(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $form = RegistrationForm::create([
            'id_user' => $student->ortu->id_user,
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'status' => 'menunggu_verifikasi',
        ]);
        $mk = fn (string $jenis) => \App\Models\RegistrationDocument::create([
            'id_registration_form' => $form->id_registration_forms,
            'jenis' => $jenis, 'path' => "$jenis.jpg", 'status' => 'pending',
        ]);
        [$kk, $akta, $foto] = [$mk('kk'), $mk('akta'), $mk('foto')];

        // Sebelum bayar diverifikasi: verifikasi dokumen ditolak (redirect + form tak maju).
        $this->actingAs($admin)
            ->post("/portal/admin/documents/{$kk->id_registration_documents}/verify", ['status' => 'diterima']);
        $this->assertSame('pending', $kk->fresh()->status);
        $this->assertSame('menunggu_verifikasi', $form->fresh()->status);

        // Bayar diverifikasi -> form 'pembayaran_diverifikasi'.
        $form->update(['status' => 'pembayaran_diverifikasi']);

        // Terima 2 dari 3: belum maju.
        foreach ([$kk, $akta] as $doc) {
            $this->actingAs($admin)->post("/portal/admin/documents/{$doc->id_registration_documents}/verify", ['status' => 'diterima']);
        }
        $this->assertSame('pembayaran_diverifikasi', $form->fresh()->status);

        // Terima dokumen ke-3 -> maju ke diproses_seleksi.
        $this->actingAs($admin)->post("/portal/admin/documents/{$foto->id_registration_documents}/verify", ['status' => 'diterima']);
        $this->assertSame('diproses_seleksi', $form->fresh()->status);
    }

    /**
     * K3.2: verifikasi dokumen via AJAX mengembalikan JSON (bukan redirect)
     * berisi status dokumen + status form terbaru untuk update UI tanpa refresh.
     */
    public function test_document_verification_returns_json_for_ajax(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $form = RegistrationForm::create([
            'id_user' => $student->ortu->id_user,
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'status' => 'pembayaran_diverifikasi',
        ]);
        $doc = \App\Models\RegistrationDocument::create([
            'id_registration_form' => $form->id_registration_forms,
            'jenis' => 'kk', 'path' => 'kk.jpg', 'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->postJson("/portal/admin/documents/{$doc->id_registration_documents}/verify", ['status' => 'diterima'])
            ->assertOk()
            ->assertJson(['ok' => true, 'doc_status' => 'diterima']);

        $this->assertSame('diterima', $doc->fresh()->status);
    }

    /**
     * Izin edit wali aktif → verifikasi dokumen & keputusan terkunci.
     */
    public function test_verification_locked_when_wali_edit_allowed(): void
    {
        $admin = $this->admin();
        $student = $this->makeStudent();
        $form = RegistrationForm::create([
            'id_user' => $student->ortu->id_user,
            'id_student' => $student->id_students,
            'id_academic_year' => $student->id_academic_year,
            'status' => 'diproses_seleksi',
            'boleh_edit' => true,
        ]);
        $doc = \App\Models\RegistrationDocument::create([
            'id_registration_form' => $form->id_registration_forms,
            'jenis' => 'kk', 'path' => 'kk.jpg', 'status' => 'diterima',
        ]);

        $this->actingAs($admin)
            ->post("/portal/admin/documents/{$doc->id_registration_documents}/verify", ['status' => 'ditolak'])
            ->assertSessionHasErrors('dokumen');
        $this->assertSame('diterima', $doc->fresh()->status);

        $this->actingAs($admin)
            ->post("/portal/admin/registrations/{$form->slug}/decide", ['keputusan' => 'lulus'])
            ->assertSessionHasErrors('keputusan');
        $this->assertSame('diproses_seleksi', $form->fresh()->status);
    }

    /**
     * RBAC: wali tidak boleh mengakses aksi admin.
     */
    public function test_wali_cannot_update_settings(): void
    {
        $wali = User::create([
            'username' => 'W2', 'email' => 'w2@test.id', 'role' => 'ortu', 'password' => Hash::make('x'),
        ]);
        $this->actingAs($wali)->post('/portal/admin/settings', [
            'nominal_pendaftaran' => 1, 'nominal_spp' => 1,
            'nominal_daftar_ulang' => 1, 'tanggal_generate_spp' => 1,
        ])->assertForbidden();
    }

    /**
     * K8.1: assign wali kelas via form Kelas — 1 guru maks 1 kelas (reassign melepas yang lama).
     */
    public function test_homeroom_teacher_assignment_is_exclusive(): void
    {
        $admin = $this->admin();
        $ta = AcademicYear::create(['tahun' => '2028/2029', 'is_aktif' => true]);
        $k1 = SchoolClass::create(['id_academic_year' => $ta->id_academic_years, 'nama_kelas' => 'K1']);
        $k2 = SchoolClass::create(['id_academic_year' => $ta->id_academic_years, 'nama_kelas' => 'K2']);
        $u = User::create(['username' => 'g', 'email' => 'g8@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        $t = Teacher::create(['id_user' => $u->id_users, 'nama' => 'Guru Wali', 'nuptk' => '2800000000000001', 'no_hp' => '628998']);

        // Set wali kelas K1.
        $this->actingAs($admin)->put('/portal/admin/classes/'.$k1->id_classes, ['nama_kelas' => 'K1', 'id_homeroom_teacher' => $t->id_teachers])->assertRedirect();
        $this->assertSame($t->id_teachers, $k1->fresh()->id_homeroom_teacher);

        // Pindah jadi wali K2 → K1 otomatis lepas (1 guru maks 1 kelas).
        $this->actingAs($admin)->put('/portal/admin/classes/'.$k2->id_classes, ['nama_kelas' => 'K2', 'id_homeroom_teacher' => $t->id_teachers])->assertRedirect();
        $this->assertNull($k1->fresh()->id_homeroom_teacher);
        $this->assertSame($t->id_teachers, $k2->fresh()->id_homeroom_teacher);
    }
}
