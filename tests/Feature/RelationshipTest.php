<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OrangTua;
use App\Models\MonthlySppBill;
use App\Models\PaymentTransaction;
use App\Models\ReRegistrationPayment;
use App\Models\RegistrationDocument;
use App\Models\RegistrationForm;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentProgressNote;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji seluruh relasi Eloquent antar-model memakai konvensi PK/FK
 * non-standar (rules.md §2.3 poin 4). Memastikan FK/PK tersambung benar.
 */
class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat satu tahun ajaran aktif sebagai data dasar.
     */
    private function tahunAjaran(): AcademicYear
    {
        return AcademicYear::create(['tahun' => '2026/2027', 'is_aktif' => true]);
    }

    /**
     * Menguji relasi User 1-1 ke OrangTua (profil wali).
     */
    public function test_user_has_one_ortu(): void
    {
        $user = User::create([
            'username' => 'Wali Test', 'email' => 'wali@test.id',
            'role' => 'ortu', 'password' => Hash::make('rahasia'),
        ]);
        $ortu = OrangTua::create([
            'id_user' => $user->id_users, 'ibu_nama' => 'Wali Test', 'ibu_no_hp' => '0812',
        ]);

        $this->assertEquals($ortu->id_parents, $user->ortu->id_parents);
        $this->assertEquals($user->id_users, $ortu->user->id_users);
    }

    /**
     * Menguji relasi User 1-1 ke Teacher (profil guru).
     */
    public function test_user_has_one_teacher(): void
    {
        $user = User::create([
            'username' => 'Guru Test', 'email' => 'guru@test.id',
            'role' => 'guru', 'password' => Hash::make('rahasia'),
        ]);
        $teacher = Teacher::create([
            'id_user' => $user->id_users, 'nama' => 'Guru Test', 'nuptk' => '1000000000000001', 'no_hp' => '08901',
        ]);

        $this->assertEquals($teacher->id_teachers, $user->teacher->id_teachers);
        $this->assertEquals($user->id_users, $teacher->user->id_users);
    }

    /**
     * Menguji relasi many-to-many Teacher <-> SchoolClass via pivot class_teacher.
     */
    public function test_teacher_belongs_to_many_classes(): void
    {
        $ta = $this->tahunAjaran();
        $user = User::create([
            'username' => 'Guru', 'email' => 'g2@test.id',
            'role' => 'guru', 'password' => Hash::make('x'),
        ]);
        $teacher = Teacher::create(['id_user' => $user->id_users, 'nama' => 'Guru', 'nuptk' => '1000000000000002', 'no_hp' => '08902']);
        $kelasA = SchoolClass::create(['id_academic_year' => $ta->id_academic_years, 'nama_kelas' => 'A']);
        $kelasB = SchoolClass::create(['id_academic_year' => $ta->id_academic_years, 'nama_kelas' => 'B']);

        $teacher->classes()->attach([$kelasA->id_classes, $kelasB->id_classes]);

        $this->assertCount(2, $teacher->fresh()->classes);
        $this->assertCount(1, $kelasA->fresh()->teachers);
    }

    /**
     * Menguji relasi Student ke OrangTua, SchoolClass, dan AcademicYear.
     */
    public function test_student_relationships(): void
    {
        $ta = $this->tahunAjaran();
        $user = User::create([
            'username' => 'W', 'email' => 'w2@test.id',
            'role' => 'ortu', 'password' => Hash::make('x'),
        ]);
        $ortu = OrangTua::create(['id_user' => $user->id_users, 'ibu_nama' => 'W', 'ibu_no_hp' => '0812']);
        $kelas = SchoolClass::create(['id_academic_year' => $ta->id_academic_years, 'nama_kelas' => 'A']);
        $student = Student::create([
            'id_parent' => $ortu->id_parents,
            'id_class' => $kelas->id_classes,
            'id_academic_year' => $ta->id_academic_years,
            'nik' => '3204010101010001', 'nama_lengkap' => 'Budi',
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2021-01-01',
        ]);

        $this->assertEquals($ortu->id_parents, $student->ortu->id_parents);
        $this->assertEquals($kelas->id_classes, $student->schoolClass->id_classes);
        $this->assertEquals($ta->id_academic_years, $student->academicYear->id_academic_years);
        $this->assertEquals($student->id_students, $ortu->students->first()->id_students);
    }

    /**
     * Menguji relasi keuangan: RegistrationForm+Document, PaymentTransaction,
     * MonthlySppBill (helper sisa), ReRegistrationPayment, dan ProgressNote.
     */
    public function test_financial_and_registration_relationships(): void
    {
        $ta = $this->tahunAjaran();
        $user = User::create([
            'username' => 'W', 'email' => 'w3@test.id',
            'role' => 'ortu', 'password' => Hash::make('x'),
        ]);
        $ortu = OrangTua::create(['id_user' => $user->id_users, 'ibu_nama' => 'W', 'ibu_no_hp' => '0']);
        $student = Student::create([
            'id_parent' => $ortu->id_parents, 'id_academic_year' => $ta->id_academic_years,
            'nik' => '3204010101010002', 'nama_lengkap' => 'Sari',
            'jenis_kelamin' => 'P', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-02-02',
        ]);

        // Registration form + document
        $form = RegistrationForm::create([
            'id_user' => $user->id_users, 'id_academic_year' => $ta->id_academic_years,
        ]);
        $doc = RegistrationDocument::create([
            'id_registration_form' => $form->id_registration_forms, 'jenis' => 'kk', 'path' => 'x.pdf',
        ]);
        $this->assertEquals($doc->id_registration_documents, $form->documents->first()->id_registration_documents);
        $this->assertEquals($form->id_registration_forms, $doc->registrationForm->id_registration_forms);

        // Payment transaction
        $trx = PaymentTransaction::create([
            'id_student' => $student->id_students, 'id_user' => $user->id_users,
            'jenis' => 'spp', 'jumlah' => 100000, 'bukti_path' => 'b.jpg', 'tanggal_bayar' => '2026-07-01',
        ]);
        $this->assertEquals($student->id_students, $trx->student->id_students);

        // Monthly SPP bill + sisa() helper
        $bill = MonthlySppBill::create([
            'id_student' => $student->id_students, 'id_academic_year' => $ta->id_academic_years,
            'bulan' => '2026-08', 'nominal' => 250000, 'jumlah_terbayar' => 100000,
        ]);
        $this->assertEquals(150000.0, $bill->sisa());

        // Re-registration payment + sisa() helper
        $daftar = ReRegistrationPayment::create([
            'id_student' => $student->id_students, 'id_academic_year' => $ta->id_academic_years,
            'total_biaya' => 1500000, 'jumlah_terbayar' => 1300000,
        ]);
        $this->assertEquals(200000.0, $daftar->sisa());

        // Progress note
        $note = StudentProgressNote::create([
            'id_student' => $student->id_students, 'catatan' => 'Baik', 'tanggal' => '2026-07-10',
        ]);
        $this->assertEquals($note->id_student_progress_notes, $student->progressNotes->first()->id_student_progress_notes);
    }

    /**
     * L5.5: slug di-generate otomatis, nama kembar dapat suffix "-{PK}" unik.
     */
    public function test_slug_uniqueness_for_namesakes(): void
    {
        $ta = AcademicYear::create(['tahun' => '2027/2028', 'is_aktif' => true]);
        $a = Student::create(['nik' => '1111111111111111', 'nama_lengkap' => 'Budi', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'id_academic_year' => $ta->id_academic_years]);
        $b = Student::create(['nik' => '2222222222222222', 'nama_lengkap' => 'Budi', 'jenis_kelamin' => 'P', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2021-01-01', 'id_academic_year' => $ta->id_academic_years]);

        $this->assertEquals('budi', $a->fresh()->slug);
        $this->assertEquals('budi-'.$b->id_students, $b->fresh()->slug);
        $this->assertNotEquals($a->fresh()->slug, $b->fresh()->slug, 'slug name-sake collision');
    }
}
