<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * SettingController (Admin) — pengaturan nominal biaya dinamis (PRD §7.6/§7.8).
 *
 * Admin mengatur nominal pendaftaran, SPP, daftar ulang, dan tanggal generate SPP.
 */
class SettingController extends Controller
{
    /**
     * Tab Set Pembayaran (P4.2): form semua nominal biaya (bukan cuma pendaftaran).
     */
    public function index(): View
    {
        $settings = [
            'nominal_pendaftaran' => (int) Setting::get('nominal_pendaftaran', 0),
            'nominal_spp' => (int) Setting::get('nominal_spp', 0),
            'nominal_daftar_ulang' => (int) Setting::get('nominal_daftar_ulang', 0),
            'tanggal_generate_spp' => (int) Setting::get('tanggal_generate_spp', 1),
            'kuota_pendaftaran' => (int) Setting::get('kuota_pendaftaran', 0),
            'bank_sekolah' => Setting::get('bank_sekolah', ''),
            'rekening_sekolah' => Setting::get('rekening_sekolah', ''),
            'atas_nama' => Setting::get('atas_nama', ''),
        ];

        // L2.4: TA sistem aktif + TA target PPDB (independen) + tetangga prev/next
        // untuk navigasi (null bila TA sebelumnya belum ada di DB → tombol prev disable).
        $taAktif = \App\Models\AcademicYear::where('is_aktif', true)->first();
        $taPpdbId = (int) Setting::get('ta_ppdb', 0);
        $taPpdb = $taPpdbId ? \App\Models\AcademicYear::find($taPpdbId) : $taAktif;
        $prevAktif = $this->tetanggaTa($taAktif?->tahun, -1);
        $prevPpdb = $this->tetanggaTa($taPpdb?->tahun, -1);

        return view('admin.settings', compact('settings', 'taAktif', 'taPpdb', 'prevAktif', 'prevPpdb'));
    }

    /**
     * L2.4: cari TA tetangga (offset -1/+1 tahun) di DB. Null bila belum ada.
     */
    private function tetanggaTa(?string $tahun, int $offset): ?\App\Models\AcademicYear
    {
        if (! $tahun) {
            return null;
        }
        $mulai = (int) explode('/', $tahun)[0] + $offset;
        return \App\Models\AcademicYear::where('tahun', ($mulai).'/'.($mulai + 1))->first();
    }

