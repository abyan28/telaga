<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah TTL wali ke parents (K2.1: profil wajib lengkap sebelum daftar/bayar).
     */
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $t) {
            $t->string('tempat_lahir')->nullable()->after('pekerjaan');
            $t->date('tanggal_lahir')->nullable()->after('tempat_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('parents', fn (Blueprint $t) => $t->dropColumn(['tempat_lahir', 'tanggal_lahir']));
    }
};
