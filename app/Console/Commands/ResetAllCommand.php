<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Command app:reset-all — factory reset database dan file upload.
 *
 * Mengosongkan seluruh data aplikasi (19 tabel) dan direktori upload,
 * lalu membuat ulang: 1 tahun ajaran aktif, settings default, dan akun
 * admin. Tabel referensi (wilayah, bank) dan konten CMS (site_contents)
 * tidak disentuh.
 *
 * @see config/filesystems.php — disk 'public' = storage/app/public
 */
class ResetAllCommand extends Command
{
    protected $signature = 'app:reset-all {--force : Lewati konfirmasi}';

    protected $description = 'Factory reset: hapus semua data + file upload, pertahankan admin + konten CMS';

    /**
     * Tabel aplikasi yang akan dikosongkan (urutan tidak penting karena FK
     * checks dinonaktifkan). Tabel referensi dan site_contents dilewati.
     */
    private const TABLES = [
        'registration_documents',
        'class_teacher',
        'student_progress_notes',
        'payment_transactions',
        'monthly_spp_bills',
        're_registration_payments',
        'registration_forms',
        'students',
        'parents',
        'teachers',
        'classes',
        'audit_logs',
        'site_features',
        'settings',
        'academic_years',
        'sessions',
        'password_reset_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'users',
    ];

    /**
     * Tabel referensi yang DI-PERTAHANKAN.
     */
    private const KEEP_TABLES = [
        't_provinsi',
        't_kota',
        't_kecamatan',
        't_kelurahan',
        'banks',
        'site_contents',
    ];

    /**
     * Direktori upload yang akan dibersihkan (relatif disk 'public').
     */
    private const UPLOAD_DIRS = [
        'konten',
        'pembayaran',
        'pendaftaran',
        'profil',
    ];

    public function handle(): int
    {
        if (! $this->option('force')) {
            $confirm = $this->ask(
                '⚠ PERINGATAN: Semua data aplikasi dan file upload akan dihapus permanen.'.PHP_EOL.
                '  Hanya tabel referensi (wilayah, bank) dan konten CMS yg dipertahankan.'.PHP_EOL.
                '  Ketik "RESET" untuk konfirmasi:'
            );
            if ($confirm !== 'RESET') {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        // ── 1. Hapus file upload ──
        $this->line('── Menghapus file upload ──');
        $disk = Storage::disk('public');
        foreach (self::UPLOAD_DIRS as $dir) {
            if ($disk->exists($dir)) {
                $count = count($disk->allFiles($dir));
                $disk->deleteDirectory($dir);
                $this->line("  Dihapus: {$dir}/ ({$count} file)");
                // Buat ulang direktori agar tetap ada
                $disk->makeDirectory($dir);
            }
        }

        // ── 2. Truncate tabel aplikasi ──
        $this->line('── Mengosongkan tabel aplikasi ──');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
            $this->line("  Truncate: {$table}");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── 3. Seed ulang data wajib ──
        $this->line('── Membuat ulang data wajib ──');

        // 3a. Tahun ajaran aktif (2026/2027)
        AcademicYear::create([
            'tahun' => '2026/2027',
            'is_aktif' => true,
        ]);
        $this->line('  AcademicYear: 2026/2027 (aktif)');

        // 3b. Settings default (DatabaseSeeder lines 32-39)
        Setting::set('nominal_pendaftaran', '150000');
        Setting::set('nominal_spp', '250000');
        Setting::set('nominal_daftar_ulang', '1500000');
        Setting::set('tanggal_generate_spp', '1');
        Setting::set('bank_sekolah', 'Bank Mandiri');
        Setting::set('rekening_sekolah', '123-456-7890');
        Setting::set('atas_nama', 'RA Al Kautsar');
        $this->line('  Settings: 7 default');

        // 3c. Akun admin
        User::create([
            'username' => 'admin',
            'email' => 'admin@telaga.sch.id',
            'role' => 'admin',
            'password' => Hash::make('admin123'),
        ]);
        $this->line('  Admin: admin / admin123');

        // ── 4. Ringkasan ──
        $this->newLine();
        $this->info('✔ Factory reset selesai.');
        $this->warn('  Tabel dikosongkan : '.count(self::TABLES));
        $this->warn('  Tabel dipertahankan: '.implode(', ', self::KEEP_TABLES));
        $this->warn('  File upload dihapus: '.count(self::UPLOAD_DIRS).' direktori');
        $this->warn('  Admin: admin / admin123');

        return self::SUCCESS;
    }
}
