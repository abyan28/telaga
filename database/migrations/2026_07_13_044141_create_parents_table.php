<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel parents (profil wali murid).
     *
     * Relasi 1-1 ke users (rules.md §1.6). FK id_user mengikuti konvensi
     * rules.md §2.2 dan menunjuk PK id_users. Unik agar satu user wali
     * hanya punya satu profil parent.
     */
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id('id_parents');                 // PK: id_parents

            // FK 1-1 ke users (unique). references id_users, cascade saat user dihapus.
            $table->unsignedBigInteger('id_user')->unique();
            $table->foreign('id_user')->references('id_users')->on('users')->cascadeOnDelete();

            $table->string('no_hp');                    // Nomor HP / WhatsApp wali
            $table->string('nama');                     // Nama lengkap wali (T3.2: pindah dari users.name)
            $table->text('alamat');                     // Alamat lengkap
            $table->string('pekerjaan')->nullable();    // Pekerjaan (opsional)
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel parents (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
