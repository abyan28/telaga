<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel registration_documents (dokumen unggahan pendaftaran).
     *
     * Menyimpan berkas KK, Akta Kelahiran (wajib), dan Foto (opsional) milik
     * satu formulir pendaftaran. Path & aturan penamaan file mengikuti rules.md §3.
     * Admin dapat menerima atau menolak tiap dokumen (PRD §7.4).
     */
    public function up(): void
    {
        Schema::create('registration_documents', function (Blueprint $table) {
            $table->id('id_registration_documents');    // PK: id_registration_documents

            // FK ke formulir pendaftaran induk
            $table->unsignedBigInteger('id_registration_form');
            $table->foreign('id_registration_form')
                  ->references('id_registration_forms')->on('registration_forms')
                  ->cascadeOnDelete();

            $table->enum('jenis', ['kk', 'akta', 'foto']);       // Jenis dokumen
            $table->string('path');                               // Lokasi file (storage)
            $table->enum('status', ['pending', 'diterima', 'ditolak'])->default('pending');
            $table->text('catatan')->nullable();                  // Catatan admin bila ditolak
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel registration_documents (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_documents');
    }
};
