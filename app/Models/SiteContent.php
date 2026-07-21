<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model SiteContent — konten teks/angka tunggal halaman publik (key-value).
 *
 * Dikelola Admin via CMS. Gunakan helper get()/set() untuk akses ringkas dari
 * view publik (rules.md §1.8).
 */
class SiteContent extends Model
{
    protected $table = 'site_contents';
    protected $primaryKey = 'id_site_contents';

    protected $fillable = ['key', 'value', 'grup', 'label', 'tipe'];

    /**
     * Mengambil nilai konten berdasarkan key, dengan fallback default.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Menyimpan/memperbarui nilai konten (buat bila belum ada).
     */
    public static function set(string $key, ?string $value, array $attrs = []): void
    {
        static::updateOrCreate(['key' => $key], array_merge(['value' => $value], $attrs));
    }
}
