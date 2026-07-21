<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Teacher — profil guru, relasi 1-1 ke User (rules.md §1.6).
 *
 * Menyimpan nomor_induk_guru (juga password awal, rules.md §1.5) dan data
 * kontak. Seorang guru dapat mengampu banyak kelas (relasi many-to-many via
 * tabel pivot class_teacher — rules.md/PRD §7.10).
 */
class Teacher extends Model
{
    use HasSlug;

    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'teachers';
    protected $primaryKey = 'id_teachers';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'id_user', 'nama', 'nuptk', 'jabatan', 'foto_path', 'tampil_di_web', 'is_aktif', 'no_hp', 'alamat', 'slug',
        'tempat_lahir', 'tanggal_lahir', 'riwayat_pendidikan', 'jenis_kelamin', 'tanggal_mulai_mengajar',
        'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
        'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama',
    ];

    protected $casts = ['tampil_di_web' => 'boolean', 'is_aktif' => 'boolean', 'tanggal_lahir' => 'date', 'tanggal_mulai_mengajar' => 'date'];

    /**
     * Relasi: profil guru ini milik satu user (akun login).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    /**
     * Relasi many-to-many: guru mengampu banyak kelas melalui pivot class_teacher.
     * Pivot: id_teacher (FK guru) & id_class (FK kelas).
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'class_teacher',   // nama tabel pivot
            'id_teacher',      // FK guru di pivot
            'id_class',        // FK kelas di pivot
            'id_teachers',     // PK lokal (teachers)
            'id_classes'       // PK tujuan (classes)
        )->withTimestamps();
    }

    /**
     * Relasi: catatan perkembangan siswa yang ditulis guru ini.
     */
    public function progressNotes(): HasMany
    {
        return $this->hasMany(StudentProgressNote::class, 'id_teacher', 'id_teachers');
    }

    /**
     * Relasi: kelas yang diwalikelaskan guru ini (maks 1). Nullable.
     */
    public function homeroomClass(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'id_homeroom_teacher', 'id_teachers');
    }

    /**
     * True bila guru ini wali kelas dari kelas siswa tsb (hak akses penuh K8.1).
     */
    public function isHomeroomOf(Student $student): bool
    {
        return $student->id_class !== null
            && $this->homeroomClass()->where('id_classes', $student->id_class)->exists();
    }

    /**
     * Mengembalikan daftar id_classes kelas yang diampu guru ini.
     *
     * Dipakai sebagai dasar query guard anti-kebocoran antar-kelas (PRD §13):
     * guru hanya boleh mengakses siswa yang berada di kelas-kelas ini.
     *
     * @return array<int>
     */
    public function ampuClassIds(): array
    {
        return $this->classes()->pluck('classes.id_classes')->all();
    }
}
