<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\SiteContent;
use App\Models\SiteFeature;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji halaman publik merender konten dari database (CMS), bukan hardcode:
 * teks dari site_contents, item dari site_features, biaya dari settings, dan
 * tenaga pendidik dari teachers (tampil_di_web). (rules.md §1.8, PRD §7.15)
 */
class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_content_from_db(): void
    {
        SiteContent::create(['key' => 'home.hero_title', 'value' => 'Judul Hero Uji', 'grup' => 'home']);
        SiteFeature::create(['grup' => 'statistik', 'judul' => '999+', 'deskripsi' => 'Statistik Uji', 'urutan' => 1, 'aktif' => true]);
        SiteFeature::create(['grup' => 'program', 'judul' => 'Program Uji', 'deskripsi' => 'Deskripsi Uji', 'urutan' => 1, 'aktif' => true]);

        $resp = $this->get('/');
        $resp->assertOk();
        $resp->assertSee('Judul Hero Uji');
        $resp->assertSee('999+');
        $resp->assertSee('Program Uji');
    }

    public function test_info_uses_settings_for_biaya(): void
    {
        Setting::set('nominal_pendaftaran', '175000');
        Setting::set('nominal_daftar_ulang', '1750000');
        Setting::set('nominal_spp', '275000');
        SiteFeature::create(['grup' => 'persyaratan', 'judul' => 'Syarat Uji', 'urutan' => 1, 'aktif' => true]);

        $resp = $this->get('/info');
        $resp->assertOk();
        $resp->assertSee('175.000');   // biaya pendaftaran dari settings
        $resp->assertSee('1.750.000'); // daftar ulang
        $resp->assertSee('275.000');   // SPP
        $resp->assertSee('Syarat Uji');
    }

    public function test_profile_shows_only_web_visible_teachers(): void
    {
        $u1 = User::create(['username' => 'Ustadz Tampil', 'email' => 't1@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        Teacher::create(['id_user' => $u1->id_users, 'nama' => 'Ustadz Tampil', 'nuptk' => '1100000000000001', 'no_hp' => '08441', 'jabatan' => 'Kepala Sekolah', 'tampil_di_web' => true]);

        $u2 = User::create(['username' => 'Ustadz Sembunyi', 'email' => 't2@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        Teacher::create(['id_user' => $u2->id_users, 'nama' => 'Ustadz Sembunyi', 'nuptk' => '1100000000000002', 'no_hp' => '08442', 'tampil_di_web' => false]);

        $resp = $this->get('/profile');
        $resp->assertOk();
        $resp->assertSee('Ustadz Tampil');
        $resp->assertSee('Kepala Sekolah');
        $resp->assertDontSee('Ustadz Sembunyi');
    }
}
