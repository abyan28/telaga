<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel student_progress_notes (catatan perkembangan siswa).
     *
     * Diisi oleh guru/admin untuk memantau kemajuan siswa (PRD §7.9). id_teacher
     * mencatat guru penulis (nullable agar admin juga bisa menulis tanpa profil guru).
     */
    public function up(): void
    {
        Schema::create('student_progress_notes', function (Blueprint $table) {
            $table->id('id_student_progress_notes');    // PK: id_student_progress_notes

            // FK ke siswa yang dicatat
            $table->unsignedBigInteger('id_student');
            $table->foreign('id_student')
                  ->references('id_students')->on('students')
                  ->cascadeOnDelete();

            // FK ke guru penulis (nullable — admin bisa menulis tanpa profil guru)
            $table->unsignedBigInteger('id_teacher')->nullable();
            $table->foreign('id_teacher')
                  ->references('id_teachers')->on('teachers')
                  ->nullOnDelete();

            $table->text('catatan');                     // Isi catatan perkembangan
            $table->date('tanggal');                     // Tanggal catatan
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel student_progress_notes (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress_notes');
    }
};
