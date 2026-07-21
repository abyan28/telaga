<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * AuditLogService — pencatatan jejak aktivitas penting (PRD §7.14).
 *
 * Menyimpan aksi krusial (verifikasi pembayaran, ubah status pendaftaran, dll)
 * beserta snapshot data sebelum/sesudah bila tersedia.
 */
class AuditLogService
{
    /**
     * Mencatat satu entri audit log.
     *
     * @param  string      $aksi          Deskripsi aksi (mis. 'verifikasi_pembayaran').
     * @param  string|null $modelTerkait  Nama entitas terkait (mis. 'PaymentTransaction#12').
     * @param  array|null  $sebelum       Snapshot data sebelum perubahan.
     * @param  array|null  $sesudah       Snapshot data sesudah perubahan.
     */
    public static function record(string $aksi, ?string $modelTerkait = null, ?array $sebelum = null, ?array $sesudah = null): void
    {
        AuditLog::create([
            'id_user' => Auth::id(),
            'aksi' => $aksi,
            'model_terkait' => $modelTerkait,
            'data_sebelum' => $sebelum,
            'data_sesudah' => $sesudah,
            'ip_address' => Request::ip(),
        ]);
    }
}
