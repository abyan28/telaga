<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L5.5: slug untuk URL yg dulu pakai PK numerik (students/teachers/registration_forms).
 * Nullable+unique; backfill diisi oleh model saat save (trait HasSlug). Kolom PK/FK
 * non-standar tetap; slug hanya kunci binding URL (getRouteKeyName override).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', fn (Blueprint $t) => $t->string('slug')->nullable()->unique()->after('id_students'));
        Schema::table('teachers', fn (Blueprint $t) => $t->string('slug')->nullable()->unique()->after('id_teachers'));
        Schema::table('registration_forms', fn (Blueprint $t) => $t->string('slug')->nullable()->unique()->after('id_registration_forms'));

        // Backfill baris lama: re-save agar hook HasSlug mengisi slug.
        \App\Models\Student::query()->each(fn ($m) => $m->save());
        \App\Models\Teacher::query()->each(fn ($m) => $m->save());
        \App\Models\RegistrationForm::query()->each(fn ($m) => $m->save());
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn('slug'));
        Schema::table('teachers', fn (Blueprint $t) => $t->dropColumn('slug'));
        Schema::table('registration_forms', fn (Blueprint $t) => $t->dropColumn('slug'));
    }
};
