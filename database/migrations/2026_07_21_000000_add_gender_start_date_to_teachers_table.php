<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L1.3: field guru — jenis kelamin + tanggal mulai mengajar (keduanya nullable,
 * data guru lama boleh kosong).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('nama');
            $table->date('tanggal_mulai_mengajar')->nullable()->after('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['jenis_kelamin', 'tanggal_mulai_mengajar']);
        });
    }
};
