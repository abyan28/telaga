<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// K8.1: wali kelas — FK nullable id_homeroom_teacher di classes.
// 1 kelas = maks 1 wali kelas (kolom tunggal jamin ini). "1 guru = maks 1 kelas
// jadi wali" ditegakkan di controller saat set (bukan constraint DB).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_homeroom_teacher')->nullable()->after('nama_kelas');
            $table->foreign('id_homeroom_teacher')->references('id_teachers')->on('teachers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['id_homeroom_teacher']);
            $table->dropColumn('id_homeroom_teacher');
        });
    }
};
