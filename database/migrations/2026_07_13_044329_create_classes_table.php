<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel classes (data kelas).
     *
     * Setiap kelas terikat pada satu tahun ajaran (FK id_academic_year).
     * Konvensi PK/FK sesuai rules.md §2. Model terkait: SchoolClass
     * (nama 'Class' dihindari karena keyword PHP).
     */
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id('id_classes');                   // PK: id_classes

            // FK ke tahun ajaran. references id_academic_years di tabel academic_years.
            $table->unsignedBigInteger('id_academic_year');
            $table->foreign('id_academic_year')
                  ->references('id_academic_years')->on('academic_years')
                  ->cascadeOnDelete();

            $table->string('nama_kelas');               // Nama kelas, mis. "Kelas A"
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel classes (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
