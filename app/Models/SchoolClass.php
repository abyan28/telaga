<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model SchoolClass — data kelas.
 *
 * Dinamai SchoolClass (bukan Class) karena 'Class' adalah keyword PHP.
 * Tabel tetap 'classes'. Satu kelas dapat diampu banyak guru (many-to-many
 * via pivot class_teacher) dan menampung banyak siswa.
 */
class SchoolClass extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'classes';
    protected $primaryKey = 'id_classes';

    // Kolom yang boleh diisi massal
    protected $fillable = ['id_academic_year', 'nama_kelas', 'id_homeroom_teacher'];

    /**
     * Route-model binding memakai primary key non-standar (rules.md §2).
     */
    public function getRouteKeyName(): string
    {
        return 'id_classes';
    }

    /**
     * Relasi: kelas ini milik satu tahun ajaran.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'id_academic_year', 'id_academic_years');
    }

    /**
     * Relasi many-to-many: kelas diampu oleh banyak guru (pivot class_teacher).
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(
            Teacher::class,
            'class_teacher',   // nama tabel pivot
            'id_class',        // FK kelas di pivot
            'id_teacher',      // FK guru di pivot
            'id_classes',      // PK lokal (classes)
            'id_teachers'      // PK tujuan (teachers)
        )->withTimestamps();
    }

    /**
     * Relasi: wali kelas (satu guru) yang memegang kelas ini. Nullable.
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'id_homeroom_teacher', 'id_teachers');
    }

    /**
     * Relasi: satu kelas memiliki banyak siswa.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'id_class', 'id_classes');
    }
}
