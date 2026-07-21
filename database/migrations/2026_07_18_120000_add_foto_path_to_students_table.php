<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom foto_path ke students (pas foto siswa untuk tampilan profil).
 * Nullable: siswa lama boleh belum punya foto (rules.md §3). Pola sama teachers.foto_path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('nama_panggilan');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });
    }
};
