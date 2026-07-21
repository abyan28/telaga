<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * WilayahController — endpoint AJAX dropdown alamat berjenjang (T2.1).
 * Data referensi statis (tabel t_*, kode BPS). Anak difilter via prefix id
 * parent: kota substr(id,1,2)=prov, kecamatan substr(id,1,4)=kota, kelurahan substr(id,1,6)=kec.
 */
class WilayahController extends Controller
{
    /** Level → [tabel, panjang prefix parent]. Provinsi tanpa parent. */
    private const LEVELS = [
        'provinsi'   => ['t_provinsi', 0],
        'kota'       => ['t_kota', 2],
        'kecamatan'  => ['t_kecamatan', 4],
        'kelurahan'  => ['t_kelurahan', 6],
    ];

    /**
     * Kembalikan daftar [{id, nama}] satu level, difilter parent + pencarian.
     * GET /wilayah/{level}?parent=<id>&q=<cari>
     */
    public function index(Request $request, string $level)
    {
        abort_unless(isset(self::LEVELS[$level]), 404);
        [$tabel, $prefix] = self::LEVELS[$level];

        $q = DB::table($tabel)->select('id', 'nama')->orderBy('nama');

        // Filter ke parent (kecuali provinsi): id anak diawali id parent.
        if ($prefix > 0) {
            $parent = (string) $request->query('parent', '');
            // Tanpa parent valid, jangan kembalikan seluruh tabel (bisa puluhan ribu baris).
            if (strlen($parent) < $prefix) {
                return response()->json([]);
            }
            // substr(id,1,n) portabel MySQL+SQLite (setara LEFT(id,n)).
            $q->whereRaw('substr(id, 1, ?) = ?', [$prefix, substr($parent, 0, $prefix)]);
        }

        // Pencarian nama (searchable dropdown).
        if ($cari = trim((string) $request->query('q', ''))) {
            $q->where('nama', 'like', '%'.$cari.'%');
        }

        return response()->json($q->limit(1000)->get());
    }
}
