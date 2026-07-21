<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model AcademicYear — merepresentasikan satu tahun ajaran (mis. "2026/2027").
 *
 * Konvensi rules.md §2: nama tabel & primary key non-standar didefinisikan
 * eksplisit. Banyak entitas (kelas, siswa, pendaftaran, tagihan SPP) terikat
 * ke tahun ajaran melalui FK id_academic_year.
 */
class AcademicYear extends Model
{
    // Nama tabel eksplisit (rules.md §2.3)
    protected $table = 'academic_years';

    // Primary key non-standar (rules.md §2.1)
    protected $primaryKey = 'id_academic_years';

    // Kolom yang boleh diisi massal
    protected $fillable = ['tahun', 'is_aktif'];

    // Cast tipe kolom
    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    /**
     * Relasi: satu tahun ajaran memiliki banyak kelas.
     * FK id_academic_year berada di tabel classes, menunjuk PK id_academic_years.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'id_academic_year', 'id_academic_years');
    }

    /**
     * Relasi: satu tahun ajaran memiliki banyak siswa.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'id_academic_year', 'id_academic_years');
    }

    /**
     * Relasi: satu tahun ajaran memiliki banyak formulir pendaftaran.
     */
    public function registrationForms(): HasMany
    {
        return $this->hasMany(RegistrationForm::class, 'id_academic_year', 'id_academic_years');
    }

    /**
     * L2.4: TA target PPDB — baca dari settings; fallback ke TA aktif.
     */
    public static function taPpdb(): self
    {
        $id = (int) Setting::get('ta_ppdb', 0);
        if ($id && $ta = AcademicYear::find($id)) {
            return $ta;
        }
        // Fallback: TA yang sedang aktif (belum diset target → PPDB untuk TA sekarang).
        return AcademicYear::where('is_aktif', true)->firstOrFail();
    }
}
