<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\ReRegistrationPayment;
use App\Models\RegistrationDocument;
use App\Models\RegistrationForm;
use App\Models\Setting;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\PaymentVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * AdminRegistrationController — alur PPDB, verifikasi dokumen/pembayaran,
 * keputusan seleksi, daftar ulang & rekap SPP.
 *
 * Konvensi PK/FK non-standar (rules.md §2) dihormati model terkait.
 */
class RegistrationController extends Controller
{
    /**
     * Kelola Pendaftaran: daftar formulir (filter TA + status server-side, paginasi).
     * L2.4: tampilkan status read-only TA Aktif & Tahun Ajaran PPDB.
     */
    public function index(Request $request): View
    {
        $tahunOpsi = AcademicYear::orderByDesc('tahun')->get(['id_academic_years', 'tahun']);
        // Default filter TA = tahun aktif (ikut ter-update saat admin generate TA baru).
        $tahun = $request->query('tahun', AcademicYear::where('is_aktif', true)->value('id_academic_years'));

        $status = $request->query('status');

        $query = RegistrationForm::with(['student', 'user.ortu', 'documents'])
            ->when($tahun, fn ($q) => $q->where('id_academic_year', $tahun))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id_registration_forms');

        $forms = $query->paginate(25)->withQueryString();
        $ppdbDibuka = Setting::get('pendaftaran_dibuka', '1') === '1';
        $tahunAktif = AcademicYear::where('is_aktif', true)->value('tahun');
        $kuota = (int) Setting::get('kuota_pendaftaran', 0);

        // L2.4: status read-only di Kelola Pendaftaran. Fallback taPpdb() = TA aktif bila belum diset.
        $taPpdb = AcademicYear::taPpdb();

        return view('admin.registrations', compact(
            'forms', 'ppdbDibuka', 'tahunAktif', 'tahunOpsi', 'tahun', 'status', 'kuota',
            'taPpdb',
        ));
    }

    /**
     * T5.6a: buka/tutup pendaftaran (setting pendftaran_dibuka).
     */
    public function togglePpdb(Request $request): RedirectResponse
    {
        $data = $request->validate(['dibuka' => ['required', 'in:0,1']]);
        Setting::set('pendaftaran_dibuka', $data['dibuka']);
        AuditLogService::record('ppdb_toggle', 'Setting', ['pendaftaran_dibuka' => $data['dibuka']]);

        return back()->with('success', $data['dibuka'] === '1'
            ? 'Pendaftaran dibuka.'
            : 'Pendaftaran ditutup.');
    }

    /**
     * L2.4: buat TA berikutnya sebagai target PPDB (TA sistem tak diubah).
     * TA baru is_aktif=FALSE; id-nya disimpan di setting ta_ppdb.
     * Test: test_ppdb_controls_and_daftar_ulang.
     */
    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $basis = (int) Setting::get('ta_ppdb', 0)
            ? AcademicYear::find((int) Setting::get('ta_ppdb', 0))?->tahun
            : AcademicYear::where('is_aktif', true)->value('tahun');
        $mulai = $basis ? (int) explode('/', $basis)[0] : (int) date('Y');
        $berikut = ($mulai + 1).'/'.($mulai + 2);

        // Cari di DB; bila belum ada, auto-create (is_aktif=FALSE, bukan TA sistem).
        $ta = AcademicYear::firstOrCreate(
            ['tahun' => $berikut],
            ['is_aktif' => false],
        );

        Setting::set('ta_ppdb', (string) $ta->id_academic_years);
        AuditLogService::record('ppdb_target_baru', 'AcademicYear', ['tahun' => $berikut, 'is_aktif' => false]);

