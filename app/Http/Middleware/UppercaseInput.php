<?php

namespace App\Http\Middleware;

use App\Support\ValidationRules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normalisasi input form sebelum validasi/simpan → berlaku SEMUA form web.
 *
 * 1. Ubah teks yang diketik user menjadi HURUF KAPITAL (T2.4).
 * 2. Normalisasi No. HP ke format 62xxxxxxxxxx (K1.1) — "081..." → "6281...".
 *
 * Dikecualikan dari uppercase: kredensial & identifier case-/format-sensitif
 * (email, password, no_hp, nik, nisn, nuptk, dan kolom *_id wilayah snapshot).
 */
class UppercaseInput
{
    /**
     * Field yang TIDAK di-uppercase:
     * - kredensial/identifier case-sensitif (email, password, no_hp, nik, nisn, nuptk, username, login),
     * - nilai enum/select yang divalidasi lowercase (status, jenis, role, keputusan, grup, jenis_kelamin),
     * - catatan/prose bebas (biarkan huruf natural).
     */
    private const SKIP = [
        'email', 'password', 'password_confirmation', 'current_password',
        'no_hp', 'nik', 'nisn', 'nuptk', 'username', 'login', '_token', '_method',
        'status', 'jenis', 'jenis_kelamin', 'role', 'keputusan', 'grup',
        'catatan', 'catatan_admin', 'scope', 'arah', 'dibuka', 'tab', 'sub',
        'sudah_mengaji', 'pernah_belajar', 'ukuran_baju',
        'agama', 'pendidikan', 'penghasilan',
        'ayah_agama', 'ayah_pendidikan', 'ayah_pekerjaan', 'ayah_penghasilan',
        'ibu_agama', 'ibu_pendidikan', 'ibu_pekerjaan', 'ibu_penghasilan',
    ];

    /**
     * Uppercase tiap nilai string kecuali field di SKIP dan kolom *_id
     * (kode wilayah/relasi — bukan teks ketikan).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ponytail: CMS konten web (judul/deskripsi/key config) + field tampil-web guru
        // (jabatan) = copy publik, bukan "form pengisian data user" → jangan di-uppercase.
        if ($request->is('portal/admin/content*') || $request->is('portal/admin/teachers/*/web')) {
            return $next($request);
        }

        $request->merge(collect($request->all())->map(function ($value, $key) {
            if (! is_string($value)) {
                return $value;
            }

            // K1.1: normalisasi No. HP (0… → 62…) sebelum validasi & simpan.
            if ($key === 'no_hp') {
                return ValidationRules::normalizeNoHp($value);
            }

            // Kolom relasi/FK (konvensi id_<singular>, mis. id_class) & wilayah snapshot (*_id) — bukan teks ketikan.
            if (in_array($key, self::SKIP, true) || str_ends_with($key, '_id') || str_starts_with($key, 'id_')) {
                return $value;
            }

            return Str::upper($value);
        })->all());

        return $next($request);
    }
}
