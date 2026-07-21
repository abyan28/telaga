<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel site_features — item berulang & dinamis halaman publik
     * (kartu program, poin misi, persyaratan, langkah alur, statistik).
     * Dibedakan kolom 'grup'; dapat ditambah/hapus/urutkan operator (rules.md §1.8).
     */
    public function up(): void
    {
        Schema::create('site_features', function (Blueprint $table) {
            $table->id('id_site_features');            // PK non-standar (rules.md §2)
            $table->string('grup');                    // program/misi/persyaratan/alur/statistik
            $table->string('judul');                   // judul item / label statistik / nilai
            $table->text('deskripsi')->nullable();     // isi item (opsional utk statistik)
            $table->string('ikon')->nullable();        // nama/ikon opsional
            $table->string('foto_path')->nullable();   // gambar opsional
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['grup', 'urutan']);
        });
    }

    /**
     * Menghapus tabel site_features.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_features');
    }
};
