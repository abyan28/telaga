<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * K2.5: flag escape-hatch. Admin set true agar wali boleh edit pendaftaran
     * lagi walau pembayaran sudah diverifikasi (proses berkas/seleksi).
     */
    public function up(): void
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            $table->boolean('boleh_edit')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            $table->dropColumn('boleh_edit');
        });
    }
};
