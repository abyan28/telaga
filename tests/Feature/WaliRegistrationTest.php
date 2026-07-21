<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
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
 * Menguji alur pendaftaran wali murid (Fase 3): submit form + upload dokumen
 * (rules.md §3 penamaan/path), validasi NIK 16 digit, dan unggah bukti bayar.
 */
class WaliRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat wali (user + ortu) dan tahun ajaran aktif untuk pengujian.
     */
    private function setupWali(): User
    {
        AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
        Setting::set('nominal_pendaftaran', '150000');

        $user = User::create([
            'username' => 'Bunda Amira', 'email' => 'amira@test.id',
            'role' => 'ortu', 'password' => Hash::make('password123'),
        ]);
        $this->completeOrtu($user->id_users, [
            'ibu_nama' => 'BUNDA AMIRA', 'ibu_no_hp' => '628100000043',
        ]);

        return $user;
    }

    /**
     * Data form pendaftaran valid + berkas palsu.
     *
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'nama_anak' => 'Amira Safira',
            'nama_panggilan' => 'Amira',
            'nik' => '3273012345678901',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2021-05-15',
            'agama' => 'ISLAM',
            'anak_ke' => 1,
            'jumlah_saudara' => 0,
            'warga_negara' => 'WNI',
            'bahasa_keseharian' => 'INDONESIA',
            'kondisi_kesehatan' => 'SEHAT',
            'ukuran_baju' => 'S',
            'sudah_mengaji' => 'Belum',
            'pernah_belajar' => 'Belum',
            'kk' => UploadedFile::fake()->create('kk.pdf', 500, 'application/pdf'),
            'akta' => UploadedFile::fake()->create('akta.pdf', 500, 'application/pdf'),
            'foto' => UploadedFile::fake()->image('foto.jpg'),
        ];
    }

    /**
     * Pendaftaran valid membuat student, form, 3 dokumen, dan menyimpan berkas
     * dengan penamaan sesuai rules.md §3.
     */
    public function test_wali_can_submit_registration_with_documents(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();

        $response = $this->actingAs($user)->post('/portal/ortu/register', $this->validPayload());

        $response->assertRedirect(route('ortu.status'));
        $student = Student::where('nik', '3273012345678901')->first();
        $this->assertNotNull($student);
        $this->assertSame('AMIRA SAFIRA', $student->nama_lengkap); // T2.4: uppercase
        // L1.1: alamat kini di ortu (bukan siswa); nama wali dari ibu_nama helper
        $this->assertSame('BUNDA AMIRA', $user->ortu->fresh()->namaWali()); // profil wali dari completeOrtu (ibu_nama override)

        $form = RegistrationForm::where('id_student', $student->id_students)->first();
        $this->assertNotNull($form);
        $this->assertSame('submitted', $form->status);
        $this->assertCount(3, $form->documents);

        // K-foto: pas foto pendaftaran juga jadi foto profil siswa.
        $this->assertNotNull($student->fresh()->foto_path);
        Storage::disk('public')->assertExists($student->fresh()->foto_path);

        // Berkas tersimpan dengan penamaan baku rules.md §3 (nama anak: spasi -> '-', +suffix PK siswa)
        $sid = $student->id_students;
        Storage::disk('public')->assertExists("pendaftaran/2026-2027/Amira-Safira-{$sid}/KK_Amira-Safira-{$sid}.pdf");
        Storage::disk('public')->assertExists("pendaftaran/2026-2027/Amira-Safira-{$sid}/Akta-Kelahiran_Amira-Safira-{$sid}.pdf");
        Storage::disk('public')->assertExists("pendaftaran/2026-2027/Amira-Safira-{$sid}/Foto_Amira-Safira-{$sid}.jpg");
    }

    /**
     * NIK bukan 16 digit ditolak validasi.
     */
    public function test_registration_rejects_invalid_nik(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();

        $payload = $this->validPayload();
        $payload['nik'] = '123'; // kurang dari 16 digit

        $this->actingAs($user)->post('/portal/ortu/register', $payload)
            ->assertSessionHasErrors('nik');
        $this->assertDatabaseCount('students', 0);
    }

    /**
     * Berkas melebihi 2 MB ditolak validasi.
     */
    public function test_registration_rejects_oversized_file(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();

        $payload = $this->validPayload();
        $payload['kk'] = UploadedFile::fake()->create('kk.pdf', 3000, 'application/pdf'); // 3 MB

        $this->actingAs($user)->post('/portal/ortu/register', $payload)
            ->assertSessionHasErrors('kk');
    }

    /**
     * Wali dapat mengunggah bukti bayar; status form -> menunggu_verifikasi.
     */
    public function test_wali_can_upload_payment_proof(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();
        $this->actingAs($user)->post('/portal/ortu/register', $this->validPayload());

        $form = RegistrationForm::where('id_user', $user->id_users)->first();

        $response = $this->actingAs($user)->post('/portal/ortu/status/proof', [
            'id_registration_form' => $form->id_registration_forms,
            'jumlah' => 150000,
            'tanggal_bayar' => '2026-07-01',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $response->assertRedirect(route('ortu.status'));
        $this->assertDatabaseHas('payment_transactions', [
            'id_registration_form' => $form->id_registration_forms,
            'jenis' => 'pendaftaran', 'status' => 'pending', 'jumlah' => 150000,
        ]);
        $this->assertSame('menunggu_verifikasi', $form->fresh()->status);
    }

    /**
     * L2.3: duplikat bukti pendaftaran (pending masih ada) ditolak.
     */
    public function test_duplicate_registration_proof_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();
        $this->actingAs($user)->post('/portal/ortu/register', $this->validPayload());
        $form = RegistrationForm::where('id_user', $user->id_users)->first();

        // Pertama ok
        $this->actingAs($user)->post('/portal/ortu/status/proof', [
            'id_registration_form' => $form->id_registration_forms,
            'jumlah' => 150000, 'tanggal_bayar' => '2026-07-01',
            'bukti' => UploadedFile::fake()->image('b1.jpg'),
        ])->assertRedirect();

        // Kedua tolak
        $this->actingAs($user)->post('/portal/ortu/status/proof', [
            'id_registration_form' => $form->id_registration_forms,
            'jumlah' => 150000, 'tanggal_bayar' => '2026-07-01',
            'bukti' => UploadedFile::fake()->image('b2.jpg'),
        ])->assertSessionHasErrors('bukti');

        $this->assertDatabaseCount('payment_transactions', 1);
    }

    /**
     * K2.5: wali dapat mengedit pendaftaran saat status masih 'submitted'
     * (pembayaran belum diverifikasi) — data anak diperbarui.
     */
    public function test_wali_can_edit_registration_before_payment_verified(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();
        $this->actingAs($user)->post('/portal/ortu/register', $this->validPayload());
        $form = RegistrationForm::where('id_user', $user->id_users)->first();

        $payload = $this->validPayload();
        $payload['nama_anak'] = 'Amira Baru';
        unset($payload['kk'], $payload['akta'], $payload['foto']); // dokumen opsional saat edit

        $this->actingAs($user)->post('/portal/ortu/register/'.$form->slug, $payload)
            ->assertRedirect(route('ortu.status'));

        $this->assertSame('AMIRA BARU', $form->student->fresh()->nama_lengkap);
    }

    /**
     * K2.5: edit ditolak (403) bila pembayaran sudah diverifikasi & flag boleh_edit off.
     */
    public function test_wali_cannot_edit_after_payment_verified(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();
        $this->actingAs($user)->post('/portal/ortu/register', $this->validPayload());
        $form = RegistrationForm::where('id_user', $user->id_users)->first();
        $form->update(['status' => 'pembayaran_diverifikasi']);

        $this->actingAs($user)->get('/portal/ortu/register/'.$form->slug.'/edit')
            ->assertRedirect(route('ortu.status'));
        $this->actingAs($user)->post('/portal/ortu/register/'.$form->slug, $this->validPayload())
            ->assertForbidden();
    }

    /**
     * K2.5: admin buka flag boleh_edit -> wali edit + ganti dokumen -> dokumen
     * reset ke 'pending' & form mundur dari 'diproses_seleksi' ke
     * 'pembayaran_diverifikasi' (efek a). Flag dimatikan setelah simpan.
     */
    public function test_admin_can_reopen_edit_and_document_change_resets_status(): void
    {
        Storage::fake('public');
        $user = $this->setupWali();
        $this->actingAs($user)->post('/portal/ortu/register', $this->validPayload());
        $form = RegistrationForm::where('id_user', $user->id_users)->first();

        // Simulasi sudah diproses seleksi + dokumen diterima.
        $form->update(['status' => 'diproses_seleksi']);
        $form->documents()->update(['status' => 'diterima']);

        // Admin buka izin edit.
        $admin = User::create(['username' => 'Admin', 'email' => 'a@test.id', 'role' => 'admin', 'password' => Hash::make('x')]);
        $this->actingAs($admin)->post('/portal/admin/registrations/'.$form->slug.'/allow-edit')
            ->assertRedirect();
        $this->assertTrue($form->fresh()->boleh_edit);

        // Wali edit + ganti KK.
        $kkLama = $form->documents()->where('jenis', 'kk')->first()->path;
        Storage::disk('public')->assertExists($kkLama);
        $payload = $this->validPayload();
        unset($payload['akta'], $payload['foto']); // hanya ganti KK
        $this->actingAs($user)->post('/portal/ortu/register/'.$form->slug, $payload)
            ->assertRedirect(route('ortu.status'));

        $form->refresh();
        $this->assertSame('pembayaran_diverifikasi', $form->status); // mundur (efek a)
        $this->assertFalse($form->boleh_edit); // flag dimatikan
        $kkBaru = $form->documents()->where('jenis', 'kk')->first();
        $this->assertSame('pending', $kkBaru->status); // KK reset
        $this->assertSame('diterima', $form->documents()->where('jenis', 'akta')->first()->status); // Akta tak tersentuh
        // Berkas KK lama dihapus bila path berubah (tak menyampah storage).
        if ($kkBaru->path !== $kkLama) {
            Storage::disk('public')->assertMissing($kkLama);
        }
    }

    /**
     * L2.5 (fix): validasi usia pakai tahun ajaran, bukan tahun kalender.
     * TA "2027/2028" (daftar Okt 2026) → cut-off 1 Juli 2027.
     * Lahir 2020-07-01 → >6 tahun per 1 Juli 2027 → ditolak.
     */
    public function test_age_validation_uses_academic_year_not_calendar_year(): void
    {
        Storage::fake('public');
        AcademicYear::create(['tahun' => '2027/2028', 'is_aktif' => true]);
        Setting::set('nominal_pendaftaran', '150000');
        $user = User::create(['username' => 'Test', 'email' => 'test@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        // Profil wali lengkap (gate K2.1) agar validasi form pendaftaran benar-benar jalan.
        $this->completeOrtu($user->id_users, [
            'ibu_nama' => 'TEST WALI', 'ibu_no_hp' => '628100000099',
        ]);

        $payload = $this->validPayload();
        // Lahir 2020-07-01 → >6 th per 1 Juli 2027 → ditolak.
        // (Kode lama pakai now()->year=2026 → cut-off 1 Juli 2026 → keliru menerima.)
        $payload['tanggal_lahir'] = '2020-07-01';

        $response = $this->actingAs($user)->post('/portal/ortu/register', $payload);
        $response->assertSessionHasErrors('tanggal_lahir');
    }
}