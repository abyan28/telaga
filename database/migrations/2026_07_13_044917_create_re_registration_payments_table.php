<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel re_registration_payments (tagihan daftar ulang per siswa).
     *
     * Dibuka setelah calon siswa dinyatakan lulus (PRD §7.7). Dapat dicicil;
     * jumlah_terbayar disinkron dari transaksi terverifikasi (jenis daftar_ulang),
     * sisa = total_biaya - jumlah_terbayar. Nominal decimal(12,2) untuk presisi.
     */
    public function up(): void
    {
        Schema::create('re_registration_payments', function (Blueprint $table) {
            $table->id('id_re_registration_payments');  // PK: id_re_registration_payments

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

            $table->decimal('total_biaya', 12, 2);       // Total biaya daftar ulang
            $table->decimal('jumlah_terbayar', 12, 2)->default(0); // Total terverifikasi
            $table->enum('status', ['belum_lunas', 'kurang', 'lunas'])->default('belum_lunas');
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel re_registration_payments (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('re_registration_payments');
    }
};
