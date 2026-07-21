<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah kolom untuk tampilan guru di halaman publik (Profil):
     * jabatan, foto, dan flag tampil_di_web (rules.md §1.8).
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('jabatan')->nullable()->after('nuptk');
            $table->string('foto_path')->nullable()->after('jabatan');
            $table->boolean('tampil_di_web')->default(false)->after('foto_path');
        });
    }

    /**
     * Mengembalikan perubahan kolom teachers.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['jabatan', 'foto_path', 'tampil_di_web']);
        });
    }
};
