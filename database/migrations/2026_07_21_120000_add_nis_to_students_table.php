<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom NIS (Nomor Induk Sekolah) ke students — 15-18 digit,
     * diberikan sekolah setelah murid diterima (diisi admin/guru, bukan form PPDB).
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nis', 18)->nullable()->unique()->after('nisn');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['nis']);
            $table->dropColumn('nis');
        });
    }
};
