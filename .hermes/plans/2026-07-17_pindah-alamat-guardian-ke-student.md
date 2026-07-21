# Pindah Alamat (guardian → student) + Dropdown Wilayah Siswa — Implementation Plan

> **For Hermes:** Eksekusi task-by-task. Setiap task: ubah kode → `php artisan test` → `npm run build`. Konvensi proyek: PK/FK non-standar (rules.md §2), komentari tiap fungsi (§5), update `.agents/tasklist.md`+`notes.md` di akhir.

**Goal:** Memindahkan kolom `alamat` + 8 kolom wilayah (provinsi/kota/kecamatan/kelurahan × id+nama) dari tabel `guardians` ke tabel `students`, lalu memasang searchable dropdown wilayah + alamat pada SEMUA form profil siswa (CMS wali saat daftar, CMS admin create/update siswa, CMS guru create/update siswa). Alamat menjadi atribut siswa, bukan wali.

**Driver sebenarnya (dari user):** Siswa lama diinput manual oleh admin/guru **tanpa akun wali** (`students.id_guardian` NULLABLE — dikonfirmasi di migration create students, komentar "siswa lama manual bisa tanpa akun wali"). Kalau alamat ada di `guardians`, siswa lama tak punya tempat menyimpan alamat. Maka alamat WAJIB pindah ke `students` agar admin/guru bisa input alamat siswa lama langsung, dan wali mengisi alamat anaknya sendiri saat daftar online. Inti: admin/guru cukup mengisi kolom-kolom yang ada di tabel `students` (termasuk alamat), tak perlu menyentuh tabel wali.

**Architecture:** Reuse total endpoint `wilayah` + pola picker Alpine yang sudah ada (dari form guru admin/mandiri). Tidak ada dependency baru, tidak ada endpoint baru. Guardian kehilangan alamat/wilayah; student mendapatkannya. Alamat wali di form daftar (Step 1) pindah ke data anak (Step 2) — jadi per-anak.

**Tech Stack:** Laravel 13, MySQL (`telaga`), Alpine.js, TailwindCSS v4, endpoint `wilayah` (WilayahController, tabel `t_*`).

---

## Keputusan (SUDAH terjawab user — tak perlu tanya lagi)

**Q1 — Admin/guru input alamat siswa lama:** YA. Justru inilah driver-nya. Form CRUD siswa admin & guru dapat alamat+dropdown. ✅

**Q2 — `guardians.alamat` dihapus total:** YA. Alamat pindah ke siswa. Guardian tak lagi simpan alamat/wilayah. `admin/registration-detail` dialihkan baca `$student->alamat`. Signup wali berhenti menulis alamat. ✅

**Q3 — Siapa yang mengisi alamat:**
- **Wali (daftar online):** mengisi alamat ANAKNYA di form pendaftaran (pindah dari Step 1 Data Wali → Step 2 Data Anak). Per-anak.
- **Admin/Guru (siswa lama):** mengisi alamat langsung di form CRUD siswa. Tak butuh akun wali (id_guardian NULL).
- Alamat = kolom di tabel `students`. Admin/guru cukup isi kolom tabel siswa. ✅

**Data produksi:** Diasumsikan semua masih DEMO (boleh `migrate:fresh`, tak perlu copy data guardian→student). Jika ternyata sudah ada data alamat wali riil → tambah langkah copy di migration up() sebelum drop (lihat Risks). **Konfirmasi terakhir sebelum eksekusi: DB sudah ada data riil atau masih demo?**

---

## Current context / assumptions