        return back()->with('success', "Tahun Ajaran PPDB diarahkan ke $berikut.");
    }

    /**
     * L2.4: set target PPDB ke TA terpilih (dropdown manual).
     */
    public function setPpdbTarget(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_academic_year' => ['required', 'exists:academic_years,id_academic_years'],
        ]);
        Setting::set('ta_ppdb', (string) $data['id_academic_year']);
        AuditLogService::record('ppdb_target_set', 'AcademicYear', $data);

        return back()->with('success', 'Tahun Ajaran PPDB diperbarui.');
    }

    /**
     * T5.6b: daftar ulang — bukti pending + histori tagihan (lunas tetap tampil).
     * K4.1: kolom bank asal + tanggal & waktu.
     */
    public function daftarUlang(Request $request): View
    {
        $tahunOpsi = AcademicYear::orderByDesc('tahun')->get(['id_academic_years', 'tahun']);
        $tahun = $request->query('tahun');
        $status = $request->query('status');
        $cari = $request->query('cari');

        // Bukti daftar ulang menunggu verifikasi (tab Bukti).
        $pending = PaymentTransaction::with('student')
            ->where('jenis', 'daftar_ulang')
            ->where('status', 'pending')
            ->latest('id_payment_transactions')
            ->get();

        $bills = ReRegistrationPayment::with(['student.ortu', 'academicYear', 'transactions' => fn ($q) => $q->whereNotNull('bukti_path')])
            ->when($tahun, fn ($q) => $q->where('id_academic_year', $tahun))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($cari, function ($q) use ($cari) {
                $q->whereHas('student', fn ($s) => $s->where('nama_lengkap', 'like', "%$cari%")
                    ->orWhere('nik', 'like', "%$cari%")
                    ->orWhereHas('ortu', fn ($g) => $g->where('ayah_no_hp', 'like', "%$cari%")
                        ->orWhere('ibu_no_hp', 'like', "%$cari%")));
            })
            ->latest('id_re_registration_payments')
            ->paginate(25)
            ->withQueryString();

        return view('admin.daftar-ulang', compact('pending', 'tahunOpsi', 'bills', 'cari', 'status', 'tahun'));
    }

    /**
     * T5.6h / K10.2: rekap tagihan SPP semua siswa + filter bulan/TA/kelas/status + search.
     * K4.1: bank asal + tanggal & waktu di tabel bukti.
     */
    public function spp(Request $request): View
    {
        $tahunOpsi = AcademicYear::orderByDesc('tahun')->get(['id_academic_years', 'tahun']);
        $classes = \App\Models\SchoolClass::orderBy('nama_kelas')->get(['id_classes', 'nama_kelas']);
        $bulanOpsi = MonthlySppBill::distinct()->orderBy('bulan')->pluck('bulan')->all();

        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');
        $kelas = $request->query('id_class');
        $statusFilter = $request->query('status');
        $cari = $request->query('cari');

        // Bukti SPP menunggu verifikasi (tab Bukti).
        $pending = PaymentTransaction::with('student')
            ->where('jenis', 'spp')
            ->where('status', 'pending')
            ->latest('id_payment_transactions')
            ->get();

        $bills = MonthlySppBill::with(['student.ortu', 'academicYear', 'transactions' => fn ($q) => $q->whereNotNull('bukti_path')])
            ->when($bulan, fn ($q) => $q->where('bulan', $bulan))
            ->when($tahun, fn ($q) => $q->where('id_academic_year', $tahun))
            ->when($kelas, fn ($q) => $q->whereHas('student', fn ($s) => $q->where('id_class', $kelas)))
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($cari, function ($q) use ($cari) {
                $q->whereHas('student', fn ($s) => $s->where('nama_lengkap', 'like', "%$cari%")
                    ->orWhere('nik', 'like', "%$cari%")
                    ->orWhereHas('ortu', fn ($g) => $g->where('ayah_no_hp', 'like', "%$cari%")
                        ->orWhere('ibu_no_hp', 'like', "%$cari%")));
            })
            ->latest('bulan')
            ->latest('id_monthly_spp_bills')
            ->paginate(25)
            ->withQueryString();

        return view('admin.spp', compact(
            'pending', 'bulanOpsi', 'tahunOpsi', 'classes', 'bills',
            'kelas', 'bulan', 'tahun', 'statusFilter', 'cari',
        ));
    }

    /**
     * T5.6a: detail & verifikasi pendaftaran (dokumen + bukti + keputusan).
     */
    public function show(RegistrationForm $form): View
    {
        $form->loadMissing(['student.ortu', 'user', 'documents']);
        $payments = PaymentTransaction::with('student')
            ->where('id_registration_form', $form->id_registration_forms)
            ->latest('id_payment_transactions')
            ->get();

        return view('admin.registration-detail', compact('form', 'payments'));
    }

    /**
     * K3.2: verifikasi dokumen (terima/tolak). AJAX → JSON (tanpa refresh).
     * Gate: bila izin edit orang tua aktif → terkunci. Bila semua dokumen diterima
     * & form sudah lewat verifikasi bayar → maju ke diproses_seleksi.
     */
    public function verifyDocument(Request $request, RegistrationDocument $document): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // K3.x: izin edit orang tua aktif → verifikasi terkunci.
        if ($document->registrationForm->boleh_edit) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Izin edit orang tua aktif — verifikasi terkunci.']);
            }
            return back()->withErrors(['dokumen' => 'Izin edit orang tua aktif — verifikasi terkunci.']);
        }

        // P2.3: berkas hanya boleh diverifikasi setelah pembayaran diverifikasi.
        $bolehVerifikasiBerkas = in_array($document->registrationForm->status,
            ['pembayaran_diverifikasi', 'diproses_seleksi', 'lulus', 'gagal'], true);
        if (! $bolehVerifikasiBerkas) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Verifikasi pembayaran dahulu.']);
            }
            return back()->withErrors(['dokumen' => 'Verifikasi bukti pembayaran terlebih dahulu.']);
        }

        $data = $request->validate(['status' => ['required', 'in:diterima,ditolak']]);
        $document->update(['status' => $data['status']]);

        // P2.3/P3.2: bila form sudah verifikasi bayar & semua dokumen diterima → seleksi.
        $form = $document->registrationForm;
        if ($form->status === 'pembayaran_diverifikasi'
            && $form->documents()->where('status', '!=', 'diterima')->doesntExist()) {
            $form->update(['status' => 'diproses_seleksi']);
            AuditLogService::record('maju_seleksi', 'RegistrationForm#'.$form->id_registration_forms,
                ['status' => 'pembayaran_diverifikasi'], ['status' => 'diproses_seleksi']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'doc_status' => $document->status,
                'form_status' => $form->status,
                'message' => 'Dokumen diperbarui.',
            ]);
        }

        return back()->with('success', 'Dokumen diverifikasi.');
    }

    /**
     * K4 / Fase 4: verifikasi bukti pembayaran (approve/reject) via PaymentVerificationService.
     */
    public function verifyPayment(Request $request, PaymentTransaction $payment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:diverifikasi,ditolak'], 'catatan' => ['nullable', 'string', 'max:255']]);
        $adminId = auth()->id();

        if ($data['status'] === 'ditolak') {
            app(PaymentVerificationService::class)->reject($payment, $adminId, $data['catatan'] ?? '');
        } else {
            app(PaymentVerificationService::class)->approve($payment, $adminId);
        }

        return back()->with('success', 'Pembayaran diverifikasi.');
    }

    /**
     * K3.1: keputusan lulus/gagal (hanya saat diproses_seleksi).
     * Lulus → buka tagihan daftar ulang (idempoten) + siswa aktif.
     */
    public function decide(Request $request, RegistrationForm $form): RedirectResponse
    {
        if ($form->status !== 'diproses_seleksi' || $form->boleh_edit) {
            return back()->withErrors(['keputusan' => 'Keputusan hanya dapat ditetapkan saat proses seleksi & izin edit orang tua tidak aktif.']);
        }

        $data = $request->validate(['keputusan' => ['required', 'in:lulus,gagal']]);
        $lulus = $data['keputusan'] === 'lulus';
        $sebelum = ['status' => $form->status];
        $form->update(['status' => $lulus ? 'lulus' : 'gagal']);
        $form->student?->update(['status' => $lulus ? 'aktif' : 'nonaktif']);

        // Lulus: buka tagihan daftar ulang (idempoten) bila belum ada.
        if ($lulus) {
            $student = $form->student;
            if ($student && ! ReRegistrationPayment::where('id_student', $student->id_students)
                ->where('id_academic_year', $form->id_academic_year)->exists()) {
                ReRegistrationPayment::create([
                    'id_student' => $student->id_students,
                    'id_academic_year' => $form->id_academic_year,
                    'total_biaya' => (int) Setting::get('nominal_daftar_ulang', 0),
                    'jumlah_terbayar' => 0,
                    'status' => 'belum_lunas',
                ]);
            }
        }

        AuditLogService::record('keputusan_seleksi', 'RegistrationForm#'.$form->id_registration_forms,
            $sebelum, ['status' => $form->status]);

        // Notifikasi email ke wali (PRD §7.13).
        $form->loadMissing('user');
        if ($form->user?->email) {
            Mail::to($form->user->email)->send(new \App\Mail\RegistrationNotification(
                $lulus ? 'Selamat, Calon Murid Dinyatakan Lulus' : 'Status Kelulusan',
                $lulus
                    ? 'Ananda dinyatakan LULUS seleksi. Silakan lanjutkan pembayaran daftar ulang.'
                    : 'Mohon maaf, status kelulusan calon murid dinyatakan TIDAK LULUS.',
            ));
        }

        return back()->with('success', $lulus ? 'Calon murid dinyatakan LULUS.' : 'Calon murid dinyatakan TIDAK LULUS.');
    }

    /**
     * K3.3: buka kembali keputusan (lulus/gagal → diproses_seleksi, siswa aktif).
     */
    public function reopenDecision(Request $request, RegistrationForm $form): RedirectResponse
    {
        $sebelum = ['status' => $form->status];
        $form->update(['status' => 'diproses_seleksi']);
        $form->student?->update(['status' => 'aktif']);

        AuditLogService::record('buka_keputusan', 'RegistrationForm#'.$form->id_registration_forms,
            $sebelum, ['status' => 'diproses_seleksi']);

        return back()->with('success', 'Keputusan dibuka kembali ke proses seleksi.');
    }

    /**
     * K2.5: admin membuka izin edit pendaftaran untuk wali.
     */
    public function allowEdit(Request $request, RegistrationForm $form): RedirectResponse
    {
        $form->update(['boleh_edit' => true]);

        return back()->with('success', 'Izin edit orang tua dibuka.');
    }
}
