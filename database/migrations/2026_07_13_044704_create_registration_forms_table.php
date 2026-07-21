<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel registration_forms (formulir pendaftaran murid baru).
     *
     * Dibuat oleh wali (id_user). Saat calon siswa dinyatakan lulus, id_student
     * diisi (menaut ke data siswa aktif). Kolom status mengikuti daftar status
     * pendaftaran & seleksi pada PRD §7.5.
     */
    public function up(): void
    {
        Schema::create('registration_forms', function (Blueprint $table) {
            $table->id('id_registration_forms');        // PK: id_registration_forms

            // FK ke user wali pendaftar
            $table->unsignedBigInteger('id_user');
            $table->foreign('id_user')
                  ->references('id_users')->on('users')
                  ->cascadeOnDelete();

            // FK ke siswa (nullable — terisi setelah lulus & data siswa dibuat)
            $table->unsignedBigInteger('id_student')->nullable();
            $table->foreign('id_student')
                  ->references('id_students')->on('students')
                  ->nullOnDelete();

            // FK ke tahun ajaran pendaftaran
            $table->unsignedBigInteger('id_academic_year');
            $table->foreign('id_academic_year')
                  ->references('id_academic_years')->on('academic_years')
                  ->cascadeOnDelete();

            // Status pendaftaran & seleksi (PRD §7.5)
            $table->enum('status', [
                'submitted',                // baru submit, belum bayar
                'menunggu_bukti',           // bukti bayar DITOLAK admin -> wali upload ulang
                'menunggu_verifikasi',      // bukti bayar diunggah, tunggu admin
                'pembayaran_diverifikasi',  // bayar sah -> proses verifikasi berkas
                'diproses_seleksi',         // berkas lengkap -> proses seleksi
                'lulus',
                'gagal',
                'dibatalkan',               // L2.1: batal setelah lulus (pembatalan DU)
            ])->default('submitted');

            $table->text('catatan_admin')->nullable();  // Catatan admin (mis. alasan tolak/perbaikan)
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel registration_forms (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_forms');
    }
};
