<?php

namespace App\Support;

/**
 * Aturan validasi terpusat untuk field yang muncul di banyak form (K1).
 *
 * Menjaga aturan No. HP & Nama konsisten di seluruh form (wali/admin/guru)
 * tanpa duplikasi (pola DRY seperti Student::alamatRules()). Normalisasi
 * awalan No. HP (0 → 62) dilakukan di middleware UppercaseInput sebelum
 * validasi, sehingga data yang tersimpan selalu berformat 62xxxxxxxxxx.
 */
class ValidationRules
{
    /**
     * Aturan Nama (K1.2): huruf + spasi + titik + apostrof + tanda hubung.
     * Menolak angka & simbol lain. Nama Indonesia sah seperti "M. RIDWAN"
     * atau "SITI NUR'AINI" tetap lolos.
     *
     * @param  bool  $required  true = wajib diisi, false = boleh kosong.
     * @return array<int, mixed>
     */
    public static function nama(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:255',
            "regex:/^[A-Za-z .'\\-]+$/",
        ];
    }

    /**
     * Aturan No. HP (K1.1): angka saja, 9–14 digit (sesudah dinormalisasi ke 62…,
     * mencakup rentang 62 + 8xx…). Aturan unique ditambahkan terpisah di tiap
     * pemanggil (target tabel & ignore-self berbeda-beda).
     *
     * @param  bool  $required  true = wajib diisi, false = boleh kosong.
     * @return array<int, string>
     */
    public static function noHp(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^[0-9]{9,14}$/',
        ];
    }

    /**
     * Normalisasi No. HP ke format 62xxxxxxxxxx (K1.1).
     * "081..." → "6281...", "+62 812-..." → "62812...". Input non-angka
     * dibuang; string kosong dikembalikan apa adanya (dibiarkan divalidasi).
     */
    public static function normalizeNoHp(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Buang semua non-digit (spasi, +, tanda hubung).
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return $value; // biarkan validator yang menolak
        }

        // Awalan 0 → 62 (nomor lokal Indonesia).
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Aturan NUPTK: tepat 16 digit angka. Aturan unique ditambahkan terpisah
     * di tiap pemanggil (store vs update ignore-self).
     *
     * @return array<int, string>
     */
    public static function nuptk(): array
    {
        return ['required', 'digits:16'];
    }

    /**
     * Pesan validasi kustom (Bahasa Indonesia) untuk aturan nama & no_hp.
     * Gabungkan ke messages() tiap form: [...ValidationRules::messages()].
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'nama.regex' => 'Nama hanya boleh huruf, spasi, titik, apostrof, dan tanda hubung.',
            'nama_wali.regex' => 'Nama hanya boleh huruf, spasi, titik, apostrof, dan tanda hubung.',
            'nama_anak.regex' => 'Nama hanya boleh huruf, spasi, titik, apostrof, dan tanda hubung.',
            'nama_lengkap.regex' => 'Nama hanya boleh huruf, spasi, titik, apostrof, dan tanda hubung.',
            'nama_panggilan.regex' => 'Nama hanya boleh huruf, spasi, titik, apostrof, dan tanda hubung.',
            'no_hp.regex' => 'No. HP harus angka 9–14 digit (contoh: 081234567890).',
            'nuptk.digits' => 'NUPTK harus tepat 16 digit angka.',
        ];
    }
}
