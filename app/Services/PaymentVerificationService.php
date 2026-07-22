<?php

namespace App\Services;

use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\ReRegistrationPayment;
use Illuminate\Support\Facades\DB;

/**
 * PaymentVerificationService — sumber kebenaran perhitungan saldo (PRD §13).
 *
 * Saat Admin memverifikasi/menolak sebuah transaksi, service ini menghitung
 * ulang `jumlah_terbayar` tagihan terkait dari TOTAL transaksi berstatus
 * 'diverifikasi' (dihitung dinamis, bukan disimpan mutable), lalu memperbarui
 * status tagihan (belum_lunas / kurang / lunas). Ini mencegah salah akumulasi
 * saldo cicilan (risiko utama PRD §13).
 */
class PaymentVerificationService
{
    /**
     * Menyetujui sebuah transaksi pembayaran.
     *
     * Menandai transaksi 'diverifikasi', mencatat admin verifikator, lalu
     * menyinkronkan saldo tagihan terkait (SPP / daftar ulang).
     *
     * @param  PaymentTransaction  $trx        Transaksi yang diverifikasi.
     * @param  int                 $adminId    id_users admin verifikator.
     */
    public function approve(PaymentTransaction $trx, int $adminId): void
    {
        DB::transaction(function () use ($trx, $adminId) {
            $trx->update(['status' => 'diverifikasi', 'verified_by' => $adminId]);
            $this->syncBill($trx);
        });
    }

    /**
     * Menolak sebuah transaksi pembayaran dengan catatan alasan.
     *
     * Status menjadi 'ditolak'; saldo tagihan disinkron ulang (transaksi ini
     * tidak lagi dihitung sebagai terbayar).
     *
     * @param  PaymentTransaction  $trx      Transaksi yang ditolak.
     * @param  int                 $adminId  id_users admin verifikator.
     * @param  string              $catatan  Alasan penolakan.
     */
    public function reject(PaymentTransaction $trx, int $adminId, string $catatan): void
    {
        DB::transaction(function () use ($trx, $adminId, $catatan) {
            $trx->update(['status' => 'ditolak', 'verified_by' => $adminId, 'catatan' => $catatan]);
            $this->syncBill($trx);
        });
    }

    /**
     * Menyinkronkan saldo tagihan terkait transaksi (SPP atau daftar ulang),
     * ATAU memajukan/memundurkan status formulir untuk transaksi pendaftaran.
     *
     * jumlah_terbayar = SUM transaksi 'diverifikasi' untuk tagihan tsb; status
     * dihitung ulang: lunas bila >= nominal, kurang bila 0 < terbayar < nominal,
     * selain itu belum_lunas. Transaksi pendaftaran tak punya tagihan cicilan —
     * verifikasinya menggerakkan status RegistrationForm (menunggu_verifikasi
     * → pembayaran_diverifikasi bila diverifikasi; kembali ke menunggu_bukti
     * agar wali unggah ulang bila ditolak).
     */
    private function syncBill(PaymentTransaction $trx): void
    {
        if ($trx->jenis === 'pendaftaran') {
            // Muat form segar (bukan relasi cached yang bisa basi bila ada >1 trx).
            $form = $trx->registrationForm()->first();
            // Hanya bergerak dari tahap awal alur (jangan timpa status lanjut
            // seperti diproses_seleksi/lulus/gagal saat verifikasi ulang).
            if ($form && in_array($form->status, ['menunggu_verifikasi', 'menunggu_bukti', 'submitted'], true)) {
                $form->update([
                    'status' => $trx->status === 'diverifikasi' ? 'pembayaran_diverifikasi' : 'menunggu_bukti',
                ]);
            }

            return;
        }

        if ($trx->jenis === 'spp' && $trx->referensi_id) {
            $bill = MonthlySppBill::find($trx->referensi_id);
            if ($bill) {
                $terbayar = $this->sumVerified('spp', $bill->id_monthly_spp_bills);
                $bill->update([
                    'jumlah_terbayar' => $terbayar,
                    'status' => $this->statusFor($terbayar, (float) $bill->nominal),
                ]);
            }
        } elseif ($trx->jenis === 'daftar_ulang' && $trx->referensi_id) {
            $daftar = ReRegistrationPayment::find($trx->referensi_id);
            if ($daftar) {
                $terbayar = $this->sumVerified('daftar_ulang', $daftar->id_re_registration_payments);
                $daftar->update([
                    'jumlah_terbayar' => $terbayar,
                    'status' => $this->statusFor($terbayar, (float) $daftar->total_biaya),
                ]);
            }
        }
    }

    /**
     * Menjumlahkan seluruh transaksi 'diverifikasi' untuk sebuah tagihan.
     *
     * @param  string  $jenis        Jenis pembayaran ('spp' | 'daftar_ulang').
     * @param  int     $referensiId  ID tagihan yang direferensikan transaksi.
     * @return float   Total nominal terverifikasi.
     */
    private function sumVerified(string $jenis, int $referensiId): float
    {
        return (float) PaymentTransaction::where('jenis', $jenis)
            ->where('referensi_id', $referensiId)
            ->where('status', 'diverifikasi')
            ->sum('jumlah');
    }

    /**
     * Menentukan status tagihan dari jumlah terbayar vs nominal total.
     */
    private function statusFor(float $terbayar, float $nominal): string
    {
        if ($terbayar >= $nominal) {
            return 'lunas';
        }

        return $terbayar > 0 ? 'kurang' : 'belum_lunas';
    }
}
