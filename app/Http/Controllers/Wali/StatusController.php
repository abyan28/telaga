<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentProofRequest;
use App\Models\PaymentTransaction;
use App\Models\RegistrationForm;
use App\Models\Setting;
use App\Services\DocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * StatusController (Wali) — status pendaftaran & unggah bukti bayar pendaftaran.
 *
 * Menampilkan progres pendaftaran anak milik wali dan menerima bukti transfer
 * biaya pendaftaran (diverifikasi manual Admin — workflow.md §2.1).
 */
class StatusController extends Controller
{
    /**
     * Menampilkan halaman status pendaftaran SELURUH anak milik wali yang login.
     *
     * Mendukung wali dengan >1 anak (P2.1): mengambil semua formulir, bukan
     * hanya yang terbaru, agar tiap anak punya blok status & timeline sendiri.
     */
    public function index(): View
    {
        // L2.2: PPDB ditutup + belum bayar daftar ulang sama sekali → batalkan
        // status lulus (form gagal, siswa nonaktif) sebelum halaman status ditampilkan.
        RegistrationForm::where('id_user', Auth::id())->where('status', 'lulus')
            ->with('student')->get()
            ->each->cancelLulusIfPpdbClosedAndUnpaid();

        // Ambil SEMUA formulir pendaftaran milik wali beserta relasinya (terbaru dulu)
        $forms = RegistrationForm::with(['student', 'documents'])
            ->where('id_user', Auth::id())
            ->latest('id_registration_forms')
            ->get();

        // Nominal biaya pendaftaran dinamis (diatur Admin)
        $nominalPendaftaran = (int) Setting::get('nominal_pendaftaran', 0);

        // Rekening tujuan sekolah (T2.2) + daftar bank asal untuk dropdown (T8.1)
        $rekening = [
            'bank' => Setting::get('bank_sekolah', ''),
            'nomor' => Setting::get('rekening_sekolah', ''),
            'atas_nama' => Setting::get('atas_nama', ''),
        ];
        $daftarBank = \App\Models\Bank::daftarNama();

        return view('ortu.status', compact('forms', 'nominalPendaftaran', 'rekening', 'daftarBank'));
    }

    /**
     * Menyimpan bukti pembayaran pendaftaran (status transaksi 'pending').
     *
     * Setelah bukti diunggah, status formulir menjadi 'menunggu_verifikasi'
     * agar Admin dapat memprosesnya (workflow.md §2.1).
     */
    public function uploadProof(StorePaymentProofRequest $request, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validated();

        // Pastikan formulir milik wali yang login (cegah akses lintas-user)
        $form = RegistrationForm::where('id_registration_forms', $data['id_registration_form'])
            ->where('id_user', Auth::id())
            ->firstOrFail();

        // K10.4: biaya pendaftaran wajib lunas (tidak dapat dicicil)
        $nominal = (int) Setting::get('nominal_pendaftaran', 0);
        if ($nominal > 0 && $data['jumlah'] < $nominal) {
            return back()->withErrors(['jumlah' => 'Biaya pendaftaran harus dibayar penuh (tidak dapat dicicil): Rp '.number_format($nominal, 0, ',', '.').'.']);
        }

        // L2.3: cegah duplikat unggahan saat masih ada bukti pendaftaran pending
        if (PaymentTransaction::where('jenis', 'pendaftaran')->where('id_registration_form', $form->id_registration_forms)->where('status', 'pending')->exists()) {
            return back()->withErrors(['bukti' => 'Masih ada bukti yang belum diverifikasi untuk pendaftaran ini.']);
        }

        // Simpan berkas bukti transfer (per-siswa, nama detail)
        $form->load('student', 'academicYear');
        $path = $uploader->storePaymentProof(
            $request->file('bukti'), 'pendaftaran',
            $form->student?->nama_lengkap ?? 'tanpa-nama',
            $form->academicYear?->tahun ?? '-',
            null,
            $form->id_student,
        );

        // Catat transaksi pembayaran pendaftaran (menunggu verifikasi admin)
        PaymentTransaction::create([
            'id_student' => $form->id_student,
            'id_registration_form' => $form->id_registration_forms,
            'id_user' => Auth::id(),
            'jenis' => 'pendaftaran',
            'jumlah' => $data['jumlah'],
            'bukti_path' => $path,
            'bank_asal' => $request->input('bank_asal'),
            'status' => 'pending',
            'tanggal_bayar' => $data['tanggal_bayar'],
        ]);

        // Perbarui status formulir menjadi menunggu verifikasi pembayaran
        $form->update(['status' => 'menunggu_verifikasi']);

        return redirect()->route('ortu.status')
            ->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi Admin.');
    }
}
