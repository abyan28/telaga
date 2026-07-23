<?php

namespace App\Services;

use App\Mail\RegistrationNotification;
use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\ReRegistrationPayment;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

        // Notifikasi email ke wali: pembayaran diverifikasi (PRD §7.13).
        $this->notifikasiWali($trx);
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

    /**
     * Kirim email notifikasi ke wali bahwa pembayaran BERHASIL diverifikasi,
     * lengkap dengan rincian transaksi (PRD §7.13). Dipanggil dari approve().
     *
     * Skip: jenis 'refund' (bukan pembayaran wali) & wali tanpa email.
     * Rekening tujuan sekolah diambil dari settings (konsisten modul keuangan).
     */
    private function notifikasiWali(PaymentTransaction $trx): void
    {
        if ($trx->jenis === 'refund') {
            return;
        }

        $trx->loadMissing(['user', 'student']);
        $email = $trx->user?->email;
        if (! $email) {
            return; // Wali belum isi email → tak ada tujuan kirim.
        }

        // Label jenis pembayaran untuk judul & rincian.
        $labelJenis = match ($trx->jenis) {
            'pendaftaran' => 'Biaya Pendaftaran (PPDB)',
            'daftar_ulang' => 'Daftar Ulang',
            'spp' => 'SPP Bulanan',
            default => ucfirst($trx->jenis),
        };

        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');

        // Rincian transaksi.
        $detail = [
            'Nama Siswa' => $trx->student?->nama_lengkap ?? '-',
            'Jenis Pembayaran' => $labelJenis,
        ];

        // SPP: sertakan bulan tagihan.
        if ($trx->jenis === 'spp' && $trx->referensi_id) {
            $bill = MonthlySppBill::find($trx->referensi_id);
            if ($bill) {
                $detail['Bulan'] = \Carbon\Carbon::parse($bill->bulan.'-01')->translatedFormat('F Y');
            }
        }

        $detail['Jumlah Dibayar'] = $rp($trx->jumlah);
        if ($trx->bank_asal) {
            $detail['Bank Asal'] = $trx->bank_asal;
        }
        $detail['Tanggal Bayar'] = $trx->tanggal_bayar
            ? $trx->tanggal_bayar->translatedFormat('d F Y')
            : '-';

        // Sisa tunggakan (DU & SPP boleh parsial → tampilkan sisa).
        if ($trx->jenis === 'daftar_ulang' && $trx->referensi_id) {
            $daftar = ReRegistrationPayment::find($trx->referensi_id);
            if ($daftar) {
                $detail['Sisa Tunggakan'] = $rp($daftar->sisa());
            }
        } elseif ($trx->jenis === 'spp' && isset($bill)) {
            $detail['Sisa Tunggakan'] = $rp($bill->fresh()->sisa());
        }

        // Rekening tujuan sekolah (dari settings).
        $bankSekolah     = Setting::get('bank_sekolah', '');
        $rekeningSekolah = Setting::get('rekening_sekolah', '');
        $atasNama        = Setting::get('atas_nama', '');
        $rekening = trim($bankSekolah.' '.$rekeningSekolah);
        if ($rekening !== '') {
            $detail['Rekening Sekolah'] = $rekening.($atasNama ? ' a.n. '.$atasNama : '');
        }

        $mailable = (new RegistrationNotification(
            'Pembayaran '.$labelJenis.' Terverifikasi',
            'Pembayaran '.$labelJenis.' atas nama '.($trx->student?->nama_lengkap ?? 'siswa').' telah berhasil diverifikasi oleh Admin. Berikut rincian transaksinya.',
            $detail,
        ))->onQueue('spp-notifications');
        Mail::to($email)->queue($mailable);
    }
}
