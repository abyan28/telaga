<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentTransaction;
use App\Models\RegistrationForm;
use App\Models\Setting;
use Illuminate\View\View;

/**
 * DashboardController (Admin) — kartu statistik ringkas dari DB (P4.1, K6.3).
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard admin dengan kartu bernilai nyata dari database.
     */
    public function index(): View
    {
        // Pendaftar baru = form yang belum diputuskan (belum lulus/gagal).
        $pendaftarBaru = RegistrationForm::whereNotIn('status', ['lulus', 'gagal'])->count();
        $lulus = RegistrationForm::where('status', 'lulus')->count();
        $kuota = (int) Setting::get('kuota_pendaftaran', 0);
        $persenKuota = $kuota > 0 ? round($lulus / $kuota * 100) : 0;

        // Keuangan masuk = total transaksi terverifikasi (source of truth PRD §13).
        // L2.1: exclude jenis 'refund' (catatan uang keluar, bukan pemasukan).
        $keuanganMasuk = (int) PaymentTransaction::where('status', 'diverifikasi')
            ->where('jenis', '!=', 'refund')->sum('jumlah');

        // Card Pending PPDB = jumlah PENDAFTAR yg masih butuh tindakan admin
        // (1 per form): bayar belum diverif, verifikasi berkas, atau seleksi belum tuntas.
        $pendingPpdb = RegistrationForm::whereIn('status',
            ['menunggu_verifikasi', 'pembayaran_diverifikasi', 'diproses_seleksi'])->count();

        // Card Pending Pembayaran = bayar PPDB belum diverif (1 per pendaftar)
        // + cicilan daftar ulang pending + cicilan SPP pending.
        $ppdbBayarBelumVerif = RegistrationForm::where('status', 'menunggu_verifikasi')->count();
        $pendingPerJenis = PaymentTransaction::where('status', 'pending')
            ->whereIn('jenis', ['daftar_ulang', 'spp'])
            ->selectRaw('jenis, COUNT(*) as c')->groupBy('jenis')->pluck('c', 'jenis');
        $pendingDaftarUlang = (int) ($pendingPerJenis['daftar_ulang'] ?? 0);
        $pendingSpp        = (int) ($pendingPerJenis['spp'] ?? 0);
        $pendingPembayaran = $ppdbBayarBelumVerif + $pendingDaftarUlang + $pendingSpp;

        // 5 aktivitas audit log terbaru.
        $auditLogs = AuditLog::with('user.ortu', 'user.teacher')->latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'pendaftarBaru', 'lulus', 'kuota', 'persenKuota', 'keuanganMasuk',
            'pendingPpdb', 'pendingPembayaran', 'pendingDaftarUlang', 'pendingSpp',
            'ppdbBayarBelumVerif', 'auditLogs',
        ));
    }
}
