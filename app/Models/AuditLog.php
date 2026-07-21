<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AuditLog — jejak aktivitas penting sistem (PRD §7.14).
 *
 * Menyimpan aksi, entitas terkait, dan snapshot data sebelum/sesudah (JSON).
 * Konvensi PK/FK eksplisit (rules.md §2).
 */
class AuditLog extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'audit_logs';
    protected $primaryKey = 'id_audit_logs';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'id_user', 'aksi', 'model_terkait',
        'data_sebelum', 'data_sesudah', 'ip_address',
    ];

    // Cast kolom JSON ke array
    protected $casts = [
        'data_sebelum' => 'array',
        'data_sesudah' => 'array',
    ];

    /**
     * Relasi: log dilakukan oleh satu user (nullable untuk aksi sistem).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }
}
