<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel site_contents — konten teks/angka tunggal halaman publik
     * (key-value), dikelola Admin via CMS (rules.md §1.8).
     */
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id('id_site_contents');            // PK non-standar (rules.md §2)
            $table->string('key')->unique();           // mis. 'home.hero_title'
            $table->text('value')->nullable();         // isi konten
            $table->string('grup')->default('umum');   // home/profile/info/kontak
            $table->string('label')->nullable();       // deskripsi utk operator
            $table->string('tipe')->default('text');   // text/textarea/number/image
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel site_contents.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
