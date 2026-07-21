<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Impor data referensi wilayah Indonesia (provinsi/kota/kecamatan/kelurahan)
 * dari dump SQL statis (database/data/wilayah_indonesia.sql) — dipakai
 * dropdown alamat berjenjang pada form pendaftaran (T2.1).
 *
 * Data referensi read-only eksternal (~99k baris), sengaja TIDAK di-remodel ke
 * konvensi PK/FK proyek (rules.md §2) — sama seperti bank.csv, ini sumber statis
 * pihak ketiga. Hierarki disandikan di kolom `id` (kode BPS): prov=2, kota=4,
 * kec=6, kel=10 digit. Anak difilter via LEFT(id, n) = parent_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Dump ini MySQL-only (CHARACTER SET, ENGINE). Lewati di sqlite (test :memory:);
        // suite yang butuh data wilayah membuat tabel t_* sendiri.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Idempotent + anti-parsial: bila impor sebelumnya gagal di tengah
        // (DDL MySQL auto-commit, tak ikut rollback), buang sisa lalu impor ulang bersih.
        $tabel = ['t_provinsi', 't_kota', 't_kecamatan', 't_kelurahan'];
        $ada = array_filter($tabel, fn ($t) => Schema::hasTable($t));
        if (count($ada) === 4) {
            return; // sudah lengkap
        }
        foreach ($ada as $t) {
            Schema::dropIfExists($t);
        }

        // Jalankan dump per-statement. Satu unprepared untuk seluruh file (5 MB)
        // melebihi max_allowed_packet — pecah per ';' di akhir baris.
        $sql = file_get_contents(database_path('data/wilayah_indonesia.sql'));

        // Buang baris komentar '--' dan direktif '/*! ... */' agar splitter bersih.
        $lines = [];
        foreach (preg_split('/\r?\n/', $sql) as $line) {
            $t = ltrim($line);
            if ($t === '' || str_starts_with($t, '--') || str_starts_with($t, '/*')) {
                continue;
            }
            $lines[] = $line;
        }

        // Pisah per statement (';' di akhir baris), jalankan yang bukan SET/START/COMMIT.
        foreach (explode(";\n", implode("\n", $lines).";\n") as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || preg_match('/^(SET|START|COMMIT|UNLOCK|LOCK)\b/i', $stmt)) {
                continue;
            }
            DB::unprepared($stmt);
        }
        // Catatan: dump sudah menambah PRIMARY KEY(`id`) di akhir tiap tabel — cukup untuk filter LEFT(id,n).
    }

    public function down(): void
    {
        foreach (['t_kelurahan', 't_kecamatan', 't_kota', 't_provinsi'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
