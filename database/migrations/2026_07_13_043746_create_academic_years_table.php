<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel academic_years (tahun ajaran).
     *
     * Konvensi PK sesuai rules.md §2.1: primary key = id_academic_years.
     * Hanya boleh ada satu baris dengan is_aktif = true pada satu waktu
     * (aturan bisnis rules.md §1.7 — ditegakkan di level aplikasi).
     */
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id('id_academic_years');            // PK: id_academic_years
            $table->string('tahun')->unique();          // Format "2026/2027" (rules.md §1.7)
            $table->boolean('is_aktif')->default(false); // Penanda tahun ajaran aktif
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel academic_years (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
