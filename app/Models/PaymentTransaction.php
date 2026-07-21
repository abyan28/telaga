<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PaymentTransaction — satu transaksi pembayaran (bisa cicilan/parsial).
 *
 * Sumber kebenaran perhitungan saldo (PRD §13): sisa tunggakan dihitung dari
 * SUM transaksi berstatus 'diverifikasi'. Konvensi PK/FK eksplisit (rules.md §2).
 */
class PaymentTransaction extends Model
{
    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'payment_transactions';
    protected $primaryKey = 'id_payment_transactions';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'id_student', 'id_registration_form', 'id_user',
        'jenis', 'referensi_id', 'jumlah', 'bukti_path', 'bank_asal',
        'status', 'catatan', 'tanggal_bayar', 'verified_by',
    ];

    // Cast tipe kolom
    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal_bayar' => 'date',
    ];

    /**
     * Daftar nama bank/e-wallet dari database/data/bank.csv (T8.1).
     * Dipakai untuk dropdown "bank asal" saat wali unggah bukti transfer.
     *
     * @return array<int, string>
     */
    public static function daftarBank(): array
    {
        $path = database_path('data/bank.csv');
        if (! is_file($path)) {
            \Illuminate\Support\Facades\Log::warning('bank.csv tidak ditemukan di '.$path.' — dropdown bank kosong.');

            return [];
        }
        // Ambil kolom pertama (nama bank), lewati baris header, unik + terurut.
        $rows = array_map(fn ($l) => str_getcsv($l, ',', '"', '\\'), file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $names = array_filter(array_map(fn ($r) => trim($r[0] ?? ''), array_slice($rows, 1)));

        return array_values(array_unique($names));
    }

    /**
     * Route-model binding memakai primary key non-standar (rules.md §2).
     */
    public function getRouteKeyName(): string
    {
        return 'id_payment_transactions';
    }

    /**
     * Relasi: transaksi milik satu siswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'id_student', 'id_students');
    }

    /**
     * Relasi: transaksi terkait satu formulir pendaftaran (untuk jenis pendaftaran).
     */
    public function registrationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'id_registration_form', 'id_registration_forms');
    }

    /**
     * Relasi: user (wali) yang mengunggah bukti transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    /**
     * Relasi: admin yang memverifikasi transaksi (nullable).
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id_users');
    }

    /**
     * Relasi (K4.2): untuk transaksi jenis 'spp', referensi_id menunjuk tagihan
     * SPP bulanan (untuk menampilkan bulan apa yang dibayar). FK non-standar via
     * referensi_id (rules.md §2). Null untuk jenis lain.
     */
    public function sppBill(): BelongsTo
    {
        return $this->belongsTo(MonthlySppBill::class, 'referensi_id', 'id_monthly_spp_bills');
    }
}
