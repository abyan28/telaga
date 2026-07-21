<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L1.1: rework parents — hapus 5 kolom lama, tambah 18 kolom ayah/ibu + 2 flag + 9 alamat (pindahan students).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            // drop unique index no_hp dulu (SQLite tolak drop kolom ber-index; MySQL longgar)
            $table->dropUnique('parents_no_hp_unique');
            // hapus kolom lama
            $table->dropColumn(['nama', 'no_hp', 'pekerjaan', 'tempat_lahir', 'tanggal_lahir']);

            // flag ada
            $table->boolean('ada_ayah')->default(true)->after('id_user');
            $table->boolean('ada_ibu')->default(true)->after('ada_ayah');

            // kolom ayah + ibu (prefix)
            foreach (['ayah_', 'ibu_'] as $p) {
                $table->string($p.'nama', 100)->nullable();
                $table->date($p.'tanggal_lahir')->nullable();
                $table->string($p.'tempat_lahir', 100)->nullable();
                $table->string($p.'agama', 50)->nullable();
                $table->string($p.'pendidikan', 20)->nullable();
                $table->string($p.'pekerjaan', 50)->nullable();
                $table->string($p.'pekerjaan_lain', 100)->nullable();
                $table->string($p.'penghasilan', 20)->nullable();
                $table->string($p.'no_hp', 20)->nullable();
            }

            // alamat — 1 set (keluarga), pindahan dari students
            $table->text('alamat')->nullable();
            $table->string('provinsi_id', 10)->nullable();
            $table->string('provinsi_nama', 100)->nullable();
            $table->string('kota_id', 10)->nullable();
            $table->string('kota_nama', 100)->nullable();
            $table->string('kecamatan_id', 10)->nullable();
            $table->string('kecamatan_nama', 100)->nullable();
            $table->string('kelurahan_id', 10)->nullable();
            $table->string('kelurahan_nama', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->string('nama', 100)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();

            $prefixes = ['ayah_', 'ibu_'];
            $cols = ['nama', 'tanggal_lahir', 'tempat_lahir', 'agama', 'pendidikan', 'pekerjaan', 'pekerjaan_lain', 'penghasilan', 'no_hp'];
            foreach ($prefixes as $p) {
                foreach ($cols as $c) {
                    $table->dropColumn($p.$c);
                }
            }
            $table->dropColumn(['ada_ayah', 'ada_ibu', 'alamat',
                'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
                'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama']);
        });
    }
};
