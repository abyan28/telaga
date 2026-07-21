<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Setting — pengaturan dinamis key-value.
 *
 * Dipakai Admin untuk menyimpan nominal biaya dinamis dan konfigurasi lain
 * (rules.md §1.3). Menyediakan helper get()/set() agar akses nilai lebih mudah.
 */
class Setting extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'settings';
    protected $primaryKey = 'id_settings';

    // Kolom yang boleh diisi massal
    protected $fillable = ['key', 'value'];

    /**
     * Mengambil nilai setting berdasarkan key.
     *
     * @param  string  $key      Nama pengaturan.
     * @param  mixed   $default  Nilai default bila key tidak ditemukan.
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    /**
     * Menetapkan (membuat/memperbarui) nilai setting berdasarkan key.
     *
     * @param  string  $key    Nama pengaturan.
     * @param  mixed   $value  Nilai yang disimpan.
     * @return static
     */
    public static function set(string $key, $value): static
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
