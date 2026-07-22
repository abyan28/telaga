<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Bank — daftar nama bank/e-wallet untuk dropdown input bukti bayar.
 * Data di-seed dari bank.csv (T8.1); CSV dihapus setelah seed.
 */
class Bank extends Model
{
    protected $table    = 'banks';
    public $timestamps  = false;
    protected $fillable = ['nama'];

    /**
     * Kembalikan array nama bank terurut A-Z.
     * Dipakai controller sebagai ganti PaymentTransaction::daftarBank().
     *
     * @return string[]
     */
    public static function daftarNama(): array
    {
        return static::orderBy('nama')->pluck('nama')->all();
    }
}
