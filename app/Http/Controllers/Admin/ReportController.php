<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\SchoolClass;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ReportController (Admin) — laporan transaksi keuangan + export CSV/PDF (PRD §7.12).
 *
 * Mendukung filter (tanggal, status, jenis) dan ekspor: CSV dibuat native
 * (streamed), PDF memakai barryvdh/laravel-dompdf (rules.md §2.4).
 */
class ReportController extends Controller
{
    /**
     * Membangun query transaksi dengan filter opsional dari request.
     */
    private function query(Request $request)
    {
        return PaymentTransaction::with('student')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->query('jenis')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal_bayar', '>=', $request->query('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal_bayar', '<=', $request->query('sampai')))
            ->latest('tanggal_bayar');
    }

    /**
     * Menampilkan halaman laporan transaksi (dengan filter).
     */
    public function index(Request $request): View
    {
        $transactions = $this->query($request)->get();
        // Audit log: dipaginasi (K9.3) — tabel yg tumbuh tanpa batas (tiap aksi +1 baris).
        $auditLogs = AuditLog::with('user.ortu', 'user.teacher')->latest()->paginate(25);

        return view('admin.reports', compact('transactions', 'auditLogs'));
    }

    /**
     * Mengekspor transaksi (terfilter) ke CSV secara native (streamed response).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $transactions = $this->query($request)->get();
        $filename = 'laporan-transaksi-'.now()->format('Ymd-His').'.csv';

        // Streamed response agar hemat memori untuk data besar
        return Response::streamDownload(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            // Header kolom CSV (escape='' eksplisit — wajib di PHP 8.4+)
            fputcsv($out, ['Tanggal', 'Murid', 'Jenis', 'Jumlah', 'Status'], ',', '"', '');
            foreach ($transactions as $t) {
                fputcsv($out, [
                    $t->tanggal_bayar?->format('Y-m-d'),
                    $t->student?->nama_lengkap ?? '-',
                    $t->jenis,
                    (int) $t->jumlah,
                    $t->status,
                ], ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Mengekspor transaksi (terfilter) ke PDF via dompdf.
     */
    public function exportPdf(Request $request)
    {
        $transactions = $this->query($request)->get();
        $total = $transactions->where('status', 'diverifikasi')->sum('jumlah');

        // Render view Blade menjadi PDF lalu unduh
        $pdf = Pdf::loadView('reports.transactions-pdf', compact('transactions', 'total'));

        return $pdf->download('laporan-transaksi-'.now()->format('Ymd-His').'.pdf');
    }

    // ─── DATA SISWA ───────────────────────────────────────────────────────────

    /**
     * L8.2: query siswa dengan filter opsional dari request.
     * "murid_baru" = NIS prefix-tahun = YY TA PPDB (substr(nis,13,2)).
     */
    private function studentsQuery(Request $request)
    {
        $yy = substr(explode('/', AcademicYear::taPpdb()->tahun)[0], -2);

        return Student::with(['schoolClass.homeroomTeacher', 'ortu'])
            ->when($request->filled('id_class'), fn ($q) => $q->where('id_class', $request->query('id_class')))
            ->when($request->filled('id_academic_year'), fn ($q) => $q->where('id_academic_year', $request->query('id_academic_year')))
            ->when($request->filled('angkatan'), fn ($q) => $q->where('angkatan', $request->query('angkatan')))
            ->when($request->query('status') && $request->query('status') !== 'semua', fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->boolean('murid_baru'), fn ($q) => $q->whereNotNull('nis')->whereRaw('SUBSTR(nis,13,2) = ?', [$yy]))
            ->orderBy('nama_lengkap');
    }

    /**
     * L8.2: halaman laporan data siswa + filter + export.
     */
    public function students(Request $request): \Illuminate\View\View
    {
        $students     = $this->studentsQuery($request)->paginate(25)->withQueryString();
        $classes      = SchoolClass::orderBy('nama_kelas')->get();
        $academicYears = AcademicYear::orderByDesc('tahun')->get();
        // ponytail: distinct angkatan dari DB — cukup untuk RA kecil; ganti ke generated range bila >500 murid.
        $angkatanList = Student::whereNotNull('angkatan')->distinct()->orderByDesc('angkatan')->pluck('angkatan');

        return view('admin.reports-students', compact('students', 'classes', 'academicYears', 'angkatanList'));
    }

