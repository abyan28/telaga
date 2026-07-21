<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Models\SiteFeature;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menguji CMS konten admin (Tahap 2.5): update konten key-value, CRUD item
 * dinamis, upload media, field web guru, dan RBAC (admin-only). rules.md §1.8.
 */
class SiteContentCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['username' => 'Admin', 'email' => 'a@test.id', 'role' => 'admin', 'password' => Hash::make('x')]);
    }

    public function test_admin_can_update_contents(): void
    {
        SiteContent::create(['key' => 'home.hero_title', 'value' => 'Lama', 'grup' => 'home']);

        $this->actingAs($this->admin())->post('/portal/admin/content', [
            'contents' => ['home.hero_title' => 'Judul Baru'],
        ])->assertRedirect();

        $this->assertSame('Judul Baru', SiteContent::get('home.hero_title'));
    }

    public function test_admin_can_add_and_delete_feature(): void
    {
        $admin = $this->admin();

        // Tambah
        $this->actingAs($admin)->post('/portal/admin/content/features', [
            'grup' => 'program', 'judul' => 'Program Baru', 'deskripsi' => 'Desk',
        ])->assertRedirect();
        $f = SiteFeature::where('judul', 'Program Baru')->first();
        $this->assertNotNull($f);

        // Hapus
        $this->actingAs($admin)->delete("/portal/admin/content/features/{$f->id_site_features}")->assertRedirect();
        $this->assertDatabaseMissing('site_features', ['id_site_features' => $f->id_site_features]);
    }

    public function test_admin_can_upload_media(): void
    {
        Storage::fake('public');
        SiteContent::create(['key' => 'kontak.logo', 'value' => null, 'grup' => 'kontak', 'tipe' => 'image']);

        $this->actingAs($this->admin())->post('/portal/admin/content/media', [
            'key' => 'kontak.logo',
            'gambar' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $path = SiteContent::get('kontak.logo');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_admin_can_set_teacher_web_fields(): void
    {
        Storage::fake('public');
        $u = User::create(['username' => 'Ustadz', 'email' => 'g@test.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        $t = Teacher::create(['id_user' => $u->id_users, 'nama' => 'Ustadz', 'nuptk' => '1200000000000001', 'no_hp' => '08551']);

        $this->actingAs($this->admin())->post("/portal/admin/teachers/{$t->slug}/web", [
            'jabatan' => 'Kepala Sekolah',
            'tampil_di_web' => '1',
            'foto' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect();

        $t->refresh();
        $this->assertSame('Kepala Sekolah', $t->jabatan);
        $this->assertTrue($t->tampil_di_web);
        $this->assertNotNull($t->foto_path);
    }

    public function test_admin_can_add_kurikulum_slide_with_photo(): void
    {
        // T7.1: item grup 'kurikulum' menerima foto opsional yang disimpan ke storage publik.
        Storage::fake('public');

        $this->actingAs($this->admin())->post('/portal/admin/content/features', [
            'grup' => 'kurikulum',
            'judul' => 'Tahfidz',
            'deskripsi' => 'Hafalan surat pendek',
            'foto' => UploadedFile::fake()->image('slide.jpg'),
        ])->assertRedirect();

        $f = SiteFeature::where('grup', 'kurikulum')->where('judul', 'Tahfidz')->first();
        $this->assertNotNull($f);
        $this->assertNotNull($f->foto_path);
        Storage::disk('public')->assertExists($f->foto_path);
    }

    public function test_non_admin_cannot_access_cms(): void
    {
        $wali = User::create(['username' => 'W', 'email' => 'w@test.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
        $this->actingAs($wali)->get('/portal/admin/content')->assertForbidden();
        $this->actingAs($wali)->post('/portal/admin/content', ['contents' => []])->assertForbidden();
    }
}
