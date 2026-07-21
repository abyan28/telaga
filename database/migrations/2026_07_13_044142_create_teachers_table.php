<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel teachers (profil guru).
     *
     * Relasi 1-1 ke users (rules.md §1.6). Kolom nomor_induk_guru bersifat unik
     * dan juga dipakai sebagai password awal guru (rules.md §1.5).
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id('id_teachers');                  // PK: id_teachers

            // FK 1-1 ke users (unique). references id_users, cascade saat user dihapus.
            $table->unsignedBigInteger('id_user')->unique();
            $table->foreign('id_user')->references('id_users')->on('users')->cascadeOnDelete();

            $table->string('nama');                        // Nama lengkap guru (T3.2: pindah dari users.name)
            $table->string('nuptk')->unique();             // NUPTK (juga password awal — rules.md §1.5)
            $table->string('no_hp')->unique();             // Nomor HP guru (wajib — dipakai login)
            $table->text('alamat')->nullable();            // Alamat guru (opsional)
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel teachers (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
