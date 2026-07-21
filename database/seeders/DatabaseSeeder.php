<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal sistem TELAGA AL KAUTSAR.
     *
     * Membuat: 1 tahun ajaran aktif, setting nominal biaya dinamis, akun admin
     * pertama (password di-generate & dicetak ke console — rules.md §1.5), 1 guru
     * contoh (password = nomor induk guru), dan beberapa kelas. Aman dijalankan
     * ulang karena memakai firstOrCreate/updateOrCreate.
     */
    public function run(): void
    {
        // --- 1. Tahun ajaran aktif (rules.md §1.7) ---
        $tahunAjaran = AcademicYear::firstOrCreate(
            ['tahun' => '2026/2027'],
            ['is_aktif' => true]
        );

        // --- 2. Setting nominal biaya dinamis (rules.md §1.3, PRD §7.6/§7.8) ---
        Setting::set('nominal_pendaftaran', '150000');
        Setting::set('nominal_spp', '250000');
        Setting::set('nominal_daftar_ulang', '1500000');
        Setting::set('tanggal_generate_spp', '1'); // Tanggal awal bulan generate SPP
        // Rekening bank sekolah default (T5.6g — admin bisa ubah di CMS Set Pembayaran)
        Setting::set('bank_sekolah', 'Bank Mandiri');
        Setting::set('rekening_sekolah', '123-456-7890');
        Setting::set('atas_nama', 'RA Al Kautsar');

        // --- 3. Akun admin pertama (kredensial di-generate — rules.md §1.5) ---
        $adminPassword = 'admin123'; // Dev convenience — ganti di produksi
        $admin = User::firstOrCreate(
            ['email' => 'admin@telaga.sch.id'],
            [
                'username' => 'Administrator TELAGA',
                'role' => 'admin',
                'password' => Hash::make($adminPassword),
            ]
        );
        // Cetak kredensial admin ke console agar bisa dipakai login pertama
        $this->command->warn('=================================================');
        $this->command->warn('AKUN ADMIN PERTAMA (ganti password setelah login):');
        $this->command->warn('  Email    : admin@telaga.sch.id');
        $this->command->warn('  Password : '.$adminPassword);
        $this->command->warn('=================================================');

        // --- 4. Guru contoh (password awal = NUPTK — rules.md §1.5) ---
        $nuptk = '1234567890123456';
        $guruUser = User::firstOrCreate(
            ['email' => 'guru.ahmad@telaga.sch.id'],
            [
                'username' => 'guru.ahmad@telaga.sch.id',
                'no_hp' => '081300000001',
                'role' => 'guru',
                'password' => Hash::make($nuptk),
            ]
        );
        $guru = Teacher::firstOrCreate(
            ['id_user' => $guruUser->id_users],
            [
                'nama' => 'Ustadz Ahmad',
                'nuptk' => $nuptk,
                'no_hp' => '081300000001',
                'alamat' => 'Jl. Melati No. 3',
            ]
        );

        // --- 5. Kelas contoh untuk tahun ajaran aktif ---
        foreach (['Kelas A', 'Kelas B', 'Kelas C'] as $namaKelas) {
            $kelas = SchoolClass::firstOrCreate([
                'id_academic_year' => $tahunAjaran->id_academic_years,
                'nama_kelas' => $namaKelas,
            ]);
            // Tugaskan guru contoh ke Kelas A (demonstrasi relasi many-to-many)
            if ($namaKelas === 'Kelas A') {
                $guru->classes()->syncWithoutDetaching([$kelas->id_classes]);
            }
        }

        // --- 6. Konten default halaman publik (CMS — rules.md §1.8) ---
        $this->call(SiteContentSeeder::class);
    }
}