    /**
     * L8.2: export data siswa ke CSV (33 kolom sesuai spec).
     */
    public function exportStudentsCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $students = $this->studentsQuery($request)->get();
        $filename = 'data-murid-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Nama Lengkap','Nama Panggilan','NIK','NISN','NIS','Kelas','Wali Kelas',
                'Jenis Kelamin','Agama','Tempat Lahir','Tanggal Lahir','Anak Ke','Jumlah Saudara',
                'Warga Negara','Bahasa Keseharian','Kondisi Kesehatan',
                'Nama Ayah','Tempat Lahir Ayah','Tanggal Lahir Ayah','Agama Ayah',
                'Pendidikan Ayah','Pekerjaan Ayah','Penghasilan Ayah','No HP Ayah',
                'Nama Ibu','Tempat Lahir Ibu','Tanggal Lahir Ibu','Agama Ibu',
                'Pendidikan Ibu','Pekerjaan Ibu','Penghasilan Ibu','No HP Ibu',
                'Alamat Lengkap',
            ], ',', '"', '');
            foreach ($students as $s) {
                $o = $s->ortu;
                fputcsv($out, [
                    $s->nama_lengkap, $s->nama_panggilan, $s->nik, $s->nisn, $s->nis,
                    $s->schoolClass?->nama_kelas ?? '-',
                    $s->schoolClass?->homeroomTeacher?->nama ?? '-',
                    $s->jenis_kelamin, $s->agama, $s->tempat_lahir,
                    $s->tanggal_lahir?->format('Y-m-d'),
                    $s->anak_ke, $s->jumlah_saudara, $s->warga_negara,
                    $s->bahasa_keseharian, $s->kondisi_kesehatan,
                    $o?->ayah_nama, $o?->ayah_tempat_lahir, $o?->ayah_tanggal_lahir?->format('Y-m-d'),
                    $o?->ayah_agama, $o?->ayah_pendidikan, $o?->ayah_pekerjaan,
                    $o?->ayah_penghasilan, $o?->ayah_no_hp,
                    $o?->ibu_nama, $o?->ibu_tempat_lahir, $o?->ibu_tanggal_lahir?->format('Y-m-d'),
                    $o?->ibu_agama, $o?->ibu_pendidikan, $o?->ibu_pekerjaan,
                    $o?->ibu_penghasilan, $o?->ibu_no_hp,
                    $o?->alamat,
                ], ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * L8.2: export data siswa ke PDF.
     */
    public function exportStudentsPdf(Request $request): \Illuminate\Http\Response
    {
        $students = $this->studentsQuery($request)->get();
        $pdf = Pdf::loadView('reports.students-pdf', compact('students'))->setPaper('a4', 'landscape');

        return $pdf->download('data-murid-'.now()->format('Ymd-His').'.pdf');
    }

    // ─── LAPORAN SPP ──────────────────────────────────────────────────────────

    /**
     * L8.2: query tagihan SPP dengan filter opsional dari request.
     */
    private function sppQuery(Request $request)
    {
        return MonthlySppBill::with(['academicYear', 'student.schoolClass', 'student.ortu'])
            ->when($request->filled('id_student'), fn ($q) => $q->where('id_student', $request->query('id_student')))
            ->when($request->filled('id_class'), fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('id_class', $request->query('id_class'))))
            ->when($request->filled('id_academic_year'), fn ($q) => $q->where('id_academic_year', $request->query('id_academic_year')))
            ->when($request->filled('bulan'), fn ($q) => $q->where('bulan', 'like', $request->query('bulan').'%'))
            ->when($request->filled('dari'), fn ($q) => $q->where('bulan', '>=', $request->query('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->where('bulan', '<=', $request->query('sampai')))
            ->when(strtolower((string) $request->query('status_bayar')) === 'lunas', fn ($q) => $q->where('status', 'lunas'))
            ->when(strtolower((string) $request->query('status_bayar')) === 'belum', fn ($q) => $q->whereIn('status', ['belum_lunas', 'kurang']))
            ->orderBy('bulan')->orderBy('id_student');
    }

    /**
     * L8.2: halaman laporan SPP + filter + export.
     */
    public function spp(Request $request): \Illuminate\View\View
    {
        $bills        = $this->sppQuery($request)->paginate(25)->withQueryString();
        $classes      = SchoolClass::orderBy('nama_kelas')->get();
        $academicYears = AcademicYear::orderByDesc('tahun')->get();
        // Dropdown siswa — untuk filter perorangan (lazy: semua aktif, cukup untuk RA kecil).
        // ponytail: load semua siswa aktif; ganti ke AJAX search bila jumlah siswa >500.
        $allStudents  = Student::orderBy('nama_lengkap')->get(['id_students', 'nama_lengkap']);

        return view('admin.reports-spp', compact('bills', 'classes', 'academicYears', 'allStudents'));
    }

    /**
     * L8.2: export laporan SPP ke CSV.
     */
    public function exportSppCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $bills    = $this->sppQuery($request)->get();
        $filename = 'laporan-spp-'.now()->format('Ymd-His').'.csv';

        // Eager load transaksi diverifikasi — hindari N+1 di loop.
        $bills->loadMissing(['transactions' => fn ($q) => $q->where('status', 'diverifikasi')->latest()]);

        return Response::streamDownload(function () use ($bills) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nama Murid','Nama Ortu (Ibu)','Kelas','Tahun Ajaran','SPP Bulan','Tanggal Bayar','Waktu Bayar','Nominal','Terbayar','Sisa','Status'], ',', '"', '');
            foreach ($bills as $b) {
                // Tanggal & waktu dari transaksi terakhir yang diverifikasi.
                $last = $b->transactions->first();
                fputcsv($out, [
                    $b->student?->nama_lengkap,
                    $b->student?->ortu?->ibu_nama ?? $b->student?->ortu?->ayah_nama,
                    $b->student?->schoolClass?->nama_kelas ?? '-',
                    $b->academicYear?->tahun ?? '-',
                    $b->bulan,
                    $last?->tanggal_bayar?->format('Y-m-d'),
                    $last?->created_at?->format('H:i'),
                    number_format((float) $b->nominal, 0, ',', '.'),
                    number_format((float) $b->jumlah_terbayar, 0, ',', '.'),
                    number_format((float) $b->sisa(), 0, ',', '.'),
                    $b->status,
                ], ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * L8.2: export laporan SPP ke PDF.
     */
    public function exportSppPdf(Request $request): \Illuminate\Http\Response
    {
        $bills = $this->sppQuery($request)->get();
        $pdf   = Pdf::loadView('reports.spp-pdf', compact('bills'))->setPaper('a4', 'landscape');

        return $pdf->download('laporan-spp-'.now()->format('Ymd-His').'.pdf');
    }
}
