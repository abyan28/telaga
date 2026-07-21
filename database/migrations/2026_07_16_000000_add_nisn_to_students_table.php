<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah kolom NISN ke students (T5.2).
     *
     * NISN terbit setelah siswa masuk sekolah, jadi nullable & unique (banyak
     * NULL diizinkan MySQL pada unique index — konsisten pola no_hp).
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nisn', 10)->nullable()->unique()->after('nik');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['nisn']);
            $table->dropColumn('nisn');
        });
    }
};
