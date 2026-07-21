<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel settings (pengaturan dinamis sistem).
     *
     * Menyimpan pasangan key-value, dipakai Admin untuk mengatur nominal biaya
     * secara dinamis (pendaftaran, SPP, daftar ulang) dan tanggal generate SPP
     * (rules.md §1.3, PRD §7.6/§7.8). Contoh key: nominal_pendaftaran, nominal_spp.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id('id_settings');                  // PK: id_settings
            $table->string('key')->unique();            // Nama pengaturan (unik)
            $table->text('value')->nullable();          // Nilai pengaturan
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel settings (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
