<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L1.2: tambah 13 kolom profil murid, hapus 9 kolom alamat (pindah ke parents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // hapus alamat (pindah ke parents)
            $table->dropColumn(['alamat',
                'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
                'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama']);

            // profil baru
            $table->string('agama', 50)->default('ISLAM')->after('jenis_kelamin');
            $table->unsignedTinyInteger('anak_ke')->nullable()->after('agama');
            $table->unsignedTinyInteger('jumlah_saudara')->nullable()->after('anak_ke');
            $table->string('warga_negara', 50)->nullable()->after('jumlah_saudara');
            $table->string('bahasa_keseharian', 100)->nullable()->after('warga_negara');
            $table->string('kondisi_kesehatan', 255)->nullable()->after('bahasa_keseharian');
            $table->enum('sudah_mengaji', ['Sudah', 'Belum'])->nullable()->after('kondisi_kesehatan');
            $table->string('ngaji_dimana', 255)->nullable()->after('sudah_mengaji');
            $table->string('ngaji_metode', 100)->nullable()->after('ngaji_dimana');
            $table->string('ngaji_jilid', 50)->nullable()->after('ngaji_metode');
            $table->enum('pernah_belajar', ['PAUD', 'Les', 'Belum'])->nullable()->after('ngaji_jilid');
            $table->string('belajar_keterangan', 255)->nullable()->after('pernah_belajar');
            $table->enum('ukuran_baju', ['S', 'M', 'L', 'XL', 'XXL', 'Jumbo'])->nullable()->after('belajar_keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['agama', 'anak_ke', 'jumlah_saudara', 'warga_negara',
                'bahasa_keseharian', 'kondisi_kesehatan', 'sudah_mengaji',
                'ngaji_dimana', 'ngaji_metode', 'ngaji_jilid',
                'pernah_belajar', 'belajar_keterangan', 'ukuran_baju']);
            // restore alamat (null)
            $table->text('alamat')->nullable();
            foreach (['provinsi', 'kota', 'kecamatan', 'kelurahan'] as $l) {
                $table->string($l.'_id', 10)->nullable();
                $table->string($l.'_nama', 100)->nullable();
            }
        });
    }
};
