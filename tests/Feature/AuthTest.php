<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji autentikasi & RBAC (rules.md §1.5): self-signup wali dengan role
 * terkunci, redirect per role saat login, dan proteksi middleware role.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat user dengan role tertentu untuk pengujian.
     */
    private function makeUser(string $role): User
    {
        return User::create([
            'username' => ucfirst($role),
            'email' => $role.'@test.id',
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Registrasi wali: role wajib terkunci 'ortu' + profil ortu dibuat.
     */
    public function test_wali_signup_forces_wali_role_and_creates_ortu(): void
    {
        $response = $this->post('/register', [
            'username' => 'bunda_amira',
            'email' => 'amira@test.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('ortu.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'amira@test.id')->first();
        $this->assertNotNull($user);
        $this->assertSame('bunda_amira', $user->username); // username dari input signup
        $this->assertSame('ortu', $user->role);        // role terkunci
        $this->assertNotNull($user->ortu);          // profil ortu dibuat
    }

    /**
     * Registrasi menolak upaya mengirim role selain wali (tetap dipaksa 'ortu').
     */
    public function test_signup_ignores_injected_role(): void
    {
        $this->post('/register', [
            'username' => 'peretas',
            'email' => 'hacker@test.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin', // percobaan eskalasi — harus diabaikan
        ]);

        $this->assertSame('ortu', User::where('email', 'hacker@test.id')->first()->role);
    }

    /**
     * Signup menolak username tak valid (spasi/tanda hubung) & duplikat;
     * endpoint check-username konsisten dengan rule.
     */
    public function test_username_constraint_and_availability(): void
    {
        // Spasi & tanda hubung ditolak
        $this->post('/register', [
            'username' => 'ada spasi', 'email' => 'a@test.id',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('username');
        $this->post('/register', [
            'username' => 'pakai-hubung', 'email' => 'b@test.id',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('username');

        // < 6 karakter ditolak (constraint min 6)
        $this->post('/register', [
            'username' => 'budi', 'email' => 'c@test.id',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('username');

        // Buat satu username, lalu duplikat harus ditolak
        User::create(['username' => 'budi.wali', 'email' => 'budi@test.id', 'role' => 'ortu', 'password' => bcrypt('x')]);
        $this->get('/register/check-username?username=budi.wali')->assertJson(['available' => false]);
        $this->get('/register/check-username?username=budi_baru')->assertJson(['available' => true]);
        $this->get('/register/check-username?username=ab')->assertJson(['available' => false]); // < 3 char
    }

    /**
     * Login mengarahkan ke dashboard sesuai role.
     */
    public function test_login_redirects_by_role(): void
    {
        $this->makeUser('admin');
        $this->post('/login', ['login' => 'admin@test.id', 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');

        $this->makeUser('guru');
        $this->post('/login', ['login' => 'guru@test.id', 'password' => 'password123'])
            ->assertRedirect(route('guru.dashboard'));
        $this->post('/logout');

        $this->makeUser('ortu');
        $this->post('/login', ['login' => 'ortu@test.id', 'password' => 'password123'])
            ->assertRedirect(route('ortu.dashboard'));
    }

    /**
     * Login juga bisa memakai nomor HP (rules.md §1.5): guru sering hanya punya HP.
     */
    public function test_login_via_no_hp(): void
    {
        $user = User::create([
            'username' => '081200000009', 'no_hp' => '081200000009',
            'role' => 'guru', 'password' => Hash::make('rahasia88'),
        ]);
        $this->post('/login', ['login' => '081200000009', 'password' => 'rahasia88'])
            ->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Login juga bisa memakai username (T3.3 — mis. admin dg nama sbg username).
     */
    public function test_login_via_username(): void
    {
        $user = User::create([
            'username' => 'AdminSatu', 'email' => 'as@test.id',
            'role' => 'admin', 'password' => Hash::make('rahasia88'),
        ]);
        $this->post('/login', ['login' => 'AdminSatu', 'password' => 'rahasia88'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Login gagal dengan kredensial salah menampilkan error & tetap tamu.
     */
    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser('ortu');
        $this->post('/login', ['login' => 'wali@test.id', 'password' => 'salah'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * K5.4: guru berstatus nonaktif (keluar/pindah) ditolak login.
     */
    public function test_inactive_teacher_cannot_login(): void
    {
        $user = User::create([
            'username' => '081200000010', 'no_hp' => '081200000010',
            'role' => 'guru', 'password' => Hash::make('rahasia88'), 'is_aktif' => false,
        ]);
        \App\Models\Teacher::create([
            'id_user' => $user->id_users, 'nama' => 'GURU KELUAR',
            'nuptk' => '9999999999999999', 'no_hp' => '081200000010', 'is_aktif' => false,
        ]);

        $this->post('/login', ['login' => '081200000010', 'password' => 'rahasia88'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * RBAC: guru dilarang mengakses portal admin (403).
     */
    public function test_guru_cannot_access_admin_portal(): void
    {
        $this->actingAs($this->makeUser('guru'))
            ->get('/portal/admin')
            ->assertForbidden();
    }

    /**
     * RBAC: wali dilarang mengakses portal guru (403).
     */
    public function test_wali_cannot_access_guru_portal(): void
    {
        $this->actingAs($this->makeUser('ortu'))
            ->get('/portal/guru')
            ->assertForbidden();
    }

    /**
     * RBAC: admin dapat mengakses portal admin (200).
     */
    public function test_admin_can_access_admin_portal(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get('/portal/admin')
            ->assertOk();
    }

    /**
     * Guest diarahkan ke login saat mengakses portal terproteksi.
     */
    public function test_guest_redirected_to_login(): void
    {
        $this->get('/portal/ortu')->assertRedirect(route('login'));
    }

    /**
     * L4.1: wali nonaktif diblok login (guard users.is_aktif).
     */
    public function test_inactive_wali_cannot_login(): void
    {
        User::create([
            'username' => 'walinonaktif', 'no_hp' => '081200000099',
            'role' => 'ortu', 'password' => Hash::make('pw'), 'is_aktif' => false,
        ]);
        $this->post('/login', ['login' => '081200000099', 'password' => 'pw'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * L4.1: admin nonaktif diblok login.
     */
    public function test_inactive_admin_cannot_login(): void
    {
        User::create([
            'username' => 'AdminNonaktif', 'email' => 'non@test.id',
            'role' => 'admin', 'password' => Hash::make('pw'), 'is_aktif' => false,
        ]);
        $this->post('/login', ['login' => 'non@test.id', 'password' => 'pw'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * L4.1: admin dapat toggle status akun wali.
     */
    public function test_admin_can_toggle_account_status(): void
    {
        $admin = $this->makeUser('admin');
        $wali = User::create([
            'username' => 'TestWali', 'role' => 'ortu', 'password' => Hash::make('x'), 'no_hp' => '6281200000001',
        ]);

        // Nonaktifkan
        $this->actingAs($admin)->post("/portal/admin/accounts/{$wali->id_users}/toggle-status")
            ->assertRedirect();
        $wali->refresh();
        $this->assertFalse($wali->is_aktif);

        // Aktifkan kembali
        $this->actingAs($admin)->post("/portal/admin/accounts/{$wali->id_users}/toggle-status")
            ->assertRedirect();
        $wali->refresh();
        $this->assertTrue($wali->is_aktif);
    }

    /**
     * L4.1: admin tidak bisa menonaktifkan diri sendiri.
     */
    public function test_admin_cannot_toggle_self(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post("/portal/admin/accounts/{$admin->id_users}/toggle-status")
            ->assertForbidden();
    }

    /**
     * L4.1: halaman Data Akun render dengan semua role.
     */
    public function test_accounts_page_shows_all_roles(): void
    {
        $admin = $this->makeUser('admin');
        User::create(['username' => 'GuruX', 'role' => 'guru', 'password' => Hash::make('x')]);
        User::create(['username' => 'WaliX', 'role' => 'ortu', 'password' => Hash::make('x')]);

        $this->actingAs($admin)->get('/portal/admin/accounts')
            ->assertOk()
            ->assertSee('GuruX')
            ->assertSee('WaliX')
            ->assertSee('Admin');
    }
}
