<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model OrangTua — profil orang tua murid (ayah + ibu dalam 1 baris), relasi 1-1 ke User.
 *
 * L1.1: data ortu dipisah prefix ayah_ / ibu_ (flat). Alamat keluarga (1 set) pindah ke sini
 * dari students (L1 revisi C10). Wali default = IBU (fallback ayah).
 */
class OrangTua extends Model
{
    protected $table = 'parents';
    protected $primaryKey = 'id_parents';

    protected $fillable = [
        'id_user', 'ada_ayah', 'ada_ibu',
        'ayah_nama', 'ayah_tempat_lahir', 'ayah_tanggal_lahir', 'ayah_agama', 'ayah_pendidikan',
        'ayah_pekerjaan', 'ayah_pekerjaan_lain', 'ayah_penghasilan', 'ayah_no_hp',
        'ibu_nama', 'ibu_tempat_lahir', 'ibu_tanggal_lahir', 'ibu_agama', 'ibu_pendidikan',
        'ibu_pekerjaan', 'ibu_pekerjaan_lain', 'ibu_penghasilan', 'ibu_no_hp',
        'alamat',
        'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
        'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama',
    ];

    protected $casts = [
        'ada_ayah' => 'boolean', 'ada_ibu' => 'boolean',
        'ayah_tanggal_lahir' => 'date', 'ibu_tanggal_lahir' => 'date',
    ];

    /**
     * L1.1: nama wali untuk tampilan — default ibu, fallback ayah.
     */
    public function namaWali(): ?string
    {
        return $this->ibu_nama ?: $this->ayah_nama;
    }

    /**
     * L1.1: no HP wali untuk kontak — default ibu, fallback ayah.
     */
    public function noHpWali(): ?string
    {
        return $this->ibu_no_hp ?: $this->ayah_no_hp;
    }

    /**
     * Aturan validasi alamat keluarga + wilayah (L1 revisi: pindah dari Student).
     * Dipakai form profil ortu & CRUD parent.
     *
     * @return array<string, array<int, string>>
     */
    public static function alamatRules(): array
    {
        return [
            'alamat' => ['nullable', 'string', 'max:500'],
            'provinsi_id' => ['nullable', 'string', 'max:10'], 'provinsi_nama' => ['nullable', 'string', 'max:255'],
            'kota_id' => ['nullable', 'string', 'max:10'], 'kota_nama' => ['nullable', 'string', 'max:255'],
            'kecamatan_id' => ['nullable', 'string', 'max:10'], 'kecamatan_nama' => ['nullable', 'string', 'max:255'],
            'kelurahan_id' => ['nullable', 'string', 'max:10'], 'kelurahan_nama' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Saring array data → hanya kolom alamat + wilayah.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function alamatData(array $data): array
    {
        return collect($data)->only(array_keys(static::alamatRules()))->all();
    }

    /**
     * Profil lengkap (K2.1 gate): minimal 1 ortu aktif + kolom wajibnya terisi + alamat.
     */
    public function isComplete(): bool
    {
        if (! $this->ada_ayah && ! $this->ada_ibu) {
            return false;
        }
        foreach (['ayah', 'ibu'] as $p) {
            if (! $this->{"ada_$p"}) {
                continue;
            }
            foreach (['nama', 'tempat_lahir', 'tanggal_lahir', 'agama', 'pendidikan', 'pekerjaan', 'penghasilan', 'no_hp'] as $c) {
                if (blank($this->{"{$p}_{$c}"})) {
                    return false;
                }
            }
        }

        return filled($this->alamat);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'id_parent', 'id_parents');
    }
}
