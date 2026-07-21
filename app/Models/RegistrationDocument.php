<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model RegistrationDocument — dokumen unggahan pada satu formulir pendaftaran.
 *
 * Menyimpan jenis (kk/akta/foto), path file, dan status verifikasi admin.
 * Konvensi PK/FK eksplisit (rules.md §2).
 */
class RegistrationDocument extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'registration_documents';
    protected $primaryKey = 'id_registration_documents';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'id_registration_form', 'jenis', 'path', 'status', 'catatan',
    ];

    /**
     * Route-model binding memakai primary key non-standar (rules.md §2).
     */
    public function getRouteKeyName(): string
    {
        return 'id_registration_documents';
    }

    /**
     * Relasi: dokumen milik satu formulir pendaftaran.
     */
    public function registrationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'id_registration_form', 'id_registration_forms');
    }
}
