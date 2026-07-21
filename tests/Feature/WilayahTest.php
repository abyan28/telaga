<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Menguji endpoint dropdown wilayah berjenjang (T2.1): filter anak per prefix
 * id parent, guard tanpa-parent, pencarian, dan RBAC (khusus wali).
 */
class WilayahTest extends TestCase
{
    use RefreshDatabase;

    /** Buat tabel t_* + seed minimal (migration impor SQL tak jalan di sqlite test). */
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['t_provinsi', 't_kota', 't_kecamatan', 't_kelurahan'] as $t) {
            Schema::create($t, function ($table) {
                $table->string('id', 10)->primary();
                $table->string('nama');
                $table->double('latitude')->default(0);
                $table->double('longitude')->default(0);
            });
        }
        DB::table('t_provinsi')->insert([
            ['id' => '32', 'nama' => 'Jawa Barat'],
            ['id' => '33', 'nama' => 'Jawa Tengah'],
        ]);
        DB::table('t_kota')->insert([
            ['id' => '3201', 'nama' => 'Kabupaten Bogor'],   // di Jabar (32)
            ['id' => '3301', 'nama' => 'Kabupaten Cilacap'], // di Jateng (33)
        ]);
    }

    private function wali(): User
    {
        return User::create(['username' => 'W', 'email' => 'w@t.id', 'role' => 'ortu', 'password' => Hash::make('x')]);
    }

    public function test_provinsi_list_returns_all(): void
    {
        $this->actingAs($this->wali())->getJson('/wilayah/provinsi')
            ->assertOk()->assertJsonCount(2);
    }

    public function test_kota_filtered_by_parent_prefix(): void
    {
        // Kota di Jabar (32) hanya Bogor, bukan Cilacap.
        $this->actingAs($this->wali())->getJson('/wilayah/kota?parent=32')
            ->assertOk()->assertJsonCount(1)->assertJsonFragment(['nama' => 'Kabupaten Bogor'])
            ->assertJsonMissing(['nama' => 'Kabupaten Cilacap']);
    }

    public function test_child_without_parent_returns_empty(): void
    {
        // Tanpa parent valid, jangan bocorkan seluruh tabel.
        $this->actingAs($this->wali())->getJson('/wilayah/kota')
            ->assertOk()->assertJsonCount(0);
    }

    public function test_guru_can_access(): void
    {
        // Endpoint dipakai form profil guru juga (T2.1 profil guru).
        $guru = User::create(['username' => 'G', 'email' => 'g@t.id', 'role' => 'guru', 'password' => Hash::make('x')]);
        $this->actingAs($guru)->getJson('/wilayah/provinsi')->assertOk()->assertJsonCount(2);
    }

    public function test_guest_forbidden(): void
    {
        // Web guard redirect ke login (bukan 401 JSON).
        $this->get('/wilayah/provinsi')->assertRedirect('/login');
    }
}
