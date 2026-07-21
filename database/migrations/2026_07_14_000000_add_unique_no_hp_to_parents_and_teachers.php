<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan unique index pada kolom no_hp di tabel parents dan teachers.
     *
     * Kebijakan sekolah: satu nomor HP hanya boleh dimiliki satu orang (tidak
     * boleh dipakai dua wali atau dua guru berbeda). Constraint ditambahkan via
     * migration terpisah agar migration awal tidak diubah (data eksisting aman).
     *
     * CATATAN: teachers.no_hp nullable — MySQL mengizinkan banyak baris NULL
     * pada unique index, jadi guru tanpa no_hp tetap boleh lebih dari satu.
     */
    public function up(): void
    {
        // parents.no_hp semula NOT NULL. Jadikan nullable agar wali yang belum
        // mengisi HP tersimpan sebagai NULL (bukan '') — MySQL mengizinkan banyak
        // NULL pada unique index, sehingga tidak bentrok satu sama lain.
        Schema::table('parents', function (Blueprint $table) {
            $table->string('no_hp')->nullable()->change();
        });

        Schema::table('parents', function (Blueprint $table) {
            $table->unique('no_hp', 'parents_no_hp_unique');
        });

        // CATATAN (T3.2): teachers.no_hp kini NOT NULL + unique langsung di
        // migration create-table (dipakai untuk login), jadi tidak lagi di-ALTER di sini.
    }

    /**
     * Menghapus unique index no_hp (rollback migration).
     */
    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropUnique('parents_no_hp_unique');
        });

        // Kembalikan parents.no_hp ke NOT NULL (kondisi migration awal).
        Schema::table('parents', function (Blueprint $table) {
            $table->string('no_hp')->nullable(false)->change();
        });
    }
};
