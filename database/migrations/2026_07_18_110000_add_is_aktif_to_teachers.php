<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * K5.4: status keluar/pindah guru (ganti hard-delete yg dibuang).
     * Boolean is_aktif — nonaktif = ex-guru: disembunyikan dari list & diblok login.
     * Data guru tetap tersimpan untuk arsip/riwayat (audit, kelas lampau).
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->boolean('is_aktif')->default(true)->after('tampil_di_web');
        });
    }

    /**
     * Rollback: buang kolom is_aktif.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('is_aktif');
        });
    }
};
