<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Model SiteFeature — item berulang & dinamis halaman publik (program, misi,
 * persyaratan, alur, statistik). Dibedakan kolom 'grup' (rules.md §1.8).
 */
class SiteFeature extends Model
{
    protected $table = 'site_features';
    protected $primaryKey = 'id_site_features';

    protected $fillable = ['grup', 'judul', 'deskripsi', 'ikon', 'foto_path', 'urutan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    /**
     * Route-model binding memakai primary key non-standar (rules.md §2).
     */
    public function getRouteKeyName(): string
    {
        return 'id_site_features';
    }

    /**
     * Scope: ambil item satu grup yang aktif, terurut (untuk render publik).
     */
    public function scopeGrup(Builder $query, string $grup): Builder
    {
        return $query->where('grup', $grup)->where('aktif', true)->orderBy('urutan');
    }
}