    /**
     * L2.4: navigasi tahun ajaran, dua scope independen.
     *  - scope=aktif: ganti TA SISTEM (next auto-create+aktifkan & nonaktifkan lama;
     *    prev hanya bila TA sebelumnya ada di DB).
     *  - scope=ppdb : ganti TAHUN AJARAN PPDB (setting ta_ppdb; next auto-create
     *    is_aktif=FALSE bila belum ada; prev hanya bila ada).
     */
    public function setAcademicYear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'in:aktif,ppdb'],
            'arah' => ['required', 'in:prev,next'],
        ]);

        $current = $data['scope'] === 'aktif'
            ? \App\Models\AcademicYear::where('is_aktif', true)->first()
            : (($id = (int) Setting::get('ta_ppdb', 0))
                ? \App\Models\AcademicYear::find($id)
                : \App\Models\AcademicYear::where('is_aktif', true)->first());

        if (! $current) {
            return back()->withErrors(['ta' => 'Tahun ajaran acuan belum ada.']);
        }

        $offset = $data['arah'] === 'next' ? 1 : -1;
        $mulai = (int) explode('/', $current->tahun)[0] + $offset;
        $target = ($mulai).'/'.($mulai + 1);

        // Prev: hanya bila TA target sudah ada (tak boleh bikin TA masa lampau).
        $ta = \App\Models\AcademicYear::where('tahun', $target)->first();
        if ($data['arah'] === 'prev' && ! $ta) {
            return back()->withErrors(['ta' => "Tahun ajaran $target belum ada di sistem."]);
        }

        if ($data['scope'] === 'aktif') {
            // Next boleh auto-create; aktifkan & nonaktifkan yang lama (1 aktif).
            $ta ??= \App\Models\AcademicYear::create(['tahun' => $target, 'is_aktif' => false]);
            \App\Models\AcademicYear::where('is_aktif', true)->update(['is_aktif' => false]);
            $ta->update(['is_aktif' => true]);
            AuditLogService::record('ta_sistem_ganti', 'AcademicYear', ['dari' => $current->tahun], ['ke' => $target]);
        } else {
            // PPDB: next auto-create (is_aktif tetap FALSE — bukan TA sistem).
            $ta ??= \App\Models\AcademicYear::create(['tahun' => $target, 'is_aktif' => false]);
            Setting::set('ta_ppdb', (string) $ta->id_academic_years);
            AuditLogService::record('ppdb_target_ganti', 'AcademicYear', ['dari' => $current->tahun], ['ke' => $target]);
        }

        return back()->with('success', "Tahun ajaran ".($data['scope'] === 'aktif' ? 'sistem' : 'PPDB')." → $target.");
    }

    /**
     * Memperbarui nominal biaya dinamis.
     */
    public function update(Request $request): RedirectResponse
    {
        // Semua field 'sometimes': hanya yang dikirim yang divalidasi & disimpan
        // (form tab terpisah kirim subset — nominal saja / rekening saja).
        $data = $request->validate([
            'nominal_pendaftaran' => ['sometimes', 'integer', 'min:0'],
            'nominal_spp' => ['sometimes', 'integer', 'min:0'],
            'nominal_daftar_ulang' => ['sometimes', 'integer', 'min:0'],
            'tanggal_generate_spp' => ['sometimes', 'integer', 'between:1,28'],
            'kuota_pendaftaran' => ['sometimes', 'integer', 'min:0'],
            'bank_sekolah' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rekening_sekolah' => ['sometimes', 'nullable', 'string', 'max:50'],
            'atas_nama' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        // Simpan tiap pengaturan (updateOrCreate via helper Setting::set)
        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        AuditLogService::record('ubah_setting_biaya', 'Setting', null, $data);

        return back()->with('success', 'Pengaturan biaya berhasil diperbarui.');
    }

    /**
     * Memicu generate tagihan SPP bulan berjalan secara manual (cadangan bila
     * cron server belum aktif — rules.md §1.3.5). Memanggil command spp:generate.
     */
    public function generateSpp(): RedirectResponse
    {
        Artisan::call('spp:generate');
        AuditLogService::record('generate_spp_manual', 'MonthlySppBill');

        return back()->with('success', trim(Artisan::output()));
    }

    /**
     * L2.1: halaman Pengaturan Sistem (NSM, persen refund, TA — pindahan dari Set Pembayaran).
     */
    public function system(): View
    {
        $settings = [
            'nsm_sekolah'   => Setting::get('nsm_sekolah', ''),
            'persen_refund' => (int) Setting::get('persen_refund', 30),
        ];

        // TA: reuse logic dari index() — cukup kirim variabel yang sama.
        $taAktif    = \App\Models\AcademicYear::where('is_aktif', true)->first();
        $taPpdbId   = (int) Setting::get('ta_ppdb', 0);
        $taPpdb     = $taPpdbId ? \App\Models\AcademicYear::find($taPpdbId) : $taAktif;
        $prevAktif  = $this->tetanggaTa($taAktif?->tahun, -1);
        $prevPpdb   = $this->tetanggaTa($taPpdb?->tahun, -1);

        return view('admin.system', compact('settings', 'taAktif', 'taPpdb', 'prevAktif', 'prevPpdb'));
    }

    /**
     * L2.1: simpan pengaturan sistem (NSM + persen refund). TA via setAcademicYear() existing.
     */
    public function updateSystem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nsm_sekolah'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'persen_refund' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        AuditLogService::record('ubah_setting_sistem', 'Setting', null, $data);

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }
}
