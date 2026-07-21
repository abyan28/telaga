<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentTransaction;
use App\Models\RegistrationDocument;
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

        // K6.3: pending verifikasi dipisah — PPDB vs Pembayaran (daftar ulang + SPP).
        // Jumlah bukti bayar pending per jenis (satu query, groupBy).
        $pendingPerJenis = PaymentTransaction::where('status', 'pending')
            ->selectRaw('jenis, COUNT(*) as c')->groupBy('jenis')->pluck('c', 'jenis');
        // Dokumen pendaftaran (KK/Akta/Foto) yang belum diverifikasi (K6.1/K6.3).
        $dokumenPending = RegistrationDocument::where('status', 'pending')->count();

        // Card PPDB = bukti bayar pendaftaran pending + dokumen pending.
        $pendingPpdb = (int) ($pendingPerJenis['pendaftaran'] ?? 0) + $dokumenPending;
        // Card Pembayaran = daftar ulang + SPP pending, dengan rincian per jenis.
        $pendingDaftarUlang = (int) ($pendingPerJenis['daftar_ulang'] ?? 0);
        $pendingSpp = (int) ($pendingPerJenis['spp'] ?? 0);
        $pendingPembayaran = $pendingDaftarUlang + $pendingSpp;

        // Bonus: 5 aktivitas audit log terbaru (ganti widget mock statik).
        $auditLogs = AuditLog::with('user.ortu', 'user.teacher')->latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'pendaftarBaru', 'lulus', 'kuota', 'persenKuota', 'keuanganMasuk',
            'pendingPpdb', 'pendingPembayaran', 'pendingDaftarUlang', 'pendingSpp',
            'pendingPerJenis', 'dokumenPending', 'auditLogs',
        ));
    }
}
