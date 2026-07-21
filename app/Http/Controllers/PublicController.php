<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use App\Models\SiteFeature;
use App\Models\Setting;
use App\Models\Teacher;
use Illuminate\View\View;

/**
 * PublicController — merender halaman publik (Beranda, Profil, Info) dari data
 * CMS (site_contents, site_features), pengaturan biaya (settings), dan data
 * guru (teachers) — bukan konten hardcode (rules.md §1.8, PRD §7.15).
 */
class PublicController extends Controller
{
    /**
     * Halaman Beranda.
     */
    public function home(): View
    {
        return view('home', [
            'c' => $this->contents('home'),
            'statistik' => SiteFeature::grup('statistik')->get(),
            'program' => SiteFeature::grup('program')->get(),
            'kurikulum' => SiteFeature::grup('kurikulum')->get(),
        ]);
    }

    /**
     * Halaman Profil Sekolah — termasuk daftar guru yang tampil di web.
     */
    public function profile(): View
    {
        return view('profile', [
            'c' => $this->contents('profile'),
            'misi' => SiteFeature::grup('misi')->get(),
            'guru' => Teacher::with('user')->where('tampil_di_web', true)->get(),
        ]);
    }

    /**
     * Halaman Info Pendaftaran — biaya diambil dari settings (sumber tunggal).
     */
    public function info(): View
    {
        return view('info', [
            'c' => $this->contents('info'),
            'persyaratan' => SiteFeature::grup('persyaratan')->get(),
            'alur' => SiteFeature::grup('alur')->get(),
            'biayaPendaftaran' => (int) Setting::get('nominal_pendaftaran', 0),
            'biayaDaftarUlang' => (int) Setting::get('nominal_daftar_ulang', 0),
            'biayaSpp' => (int) Setting::get('nominal_spp', 0),
        ]);
    }

    /**
     * Mengambil seluruh konten key-value satu grup sebagai array [key => value]
     * untuk akses ringkas di view (mis. $c['home.hero_title']).
     *
     * @return array<string, string|null>
     */
    private function contents(string $grup): array
    {
        return SiteContent::where('grup', $grup)->pluck('value', 'key')->all();
    }
}
