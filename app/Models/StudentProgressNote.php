<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model StudentProgressNote — catatan perkembangan sederhana siswa (PRD §7.9).
 *
 * Ditulis guru (atau admin). Konvensi PK/FK eksplisit (rules.md §2).
 */
class StudentProgressNote extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'student_progress_notes';
    protected $primaryKey = 'id_student_progress_notes';

    // Kolom yang boleh diisi massal
    protected $fillable = ['id_student', 'id_teacher', 'catatan', 'tanggal'];

    // Cast tipe kolom
    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Relasi: catatan milik satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'id_student', 'id_students');
    }

    /**
     * Relasi: catatan ditulis oleh satu guru (nullable).
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'id_teacher', 'id_teachers');
    }
}