**Data alamat/wilayah kini di `guardians`:**
- Migration: `2026_07_13_044141_create_guardians_table.php` (`alamat` text), `2026_07_17_000001_add_wilayah_to_guardians.php` (8 wilayah).
- Model `Guardian` fillable: `alamat` + 8 wilayah.
- Ditulis: `RegisterController@register` (alamat), `Wali\RegistrationController@store` (alamat + 8 wilayah via collect->only).
- Dibaca: `wali/register.blade.php` (prefill `guardian?->alamat` + `old(provinsi_id)`…), `admin/registration-detail.blade.php:182`.
- Seeder: `DemoSeeder:101`, `DatabaseSeeder:76` (alamat).
- Test: `RelationshipTest`, `WaliRegistrationTest`, `WaliViewsTest`, `MultiChildTest` (semua `Guardian::create([... 'alamat' => ...])`).

**Endpoint wilayah sudah ada & DB-agnostik:** `Route::get('/wilayah/{level}')` name `wilayah`, `substr(id,1,n)` filter. Reuse langsung.

**Students belum punya alamat sama sekali** (fillable tanpa alamat).

**Validasi wilayah reusable (8 rule nullable) sudah ada 3x** di StoreRegistrationRequest, GuruController@updateProfile, MasterDataController (store/updateTeacher). Untuk siswa akan muncul lagi → pertimbangkan DRY (lihat Task 2).

---

## Proposed approach (ladder)

- **Rung 2 (reuse):** picker Alpine + endpoint `wilayah` + pola `collect->only` sudah ada. Copy pola, jangan cipta baru.
- **DRY:** 8 rule wilayah + daftar 8 key kini dipakai di banyak controller. Ekstrak ke satu tempat (helper statis di `Student` atau trait kecil) — dipakai wali-register, admin-student, guru-student. Guardian/teacher yang sudah ada BIARKAN (jangan refactor drive-by; hanya siswa yang baru).
- **Migration:** satu migration `move` — `students` +alamat+8, `guardians` −alamat−8. Data lama guardian TIDAK dimigrasi ke student (data hanya demo; `migrate:fresh` reseed). Kalau perlu preserve → tambah langkah copy (lihat Risks).

---

## Step-by-step plan

### Task 1: Migration pindah kolom

**Files:**
- Create: `database/migrations/2026_07_17_000003_move_alamat_wilayah_to_students.php`

**Isi:** up() → `Schema::table('students')` tambah `alamat` (string 500 nullable) + 8 wilayah (id string 10 / nama string, nullable, `after`). Lalu `Schema::table('guardians')` dropColumn `alamat` + 8 wilayah. down() kebalikannya.

Catatan: `guardians.alamat` di migration create adalah `text` NOT NULL — drop aman. Untuk `students.alamat` pakai `string(500)` nullable (konsisten pola guru/wali).

**Verify:** `php artisan migrate` DONE; `php artisan tinker --execute="echo Schema::hasColumn('students','provinsi_id')?'OK':'NO'; echo Schema::hasColumn('guardians','alamat')?'STILL':'GONE';"` → `OK` + `GONE`.

### Task 2: Model + helper DRY

**Files:**
- Modify: `app/Models/Student.php` — fillable +`alamat`+8 wilayah. Tambah 2 static helper (dipakai semua form siswa):
  ```php
  /** Aturan validasi alamat+wilayah siswa (nullable) — dipakai form wali/admin/guru. */
  public static function alamatRules(): array {
      return [
          'alamat' => ['nullable','string','max:500'],
          'provinsi_id' => ['nullable','string','max:10'], 'provinsi_nama' => ['nullable','string','max:255'],
          'kota_id' => ['nullable','string','max:10'], 'kota_nama' => ['nullable','string','max:255'],
          'kecamatan_id' => ['nullable','string','max:10'], 'kecamatan_nama' => ['nullable','string','max:255'],
          'kelurahan_id' => ['nullable','string','max:10'], 'kelurahan_nama' => ['nullable','string','max:255'],
      ];
  }
  /** Ambil hanya kolom alamat+wilayah dari array data tervalidasi. */
  public static function alamatData(array $data): array {
      return collect($data)->only(array_keys(static::alamatRules()))->all();
  }
  ```
- Modify: `app/Models/Guardian.php` — fillable −`alamat`−8 wilayah.

