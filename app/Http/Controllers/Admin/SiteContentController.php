<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Models\SiteFeature;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * SiteContentController (Admin) — CMS pengelolaan konten halaman publik
 * (site_contents key-value + site_features item dinamis + upload media).
 * rules.md §1.8, PRD §7.15. Semua aksi di bawah middleware role:admin.
 */
class SiteContentController extends Controller
{
    /** Grup konten yang dikelola. */
    private const GRUP_KONTEN = ['home', 'profile', 'info', 'kontak'];

    /** Grup item berulang yang dikelola. ('kurikulum' = slider foto Curriculum Highlight, T7.1) */
    private const GRUP_FITUR = ['statistik', 'program', 'misi', 'persyaratan', 'alur', 'kurikulum'];

    /**
     * Menampilkan halaman CMS konten (konten per grup + fitur per grup).
     */
    public function index(): View
    {
        $contents = SiteContent::orderBy('grup')->orderBy('key')->get()->groupBy('grup');
        $features = SiteFeature::orderBy('grup')->orderBy('urutan')->get()->groupBy('grup');

        return view('admin.content', compact('contents', 'features'));
    }

    /**
     * Menyimpan perubahan konten key-value secara batch.
     */
    public function updateContents(Request $request): RedirectResponse
    {
        // Input: array contents[key] = value
        $data = $request->validate([
            'contents' => ['required', 'array'],
            'contents.*' => ['nullable', 'string'],
        ]);

        foreach ($data['contents'] as $key => $value) {
            SiteContent::where('key', $key)->update(['value' => $value]);
        }

        AuditLogService::record('update_konten_web', 'SiteContent', null, ['jumlah' => count($data['contents'])]);

        return back()->with('success', 'Konten halaman berhasil diperbarui.');
    }

    /**
     * Menambah item fitur baru (program/misi/persyaratan/alur/statistik).
     */
    public function storeFeature(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grup' => ['required', 'in:'.implode(',', self::GRUP_FITUR)],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Foto opsional (dipakai grup 'kurikulum' untuk slider); simpan ke storage publik (rules.md §3).
        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('konten', 'public');
        }
        unset($data['foto']);

        // Urutan = maksimum grup + 1
        $data['urutan'] = (int) SiteFeature::where('grup', $data['grup'])->max('urutan') + 1;
        $data['aktif'] = true;
        SiteFeature::create($data);

        return back()->with('success', 'Item berhasil ditambahkan.');
    }

    /**
     * Memperbarui satu item fitur.
     */
    public function updateFeature(Request $request, SiteFeature $feature): RedirectResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $data['aktif'] = $request->boolean('aktif');

        // Ganti foto bila diunggah; hapus file lama agar tak menumpuk.
        if ($request->hasFile('foto')) {
            if ($feature->foto_path) {
                Storage::disk('public')->delete($feature->foto_path);
            }
            $data['foto_path'] = $request->file('foto')->store('konten', 'public');
        }
        unset($data['foto']);

        $feature->update($data);

        return back()->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Menghapus satu item fitur.
     */
    public function destroyFeature(SiteFeature $feature): RedirectResponse
    {
        // Bersihkan foto terkait bila ada.
        if ($feature->foto_path) {
            Storage::disk('public')->delete($feature->foto_path);
        }
        $feature->delete();

        return back()->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Mengunggah media (gambar/logo) untuk sebuah key konten bertipe image.
     * File divalidasi (gambar, maks 2 MB) dan disimpan ke storage publik.
     */
    public function uploadMedia(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'exists:site_contents,key'],
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Simpan ke storage/app/public/konten (rules.md §1.8/§3)
        $path = $request->file('gambar')->store('konten', 'public');
        SiteContent::where('key', $data['key'])->update(['value' => $path, 'tipe' => 'image']);

        AuditLogService::record('upload_media_web', 'SiteContent#'.$data['key']);

        return back()->with('success', 'Gambar berhasil diunggah.');
    }
}
