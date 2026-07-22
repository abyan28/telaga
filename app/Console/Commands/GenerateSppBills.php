<?php

namespace App\Console\Commands;

use App\Mail\RegistrationNotification;
use App\Models\AcademicYear;
use App\Models\MonthlySppBill;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Command spp:generate — membuat tagihan SPP bulanan untuk siswa aktif.
 *
 * Idempotent (firstOrCreate per id_student+bulan) sehingga aman dijalankan
 * berulang tanpa tagihan dobel (rules.md §1.3.5). Nominal diambil dari setting
 * dinamis. Dijadwalkan tiap awal bulan (Scheduler) dan dapat dipicu manual dari CMS.
 */
class GenerateSppBills extends Command
{
    // Signature: opsi --bulan untuk override bulan target (default: bulan berjalan)
    protected $signature = 'spp:generate {--bulan= : Bulan target format YYYY-MM (default bulan ini)}';

    protected $description = 'Generate tagihan SPP bulanan untuk seluruh siswa aktif';

    /**
     * Menjalankan pembuatan tagihan SPP.
     *
     * @return int  Kode keluar command (0 = sukses).
     */
    public function handle(): int
    {
        // Bulan target (override via opsi atau default bulan berjalan)
        $bulan = $this->option('bulan') ?: now()->format('Y-m');

        // Tahun ajaran aktif sebagai acuan (rules.md §1.7)
        $tahunAjaran = AcademicYear::where('is_aktif', true)->first();
        if (! $tahunAjaran) {
            $this->error('Tidak ada tahun ajaran aktif. Batalkan generate SPP.');

            return self::FAILURE;
        }

        // Nominal SPP dinamis (diatur Admin)
        $nominal = (int) Setting::get('nominal_spp', 0);

        // Buat tagihan hanya untuk siswa berstatus aktif
        $count = 0;
        foreach (Student::with('ortu.user')->where('status', 'aktif')->cursor() as $student) {
            $bill = MonthlySppBill::firstOrCreate(
                [
                    'id_student' => $student->id_students,
                    'bulan' => $bulan,
                ],
                [
                    'id_academic_year' => $tahunAjaran->id_academic_years,
                    'nominal' => $nominal,
                    'jumlah_terbayar' => 0,
                    'status' => 'belum_lunas',
                ],
            );

            // Hitung hanya tagihan yang benar-benar baru dibuat
            if ($bill->wasRecentlyCreated) {
                $count++;

                // Kirim email notifikasi tagihan baru ke wali (PRD §7.13).
                $email = $student->ortu?->user?->email;
                if ($email) {
                    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
                    $detail = [
                        'Nama Siswa' => $student->nama_lengkap,
                        'Bulan' => \Carbon\Carbon::parse($bulan.'-01')->translatedFormat('F Y'),
                        'Nominal SPP' => $rp($nominal),
                    ];
                    $rekening = trim(Setting::get('bank_sekolah', '').' '.Setting::get('rekening_sekolah', ''));
                    if ($rekening !== '') {
                        $atasNama = Setting::get('atas_nama', '');
                        $detail['Rekening Sekolah'] = $rekening.($atasNama ? ' a.n. '.$atasNama : '');
                    }
                    Mail::to($email)->send(new RegistrationNotification(
                        'Tagihan SPP Baru — '.$student->nama_lengkap,
                        'Tagihan SPP bulanan untuk '.$student->nama_lengkap.' telah diterbitkan. Silakan lakukan pembayaran sebelum batas waktu yang ditentukan.',
                        $detail,
                    ));
                }
            }
        }

        $this->info("Generate SPP {$bulan}: {$count} tagihan baru dibuat.");

        return self::SUCCESS;
    }
}
