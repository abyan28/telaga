<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE students SET status = 'alumni' WHERE status = 'lulus' AND nis IS NOT NULL");
        DB::statement("UPDATE students SET status = 'calon' WHERE status = 'lulus'");

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('calon','aktif','alumni','nonaktif') NOT NULL DEFAULT 'calon'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('aktif','lulus','nonaktif') NOT NULL DEFAULT 'aktif'");
        }

        DB::statement("UPDATE students SET status = 'lulus' WHERE status = 'alumni'");
        DB::statement("UPDATE students SET status = 'lulus' WHERE status = 'calon'");
    }
};
