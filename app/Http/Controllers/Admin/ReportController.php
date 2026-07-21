<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentTransaction;
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
}
