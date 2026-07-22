<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstallmentRequest;
use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\ReRegistrationPayment;
use App\Models\RegistrationForm;
use App\Models\Student;
use App\Services\DocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * PaymentController (Wali) — pembayaran cicilan SPP & Daftar Ulang (PRD §7.7/§7.8).
 *
 * Wali mengunggah bukti pembayaran parsial; transaksi dicatat berstatus 'pending'
 * lalu diverifikasi Admin (PaymentVerificationService menyinkron saldo). Sisa
 * tunggakan dihitung dinamis (nominal - jumlah_terbayar) — PRD §13.
 */
class PaymentController extends Controller
{
    /**
     * Menampilkan halaman pembayaran: tagihan SPP & daftar ulang milik anak wali.
     */
    public function index(): View
    {
        // Ambil siswa milik wali yang login (lewat profil ortu)
        $ortuId = Auth::user()->ortu?->id_parents;

        // L2.2: PPDB ditutup + belum bayar daftar ulang sama sekali → batalkan
        // status lulus (form gagal, siswa nonaktif) sebelum tagihan ditampilkan.
        RegistrationForm::where('id_user', Auth::id())->where('status', 'lulus')
            ->with('student')->get()
            ->each->cancelLulusIfPpdbClosedAndUnpaid();

        $students = Student::where('id_parent', $ortuId)
            ->with(['monthlySppBills.transactions', 'reRegistrationPayments.transactions'])
            ->get();

        // Riwayat transaksi cicilan wali (untuk daftar riwayat)
        // K4.2: eager-load student (atas nama anak) & sppBill (bulan SPP).
        $history = PaymentTransaction::where('id_user', Auth::id())
            ->whereIn('jenis', ['spp', 'daftar_ulang'])
            ->with(['student', 'sppBill'])
            ->latest('id_payment_transactions')
            ->get();

        // L2.2: status PPDB untuk UI guard tombol upload DU
        $ppdbDibuka = \App\Models\Setting::get('pendaftaran_dibuka', '1') === '1';

        // Rekening tujuan sekolah (T2.2) + daftar bank asal untuk dropdown (T8.1)
        $rekening = [
            'bank' => \App\Models\Setting::get('bank_sekolah', ''),
            'nomor' => \App\Models\Setting::get('rekening_sekolah', ''),
            'atas_nama' => \App\Models\Setting::get('atas_nama', ''),
        ];
        $daftarBank = \App\Models\Bank::daftarNama();

        return view('ortu.payments', compact('students', 'history', 'rekening', 'daftarBank', 'ppdbDibuka'));
    }

    /**
     * Menyimpan pembayaran cicilan (SPP atau Daftar Ulang) berstatus 'pending'.
     *
     * Memvalidasi kepemilikan tagihan (anak milik wali) dan mencegah bayar
     * melebihi sisa tunggakan. Sinkronisasi saldo terjadi saat Admin memverifikasi.
     */
    public function store(StoreInstallmentRequest $request, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validated();
        $ortuId = Auth::user()->ortu?->id_parents;

        // Ambil tagihan sesuai jenis + pastikan milik anak wali ini + hitung sisa
        [$student, $sisa, $bulan] = $this->resolveBill($data['jenis'], (int) $data['referensi_id'], $ortuId);

        // Tolak bila nominal melebihi sisa tunggakan (cegah overpay)
        if ($data['jumlah'] > $sisa) {
            return back()->withErrors(['jumlah' => 'Nominal melebihi sisa tunggakan (Rp '.number_format($sisa, 0, ',', '.').').']);
        }
        // K10.4: SPP wajib lunas (jumlah == sisa), daftar ulang boleh cicil
        if ($data['jenis'] === 'spp' && $data['jumlah'] < $sisa) {
            return back()->withErrors(['jumlah' => 'Pembayaran SPP harus lunas (tidak dapat dicicil). Sisa: Rp '.number_format($sisa, 0, ',', '.').'.']);
        }

        // L2.2: PPDB ditutup + belum bayar daftar ulang sama sekali → blok upload
        // (kelulusan sudah dibatalkan; wali tak boleh lagi menyetor bukti).
        if ($data['jenis'] === 'daftar_ulang'
            && \App\Models\Setting::get('pendaftaran_dibuka', '1') !== '1'
            && (float) $student->reRegistrationPayments()->where('id_re_registration_payments', $data['referensi_id'])->value('jumlah_terbayar') <= 0) {
            return back()->withErrors(['bukti' => 'Pendaftaran sudah ditutup dan Anda belum membayar daftar ulang. Kesempatan pembayaran telah berakhir.']);
        }

        // L2.3: cegah duplikat unggahan saat masih ada transaksi pending
        if (PaymentTransaction::where('jenis', $data['jenis'])->where('referensi_id', $data['referensi_id'])->where('status', 'pending')->exists()) {
            return back()->withErrors(['bukti' => 'Masih ada bukti yang belum diverifikasi untuk tagihan ini.']);
        }

        // Simpan bukti transfer (per-siswa, nama detail) & catat transaksi
        $student->loadMissing('academicYear');
        $path = $uploader->storePaymentProof(
            $request->file('bukti'), $data['jenis'],
            $student->nama_lengkap ?? 'tanpa-nama',
            $student->academicYear?->tahun ?? '-',
            $bulan,
            $student->id_students,
        );
        PaymentTransaction::create([
            'id_student' => $student->id_students,
            'id_user' => Auth::id(),
            'jenis' => $data['jenis'],
            'referensi_id' => $data['referensi_id'],
            'jumlah' => $data['jumlah'],
            'bukti_path' => $path,
            'bank_asal' => $request->input('bank_asal'),
            'status' => 'pending',
            'tanggal_bayar' => $data['tanggal_bayar'],
        ]);

        return redirect()->route('ortu.payments')
            ->with('success', 'Bukti cicilan berhasil diunggah. Menunggu verifikasi Admin.');
    }

    /**
     * Mengambil tagihan (SPP/daftar ulang), memvalidasi kepemilikan, dan
     * menghitung sisa tunggakan dinamis.
     *
     * @return array{0: Student, 1: float, 2: ?string}  [siswa, sisa tunggakan, bulan SPP/null]
     */
    private function resolveBill(string $jenis, int $referensiId, ?int $ortuId): array
    {
        if ($jenis === 'spp') {
            $bill = MonthlySppBill::with('student')->findOrFail($referensiId);
            abort_unless($bill->student->id_parent === $ortuId, 403);

            return [$bill->student, $bill->sisa(), $bill->bulan];
        }

        $daftar = ReRegistrationPayment::with('student')->findOrFail($referensiId);
        abort_unless($daftar->student->id_parent === $ortuId, 403);

        return [$daftar->student, $daftar->sisa(), null];
    }
}
