<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom alamat wilayah berjenjang ke teachers (T2.1, profil guru).
 * Sama dengan parents: id (kode BPS) + nama snapshot, nullable. Kolom `alamat`
 * lama tetap untuk detail jalan/RT/RW.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('provinsi_id', 10)->nullable()->after('alamat');
            $table->string('provinsi_nama')->nullable()->after('provinsi_id');
            $table->string('kota_id', 10)->nullable()->after('provinsi_nama');
            $table->string('kota_nama')->nullable()->after('kota_id');
            $table->string('kecamatan_id', 10)->nullable()->after('kota_nama');
            $table->string('kecamatan_nama')->nullable()->after('kecamatan_id');
            $table->string('kelurahan_id', 10)->nullable()->after('kecamatan_nama');
            $table->string('kelurahan_nama')->nullable()->after('kelurahan_id');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
                'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama',
            ]);
        });
    }
};
