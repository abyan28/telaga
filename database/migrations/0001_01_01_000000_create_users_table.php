<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel users, password_reset_tokens, dan sessions.
     *
     * users: satu tabel untuk semua role (rules.md §1.5). Dibedakan kolom `role`.
     * Konvensi PK sesuai rules.md §2.1: primary key = id_users.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_users');                         // PK: id_users (rules.md §2.1)
            // Nama tampilan fallback (T3.2): nama asli pindah ke profil
            // (parents.nama / teachers.nama). username diisi email/HP saat auto-akun.
            $table->string('username');
            // Identifier login (rules.md §1.5): email ATAU no_hp. Keduanya nullable
            // agar guru bisa dibuat admin hanya dg no_hp (email diisi guru belakangan).
            $table->string('email')->nullable()->unique();
            $table->string('no_hp')->nullable()->unique();  // login alternatif via HP
            // Role penentu hak akses (RBAC via middleware) — rules.md §1.5
            $table->enum('role', ['ortu', 'admin', 'guru'])->default('ortu');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // CATATAN: kolom WAJIB bernama 'user_id' karena driver session bawaan
            // Laravel (DatabaseSessionHandler) meng-hardcode nama kolom ini.
            // Ini pengecualian sah terhadap konvensi FK rules.md demi kompatibilitas.
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Menghapus tabel users, password_reset_tokens, dan sessions (rollback).
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
