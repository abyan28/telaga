<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel pivot class_teacher (penugasan guru ke kelas).
     *
     * Mendukung relasi many-to-many: satu guru dapat mengampu banyak kelas,
     * dan satu kelas dapat memiliki lebih dari satu guru (PRD §7.10).
     * Kombinasi (id_class, id_teacher) dibuat unik agar tidak dobel.
     */
    public function up(): void
    {
        Schema::create('class_teacher', function (Blueprint $table) {
            $table->id('id_class_teacher');             // PK: id_class_teacher

            // FK ke kelas
            $table->unsignedBigInteger('id_class');
            $table->foreign('id_class')
                  ->references('id_classes')->on('classes')
                  ->cascadeOnDelete();

            // FK ke guru
            $table->unsignedBigInteger('id_teacher');
            $table->foreign('id_teacher')
                  ->references('id_teachers')->on('teachers')
                  ->cascadeOnDelete();

            // Cegah penugasan dobel guru yang sama ke kelas yang sama
            $table->unique(['id_class', 'id_teacher']);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel pivot class_teacher (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('class_teacher');
    }
};
