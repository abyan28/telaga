<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Student — data murid (bukan akun login, rules.md §1.6).
 */
class Student extends Model
{
    use HasSlug;

    protected $table = 'students';
    protected $primaryKey = 'id_students';

    protected $fillable = [
        'id_parent', 'id_class', 'id_academic_year',
        'nik', 'nisn', 'nis', 'angkatan', 'nama_lengkap', 'nama_panggilan', 'foto_path',
        'jenis_kelamin', 'agama', 'anak_ke', 'jumlah_saudara',
        'warga_negara', 'bahasa_keseharian', 'kondisi_kesehatan',
        'sudah_mengaji', 'ngaji_dimana', 'ngaji_metode', 'ngaji_jilid',
        'pernah_belajar', 'belajar_keterangan', 'ukuran_baju',
        'tempat_lahir', 'tanggal_lahir', 'status', 'slug',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'anak_ke' => 'integer', 'jumlah_saudara' => 'integer',
        'angkatan' => 'integer',
    ];

    /**
     * L8.3: angkatan 4-digit dari prefix tahun NIS (NSM 12 + YY 2 + urut 3).
     * NIS "12345678901225001" → 2025. Kosong/pendek → null.
     */
    public static function nisToAngkatan(?string $nis): ?int
    {
        return $nis && strlen($nis) >= 15 ? (int) ('20'.substr($nis, 12, 2)) : null;
    }

    /**
     * L1.2: aturan validasi field profil murid (DRY — dipakai 3 form).
     *
     * @return array<string, mixed>
     */
    public static function profilRules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nama_panggilan' => ['required', 'string', 'max:100'],
            'nik' => ['required', 'digits:16'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'agama' => ['required', 'string', 'max:50'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'anak_ke' => ['required', 'integer', 'min:1', 'max:20'],
            'jumlah_saudara' => ['required', 'integer', 'min:0', 'max:50'],
            'warga_negara' => ['required', 'string', 'max:100'],
            'bahasa_keseharian' => ['required', 'string', 'max:100'],
            'kondisi_kesehatan' => ['required', 'string', 'max:255'],
            'ukuran_baju' => ['required', 'in:S,M,L,XL,XXL,Jumbo'],
            // cabang
            'sudah_mengaji' => ['required', 'in:Sudah,Belum'],
            'ngaji_dimana' => ['nullable', 'required_if:sudah_mengaji,Sudah', 'string', 'max:255'],
            'ngaji_metode' => ['nullable', 'required_if:sudah_mengaji,Sudah', 'string', 'max:100'],
            'ngaji_jilid' => ['nullable', 'required_if:sudah_mengaji,Sudah', 'string', 'max:50'],
            'pernah_belajar' => ['required', 'in:PAUD,Les,Belum'],
            'belajar_keterangan' => ['nullable', 'required_if:pernah_belajar,PAUD,Les', 'string', 'max:255'],
        ];
    }

    // --- relasi ---
    public function ortu(): BelongsTo
    {
        return $this->belongsTo(OrangTua::class, 'id_parent', 'id_parents');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'id_class', 'id_classes');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'id_academic_year', 'id_academic_years');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'id_student', 'id_students');
    }

    public function monthlySppBills(): HasMany
    {
        return $this->hasMany(MonthlySppBill::class, 'id_student', 'id_students');
    }

    public function reRegistrationPayments(): HasMany
    {
        return $this->hasMany(ReRegistrationPayment::class, 'id_student', 'id_students');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(StudentProgressNote::class, 'id_student', 'id_students');
    }

    public function registrationForms(): HasMany
    {
        return $this->hasMany(RegistrationForm::class, 'id_student', 'id_students');
    }

    protected function slugSourceColumn(): string
    {
        return 'nama_lengkap';
    }
}
