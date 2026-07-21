<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\RegistrationDocument;
use App\Models\RegistrationForm;
use App\Models\ReRegistrationPayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder — data contoh kaya skenario untuk peninjauan tampilan & laporan (Tahap 3).
 *
 * Skenario yang dibangun:
 *  1. Wali A (Bunda Amira) punya 2 anak sekaligus di tahun ajaran aktif
 *     (satu sudah lulus & dicicil, satu masih menunggu verifikasi).
 *  2. Wali B (Bapak Rizal) mendaftarkan kakak tahun lalu (2025/2026, lulus & lunas)
 *     dan adik tahun ini (2026/2027) — skenario lintas tahun ajaran.
 *  3. Banyak transaksi pembayaran (verified/pending/ditolak) untuk laporan CSV/PDF.
 *
 * Jalankan: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // --- Tahun ajaran: tahun lalu (arsip) + tahun aktif ---
        $taLalu = AcademicYear::firstOrCreate(['tahun' => '2025/2026'], ['is_aktif' => false]);
        $taAktif = AcademicYear::firstOrCreate(['tahun' => '2026/2027'], ['is_aktif' => true]);

        // --- Guru + kelas untuk tiap tahun ---
        $guru = $this->guru('Ustadz Ahmad, S.Ag', 'guru.ahmad@telaga.sch.id', '1234567890123456');
        $kelasLalu = SchoolClass::firstOrCreate(['id_academic_year' => $taLalu->id_academic_years, 'nama_kelas' => 'Kelas A - Sabar']);
        $kelasAktif = SchoolClass::firstOrCreate(['id_academic_year' => $taAktif->id_academic_years, 'nama_kelas' => 'Kelas A - Rahmat']);
        $kelasAktifB = SchoolClass::firstOrCreate(['id_academic_year' => $taAktif->id_academic_years, 'nama_kelas' => 'Kelas B - Syukur']);
        $guru->classes()->syncWithoutDetaching([$kelasLalu->id_classes, $kelasAktif->id_classes, $kelasAktifB->id_classes]);

        // ============ SKENARIO 1: Wali A, 2 anak sekaligus (tahun aktif) ============
        $waliA = $this->wali('Bunda Amira', 'wali@telaga.sch.id', '081234567890', 'Jl. Melati No. 4, Bandung');

        // Anak 1: sudah lulus, ditempatkan, cicil daftar ulang + SPP
        $anak1 = $this->student($waliA, $kelasAktif, $taAktif, '3273010000000001', 'Amira Safira', 'P', '2021-05-15', 'aktif');
        $this->registration($waliA, $anak1, $taAktif, 'lulus', 'diterima');
        $this->reReg($anak1, $taAktif, 1500000, 1300000, 'kurang');   // kurang 200rb
        $this->spp($anak1, $taAktif, '2026-07', 250000, 100000, 'kurang'); // kurang 150rb
        $this->spp($anak1, $taAktif, '2026-08', 250000, 250000, 'lunas');
        $this->trx($anak1, $waliA, 'daftar_ulang', 1300000, 'diverifikasi', '2026-06-20');
        $this->trx($anak1, $waliA, 'spp', 100000, 'diverifikasi', '2026-07-02');
        $this->trx($anak1, $waliA, 'spp', 250000, 'diverifikasi', '2026-08-01');

        // Anak 2: baru daftar, menunggu verifikasi pembayaran
        $anak2 = $this->student($waliA, null, $taAktif, '3273010000000002', 'Aisyah Safira', 'P', '2023-03-10', 'aktif');
        $this->registration($waliA, $anak2, $taAktif, 'menunggu_verifikasi', 'pending');
        $this->trx($anak2, $waliA, 'pendaftaran', 150000, 'pending', '2026-07-10');

        // ============ SKENARIO 2: Wali B, kakak (thn lalu) + adik (thn ini) ============
        $waliB = $this->wali('Bapak Rizal', 'rizal@telaga.sch.id', '081298765432', 'Jl. Kenanga No. 8, Bandung');

        // Kakak: tahun lalu, lulus & lunas semua (arsip)
        $kakak = $this->student($waliB, $kelasLalu, $taLalu, '3273010000000003', 'Rizal Akbar', 'L', '2020-01-20', 'aktif');
        $this->registration($waliB, $kakak, $taLalu, 'lulus', 'diterima');
        $this->reReg($kakak, $taLalu, 1400000, 1400000, 'lunas');
        $this->spp($kakak, $taLalu, '2025-07', 250000, 250000, 'lunas');
        $this->trx($kakak, $waliB, 'daftar_ulang', 1400000, 'diverifikasi', '2025-06-15');
        $this->trx($kakak, $waliB, 'spp', 250000, 'diverifikasi', '2025-07-05');

        // Adik: tahun ini, lulus, baru mulai cicil (ada 1 transaksi ditolak sbg variasi)
        $adik = $this->student($waliB, $kelasAktifB, $taAktif, '3273010000000004', 'Rizka Akbar', 'P', '2022-09-05', 'aktif');
        $this->registration($waliB, $adik, $taAktif, 'lulus', 'diterima');
        $this->reReg($adik, $taAktif, 1500000, 500000, 'kurang');
        $this->spp($adik, $taAktif, '2026-08', 250000, 0, 'belum_lunas');
        $this->trx($adik, $waliB, 'daftar_ulang', 500000, 'diverifikasi', '2026-07-18');
        $this->trx($adik, $waliB, 'daftar_ulang', 300000, 'ditolak', '2026-07-25', 'Nominal transfer tidak sesuai');

        $this->command->info('DemoSeeder selesai.');
        $this->command->info('  Wali A (2 anak):        wali@telaga.sch.id / password');
        $this->command->info('  Wali B (kakak+adik):    rizal@telaga.sch.id / password');
        $this->command->info('  Guru:                   guru.ahmad@telaga.sch.id (atau 081311112222) / 1234567890123456');
    }

    /** Membuat/mengambil akun guru + profil. Param ketiga = NUPTK (juga password awal). */
    private function guru(string $nama, string $email, string $nuptk): Teacher
    {
        $u = User::firstOrCreate(['email' => $email], ['username' => $email, 'no_hp' => '081311112222', 'role' => 'guru', 'password' => Hash::make($nuptk)]);

        return Teacher::firstOrCreate(['id_user' => $u->id_users], ['nama' => $nama, 'nuptk' => $nuptk, 'no_hp' => '081311112222', 'jabatan' => 'Guru Utama / Wali Kelas', 'tampil_di_web' => true]);
    }

    /** Membuat/mengambil akun wali + profil ortu (skema L1.1: ibu_* + alamat). */
    private function wali(string $nama, string $email, string $hp, string $alamat): User
    {
        $u = User::firstOrCreate(['email' => $email], ['username' => $email, 'no_hp' => $hp, 'role' => 'ortu', 'password' => Hash::make('password')]);
        OrangTua::firstOrCreate(['id_user' => $u->id_users], [
            'ada_ayah' => false, 'ada_ibu' => true,
            'ibu_nama' => strtoupper($nama), 'ibu_no_hp' => $hp,
            'ibu_pekerjaan' => 'WIRASWASTA', 'ibu_tempat_lahir' => 'BANDUNG',
            'ibu_tanggal_lahir' => '1990-01-01', 'ibu_agama' => 'ISLAM',
            'ibu_pendidikan' => 'SMA', 'ibu_penghasilan' => '1 - 2 JUTA',
            'alamat' => $alamat,
        ]);

        return $u;
    }

    /** Membuat/mengambil data siswa milik wali. */
    private function student(User $wali, ?SchoolClass $kelas, AcademicYear $ta, string $nik, string $nama, string $jk, string $lahir, string $status): Student
    {
        return Student::firstOrCreate(['nik' => $nik], [
            'id_parent' => $wali->ortu->id_parents,
            'id_class' => $kelas?->id_classes,
            'id_academic_year' => $ta->id_academic_years,
            'nama_lengkap' => $nama,
            'nama_panggilan' => explode(' ', $nama)[0],
            'jenis_kelamin' => $jk,
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => $lahir,
            'status' => $status,
        ]);
    }

    /** Membuat formulir pendaftaran + 3 dokumen. */
    private function registration(User $wali, Student $s, AcademicYear $ta, string $status, string $docStatus): void
    {
        $form = RegistrationForm::firstOrCreate(['id_student' => $s->id_students], [
            'id_user' => $wali->id_users, 'id_academic_year' => $ta->id_academic_years, 'status' => $status,
        ]);
        foreach (['kk', 'akta', 'foto'] as $jenis) {
            RegistrationDocument::firstOrCreate(
                ['id_registration_form' => $form->id_registration_forms, 'jenis' => $jenis],
                ['path' => "pendaftaran/{$ta->tahun}/{$s->nama_lengkap}/{$jenis}.jpg", 'status' => $docStatus],
            );
        }
    }

    /** Membuat tagihan daftar ulang. */
    private function reReg(Student $s, AcademicYear $ta, int $total, int $bayar, string $status): void
    {
        ReRegistrationPayment::firstOrCreate(
            ['id_student' => $s->id_students, 'id_academic_year' => $ta->id_academic_years],
            ['total_biaya' => $total, 'jumlah_terbayar' => $bayar, 'status' => $status],
        );
    }

    /** Membuat tagihan SPP bulanan. */
    private function spp(Student $s, AcademicYear $ta, string $bulan, int $nominal, int $bayar, string $status): void
    {
        MonthlySppBill::firstOrCreate(
            ['id_student' => $s->id_students, 'bulan' => $bulan],
            ['id_academic_year' => $ta->id_academic_years, 'nominal' => $nominal, 'jumlah_terbayar' => $bayar, 'status' => $status],
        );
    }

    /** Membuat catatan transaksi pembayaran (riwayat/laporan). */
    private function trx(Student $s, User $wali, string $jenis, int $jumlah, string $status, string $tgl, ?string $catatan = null): void
    {
        PaymentTransaction::firstOrCreate(
            ['id_student' => $s->id_students, 'jenis' => $jenis, 'jumlah' => $jumlah, 'tanggal_bayar' => $tgl],
            [
                'id_user' => $wali->id_users,
                'bukti_path' => "pembayaran/{$jenis}/demo-{$s->id_students}-{$jumlah}.jpg",
                'status' => $status,
                'catatan' => $catatan,
            ],
        );
    }
}
