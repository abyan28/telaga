<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pindahkan alamat + wilayah berjenjang dari parents ke students (T2.1 revisi).
 *
 * Alasan: siswa lama diinput admin/guru TANPA akun wali (students.id_parent
 * nullable), sehingga alamat tak bisa disandarkan ke parents. Alamat adalah
 * atribut siswa (bisa berbeda antar kakak-adik), jadi dipindah ke students.
 * Data lama tidak disalin (isi DB masih demo; migrate:fresh reseed).
 */
return new class extends Migration
{
    /** Daftar kolom wilayah yang dipindah (id kode BPS + nama snapshot). */
    private array $wilayah = [
        'provinsi_id', 'provinsi_nama', 'kota_id', 'kota_nama',
        'kecamatan_id', 'kecamatan_nama', 'kelurahan_id', 'kelurahan_nama',
    ];

    public function up(): void
    {
        // students: tambah alamat + 8 kolom wilayah (semua nullable — siswa lama parsial).
        Schema::table('students', function (Blueprint $table) {
            $table->string('alamat', 500)->nullable()->after('tanggal_lahir');
            $table->string('provinsi_id', 10)->nullable()->after('alamat');
            $table->string('provinsi_nama')->nullable()->after('provinsi_id');
            $table->string('kota_id', 10)->nullable()->after('provinsi_nama');
            $table->string('kota_nama')->nullable()->after('kota_id');
            $table->string('kecamatan_id', 10)->nullable()->after('kota_nama');
            $table->string('kecamatan_nama')->nullable()->after('kecamatan_id');
            $table->string('kelurahan_id', 10)->nullable()->after('kecamatan_nama');
            $table->string('kelurahan_nama')->nullable()->after('kelurahan_id');
        });

        // parents: buang alamat + 8 kolom wilayah (kini milik students).
        Schema::table('parents', function (Blueprint $table) {
            $table->dropColumn(array_merge(['alamat'], $this->wilayah));
        });
    }

    public function down(): void
    {
        // Kembalikan alamat + wilayah ke parents.
        Schema::table('parents', function (Blueprint $table) {
            $table->text('alamat')->after('no_hp');
            $table->string('provinsi_id', 10)->nullable()->after('alamat');
            $table->string('provinsi_nama')->nullable()->after('provinsi_id');
            $table->string('kota_id', 10)->nullable()->after('provinsi_nama');
            $table->string('kota_nama')->nullable()->after('kota_id');
            $table->string('kecamatan_id', 10)->nullable()->after('kota_nama');
            $table->string('kecamatan_nama')->nullable()->after('kecamatan_id');
            $table->string('kelurahan_id', 10)->nullable()->after('kecamatan_nama');
            $table->string('kelurahan_nama')->nullable()->after('kelurahan_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(array_merge(['alamat'], $this->wilayah));
        });
    }
};
