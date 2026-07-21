<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ReRegistrationPayment — tagihan daftar ulang satu siswa.
 *
 * Dapat dicicil (PRD §7.7). Sisa dihitung dinamis dari total_biaya dikurangi
 * jumlah_terbayar. Konvensi PK/FK eksplisit (rules.md §2).
 */
class ReRegistrationPayment extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 're_registration_payments';
    protected $primaryKey = 'id_re_registration_payments';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'id_student', 'id_academic_year',
        'total_biaya', 'jumlah_terbayar', 'status',
    ];

    // Cast tipe kolom
    protected $casts = [
        'total_biaya' => 'decimal:2',
        'jumlah_terbayar' => 'decimal:2',
    ];

    /**
     * Menghitung sisa tunggakan daftar ulang (total_biaya - jumlah_terbayar).
     * Tidak pernah negatif.
     */
    public function sisa(): float
    {
        return max(0, (float) $this->total_biaya - (float) $this->jumlah_terbayar);
    }

    /**
     * Relasi: transaksi pembayaran daftar ulang untuk tagihan ini (FK non-standar
     * referensi_id → id_re_registration_payments). Dipakai menampilkan bukti/bank/tanggal.
     */
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'referensi_id', 'id_re_registration_payments')
            ->where('jenis', 'daftar_ulang');
    }

    /**
     * Relasi: pembayaran milik satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'id_student', 'id_students');
    }

    /**
     * Relasi: pembayaran terikat pada satu tahun ajaran.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'id_academic_year', 'id_academic_years');
    }
}
