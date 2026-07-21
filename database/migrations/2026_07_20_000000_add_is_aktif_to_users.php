<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L4.1: kolom is_aktif di users — satu titik penonaktifan untuk semua role.
     * Backfill dari teachers.is_aktif agar guru yang sudah dinonaktifkan tetap sinkron.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_aktif')->default(true)->after('must_change_password');
        });

        // Backfill: status guru dari teachers → users (MySQL). Skip di sqlite (test seed sendiri).
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE users u JOIN teachers t ON u.id_users = t.id_user SET u.is_aktif = t.is_aktif WHERE u.role = 'guru'");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_aktif');
        });
    }
};
