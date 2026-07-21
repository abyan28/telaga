<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rapikan lebar kolom no_hp: varchar(255) default → varchar(20) (K1.1).
     * no_hp dijamin 9–14 digit angka via validasi; 255 boros. change() hanya
     * mengubah tipe/lebar & flag nullable — unique index existing tak tersentuh.
     */
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('no_hp', 20)->nullable()->change());
        Schema::table('parents', fn (Blueprint $t) => $t->string('no_hp', 20)->nullable()->change());
        Schema::table('teachers', fn (Blueprint $t) => $t->string('no_hp', 20)->change()); // NOT NULL (login guru)
    }

    /**
     * Kembalikan ke varchar default (rollback).
     */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('no_hp')->nullable()->change());
        Schema::table('parents', fn (Blueprint $t) => $t->string('no_hp')->nullable()->change());
        Schema::table('teachers', fn (Blueprint $t) => $t->string('no_hp')->change());
    }
};
