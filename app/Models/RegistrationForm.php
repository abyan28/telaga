<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model RegistrationForm — formulir pendaftaran murid baru.
 *
 * Dibuat wali (user), terikat tahun ajaran, dan menaut ke siswa setelah lulus.
 * Menampung dokumen unggahan (KK/Akta/Foto). Konvensi PK/FK eksplisit (rules.md §2).
 */
class RegistrationForm extends Model
{
    use HasSlug;

    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'registration_forms';
    protected $primaryKey = 'id_registration_forms';

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'id_user', 'id_student', 'id_academic_year', 'status', 'boleh_edit', 'catatan_admin', 'slug',
    ];

    protected $casts = ['boleh_edit' => 'boolean'];

    /**
     * Sumber slug: nama siswa bila tertaut, else 'pendaftaran'. Suffix PK jamin unik.
     */
    protected function slugSource(): string
    {
        return $this->student?->nama_lengkap ?: 'pendaftaran';
    }

    /**
     * K2.5: wali boleh edit pendaftaran selama pembayaran belum diverifikasi
     * (submitted/menunggu_bukti), ATAU admin membuka flag boleh_edit.
     */
    public function canBeEditedByWali(): bool
    {
        return in_array($this->status, ['submitted', 'menunggu_bukti'], true)
            || $this->boleh_edit;
    }

    /**
     * L2.2: batalkan status lulus bila PPDB sudah ditutup dan calon murid belum
     * membayar daftar ulang sama sekali (belum ada satupun jumlah_terbayar > 0).
     *
     * "Belum bayar sama sekali" = tak ada tagihan daftar ulang dengan
     * jumlah_terbayar > 0 pada tahun ajaran formulir ini. Bila terpenuhi, status
     * form → 'gagal' & siswa → 'nonaktif'. Idempotent (hanya berlaku saat status
     * masih 'lulus'). Dipanggil lazily di halaman wali (payments/status) sehingga
     * penutupan PPDB langsung berefek tanpa job terjadwal.
     *
     * @return bool  true bila kelulusan baru saja dibatalkan pada pemanggilan ini
     */
    public function cancelLulusIfPpdbClosedAndUnpaid(): bool
    {
        // Hanya relevan untuk form yang berstatus lulus.
        if ($this->status !== 'lulus') {
            return false;
        }

        // Tidak berlaku selama PPDB masih dibuka.
        if (Setting::get('pendaftaran_dibuka', '1') === '1') {
            return false;
        }

        // Sudah ada pembayaran daftar ulang (sebagian/lunas) → jangan batalkan.
        $sudahBayar = $this->student
            ?->reRegistrationPayments()
            ->where('jumlah_terbayar', '>', 0)
            ->exists() ?? false;

        if ($sudahBayar) {
            return false;
        }

        // Batalkan kelulusan: form gagal, siswa nonaktif (audit tercatat).
        $this->update(['status' => 'gagal']);
        $this->student?->update(['status' => 'nonaktif']);

        AuditLogService::record(
            'batal_lulus_ppdb_tutup',
            'RegistrationForm#'.$this->id_registration_forms,
            ['status' => 'lulus'],
            ['status' => 'gagal'],
        );

        // Notifikasi email ke wali (PRD §7.13). MAIL_MAILER=log saat dev → terkirim
        // otomatis begitu SMTP asli di-set (deploy 3.C).
        $this->loadMissing('user');
        if ($this->user?->email) {
            \Illuminate\Support\Facades\Mail::to($this->user->email)->send(
                new \App\Mail\RegistrationNotification(
                    'Status Kelulusan Dibatalkan',
                    'Mohon maaf, status kelulusan calon murid dibatalkan karena masa pendaftaran (PPDB) telah ditutup '
                    .'dan pembayaran daftar ulang belum dilakukan sama sekali. Silakan hubungi pihak sekolah untuk informasi lebih lanjut.',
                )
            );
        }

        return true;
    }

    /**
     * Relasi: formulir dibuat oleh satu user (wali).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    /**
     * Relasi: formulir dapat menaut ke satu siswa (setelah lulus).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'id_student', 'id_students');
    }

    /**
     * Relasi: formulir terikat pada satu tahun ajaran.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'id_academic_year', 'id_academic_years');
    }

    /**
     * Relasi: dokumen-dokumen unggahan milik formulir ini.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'id_registration_form', 'id_registration_forms');
    }
}
