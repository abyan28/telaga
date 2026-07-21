<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model MonthlySppBill — tagihan SPP satu siswa untuk satu bulan.
 *
 * Tiap bulan berdiri sendiri (rules.md §1.3.3). Sisa tunggakan dihitung dinamis
 * dari nominal dikurangi jumlah_terbayar. Konvensi PK/FK eksplisit (rules.md §2).
 */
class MonthlySppBill extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'monthly_spp_bills';
    protected $primaryKey = 'id_monthly_spp_bills';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'id_student', 'id_academic_year', 'bulan',
        'nominal', 'jumlah_terbayar', 'status',
    ];

    // Cast tipe kolom
    protected $casts = [
        'nominal' => 'decimal:2',
        'jumlah_terbayar' => 'decimal:2',
    ];

    /**
     * Menghitung sisa tunggakan bulan ini (nominal - jumlah_terbayar).
     * Tidak pernah negatif.
     */
    public function sisa(): float
    {
        return max(0, (float) $this->nominal - (float) $this->jumlah_terbayar);
    }

    /**
     * Relasi: transaksi pembayaran SPP untuk tagihan ini (FK non-standar
     * referensi_id → id_monthly_spp_bills). Dipakai untuk menampilkan bukti bayar.
     */
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'referensi_id', 'id_monthly_spp_bills')
            ->where('jenis', 'spp');
    }

    /**
     * Relasi: tagihan milik satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'id_student', 'id_students');
    }

    /**
     * Relasi: tagihan terikat pada satu tahun ajaran.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'id_academic_year', 'id_academic_years');
    }
}
