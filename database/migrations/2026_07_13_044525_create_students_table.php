<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel students (data siswa).
     *
     * Siswa BUKAN akun login (rules.md §1.6). Bisa berasal dari pendaftaran
     * online (terkait parent) maupun input manual siswa lama. FK id_parent
     * & id_class nullable agar fleksibel; id_academic_year menandai tahun ajaran.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id('id_students');                  // PK: id_students

            // FK ke wali (nullable — siswa lama manual bisa tanpa akun wali)
            $table->unsignedBigInteger('id_parent')->nullable();
            $table->foreign('id_parent')
                  ->references('id_parents')->on('parents')
                  ->nullOnDelete();

            // FK ke kelas (nullable — siswa baru mungkin belum ditempatkan)
            $table->unsignedBigInteger('id_class')->nullable();
            $table->foreign('id_class')
                  ->references('id_classes')->on('classes')
                  ->nullOnDelete();

            // FK ke tahun ajaran
            $table->unsignedBigInteger('id_academic_year');
            $table->foreign('id_academic_year')
                  ->references('id_academic_years')->on('academic_years')
                  ->cascadeOnDelete();

            $table->string('nik', 16)->unique();        // NIK anak wajib, 16 digit (PRD §7.3)
            $table->string('nama_lengkap');
            $table->string('nama_panggilan')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P']);  // L = Laki-laki, P = Perempuan
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            // Status siswa dalam sistem
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel students (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
