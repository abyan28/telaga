<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menambah TTL & riwayat pendidikan guru (T5.4).
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('tempat_lahir')->nullable()->after('alamat');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->text('riwayat_pendidikan')->nullable()->after('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'tanggal_lahir', 'riwayat_pendidikan']);
        });
    }
};
