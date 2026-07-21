<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel audit_logs (jejak aktivitas penting sistem).
     *
     * Mencatat login, verifikasi pembayaran, perubahan status, dan perubahan data
     * beserta snapshot sebelum/sesudah bila memungkinkan (PRD §7.14). id_user
     * nullable agar aksi sistem/anonim tetap tercatat.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('id_audit_logs');                 // PK: id_audit_logs

            // FK ke user pelaku (nullable untuk aksi sistem)
            $table->unsignedBigInteger('id_user')->nullable();
            $table->foreign('id_user')
                  ->references('id_users')->on('users')
                  ->nullOnDelete();

            $table->string('aksi');                      // Deskripsi aksi, mis. "verifikasi_pembayaran"
            $table->string('model_terkait')->nullable(); // Nama model/entitas terkait
            $table->json('data_sebelum')->nullable();    // Snapshot data sebelum perubahan
            $table->json('data_sesudah')->nullable();    // Snapshot data sesudah perubahan
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel audit_logs (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
