<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreRegistrationRequest — validasi form pendaftaran murid baru (L1.2).
 *
 * Alamat kini di profil wali (ortu) → tidak divalidasi di sini.
 * Field profil murid: Student::profilRules() (DRY).
 */
class StoreRegistrationRequest extends FormRequest
{
    /**
     * L2.5: rentang tanggal lahir usia 4–6 th per 1 Juli TAHUN AJARAN.
     */
    private function usiaRule(): array
    {
        $tahun = \App\Models\AcademicYear::taPpdb()->tahun;
        $tahunAjaran = ($tahun && preg_match('/^(\d{4})/', $tahun, $m)) ? (int) $m[1] : now()->year;

        return ['after_or_equal:'.($tahunAjaran - 6).'-07-01', 'before_or_equal:'.($tahunAjaran - 4).'-07-01'];
    }

    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->role === 'ortu'
            && \App\Models\Setting::get('pendaftaran_dibuka', '1') === '1';
    }

    public function rules(): array
    {
        // Profil murid DRY dari Student::profilRules() + override nik unique + usia + berkas.
        $profil = Student::profilRules();
        // Override NIK: unique di sini (bukan di profilRules — update pakai ignore)
        $profil['nik'] = ['required', 'digits:16', 'unique:students,nik'];
        // Override tanggal_lahir: tambah usia constraint
        $profil['tanggal_lahir'] = ['required', 'date', ...$this->usiaRule()];
        // nama_anak → maps ke nama_lengkap di controller
        $profil['nama_anak'] = $profil['nama_lengkap'];
        unset($profil['nama_lengkap']);

        return [
            ...$profil,
            // Berkas
            'kk'   => ['required', 'file', 'mimes:jpg,jpeg,pdf', 'max:2048'],
            'akta' => ['required', 'file', 'mimes:jpg,jpeg,pdf', 'max:2048'],
            'foto' => ['required', 'file', 'mimes:jpg,jpeg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            ...ValidationRules::messages(),
            'nik.digits' => 'NIK harus tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'tanggal_lahir.after_or_equal' => 'Usia calon murid maksimal 6 tahun per 1 Juli.',
            'tanggal_lahir.before_or_equal' => 'Usia calon murid minimal 4 tahun per 1 Juli.',
            'kk.max' => 'Ukuran file KK maksimal 2 MB.',
            'akta.max' => 'Ukuran file Akta maksimal 2 MB.',
            'foto.max' => 'Ukuran file Foto maksimal 2 MB.',
            'kk.mimes' => 'Format KK harus JPG atau PDF.',
            'akta.mimes' => 'Format Akta harus JPG atau PDF.',
            'foto.mimes' => 'Format Foto harus JPG.',
        ];
    }
}