**Verify:** `php artisan test` (belum ada test baru; pastikan tak merah karena fillable).

### Task 3: Wali register — pindah alamat Step 1 → Step 2 (student)

**Files:**
- Modify: `resources/views/wali/register.blade.php`
  - HAPUS blok dropdown wilayah (baris ~96-160) + textarea alamat dari Step 1.
  - TAMBAH blok yang sama di Step 2 (Data Anak), setelah field anak. Prefill: hapus `auth()->user()->guardian?->alamat` (siswa baru, kosong) — cukup `old(...)`.
- Modify: `app/Http/Requests/StoreRegistrationRequest.php`
  - Ganti 9 rule alamat+wilayah lama → `...Student::alamatRules()` (spread). Import `App\Models\Student`.
- Modify: `app/Http/Controllers/Wali/RegistrationController.php`
  - `$guardian->update()` HAPUS `alamat` + `collect->only(wilayah)` (guardian tak lagi punya). Guardian update tinggal `no_hp`.
  - `Student::create([...])` tambah `+ Student::alamatData($data)`.

**Verify:** `php artisan test --filter=WaliRegistrationTest`; manual: register anak → `students.alamat`/`provinsi_nama` terisi, `guardians` tak punya kolom.

### Task 4: RegisterController signup — buang alamat vestigial

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisterController.php`
  - Hapus rule `'alamat'` dari validate.
  - `Guardian::create([...])` hapus `'alamat' => ...`.

**Verify:** `php artisan test --filter=AuthTest` (signup masih lolos).

### Task 5: admin/registration-detail — baca alamat dari student

**Files:**
- Modify: `resources/views/admin/registration-detail.blade.php:182`
  - `$guardian?->alamat` → `$student?->alamat`. (Bisa tampilkan wilayah lengkap: `$student?->provinsi_nama` dst bila diinginkan — minimal ganti alamat dulu.)

**Verify:** `php artisan test --filter=AdminGuruViewsTest` (detail render).

### Task 6: Admin student CRUD — tambah alamat+dropdown

**Files:**
- Modify: `app/Http/Controllers/Admin/MasterDataController.php`
  - `studentRules()` (DRY existing): tambah `+ Student::alamatRules()`.
  - `storeStudent`/`updateStudent`: `Student::create/update([...] + Student::alamatData($data))`.
- Modify: `resources/views/admin/data/students.blade.php`
  - Modal Alpine student: tambah 4 picker wilayah + textarea alamat (pola x-model `f.*`, sama seperti `teachers.blade.php` yang sudah jadi — SALIN pola itu). Extend `Js::from` row + blank + openEdit dgn 8 wilayah + alamat.

**Verify:** `php artisan test --filter=AdminGuruViewsTest`; assert student CRUD simpan `provinsi_nama`.

### Task 7: Guru student CRUD — tambah alamat+dropdown

**Files:**
- Modify: `app/Http/Controllers/Guru/GuruController.php`
  - `storeStudent`: validasi `+ Student::alamatRules()`, create `+ Student::alamatData($data)`.
  - `updateStudent`: idem (cek apakah updateStudent ada; bila ya extend).
- Modify: `resources/views/guru/dashboard.blade.php`
  - Modal tambah/edit siswa: 4 picker + alamat (pola sama). Endpoint `wilayah` sudah `auth` (guru boleh).

**Verify:** `php artisan test --filter=GuruTest`.

### Task 8: Seeder + test disesuaikan

**Files:**
- Modify: `database/seeders/DemoSeeder.php:101` — `Guardian::firstOrCreate` buang `'alamat'`. Bila mau data demo alamat siswa, set di `Student::create` (opsional).
- Modify: `database/seeders/DatabaseSeeder.php:76` — buang `'alamat'` dari guardian.
- Modify test yang `Guardian::create([... 'alamat' => ...])`:
  `RelationshipTest.php` (3x), `WaliRegistrationTest.php:37`, `WaliViewsTest.php:42`, `MultiChildTest.php:42` → buang `'alamat'` key (kolom sudah tak ada, akan error mass-assign/DB).
  `WaliRegistrationTest.php:53` (`'alamat' => 'Jl. Melati 4'` di POST daftar) → tetap OK (kini masuk student); tambah assert `students.alamat`.

**Verify:** `php artisan test` = semua hijau (target ~81+ passed).

### Task 9: Verifikasi penuh + docs

- `php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder` sukses (skema baru + wilayah re-import dari `database/data/`).
- `php artisan test` hijau, `npm run build` EXIT 0, `php artisan view:cache` compile.
- Update `.agents/notes.md` (T2.1 tambah catatan pindah) + `.agents/tasklist.md` (Fase baru: pindah alamat ke student).

---

## Files likely to change

**Migration (1 baru):** `2026_07_17_000003_move_alamat_wilayah_to_students.php`
**Model (2):** `Student.php` (+fillable +2 helper), `Guardian.php` (−fillable)
**Controller (4):** `Auth/RegisterController.php`, `Wali/RegistrationController.php`, `Admin/MasterDataController.php`, `Guru/GuruController.php`
**Request (1):** `StoreRegistrationRequest.php`
**View (4):** `wali/register.blade.php`, `admin/registration-detail.blade.php`, `admin/data/students.blade.php`, `guru/dashboard.blade.php`
**Seeder (2):** `DemoSeeder.php`, `DatabaseSeeder.php`
**Test (5):** `RelationshipTest`, `WaliRegistrationTest`, `WaliViewsTest`, `MultiChildTest`, `AdminGuruViewsTest` (+student alamat assert)

**TIDAK disentuh (biarkan):** teacher alamat/wilayah (alamat guru = milik guru sendiri, benar di `teachers`). Endpoint `wilayah`, WilayahController, migration import.

## Tests / validation

- `php artisan test` (target hijau; +assert student alamat di wali-register & admin-student).
- `php artisan migrate:fresh --seed` + DemoSeeder sukses.
- `npm run build` EXIT 0, `view:cache` compile.
- Manual smoke: daftar anak → `students.alamat` terisi; admin edit siswa → wilayah tersimpan; `guardians` tak punya kolom alamat.

## Risks, tradeoffs, open questions

- **Data loss (drop guardian cols):** data alamat wali existing hilang. Aman krn data hanya demo + `migrate:fresh` reseed. Bila DB produksi sudah ada data riil → tambah langkah copy `guardians.*` → `students.*` di migration up() sebelum drop (join via id_guardian). **Konfirmasi: ada data produksi?** (diasumsikan TIDAK).
- **Multi-anak alamat (Q3):** kini per-anak. Kalau wali 3 anak serumah → isi alamat 3x. Trade-off diterima demi "alamat = atribut siswa". Bila mau auto-prefill dari anak sebelumnya → enhancement Alpine kecil (opsional, tak di plan).
- **DRY helper di Model:** menaruh rule validasi di model (`Student::alamatRules`) sedikit tak lazim (biasanya di FormRequest), tapi karena dipakai 3 controller berbeda tanpa FormRequest bersama, ini titik reuse termurah. Alternatif: FormRequest bersama `StoreStudentAlamat` — lebih berat, skip.
- **Guardian/teacher wilayah lama:** tidak di-refactor ke helper (drive-by). Hanya siswa pakai helper baru. Konsisten "jangan sentuh yang tak diminta".

## Open questions untuk user (jawab sebelum eksekusi)

1. **Q2:** Hapus `guardians.alamat` total (rekomendasi) atau sisakan?
2. **Q3:** Alamat jadi per-anak di form daftar — OK?
3. **Data produksi:** DB `telaga` sudah punya data alamat wali riil yang harus di-copy ke siswa, atau semua masih demo (boleh `migrate:fresh`)?
