<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadikan parents.nama nullable. Sejak signup wali hanya minta username,
 * nama lengkap wali diisi belakangan lewat CMS wali (bukan saat daftar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->string('nama')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->string('nama')->nullable(false)->change();
        });
    }
};
