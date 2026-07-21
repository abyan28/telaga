<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel monthly_spp_bills (tagihan SPP bulanan per siswa).
     *
     * Digenerate otomatis tiap awal bulan (rules.md §1.3.5). Tiap bulan berdiri
     * sendiri; kekurangan tetap tercatat di bulannya (status kurang) dan TIDAK
     * digeser ke bulan berikutnya (rules.md §1.3.3). jumlah_terbayar disinkron
     * dari transaksi terverifikasi; sisa = nominal - jumlah_terbayar. Kombinasi
     * (id_student, bulan) unik agar idempotent.
     */
    public function up(): void
    {
        Schema::create('monthly_spp_bills', function (Blueprint $table) {
            $table->id('id_monthly_spp_bills');         // PK: id_monthly_spp_bills

            // FK ke siswa
            $table->unsignedBigInteger('id_student');
            $table->foreign('id_student')
                  ->references('id_students')->on('students')
                  ->cascadeOnDelete();

            // FK ke tahun ajaran
            $table->unsignedBigInteger('id_academic_year');
            $table->foreign('id_academic_year')
                  ->references('id_academic_years')->on('academic_years')
                  ->cascadeOnDelete();

            $table->string('bulan', 7);                  // Format "2026-08" (tahun-bulan)
            $table->decimal('nominal', 12, 2);           // Nominal tagihan bulan itu
            $table->decimal('jumlah_terbayar', 12, 2)->default(0); // Total terverifikasi
            $table->enum('status', ['belum_lunas', 'kurang', 'lunas'])->default('belum_lunas');

            // Cegah tagihan dobel untuk siswa & bulan yang sama (idempotent)
            $table->unique(['id_student', 'bulan']);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel monthly_spp_bills (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_spp_bills');
    }
};
