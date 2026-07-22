# Tasklist — Sistem Informasi TELAGA AL KAUTSAR

Progress Keseluruhan: 98%
(Tahap 1 Front End: 100% · Tahap 2 Back End: 100% · Tahap 3: 3.A & 3.B selesai, tinggal 3.C Deploy)

> Konvensi wajib saat koding: PK/FK non-standar rules.md §2 (PK `id_<tabel>`, FK `id_<singular>`), model set `$table`+`$primaryKey`, relasi/FK eksplisit, komentari tiap fungsi (§5). Keputusan arsitektur final ada di rules.md §1.5–§1.7 & §2.4.

## Tahap 1 — Front End (Mockup & Interaktif)

- [x] ✅ Task 1.1 - Inisialisasi Proyek Laravel & Setup Tailwind CSS `[Mudah]` (Selesai)
  * Menginisialisasi Laravel di workspace.
  * Menginstal & mengonfigurasi Tailwind CSS v4, Vite, dan AlpineJS.
- [x] ✅ Task 1.2 - Membuat Shared Layouts (Public & Dashboard Portal) `[Sedang]` (Selesai)
  * Membuat layout publik `public.blade.php`.
  * Membuat layout dashboard `dashboard.blade.php` dengan mock role switcher.
- [x] ✅ Task 1.3 - Membuat Halaman Publik (Beranda, Profil, Info Pendaftaran) `[Mudah]` (Selesai)
  * Membuat file view `home.blade.php`, `profile.blade.php`, dan `info.blade.php` dengan desain modern dan responsif.
- [x] ✅ Task 1.4 - Membuat Halaman Login & Signup dengan Mock Role Switcher `[Mudah]` (Selesai)
  * Membuat halaman login dan signup terintegrasi dengan Quick Login Switcher untuk memudahkan demonstrasi/pengujian.
- [x] ✅ Task 1.5 - Membuat Dashboard Wali Murid: Form Multi-step Pendaftaran `[Sukar]` (Selesai)
  * Merancang form pendaftaran multi-step interaktif dengan AlpineJS (Data Wali, Data Anak dengan NIK wajib, Upload Berkas).
- [x] ✅ Task 1.6 - Membuat Dashboard Wali Murid: Status Seleksi & Timeline `[Sedang]` (Selesai)
  * Membuat visualisasi timeline pendaftaran dengan mock switcher status pendaftaran (Draft hingga Lulus/Gagal).
- [x] ✅ Task 1.7 - Membuat Dashboard Wali Murid: Pembayaran SPP & Daftar Ulang (Cicilan) `[Sukar]` (Selesai)
  * Membuat portal pembayaran yang mendukung pencatatan pembayaran cicilan/parsial, riwayat transaksi, dan kalkulator tunggakan.
- [x] ✅ Task 1.8 - Membuat Dashboard Admin CMS: Verifikasi Pendaftaran & Keuangan `[Sukar]` (Selesai)
  * Membuat dashboard admin yang mencakup pengaturan nominal pendaftaran dinamis, review pendaftar baru, verifikasi pembayaran dengan opsi Setuju/Tolak + Catatan perbaikan.
- [x] ✅ Task 1.9 - Membuat Dashboard Admin CMS: CRUD Mock Data Siswa, Guru, Kelas `[Sedang]` (Selesai)
  * Membuat tabel kelola data siswa, guru (mendukung 1 guru mengampu banyak kelas), serta modal pendaftaran pendidik baru.
- [x] ✅ Task 1.10 - Membuat Dashboard Admin CMS: Mock Laporan & Audit Log `[Mudah]` (Selesai)
  * Merancang visualisasi audit log aktivitas dan tombol ekspor CSV/PDF untuk transaksi keuangan dan data siswa.
- [x] ✅ Task 1.11 - Membuat Dashboard Guru: Siswa & Catatan Perkembangan Sederhana `[Sedang]` (Selesai)
  * Membuat dashboard guru dengan pemilihan kelas yang diampu (multi-kelas), tabel siswa per kelas, modal edit profil siswa, dan modal catatan perkembangan sederhana.
- [x] ✅ Task 1.12 - Validasi Navigasi, Responsivitas, dan Polishing UI `[Sedang]` (Selesai)
  * Mengganti semua emoji icon ke inline SVG untuk konsistensi lintas OS (14 file view).
  * Menambah validasi front-end (NIK 16 digit, file size 2MB, format file, required fields).
  * Menambah konfirmasi Alpine untuk aksi destruktif (hapus guru, tolak/verifikasi bayar, lulus/gagal).
  * Menghubungkan search & filter admin registrations ke Alpine (pencarian real-time).
  * Menambahkan `shadow-2xs` ke @theme CSS (custom shadow Tailwind v4).
  * Menghapus file `welcome.blade.php` (tidak terpakai).
  * Membersihkan icon close modal (🗙) diganti SVG X.
  * Validasi semua navigasi, role-based routing, dan @click.away modal berfungsi.

---

## Tahap 2 — Back End (Laravel + MySQL)

### Fase 0 — Perbaikan Lingkungan & Fondasi
- [x] ✅ Task 0.1 - Perbaikan lingkungan & konfigurasi `[Mudah]` (Selesai)
  * Perbaiki terminal bash (install Git for Windows -> git-bash asli).
  * `.env`: APP_NAME="TELAGA AL KAUTSAR", DB tetap `telaga` (final), + komentar penjelas.
  * `.env.example` diselaraskan ke MySQL + telaga.
  * Hapus `database/database.sqlite` (leftover).
- [x] ✅ Task 0.2 - storage:link & bersihkan scaffold `[Mudah]` (Selesai)
  * `php artisan storage:link` (symlink public/storage terbuat).
  * Bersihkan `DatabaseSeeder` dari Test User default (siap diisi seeder asli).

### Fase 1 — Database & Model (konvensi PK/FK rules.md §2)
- [x] ✅ Task 1.F1 - Migration + Model `academic_years` `[Mudah]` (Selesai)
  * `database/migrations/2026_07_13_043746_create_academic_years_table.php` (PK id_academic_years, tahun, is_aktif).
  * `app/Models/AcademicYear.php` (relasi hasMany classes/students/registrationForms eksplisit).
- [x] ✅ Task 1.F2 - Migration `users` (id_users + role) + Model User `[Sedang]` (Selesai)
  * Ubah PK ke `id_users`, tambah kolom `role` enum(wali,admin,guru).
  * Kolom `sessions.user_id` dipertahankan (hardcoded driver Laravel) — dikomentari sebagai pengecualian sah.
  * `app/Models/User.php`: $primaryKey, relasi guardian()/teacher(), helper isAdmin/isGuru/isWali.
- [x] ✅ Task 1.F3 - Migration + Model `guardians` (profil wali, 1-1 ke users) `[Mudah]` (Selesai)
  * `2026_07_13_044141_create_guardians_table.php` (PK id_guardians, FK id_user unique -> users, no_hp, alamat, pekerjaan).
  * `app/Models/Guardian.php` (relasi user() belongsTo, students() hasMany eksplisit).
- [x] ✅ Task 1.F4 - Migration + Model `teachers` (profil guru, nomor_induk_guru) `[Mudah]` (Selesai)
  * `2026_07_13_044142_create_teachers_table.php` (PK id_teachers, FK id_user unique -> users, nomor_induk_guru unique, no_hp, alamat).
  * `app/Models/Teacher.php` (relasi user() belongsTo, classes() belongsToMany via class_teacher, progressNotes() hasMany).
- [x] ✅ Task 1.F5 - Migration + Model `classes` (Model: SchoolClass) `[Mudah]` (Selesai)
  * `2026_07_13_044329_create_classes_table.php` (PK id_classes, FK id_academic_year -> academic_years, nama_kelas).
  * `app/Models/SchoolClass.php` (academicYear() belongsTo, teachers() belongsToMany, students() hasMany).
- [x] ✅ Task 1.F6 - Migration pivot `class_teacher` (guru banyak kelas) `[Sedang]` (Selesai)
  * `2026_07_13_044330_create_class_teacher_table.php` (PK id_class_teacher, FK id_class + id_teacher, unique(id_class,id_teacher)).
  * Pivot ditangani via belongsToMany di model Teacher & SchoolClass (tanpa model pivot terpisah).
- [x] ✅ Task 1.F7 - Migration + Model `students` (NIK, kelas, wali) `[Sedang]` (Selesai)
  * `2026_07_13_044525_create_students_table.php` (PK id_students, FK id_guardian/id_class nullable + id_academic_year, nik 16 unique, jenis_kelamin enum, status enum).
  * `app/Models/Student.php` (guardian/schoolClass/academicYear belongsTo, payments/spp/reRegist/progressNotes hasMany).
- [x] ✅ Task 1.F8 - Migration + Model `settings` (biaya dinamis) `[Mudah]` (Selesai)
  * `2026_07_13_044526_create_settings_table.php` (PK id_settings, key unique, value).
  * `app/Models/Setting.php` (helper statis get()/set()).
- [x] ✅ Task 1.F9 - Migration + Model `registration_forms` (status seleksi) `[Sedang]` (Selesai)
  * `2026_07_13_044704_create_registration_forms_table.php` (PK id_registration_forms, FK id_user + id_student nullable + id_academic_year, status enum 8 tahap PRD §7.5, catatan_admin).
  * `app/Models/RegistrationForm.php` (user/student/academicYear belongsTo, documents hasMany).
- [x] ✅ Task 1.F10 - Migration + Model `registration_documents` (KK/Akta/Foto) `[Mudah]` (Selesai)
  * `2026_07_13_044705_create_registration_documents_table.php` (PK id_registration_documents, FK id_registration_form, jenis enum, path, status enum, catatan).
  * `app/Models/RegistrationDocument.php` (registrationForm belongsTo).
- [x] ✅ Task 1.F11 - Migration + Model `payment_transactions` (cicilan/lunas) `[Sedang]` (Selesai)
  * `2026_07_13_044916_create_payment_transactions_table.php` (PK id_payment_transactions, FK id_student/id_registration_form nullable + id_user + verified_by, jenis enum, jumlah decimal(12,2), status enum).
  * `app/Models/PaymentTransaction.php` (student/registrationForm/user/verifier belongsTo). Source of truth saldo (PRD §13).
- [x] ✅ Task 1.F12 - Migration + Model `monthly_spp_bills` (tagihan SPP bulanan) `[Sedang]` (Selesai)
  * `2026_07_13_044916_create_monthly_spp_bills_table.php` (PK id_monthly_spp_bills, FK id_student + id_academic_year, bulan, nominal/jumlah_terbayar decimal, status enum, unique(id_student,bulan)).
  * `app/Models/MonthlySppBill.php` (helper sisa(), student/academicYear belongsTo).
- [x] ✅ Task 1.F13 - Migration + Model `re_registration_payments` (daftar ulang) `[Mudah]` (Selesai)
  * `2026_07_13_044917_create_re_registration_payments_table.php` (PK id_re_registration_payments, FK id_student + id_academic_year, total_biaya/jumlah_terbayar decimal, status enum).
  * `app/Models/ReRegistrationPayment.php` (helper sisa(), student/academicYear belongsTo).
- [x] ✅ Task 1.F14 - Migration + Model `student_progress_notes` (catatan perkembangan) `[Mudah]` (Selesai)
  * `2026_07_13_045428_create_student_progress_notes_table.php` (PK id_student_progress_notes, FK id_student + id_teacher nullable, catatan, tanggal).
  * `app/Models/StudentProgressNote.php` (student/teacher belongsTo).
- [x] ✅ Task 1.F15 - Migration + Model `audit_logs` `[Mudah]` (Selesai)
  * `2026_07_13_045428_create_audit_logs_table.php` (PK id_audit_logs, FK id_user nullable, aksi, model_terkait, data_sebelum/sesudah JSON, ip_address).
  * `app/Models/AuditLog.php` (user belongsTo, cast JSON->array).
- [x] ✅ Task 1.F16 - DatabaseSeeder (tahun ajaran aktif, settings, admin pertama, guru contoh, kelas) `[Sedang]` (Selesai)
  * `database/seeders/DatabaseSeeder.php`: 1 tahun ajaran aktif 2026/2027, 4 setting nominal, admin (password acak dicetak console), guru contoh (password=nomor induk G-2026-001), 3 kelas, guru ditugaskan ke Kelas A.
  * Verifikasi: `php artisan migrate:fresh --seed` sukses, kredensial admin tercetak.
- [x] ✅ Task 1.F17 - Relationship tests + `migrate:fresh --seed` hijau `[Sedang]` (Selesai)
  * `tests/Feature/RelationshipTest.php`: 5 test (16 assertions) menguji semua relasi FK/PK non-standar — User↔Guardian/Teacher 1-1, Teacher↔SchoolClass many-to-many, Student↔guardian/kelas/tahun, keuangan+pendaftaran+helper sisa().
  * Aktifkan `pdo_sqlite`/`sqlite3` di `C:\php\php.ini` (untuk test :memory:).
  * Hapus `tests/Feature/ExampleTest.php` & `tests/Unit/ExampleTest.php` (scaffold).
  * Verifikasi: `php artisan test` = 5 passed (16 assertions), `migrate:fresh --seed` sukses.

**>>> FASE 1 SELESAI (Database & Model, 16 tabel + 12 model + seeder + test hijau) <<<**

### Fase 2 — Autentikasi & RBAC
- [x] ✅ Task 2.1 - Registrasi & Login Wali Murid (role dikunci 'wali') `[Sedang]` (Selesai)
  * `app/Http/Controllers/Auth/RegisterController.php` (self-signup wali, role dikunci 'wali', buat guardian via DB::transaction, auto-login).
  * `app/Http/Controllers/Auth/AuthController.php` (login/logout, redirect per role).
- [x] ✅ Task 2.2 - Login Admin & Guru + redirect per role `[Sedang]` (Selesai)
  * AuthController::redirectByRole() → admin/guru/wali dashboard. Login form tunggal (satu tabel users).
- [x] ✅ Task 2.3 - Middleware `role:` (RBAC) + proteksi route portal `[Sedang]` (Selesai)
  * `app/Http/Middleware/EnsureUserHasRole.php` (alias `role` di bootstrap/app.php).
  * `routes/web.php` ditulis ulang: portal wali/admin/guru dilindungi `auth`+`role:<role>`; nama route dipertahankan.
- [x] ✅ Task 2.4 - Ganti mock role switcher dengan auth asli `[Sedang]` (Selesai)
  * `auth/login.blade.php`: form login/signup POST asli + @csrf + tampilan error + partial `auth/_tabs.blade.php`; hapus quick-switcher mock & kredensial hardcoded.
  * `layouts/dashboard.blade.php`: @php pakai auth()->user(); ganti Mock Role select → tombol Logout (POST); hapus banner "MOCKUP ACTIVE".
- [x] ✅ Task 2.5 - Test auth+RBAC + verifikasi `[Sedang]` (Selesai)
  * `tests/Feature/AuthTest.php`: 8 test (signup lock role, ignore injected role, redirect per role, wrong password, RBAC lintas-role 403, guest→login).
  * Verifikasi: `php artisan test` = 13 passed (37 assertions), `npm run build` sukses, `view:cache` OK.

**>>> FASE 2 SELESAI (Autentikasi & RBAC) <<<**

### Fase 3 — Alur Wali Murid
- [x] ✅ Task 3.1 - Form pendaftaran murid baru (validasi NIK 16 digit dll) `[Sukar]` (Selesai)
  * `app/Http/Requests/StoreRegistrationRequest.php` (validasi NIK digits:16 unique, file JPG/PDF <=2MB, authorize role wali).
  * `app/Http/Controllers/Wali/RegistrationController.php` (create + store: buat student+form+3 dokumen via DB::transaction, status 'submitted').
  * `resources/views/wali/register.blade.php` disambungkan ke POST asli (form multipart + @csrf + name + tampil error server; Alpine tetap untuk step/validasi klien).
- [x] ✅ Task 3.2 - Service upload dokumen (JPG/PDF <=2MB, penamaan+path rules.md §3) `[Sukar]` (Selesai)
  * `app/Services/DocumentUploadService.php`: simpan ke `pendaftaran/[tahun-ajaran]/[nama-anak]/` dgn nama `KK_`, `Akta-Kelahiran_`, `Foto_` (spasi->'-', '/'->'-' pada tahun). Helper storePaymentProof().
  * Aktifkan `extension=gd` di `C:\php\php.ini` (untuk UploadedFile::fake()->image() & thumbnail masa depan — tuntaskan catatan B-2).
- [x] ✅ Task 3.3 - Status pendaftaran + upload bukti bayar pendaftaran `[Sedang]` (Selesai — data layer)
  * `app/Http/Controllers/Wali/StatusController.php` (index: tampilkan form+dokumen milik wali; uploadProof: simpan PaymentTransaction 'pending' + status form -> 'menunggu_verifikasi').
  * `app/Http/Requests/StorePaymentProofRequest.php` (bukti JPG/PDF <=2MB).
  * Route `wali.register.store`, `wali.status`, `wali.status.proof` di web.php.
  * CATATAN: `wali/status.blade.php` & `wali/dashboard.blade.php` masih tampilan mock — controller sudah kirim $form & $nominalPendaftaran; binding tampilan status ke data asli dijadwalkan saat integrasi UI (bareng polishing Tahap 3).
- [x] ✅ Task 3.4 - Test alur wali + verifikasi `[Sedang]` (Selesai)
  * `tests/Feature/WaliRegistrationTest.php`: 4 test (submit+3 dokumen tersimpan di path rules.md §3, tolak NIK invalid, tolak file >2MB, upload bukti bayar -> status menunggu_verifikasi).
  * Verifikasi: `php artisan test` = 17 passed (56 assertions), `npm run build` sukses.

**>>> FASE 3 SELESAI (Alur Wali Murid — data layer & upload; binding tampilan status menyusul) <<<**

### Fase 4 — Alur Admin (CMS)
- [x] ✅ Task 4.1 - Verifikasi dokumen & pembayaran (setuju/tolak + catatan) `[Sukar]` (Selesai)
  * `app/Services/PaymentVerificationService.php` (approve/reject: sinkron jumlah_terbayar dari SUM transaksi 'diverifikasi' + status belum_lunas/kurang/lunas — source of truth saldo PRD §13).
  * `app/Services/AuditLogService.php` (record aksi krusial — PRD §7.14).
  * `app/Http/Controllers/Admin/RegistrationController.php` (index/show, verifyDocument, verifyPayment; update status form sesuai workflow.md §2.1).
- [x] ✅ Task 4.2 - Atur nominal biaya dinamis + tombol Lulus/Gagal `[Sedang]` (Selesai)
  * `app/Http/Controllers/Admin/SettingController.php` (update nominal pendaftaran/spp/daftar_ulang/tanggal_generate).
  * RegistrationController@decide (lulus/gagal manual; siswa aktif/nonaktif; audit log).
- [x] ✅ Task 4.3 - Manajemen siswa/guru/kelas + penugasan guru multi-kelas `[Sukar]` (Selesai)
  * `app/Http/Controllers/Admin/MasterDataController.php` (index; storeTeacher password=nomor induk via transaction; storeClass; assignClasses sync many-to-many).
  * Tambah getRouteKeyName() PK non-standar di model RegistrationForm/RegistrationDocument/PaymentTransaction/Teacher (route-model binding).
- [x] ✅ Task 4.4 - Test alur admin + verifikasi `[Sedang]` (Selesai)
  * `tests/Feature/AdminWorkflowTest.php`: 6 test (sinkron saldo SPP cicilan→kurang→lunas, tolak tak dihitung, decide lulus, update settings, buat guru+kelas password=nomor induk, RBAC wali ditolak).
  * Verifikasi: `php artisan test` = 23 passed (75 assertions), `npm run build` sukses.
  * CATATAN: view admin (registrations/users/payments) masih mock; controller sudah kirim data asli ($forms/$teachers/$classes/$students). Route admin.registrations.show pakai view admin.registration-detail (belum dibuat) — binding tampilan admin dijadwalkan saat integrasi UI.

**>>> FASE 4 SELESAI (Alur Admin — logic & service; binding tampilan CMS menyusul) <<<**

### Fase 5 — Keuangan (Cicilan)
- [x] ✅ Task 5.1 - Service pembayaran parsial (tunggakan dinamis, progress, rincian) `[Sukar]` (Selesai)
  * `app/Http/Controllers/Wali/PaymentController.php` (index: tagihan SPP+daftar ulang anak wali + riwayat; store: cicilan 'pending', cek kepemilikan + tolak overpay).
  * `app/Http/Requests/StoreInstallmentRequest.php` (jenis spp/daftar_ulang, bukti JPG/PDF <=2MB).
  * Sisa tunggakan dihitung dinamis via helper sisa() (PRD §13); sinkron saldo saat admin verifikasi (PaymentVerificationService, Fase 4).
- [x] ✅ Task 5.2 - Daftar ulang (cicilan) `[Sedang]` (Selesai)
  * Tagihan re_registration_payments dibuka otomatis (idempotent) saat Admin memutuskan 'lulus' (RegistrationController@decide), nominal dari setting nominal_daftar_ulang.
  * Pembayaran daftar ulang memakai jalur cicilan yang sama (PaymentController jenis 'daftar_ulang').
- [x] ✅ Task 5.3 - SPP bulanan: command `spp:generate` + Scheduler + tombol manual CMS `[Sukar]` (Selesai)
  * `app/Console/Commands/GenerateSppBills.php` (idempotent firstOrCreate per id_student+bulan, siswa aktif, nominal dinamis, opsi --bulan).
  * `routes/console.php`: Schedule `spp:generate` monthlyOn(1, 00:05).
  * `SettingController@generateSpp` + route `admin.settings.generate-spp` (tombol manual CMS cadangan).
- [x] ✅ Task 5.4 - Test keuangan + verifikasi `[Sedang]` (Selesai)
  * `tests/Feature/FinanceTest.php`: 5 test (generate idempotent, wali bayar cicilan SPP pending, tolak overpay, tolak bayar tagihan anak wali lain 403, lulus buka daftar ulang).
  * Verifikasi: `php artisan test` = 28 passed (91 assertions), `npm run build` sukses.

**>>> FASE 5 SELESAI (Keuangan/Cicilan — cicilan wali, daftar ulang, generate SPP; binding tampilan menyusul) <<<**

### Fase 6 — Alur Guru
- [x] ✅ Task 6.1 - Dashboard guru dibatasi kelas diampu (query guard anti-kebocoran) `[Sukar]` (Selesai)
  * `app/Http/Controllers/Guru/GuruController.php` (index: siswa difilter whereIn id_class ∈ kelas yang diampu).
  * `Teacher::ampuClassIds()` helper + guardStudent() abort(403) untuk akses lintas-kelas (PRD §13).
  * Tambah getRouteKeyName() di model Student (route-model binding PK non-standar).
- [x] ✅ Task 6.2 - Edit profil siswa + catatan perkembangan `[Sedang]` (Selesai)
  * GuruController@updateStudent (edit profil, guarded) + @storeNote (StudentProgressNote, guarded).
  * Route guru.students.update (PUT) & guru.students.notes (POST).
- [x] ✅ Task 6.3 - Test guru + verifikasi `[Sedang]` (Selesai)
  * `tests/Feature/GuruTest.php`: 5 test (dashboard hanya siswa kelas diampu, tambah catatan siswa sendiri, TOLAK catatan siswa kelas lain 403, TOLAK edit siswa kelas lain 403, RBAC wali ditolak).
  * Verifikasi: `php artisan test` = 33 passed (100 assertions), `npm run build` sukses.

**>>> FASE 6 SELESAI (Alur Guru — akses per-kelas dengan query guard anti-kebocoran; binding tampilan menyusul) <<<**

### Fase 7 — Lintas-Fitur
- [x] ✅ Task 7.1 - Audit log (login, verifikasi bayar, ubah status, before/after) `[Sedang]` (Selesai)
  * `AppServiceProvider` boot(): listener event Login/Logout → AuditLogService (PRD §7.14).
  * Aksi krusial lain sudah tercatat sejak Fase 4 (verifikasi dokumen/pembayaran, keputusan seleksi, setting, guru).
- [x] ✅ Task 7.2 - Email notifikasi (trigger PRD §7.13, MAIL_MAILER=log dulu) `[Sedang]` (Selesai)
  * `app/Mail/RegistrationNotification.php` (Mailable generik judul+pesan) + `resources/views/emails/notification.blade.php`.
  * Trigger: pendaftaran terkirim (Wali\RegistrationController), pembayaran diverifikasi/ditolak & keputusan lulus/gagal (Admin\RegistrationController). MAIL_MAILER=log saat dev.
- [x] ✅ Task 7.3 - Search/filter + export CSV (native) & PDF (barryvdh/laravel-dompdf) `[Sukar]` (Selesai)
  * Install `barryvdh/laravel-dompdf` v3.1.2.
  * `app/Http/Controllers/Admin/ReportController.php` (filter status/jenis/tanggal; exportCsv streamed native; exportPdf via dompdf).
  * `resources/views/reports/transactions-pdf.blade.php`. Route reports + reports.csv + reports.pdf.
- [x] ✅ Task 7.4 - Test lintas-fitur + verifikasi `[Sedang]` (Selesai)
  * `tests/Feature/CrossFeatureTest.php`: 5 test (login teraudit, kelulusan kirim email via Mail::fake, export CSV berisi data, export PDF application/pdf, filter status).
  * Verifikasi: `php artisan test` = 38 passed (110 assertions), `npm run build` sukses.

**>>> FASE 7 SELESAI (Lintas-Fitur — audit, email, export CSV/PDF) — SELURUH BACKEND (Tahap 2) SELESAI <<<**

## Tahap 2.5 — CMS Konten Website Publik (fitur tambahan, disetujui 2026-07-13)
Referensi: PRD §7.15, rules.md §1.8, workflow.md §4, plan `.hermes/plans/2026-07-13_cms-konten-publik.md`. Pendekatan C (hibrida), item berulang dinamis, biaya dari settings, guru dari DB, upload gambar, pengelola admin.
- [x] ✅ Task CMS-1 - Migrasi + Model + Seeder `[Sukar]` (Selesai)
  * `site_contents` (PK id_site_contents, key unique, value, grup, label, tipe) + Model SiteContent (helper get/set).
  * `site_features` (PK id_site_features, grup, judul, deskripsi, ikon, foto_path, urutan, aktif) + Model SiteFeature (scope grup aktif+urut, getRouteKeyName).
  * ALTER `teachers`: + jabatan, foto_path, tampil_di_web (+ cast). Teacher fillable diperbarui.
  * SiteContentSeeder = 22 konten + 19 item default (= teks mockup). Dipanggil dari DatabaseSeeder.
- [x] ✅ Task CMS-2 - Render publik dari DB `[Sukar]` (Selesai)
  * PublicController@home/profile/info (SiteContent grup, SiteFeature grup, Setting utk biaya, Teacher tampil_di_web). Route publik pakai controller.
  * home/profile/info di-bind ke DB. Biaya info dari settings. Guru profil dari teachers (foto/inisial + jabatan).
- [x] ✅ Task CMS-3 - CMS Admin konten `[Sukar]` (Selesai)
  * Admin\SiteContentController (index tab per grup; updateContents batch; features store/update/destroy; uploadMedia ke storage/public/konten).
  * View admin/content.blade.php (tab Beranda/Profil/Info/Kontak + editor item dinamis tambah/hapus/urut/aktif + upload gambar). Menu 'Kelola Konten Web' di sidebar admin (desktop).
  * Route admin.content.* (role:admin). CATATAN: menu mobile drawer belum ditambah link content (opsional).
- [x] ✅ Task CMS-4 - Field guru web `[Sedang]` (Selesai)
  * MasterDataController@updateTeacherWeb (jabatan/foto/tampil_di_web) + route admin.teachers.web. DemoSeeder set guru contoh tampil_di_web=true + jabatan.
- [x] ✅ Task CMS-5 - Test + verifikasi `[Sedang]` (Selesai)
  * PublicContentTest (3): home render dari DB, info biaya dari settings, profil hanya guru tampil_di_web.
  * SiteContentCmsTest (5): update konten, tambah+hapus item, upload media, set field web guru, RBAC non-admin ditolak.
  * `php artisan test` = 58 passed (170 assertions), `npm run build` OK, view:cache compile.

**>>> TAHAP 2.5 SELESAI (CMS Konten Website Publik) <<<**

## Tambahan Integritas Data (2026-07-14)
- [x] ✅ Keunikan nomor HP & nama kelas + dokumen skenario uji `[Sedang]` (Selesai)
  * Migration `2026_07_14_000000_add_unique_no_hp_to_guardians_and_teachers.php`:
    unique index guardians.no_hp & teachers.no_hp; guardians.no_hp jadi nullable
    (no_hp kosong = NULL, bukan '', agar banyak NULL tidak bentrok).
  * Validasi: RegisterController (unique:guardians,no_hp), StoreRegistrationRequest
    (Rule::unique guardians ignore diri sendiri utk multi-anak), storeTeacher
    (unique:teachers,no_hp). storeClass: nama_kelas unik per tahun ajaran aktif.
  * Fix test factory FinanceTest & MultiChildTest (no_hp diturunkan dari email).
  * `.agents/test-scenarios.md` DIBUAT — skenario UAT sukses+gagal (Wali/Admin/Guru/RBAC),
    berbasis validasi kode nyata; termasuk A1.8, A3.9, A3.10, B5.8, B5.9.
  * Verifikasi: `php artisan test` = 58 passed (170 assertions), `migrate:fresh --seed` sukses.

## Tahap 3 — Penyempurnaan & Pengujian
- [x] ✅ Task 3.A - Validasi form lanjutan & polishing UI (binding view) `[Sedang]` (Selesai)
  * Wali Dashboard di-bind + MULTI-ANAK (koleksi $students, 1 blok/anak, total tunggakan agregat). Badge status sejajar-kanan + anti-overflow.
  * relasi Student::registrationForms() ditambahkan (celah Fase 1).
  * DemoSeeder 2 skenario — Wali A (wali@telaga.sch.id/password) 2 anak; Wali B (rizal@telaga.sch.id/password) kakak 2025/2026 + adik 2026/2027. 8 transaksi utk laporan.
  * wali/status di-bind: timeline dari status form (8 status→5 tahap), dokumen terunggah, modal upload bukti POST asli.
  * wali/payments di-bind: tagihan semua anak (SPP+daftar ulang), riwayat asli, modal cicilan POST asli (max = sisa).
  * admin/registrations di-bind ($forms + badge + link detail); admin/users di-bind ($students/$teachers/$classes + form tambah guru/kelas); admin/registration-detail DIBUAT (verifikasi dokumen/bayar + keputusan lulus/gagal); admin/payments minimal; admin/reports ($transactions + filter + export).
  * guru/dashboard di-bind ($classes filter, $students tabel, modal edit profil PUT + catatan POST).
  * Fix Tailwind v4: kelas warna dinamis → string statik (JIT tak baca kelas interpolasi runtime).
- [x] ✅ Task 3.B - Pengujian menyeluruh (UAT) & perbaikan bug `[Sukar]` (Selesai)
  * MultiChildTest (4): dashboard semua anak, total tunggakan agregat, kakak-adik lintas tahun, empty-state.
  * WaliViewsTest (3): status render, upload bukti → status berubah, payments render tagihan.
  * AdminGuruViewsTest (5): admin registrations/detail/users/reports render, guru dashboard render dgn siswa (verifikasi independen klaim subagent).
  * Verifikasi laporan CSV & PDF (data demo, file valid: CSV 8 baris, PDF v1.7 3 hal). Fix fputcsv escape='' (PHP 8.4+).
  * `php artisan test` = 50 passed (142 assertions), `npm run build` OK, view:cache semua compile.
  * CATATAN: binding view admin & guru didelegasikan ke 2 subagent paralel; hasil diverifikasi ulang mandiri via smoke test (bukan hanya self-report).
- [ ] Task 3.C - Deployment awal `[Sedang]`

---

## Tambahan Infrastruktur (2026-07-19)
- [x] ✅ Tunnel: `start-tunnel.bat` dioptimasi — ganti `npm run dev` → `npm run build` + rapikan step numbering.
- [x] ✅ Mixed Content via Cloudflare Tunnel: tambah `trustProxies(at: ['127.0.0.1', '::1'])` di `bootstrap/app.php` agar `X-Forwarded-Proto: https` dibaca → asset URL pakai HTTPS, tidak diblokir browser.

---

## Tahap 4 — Backlog Perbaikan (dari .agents/notes.md)

### Fase A — Bug Fix Alur Inti (Selesai 2026-07-16)
- [x] ✅ Fase A - Perbaikan alur pendaftaran & pembayaran `[Sedang]` (Selesai)
  * [P2.1] StatusController: `->first()` → ambil semua form; `wali/status.blade.php` loop per anak (dukung wali >1 anak).
  * [P2.2] Terkonfirmasi sudah benar (PaymentController + wali/payments sudah loop semua anak) — tidak ada perubahan.
  * [P2.3] Timeline `wali/status` dipisah jadi 6 tahap (Verifikasi Pembayaran & Verifikasi Berkas terpisah); guard: berkas tak bisa diverifikasi sebelum bayar diverifikasi.
  * [P3.1] `admin/registration-detail`: card Verifikasi Bukti Pembayaran dipindah ke paling atas.
  * [P3.2] `RegistrationController@verifyDocument`: auto-maju form ke `diproses_seleksi` saat semua dokumen `diterima` (+ guard urutan bayar dulu, + audit `maju_seleksi`).
  * [T4.1] Root cause enum: view cek `'menunggu'` sedangkan DB/controller `'pending'` → tombol Tolak/Verifikasi mati. Seragamkan view ke `'pending'` + tambah link "Lihat Bukti".
  * Test baru: `AdminWorkflowTest::test_document_verification_gated_and_advances_to_selection`.
  * Verifikasi: `php artisan test` = 59 passed (174 assertions), `npm run build` OK.
  * Plan: `.hermes/plans/2026-07-16_fase-a-bugfix-alur-inti.md`.

### Fase B — Struktur CMS Admin (Selesai 2026-07-16)
- [x] ✅ Fase B - Restrukturisasi sidebar + pisah tab data `[Sedang]` (Selesai)
  * [T5.1] `admin/users.blade.php` dipecah jadi `admin/data/{students,teachers,classes}.blade.php`; MasterDataController + method students()/teachers()/classes(); route `admin.data.*` (users jadi alias).
  * [T5.6] Sidebar (desktop+mobile) direstruktur: Dashboard, PPDB (dropdown), Data Guru, Data Siswa, Data Kelas, Konten Web, Pembayaran (dropdown), Laporan. Dropdown Alpine native. Child dropdown yg butuh fitur baru (Daftar Ulang, Set Pembayaran, SPP) ditunda ke Fase C.
  * Bonus: tab Guru tampilkan jumlah murid diampu; tab Kelas tampilkan jumlah guru+siswa (eager-load anti N+1).
  * Test: `AdminGuruViewsTest::test_admin_data_pages_render` (3 route baru).
  * Verifikasi: `php artisan test` = 59 passed (176 assertions), `npm run build` OK.
  * CATATAN: CRUD penuh + filter/search (T5.2–T5.5, T5.6a–i) = Fase C.

### Fase C — CRUD & Fitur Data (berjalan; 2026-07-16)
- [x] ✅ Fase C.1 - CRUD Siswa + filter/search + NISN (T5.2) `[Sukar]` (Selesai)
  * Migration `2026_07_16_000000_add_nisn_to_students_table` (nisn nullable+unique). Student fillable + nisn.
  * MasterDataController: students() + filter kelas/status + search; studentRules() (DRY, ignore-self); storeStudent/updateStudent/destroyStudent.
  * Route admin.students.{store,update,destroy}. View admin/data/students.blade.php: filter bar + modal tambah/edit (Js::from utk Alpine) + hapus.
  * Test: `AdminGuruViewsTest::test_student_crud`. Verifikasi: `php artisan test` = 60 passed (185 assertions), `npm run build` OK.
  * SISA Fase C: T5.3 (profil siswa+riwayat bayar), T5.4/T5.6c (guru CRUD+TTL+riwayat pendidikan), T5.5/T5.6e (kelas CRUD hapus), T5.6i (akun CRUD), T5.6a/T5.6b (PPDB buka-tutup/daftar ulang), P4 (dashboard cards+kuota), P4.2/T5.6g (set biaya+bank).
- [x] ✅ Fase C.2 - Tab Set Pembayaran (P4.2) `[Sedang]` (Selesai)
  * SettingController@index + route GET admin.settings + view admin/settings.blade.php (3 nominal + tanggal generate + tombol generate SPP, form POST asli ke settings.update).
  * Hapus widget mock "Biaya Pendaftaran Dinamis" (x-model + setTimeout) dari dashboard. Sidebar Pembayaran>Set Pembayaran (desktop dropdown + mobile).
  * Test: `AdminGuruViewsTest::test_admin_settings_page_and_save_all_nominals`. Verifikasi: `php artisan test` = 61 passed (190 assertions), `npm run build` OK.
  * SISA: bank & rekening sekolah (T2.2/T5.6g) belum; dashboard cards + kuota (P4.1) belum.
- [x] ✅ Fase C.3 - Dashboard cards dari DB + kuota (P4.1) `[Sedang]` (Selesai)
  * `Admin\DashboardController@index`: 4 card dari DB (pendaftar belum diputuskan, lulus, keuangan terverifikasi, pending verifikasi). Route admin.dashboard pakai controller (bukan closure).
  * Setting `kuota_pendaftaran` ditambah di tab Set Pembayaran (SettingController index+update+view); card "Murid Lulus Seleksi" tampil % dari kuota.
  * Test: `AdminGuruViewsTest::test_admin_dashboard_cards_from_db`. Verifikasi: `php artisan test` = 62 passed (192 assertions), `npm run build` OK.
- [x] ✅ Fase C.4 - Profil siswa + riwayat bayar (T5.3) `[Sedang]` (Selesai)
  * MasterDataController@showStudent + route admin.students.show + view admin/data/student-profile.blade.php (biodata + tagihan daftar ulang + tagihan SPP + riwayat transaksi via relasi existing). Link "Profil" di tabel siswa.
  * Test: profil render di `test_admin_data_pages_render`. Verifikasi: `php artisan test` = 62 passed (195 assertions), `npm run build` OK.
- [x] ✅ Fase C.5 - Guru CRUD + TTL + riwayat pendidikan (T5.4) `[Sedang]` (Selesai)
  * Migration `2026_07_16_010000_add_bio_to_teachers_table` (tempat_lahir/tanggal_lahir/riwayat_pendidikan). Teacher fillable+casts.
  * MasterDataController: teachers() + search; storeTeacher extend bio; updateTeacher + destroyTeacher (hapus cascade akun). Route teachers.update/destroy.
  * View admin/data/teachers.blade.php: search bar + modal tambah/edit (Js::from) + hapus + bonus TTL/riwayat fields.
  * Test: `test_teacher_update_and_destroy`. Verifikasi: `php artisan test` = 63 passed (202 assertions), `npm run build` OK.
- [x] ✅ Fase C.6 - Kelas CRUD (T5.5) `[Mudah]` (Selesai)
  * MasterDataController updateClass (unique per T.A. ignore self) + destroyClass. Route classes.update/destroy. SchoolClass getRouteKeyName ditambah.
  * View admin/data/classes.blade.php: tombol edit/hapus per kartu + modal dual-mode (classCrud).
  * Test: `test_class_update_and_destroy`. Verifikasi: `php artisan test` = 64 passed (206 assertions), `npm run build` OK.
- [x] ✅ Fase C.7 - PPDB buka/tutup + tahun ajaran + Daftar Ulang (T5.6a/b) `[Sedang]` (Selesai)
  * AdminRegistrationController: togglePpdb (setting pendaftaran_dibuka), storeAcademicYear (buat+aktifkan, nonaktif lama), daftarUlang (list bills + pending). Route admin.ppdb.toggle/year + admin.daftar-ulang.
  * Gate: Wali\RegistrationController@create+store abort 403 saat PPDB ditutup (ensurePpdbOpen, root-cause di 2 entry).
  * View: kontrol PPDB di atas registrations.blade.php; admin/daftar-ulang.blade.php (verifikasi reuse admin.payments.verify). Sidebar PPDB dropdown + Daftar Ulang child (desktop+mobile).
  * Test: `test_ppdb_controls_and_daftar_ulang` + `test_wali_blocked_when_ppdb_closed`. Verifikasi: `php artisan test` = 66 passed (215 assertions), `npm run build` OK.
- [x] ✅ Fase C.8 - Data Akun admin CRUD (T5.6i) `[Mudah]` (Selesai)
  * AccountController (index/store/destroy admin; guard hapus diri sendiri). User getRouteKeyName ditambah. Route admin.accounts.*.
  * View admin/accounts.blade.php + menu sidebar "Data Akun" (desktop+mobile). Akun guru tetap dikelola di Data Guru.
  * Test: `test_admin_account_crud`. Verifikasi: `php artisan test` = 67 passed (223 assertions), `npm run build` OK.
- [x] ✅ Fase C.9 - Guru ganti password (T6.1) `[Mudah]` (Selesai)
  * GuruController passwordForm + updatePassword (rule current_password + Password::min(8) confirmed). Route guru.password(.update). View guru/password.blade.php + menu sidebar guru (desktop+mobile).
  * Test: `GuruTest::test_guru_change_password`. Verifikasi: `php artisan test` = 68 passed (227 assertions), `npm run build` OK.
- [x] ✅ Fase C.10 - Guru: search siswa + tambah siswa (T6.2, T6.3) `[Sedang]` (Selesai)
  * T6.2: search Alpine client-side (nama/NIK) di dashboard guru, filter kelas dipertahankan.
  * T6.3: GuruController@storeStudent (guard id_class ∈ kelas diampu, Rule::in) + route guru.students.store + modal tambah di dashboard. Edit profil sudah ada sejak Fase 6; hapus TIDAK diberikan ke guru (kewenangan admin).
  * Test: `GuruTest::test_guru_add_student_scoped_to_own_class`. Verifikasi: `php artisan test` = 69 passed (231 assertions), `npm run build` OK.
  * T5.10 DITUNDA: auto-akun guru pakai HP/NUPTK bentrok rules.md §1.5 (password=nomor induk) + butuh kolom NUPTK + skema username (T3.2). Perlu keputusan user. Lihat notes.md.

### Fase D — Skema Akun: username + login email/HP + NUPTK (T3.2 & T5.10, Selesai 2026-07-16)
- [x] ✅ Fase D - Pindah nama ke profil, login email/HP, auto-akun guru NUPTK `[Sukar]` (Selesai)
  * [T3.2] Migration: `users.name`→`username`; +`users.no_hp` (nullable unique); `email` nullable unique;
    +`guardians.nama`; +`teachers.nama`. Kolom `nomor_induk_guru` DIHAPUS total (fresh; data hanya demo).
  * [T5.10] +`teachers.nuptk` (unique, wajib); `teachers.no_hp` jadi NOT NULL+unique (dipakai login).
    Auto-akun guru: password awal = NUPTK; username = email??no_hp; email opsional (guru isi sendiri).
  * Model: User (`fillable` username/no_hp; helper `displayName()`), Guardian (+nama), Teacher (+nama+nuptk, -NIG).
  * AuthController@login: field tunggal `login` = email, no_hp, ATAU username (T3.3; email via
    FILTER_VALIDATE_EMAIL, sisanya cocokkan no_hp/username lalu attempt by id_users).
  * Controller lain disesuaikan: RegisterController (guardian.nama), MasterDataController (storeTeacher/updateTeacher
    NUPTK+nama, search nama/nuptk/hp), AccountController (username). Register* validasi no_hp unik di users+guardians.
  * Migration lama diselaraskan: `add_unique_no_hp` (buang bagian teachers, sudah unique di create),
    `add_web_fields` (`after('nuptk')`).
  * Seeder/Factory: DatabaseSeeder & DemoSeeder (username+profil.nama+nuptk), UserFactory (username).
    Guru demo kini: guru.ahmad@telaga.sch.id atau 081311112222 / 1234567890123456 (NUPTK).
  * Views: login (field Email/No. HP), layouts/dashboard (`displayName()`, email??no_hp), guru+wali dashboard,
    teachers.blade (form nama/NUPTK/email-opsional/HP-wajib), registrations/students/student-profile/daftar-ulang/
    profile/accounts (nama dari profil / username).
  * Tests: semua `User::create` pakai `username`; guardian/teacher +nama+nuptk; +`test_login_via_no_hp`;
    storeTeacher test pakai NUPTK; password guru = NUPTK.
  * Verifikasi: `php artisan test` = 71 passed (241 assertions), `npm run build` OK, `migrate:fresh --seed`+DemoSeeder OK.
  * Revisi dokumen: rules.md §1.5 (password guru=NUPTK, login email/HP) & §1.6 (nama di profil, users kolom baru).
  * Plan: `.hermes/plans/2026-07-16_t3.2-t5.10-username-nuptk.md`.
  * SISA relevan: T2.3 (pengaturan akun mandiri wali/guru — update email+HP sendiri) belum; halaman lupa sandi (T1.2) belum.

### Fase E — Pengaturan Akun Mandiri (T2.3, Selesai 2026-07-16)
- [x] ✅ Fase E - Wali & Guru update email + no. HP sendiri `[Mudah]` (Selesai)
  * Wali: `WaliDashboardController` accountForm+updateAccount; route `wali.account`(.update); view `wali/account.blade.php`
    (email wajib + no_hp opsional); menu sidebar "Pengaturan Akun" (desktop+mobile).
  * Guru: `GuruController@updateAccount`; route `guru.account.update`; card ditambah ke `guru/password.blade.php`
    (email opsional + no_hp wajib—identifier login guru).
  * Keduanya menyinkron `users` + profil (`guardians`/`teachers`.no_hp) dalam satu transaksi; `Rule::unique` ignore-self
    di semua tabel terkait (email+no_hp lintas users, no_hp di profil).
  * Test: `WaliViewsTest::test_account_update_syncs_email_and_hp`, `GuruTest::test_guru_can_update_own_account`.
  * Verifikasi: `php artisan test` = 73 passed (247 assertions), `npm run build` OK.
  * CATATAN: T6.4 (CRUD profil guru lengkap) sebagian terpenuhi (email/HP); sisa profil guru lain belum.

### Fase F — Rekening Bank Sekolah + Bank Asal (T2.2 + T5.6g + T8.1, Selesai 2026-07-16)
- [x] ✅ Fase F - Rekening sekolah dari DB + dropdown bank asal `[Sedang]` (Selesai)
  * [T5.6g] SettingController index+update tambah 3 field: `bank_sekolah`, `rekening_sekolah`, `atas_nama` (reuse tabel settings, no migration).
    Card "Rekening Bank Sekolah" di admin/settings.blade.php (form kedua, endpoint sama, hidden nominal agar validasi required lolos).
  * [T2.2] Rekening tujuan ditarik dari settings & ditampilkan di 2 modal upload (wali/status + wali/payments);
    menggantikan hardcode "Bank Mandiri 123-456-789". Empty-state bila admin belum mengisi.
  * [T8.1] `PaymentTransaction::daftarBank()` parse `bank.csv` (kolom nama bank, buang header + sandi, unik).
    Kolom baru `payment_transactions.bank_asal` (migration `2026_07_16_020000` + fillable). Dropdown "Bank Asal Transfer"
    di 2 modal; disimpan via StatusController@uploadProof & PaymentController@store. Validasi `bank_asal` nullable|string|max:100
    di StorePaymentProofRequest + StoreInstallmentRequest.
  * Seeder: DatabaseSeeder set rekening default (Bank Mandiri / 123-456-7890 / RA Al Kautsar).
  * Test: `WaliViewsTest::test_status_upload_proof_works` (+assert bank_asal), `WaliViewsTest::test_daftar_bank_parses_csv`.
  * Verifikasi: `php artisan test` = 74 passed (249 assertions), `npm run build` OK, `php artisan migrate` DONE.
  * CATATAN: admin/reports & registration-detail belum menampilkan kolom bank_asal (data tersimpan; tambahkan bila diminta).

### Fase G — Verifikasi SPP terpisah + Profil Guru (T5.6h + T6.4, Selesai 2026-07-16)
- [x] ✅ Fase G - Tab Verifikasi SPP + guru edit profil sendiri `[Sedang]` (Selesai)
  * [T5.6h] AdminRegistrationController@spp (rekap tagihan SPP + bukti SPP pending); view admin/spp.blade.php;
    route admin.spp; verifikasi reuse admin.payments.verify (PaymentVerificationService sinkron saldo).
    Sidebar child "Verifikasi SPP" di dropdown Pembayaran (desktop+mobile).
  * [T6.4] GuruController@updateProfile (nama, TTL, riwayat_pendidikan; NUPTK read-only — identitas resmi, dikelola admin).
    Route guru.profile.update. Card "Profil Saya" ditambah ke guru/password.blade.php → halaman kini "Akun & Profil"
    (Profil + Akun email/HP dari T2.3 + Ganti Password). Sidebar label guru → "Akun & Profil".
  * Test: `AdminGuruViewsTest::test_ppdb_controls_and_daftar_ulang` (+spp render), `GuruTest::test_guru_can_update_own_profile`.
  * Verifikasi: `php artisan test` = 75 passed (252 assertions), `npm run build` OK.

### Fase H — Curriculum Highlight slider (T7.1, Selesai 2026-07-17)
- [x] ✅ Fase H - Slider foto Curriculum Highlight editable via CMS `[Sedang]` (Selesai)
  * [T7.1] Reuse `site_features` grup baru `kurikulum` (judul=caption, deskripsi=subcaption, foto_path=gambar, urutan, aktif).
    Tanpa migration (kolom foto_path sudah ada sejak CMS-1).
  * `home.blade.php`: card "Curriculum Highlight" (kolom kanan hero) diubah jadi carousel Alpine — `x-init` setInterval 4 dtk
    auto-slide + dot indicator (klik pindah), `x-transition` fade, `object-cover` + overlay gradient untuk caption terbaca.
    Fallback ke card statik lama bila belum ada slide (empty-state).
  * `SiteContentController`: +`kurikulum` di GRUP_FITUR; `storeFeature`/`updateFeature` terima `foto` opsional
    (image|mimes|max:2048 → `store('konten','public')`); `updateFeature`+`destroyFeature` hapus file lama agar tak menumpuk.
  * `PublicController@home` kirim `$kurikulum` (SiteFeature::grup('kurikulum')).
  * `admin/content.blade.php`: tab `kurikulum`→halaman `home`; form add & edit jadi `multipart/form-data` + input file `foto`
    + thumbnail 12x12 (kondisional hanya grup kurikulum, tak mengganggu grup lain).
  * `SiteContentSeeder`: 3 slide default `kurikulum` (tanpa foto — admin unggah via CMS).
  * Test: `SiteContentCmsTest::test_admin_can_add_kurikulum_slide_with_photo`.
  * Verifikasi: `php artisan test` = 76 passed (256 assertions), `npm run build` OK (4m55s — lambat karena tailwind@vite plugin
    di host Windows, bukan efek perubahan), `view:cache` compile OK.
  * CATATAN: `.agents/workflow.md` (disebut AGENTS.md) TIDAK ada di repo — referensi mati, bukan blocker.

### Fase I — Dropdown alamat wilayah berjenjang (T2.1, Selesai 2026-07-17)
- [x] ✅ Fase I - Searchable dropdown provinsi→kota→kecamatan→desa dari DB `[Sedang]` (Selesai)
  * [T2.1] Keputusan SQL vs JSON → **SQL diimpor ke MySQL** (data ~99k baris + hierarki berjenjang butuh query/index;
    baca ribuan file JSON per request lambat & rapuh). Konsisten arsitektur DB.
  * Migration `2026_07_17_000000_import_wilayah_indonesia`: impor `data-indonesia-master/wilayah_indonesia.sql` ke 4 tabel
    `t_provinsi/t_kota/t_kecamatan/t_kelurahan` (referensi read-only, TIDAK di-remodel ke konvensi PK/FK — seperti bank.csv).
    Dijalankan **per-statement** (split `;\n`) karena 1 `unprepared` untuk file 5 MB kena `max_allowed_packet`. Skip di sqlite
    (dump MySQL-only: CHARACTER SET/ENGINE) → test seed tabelnya sendiri. Anti-parsial: drop sisa bila impor gagal di tengah
    (DDL MySQL auto-commit). Dump sudah bawa `PRIMARY KEY(id)`.
  * Migration `2026_07_17_000001_add_wilayah_to_guardians`: +8 kolom (provinsi/kota/kecamatan/kelurahan × id+nama), nullable.
    Simpan id (kode BPS) + nama snapshot (label tahan walau referensi berubah). Kolom `alamat` lama tetap = detail jalan/RT/RW.
  * `WilayahController@index`: endpoint AJAX `GET portal/wali/wilayah/{level}?parent=&q=`. Hierarki di kolom `id`
    (prov=2,kota=4,kec=6,kel=10 digit) → filter `substr(id,1,n) = parent` (portabel MySQL+SQLite, ganti dari LEFT).
    Guard: tanpa parent valid kembalikan `[]` (jangan bocorkan puluhan ribu baris); `limit(1000)`; searchable `LIKE`.
    Route `wali.wilayah` (auth+role:wali).
  * `wali/register.blade.php` step 1: 4 searchable dropdown Alpine (input teks + list hasil `fetch` debounce 300ms; pilih item
    kunci id+nama ke hidden field & reset semua level di bawahnya; child disabled sampai parent dipilih). Guardian fillable +8 kolom.
  * `StoreRegistrationRequest`: +8 field wilayah nullable. `Wali\RegistrationController@store`: simpan wilayah via `collect->only`.
  * Test: `WilayahTest` (4) — list provinsi, filter prefix parent, guard tanpa-parent → kosong, RBAC non-wali 403.
  * Verifikasi: `php artisan test` = 80 passed (265 assertions), `npm run build` OK (14.4s), `view:cache` compile OK,
    `php artisan migrate` sukses (data live: 38 prov / 514 kota / 7285 kec / 83762 kel).
  * CATATAN: dropdown baru dipasang di form pendaftaran wali (guardian). Belum ditampilkan di profil siswa admin / edit —
    tambahkan bila diperlukan. Detail jalan tetap manual di textarea Alamat.
  * LANJUTAN 2026-07-17 (profil guru + hapus folder sumber):
    - Dropdown wilayah + alamat ditambah ke PROFIL GURU (guru/password.blade.php "Profil Saya"). Migration `000002` +8 kolom
      wilayah ke `teachers`; `GuruController@updateProfile` validasi +alamat+8 wilayah; `Teacher` fillable +9.
    - Route `wilayah` dipindah role:wali → grup `auth` polos (dipakai wali+guru); `wali/register.blade.php` ref disesuaikan
      (`wali.wilayah`→`wilayah`). `WilayahTest` kini 5 test (+guru boleh akses; RBAC→guest redirect /login).
    - SQL dump direlokasi `data-indonesia-master/` → `database/data/wilayah_indonesia.sql` (pakai `database_path()`); migration
      import tetap jalan (`migrate:rollback`+`migrate` re-import terverifikasi, 38 prov / 83762 kel utuh).
    - Folder `data-indonesia-master` DIHAPUS (JSON 91k file + Medoo.php + demo PHP tak dirujuk kode; hanya .sql yang dipakai,
      sudah diselamatkan ke database/data/). `migrate:fresh` masa depan tetap bisa re-import.
    - Verifikasi: `php artisan test` = 81 passed (268 assertions), `npm run build` OK, `view:cache` compile OK.
  * LANJUTAN#2 2026-07-17 (profil guru via CMS admin):
    - Modal tambah/edit guru admin (`admin/data/teachers.blade.php`) +4 searchable dropdown wilayah + textarea alamat.
      Modal x-model-driven → picker tulis ke `f.<key>_id/_nama`, hidden `:value`, reset child; `Js::from` row +alamat+8 wilayah;
      `blank`+`openEdit` generik (Object.fromEntries atas keys blank).
    - `MasterDataController` storeTeacher + updateTeacher: validasi +alamat+8 wilayah, simpan via `collect->only`.
    - Test: `AdminGuruViewsTest::test_teacher_update_and_destroy` +assert `provinsi_nama`.
    - Verifikasi: `php artisan test` = 81 passed (269 assertions), `npm run build` OK, `view:cache` OK.
    - SISA: profil siswa admin belum punya alamat wilayah (kolom belum ada di `students`).

### Fase J — Pindah alamat guardian → student (2026-07-17)
- [x] ✅ Fase J - Kolom alamat + wilayah dipindah dari `guardians` ke `students` `[Sukar]` (Selesai)
  * Alasan: siswa lama diinput admin/guru TANPA akun wali (`students.id_guardian` nullable) — alamat tak bisa disandarkan
    ke guardian. Alamat = atribut siswa (bisa beda antar kakak-adik). Plan: `.hermes/plans/2026-07-17_pindah-alamat-guardian-ke-student.md`.
  * Migration `2026_07_17_000003_move_alamat_wilayah_to_students`: students +`alamat`+8 wilayah; guardians −`alamat`−8.
    Data lama tak disalin (DB masih demo; migrate:fresh reseed).
  * `Student`: fillable +9; helper DRY `alamatRules()` (9 rule nullable) + `alamatData()` (filter kolom) — dipakai
    3 controller. `Guardian`: fillable −9.
  * Wali register (`wali/register.blade.php`): dropdown+alamat PINDAH Step 1 (Data Wali) → Step 2 (Data Anak), tulis ke student.
    `StoreRegistrationRequest` pakai `...Student::alamatRules()`; `Wali\RegistrationController@store` guardian update tinggal
    no_hp, `Student::create([...] + Student::alamatData($data))`.
  * Signup (`RegisterController`): buang param `alamat` vestigial (tak ada input di halaman signup).
  * `admin/registration-detail`: label "Alamat Siswa", baca `$student->alamat`.
  * Admin CRUD siswa (`MasterDataController::studentRules()` + `Student::alamatRules()`; store/update auto-simpan;
    `admin/data/students.blade.php` modal x-model + 4 picker + alamat, blank/openEdit generik).
  * Guru CRUD siswa (`GuruController` store+updateStudent + `Student::alamatRules()`; `guru/dashboard.blade.php` modal tambah
    + modal edit-profil, picker lokal per modal, edit prefill dari `$student`).
  * Seeder (`DemoSeeder`, guardian buang alamat) + 6 test (guardian create buang `'alamat'`); `WaliRegistrationTest`
    +assert `$student->alamat`.
  * Verifikasi: `php artisan test` = 81 passed (270 assertions), `npm run build` OK, `view:cache` OK,
    `migrate:fresh --seed`+DemoSeeder sukses (guardians.alamat GONE, students punya kolom wilayah).
  * Fix UX (2026-07-17): form Tambah siswa guru (`guru/dashboard.blade.php`) tambah `<template x-if="loading">Memuat…</template>`
    di dropdown wilayah agar seragam dgn form lain (feedback loading saat fetch di jaringan lambat).

### T2.4 — Uppercase input form (2026-07-17)
- [x] ✅ T2.4 - Semua input teks form user jadi HURUF KAPITAL `[Sedang]` (Selesai)
  * Middleware global `UppercaseInput` (web group, `bootstrap/app.php`): `Str::upper` tiap input string sebelum validasi.
  * SKIP: kredensial/identifier (email/password/no_hp/nik/nisn/nuptk/username/login), enum select
    (status/jenis/jenis_kelamin/role/keputusan/grup), prose (catatan/catatan_admin), kolom `*_id`.
  * Skip route `portal/admin/content*` (CMS konten publik) & `portal/admin/teachers/*/web` (jabatan tampil-web) — copy publik.
  * Test `Unit/UppercaseInputTest` + assertion feature-test (nama/alamat/kelas/guru → uppercase). 82 passed (277 assertions), build OK.

### T9.1 — Akun wali murid lama + paksa ganti password (2026-07-17)
- [x] ✅ T9.1 - Admin buatkan/tautkan akun wali siswa lama + ForceChangePassword `[Sukar]` (Selesai)
  * Plan: `.hermes/plans/2026-07-17_t9.1-akun-wali-lama.md`. Opsi A (tanpa self-claim/token).
  * Tombol "+ Akun Wali" per-baris Data Siswa (kondisional `!guardian?->user`) → modal nama+no_hp.
  * `MasterDataController@createWaliAccount`: guardian by no_hp → belum ada buat user(role wali,
    username=password=no_hp, must_change_password=true)+guardian; ada (kakak-adik) → tautkan akun sama. Route `admin.students.wali-account`.
  * Migration `users.must_change_password` (bool default false). Middleware `ForceChangePassword` (web group):
    wali/guru berflag di-redirect ke form ganti-password sampai ganti. Guru (password awal NUPTK) juga kena.
  * Form ganti-password WALI baru (belum ada sebelumnya): `WaliDashboardController@passwordForm/updatePassword`,
    `wali/password.blade.php`, route `wali.password(.update)`, sidebar. Clear flag saat ganti. Guru updatePassword idem.
  * Test `WaliAccountTest` (4): buat akun, kakak-adik 1 akun, wali dipaksa ganti, guru dipaksa ganti. 86 passed (295 assertions), build OK.

---
**Checkpoint terakhir (2026-07-16):** Backlog Fase A–G SELESAI. Terbaru: Fase E (T2.3 pengaturan akun wali/guru),
Fase F (T2.2/T5.6g/T8.1 rekening bank sekolah dari DB + dropdown bank asal searchable dari bank.csv),
Fase G (T5.6h verifikasi SPP terpisah + T6.4 guru edit profil). Audit P1.2 (cache/jobs) & T3.1 (eager-load, 1 N+1 fix) beres.
`php artisan test` = 75 passed (252 assertions), `npm run build` OK. Akun demo: wali@telaga.sch.id / rizal@telaga.sch.id (password);
guru.ahmad@telaga.sch.id atau 081311112222 / 1234567890123456 (NUPTK); admin di-seed password acak.
**Lanjut berikutnya:** SISA backlog — T1.1/T1.2/T1.3 (verifikasi email + lupa sandi, nyerempet deploy 3.C butuh SMTP).
Plus Task 3.C Deployment awal. (T7.1 curriculum slider = Fase H; T2.1 dropdown wilayah = Fase I — keduanya SELESAI.)
**Sisa opsional:** admin/payments halaman keuangan masih minimal; bank_asal belum tampil di reports/registration-detail.

---

## Audit Kesehatan Sistem + Sinkron test-scenarios.md (2026-07-17)
- [x] ✅ Cek error/fitur menyeluruh + update `.agents/test-scenarios.md` `[Sedang]` (Selesai)
  * Verifikasi otomatis: `php artisan test` = 86 passed (295 assertions); `npm run build` OK (727ms);
    `php artisan view:cache` semua Blade compile; `route:list` 73 route resolve.
  * Smoke test LIVE terhadap MySQL (bukan hanya SQLite test): boot `artisan serve`, uji 3 role —
    admin (11 halaman), wali (6 halaman + wilayah AJAX), guru (login via HP+NUPTK, 2 halaman).
    Semua → 200; dynamic binding (registrations/{form}, students/{id}) OK; RBAC lintas-role → 403;
    login salah → 302 (bukan 500). Log server & laravel.log TANPA error baru (113 error di log = histori lama 07-13..07-17 00:55).
  * KESIMPULAN: tidak ada fungsi/fitur yang error. Sistem sehat.
  * `test-scenarios.md` diselaraskan ke pengerjaan terkini (Fase D–J, T2.4, T9.1):
    - A2.2 login email/HP/username; A3.1/A3.8/A3.11 alamat+wilayah pindah ke student + dropdown berjenjang;
      A3.12 gate PPDB ditutup (403); A4.2 rekening sekolah + bank_asal; A6 pengaturan akun mandiri +
      ForceChangePassword; B5.1/B5.3/B5.6/B5.9 guru pakai NUPTK+no_hp (hapus ref `nomor_induk_guru`);
      B5.10 wilayah guru; B5.11 akun wali siswa lama; B6.5 curriculum slider; B6.6 PPDB/tahun-ajaran/set-pembayaran;
      C4 akun & profil guru; BAGIAN E baru (E1/E2 uppercase input); header + catatan verifikasi diperbarui.

## Username akun (signup wali + ganti-password guru) — 2026-07-17
- [x] ✅ Signup wali pakai Username (bukan Nama) + cek ketersediaan live `[Sedang]` (Selesai)
  * Form signup: field "Nama Wali Murid" → "Username". Masuk `users.username`. Nama wali diisi
    belakangan di CMS wali → `guardians.nama` dijadikan nullable (migration `..._000004`).
  * Constraint: unik, 6–30 char, regex `^[a-zA-Z0-9_.]+$` (tanpa spasi/tanda hubung/simbol), min 6.
  * Endpoint AJAX `GET /register/check-username?username=&min=` (RegisterController@checkUsername; min opsional, default 3) +
    Alpine debounce 400ms di form signup (indikator tersedia/dipakai/invalid, kirim `min=6`).
  * Test: `AuthTest::test_username_constraint_and_availability` (+spasi/tanda hubung/<6 char ditolak).
- [x] ✅ Form paksa-ganti-password guru + isian Username (min 6) `[Mudah]` (Selesai)
  * `guru/password.blade.php` form ganti-password: +field Username (prefill username saat ini) +
    cek live reuse endpoint check-username `?min=6`.
  * `GuruController@updatePassword`: +validasi username (required, min 6, regex, unique ignore-self)
    → simpan username + password + clear `must_change_password` dalam satu update.
  * `checkUsername` terima param `min` opsional (default 3; guru & wali kirim 6).
  * Label form guru dibetulkan: "nomor induk guru" → "NUPTK" (sisa pra-Fase D).
  * Test: `GuruTest::test_guru_change_password` (+username<6 ditolak, +assert username & flag clear).
- Password semua akun user-set SUDAH min 8 (RegisterController signup, GuruController/WaliDashboardController
  ganti, AccountController admin-buat). Password awal auto (guru=NUPTK, wali lama=no_hp) dipaksa ganti via ForceChangePassword.
- Verifikasi: `php artisan test` = 87 passed (309 assertions), `npm run build` OK, `migrate` OK.

## Bugfix form pendaftaran + Backlog Fase K bagian K0 — 2026-07-17
- [x] ✅ Fix tombol "Langkah Selanjutnya" mati di Step 1 form pendaftaran `[Mudah]` (Selesai)
  * Root cause: `wali/register.blade.php` validateStep(1) cek `$refs.alamat.value` padahal alamat sudah
    dipindah ke Step 2 (Fase J) → `undefined.value` TypeError → goToStep mati diam. Fix: buang cek alamat dari step 1.
- [x] ✅ [K0.1] Nama & no_hp wali tak masuk DB saat pendaftaran `[Mudah]` (Selesai)
  * Root cause: `Wali\RegistrationController@store` hanya update no_hp, abaikan nama_wali → guardians.nama NULL.
    Fix: update ['nama'=>nama_wali,'no_hp'=>...]. Test WaliRegistrationTest +assert nama.
- [x] ✅ [K0.3] Audit Log kosong (tabel mock statik) `[Mudah]` (Selesai)
  * Root cause: `admin/reports.blade.php` tabel Audit Log 4 baris hardcoded, tak pernah query audit_logs.
    Fix: `ReportController@index` kirim $auditLogs (AuditLog::with(user.guardian/teacher) latest 50);
    view @forelse dari DB + empty-state + format tanggal ID. Test +assertSee.
- [x] ✅ [K0.4→dibatalkan] HAPUS fitur delete data siswa & guru `[Sedang]` (Selesai)
  * Keputusan user: hard-delete tak standar SIS (data terikat keuangan/akademik/audit). Dibuang total:
    `destroyStudent`/`destroyTeacher` + route students.destroy/teachers.destroy + tombol Hapus di
    admin/data/{students,teachers}.blade. Fitur ubah-status keluar/pindah ditunda → notes.md K5.4 [DISKUSI].
  * K0.2 ditutup (bukan item koding: verifikasi tulis-DB = tanggung jawab test+transaksi+constraint).
- [x] ✅ Username wali min 6 + label guru NUPTK (lanjutan) `[Mudah]` (Selesai)
  * Signup wali: username min 3→6 (validasi+regex JS+hint+endpoint min=6). Label guru "nomor induk guru"→"NUPTK".
- Verifikasi: `php artisan test` = 87 passed (306 assertions), `npm run build` OK.
- Housekeeping: `.agents/notes.md` catatan testing bebas (41 poin) dikelompokkan → Backlog Fase K (K0–K10, +K5.4).

## Backlog Fase K bagian K1 — Validasi input (trust boundary) — 2026-07-18
- [x] ✅ [K1.1] Validasi + normalisasi No. HP `[Sedang]` (Selesai)
  * Aturan: angka saja, 9–14 digit. Normalisasi awalan 0→62 ("081..."→"6281...") di SATU titik:
    middleware `UppercaseInput@handle` (jalan tiap POST web, sebelum validasi & simpan) → semua form
    (wali/admin/guru) otomatis konsisten. Login (`AuthController@login`) juga menormalkan identifier no_hp
    agar cocok dg data tersimpan. Data lama TIDAK dimigrasi (hanya input baru — YAGNI).
- [x] ✅ [K1.2] Validasi Nama (huruf + spasi + titik + apostrof + tanda hubung) `[Mudah]` (Selesai)
  * regex `/^[A-Za-z .'\-]+$/` — tolak angka/simbol, terima nama sah ("M. RIDWAN", "SITI NUR'AINI",
    "ABDUL-AZIZ"). Keputusan user: mode longgar (bukan huruf-murni) agar tak blokir nama sah.
- [x] ✅ [K1.3] Validasi NIK `[Trivial]` (Selesai — verifikasi saja)
  * Sudah beres sejak awal: SEMUA form NIK pakai `digits:16` (memaksa angka murni + tepat 16 digit).
    Tidak ada form NIK tanpa validasi. Tanpa perubahan kode.
- DRY: helper terpusat baru `app/Support/ValidationRules.php` — `nama($required)`, `noHp($required)`,
  `normalizeNoHp()`, `messages()` (pesan ID). Dipakai ulang di StoreRegistrationRequest, RegisterController,
  AuthController, Wali/DashboardController, Guru/GuruController, Admin/MasterDataController (studentRules +
  createWaliAccount + store/updateTeacher). Pola mengikuti `Student::alamatRules()`.
- File diubah: app/Support/ValidationRules.php (baru), app/Http/Middleware/UppercaseInput.php,
  app/Http/Controllers/{Auth/AuthController,Auth/RegisterController,Wali/DashboardController,
  Guru/GuruController,Admin/MasterDataController}.php, app/Http/Requests/StoreRegistrationRequest.php.
- Test: tests/Unit/ValidationRulesTest.php (baru, 3 test) + sesuaikan assertion no_hp ke format
  ternormalisasi di WaliAccountTest/WaliViewsTest/GuruTest/AdminGuruViewsTest/AdminWorkflowTest
  (nomor fixture pendek non-valid dinaikkan ke 9–14 digit).
- Verifikasi: `php artisan test` = 90 passed (321 assertions), `npm run build` OK.

## Housekeeping skema + Backlog Fase K bagian K2 (sebagian) — 2026-07-18
- [x] ✅ Rapikan lebar kolom no_hp varchar(255)→varchar(20) `[Trivial]` (Selesai)
  * Migration 2026_07_18_000000_narrow_no_hp_columns: users/guardians/teachers.no_hp → varchar(20)
    (change(), pertahankan nullable/unique per tabel). no_hp dijamin 9–14 digit via K1.1.
  * Catatan tipe data: no_hp/nik/nisn SENGAJA varchar (bukan int) — digit identitas bisa leading-zero,
    tak dihitung, NIK 16-digit overflow INT. tanggal=date, uang=decimal(12,2) — semua sudah tepat.
- [x] ✅ [K2.2] Teks status pendaftaran per-state `[Mudah]` (Selesai)
  * wali/status.blade: menunggu_verifikasi → "Pembayaran…diverifikasi"; lainnya → "Berkas…diproses".
- [x] ✅ [K2.4] Hapus opsi "Draft" dari filter Kelola Pendaftaran `[Trivial]` (Selesai)
  * admin/registrations.blade: <option value="draft"> dibuang (sistem tak simpan draft belum-submit).
- [x] ✅ [K2.7] Halaman PPDB-ditutup di dalam layout CMS (bukan 403 telanjang) `[Sedang]` (Selesai)
  * create() → view wali/registration-closed.blade saat ditutup. Gate POST dipindah ke
    StoreRegistrationRequest@authorize (submit langsung → 403, bukan 422). ensurePpdbOpen() dihapus (redundant).
- Verifikasi: `php artisan test` = 90 passed (323 assertions), `npm run build` OK.

## Backlog Fase K bagian K2.1 — Gate wajib-isi-profil wali — 2026-07-18
- [x] ✅ [K2.1] Wali wajib lengkapi profil sebelum daftar/bayar `[Sedang]` (Selesai)
  * Migration 2026_07_18_000001: guardians +tempat_lahir/tanggal_lahir. Guardian::isComplete()
    (nama+no_hp+pekerjaan+TTL) + cast tanggal_lahir.
  * Form profil: wali/profile.blade + DashboardController@profileForm/@updateProfile (route wali.profile[.update]);
    no_hp disinkron ke users (identifier login), reuse ValidationRules (nama/noHp/messages).
  * Gate: middleware RequireGuardianProfile (web group, setelah ForceChangePassword). Redirect wali
    berprofil-belum-lengkap ke wali.profile — HANYA rute wali.* (guru/admin dibiarkan ke role:→403;
    account/password/logout di-allow agar tak terkunci). Sidebar +menu Profil Wali (desktop+mobile).
  * File: app/Http/Middleware/RequireGuardianProfile.php (baru), bootstrap/app.php (+append),
    app/Models/Guardian.php, app/Http/Controllers/Wali/DashboardController.php, routes/web.php,
    resources/views/wali/profile.blade.php (baru), layouts/dashboard.blade.php, tests/TestCase.php
    (+completeGuardian() helper) + 6 test fixture guardian dilengkapi + test baru wali_forced_to_complete_profile.
  * ponytail: field nama_wali/no_hp terselip di form daftar anak dibiarkan (redundan tapi harmless);
    hapus saat rework form daftar (K2.5).
- Verifikasi: `php artisan test` = 91 passed (329 assertions), `npm run build` OK.

## Backlog Fase K bagian K2.3 — Rename status + rapikan enum pendaftaran — 2026-07-18
- [x] ✅ [K2.3] Rename label status + rapikan enum registration_forms `[Mudah]` (Selesai)
  * Keputusan user: enum kanonik = submitted → menunggu_verifikasi → pembayaran_diverifikasi →
    diproses_seleksi → lulus/gagal. Status `draft` DIBUANG (tak pernah dipakai — sistem tak simpan draft).
    Status `menunggu_bukti` DIPERTAHANKAN (Opsi B) sebagai state koreksi: bukti bayar DITOLAK admin → wali upload ulang.
  * Label baru (3 blade statusMap: wali/dashboard, admin/registrations, admin/registration-detail):
    submitted="Sudah Submit", menunggu_bukti="Bukti Ditolak – Upload Ulang" (rose),
    menunggu_verifikasi="Menunggu Verifikasi Pembayaran", pembayaran_diverifikasi="Proses Verifikasi Berkas",
    diproses_seleksi="Proses Seleksi". Opsi filter admin/registrations disesuaikan (draft dibuang).
  * K2.3b timer 30dtk DIBATALKAN (redundan — transisi pembayaran_diverifikasi→diproses_seleksi sudah otomatis
    via advanceToSelectionIfReady saat semua dokumen diterima; timer artifisial dari mockup lama).
  * File: migration 2026_07_13_044704 (enum −draft, default submitted+komentar tiap state);
    wali/status.blade ($tahapMap −draft, $butuhBukti −draft, fallback ?? 'submitted');
    wali/dashboard.blade (statusMap + fallback ?? 'submitted'); admin/registrations.blade (statusMap + filter);
    admin/registration-detail.blade (statusMap). verifyPayment tolak→menunggu_bukti dibiarkan (Opsi B).
- Verifikasi: `php artisan test` = 91 passed (329 assertions), `npm run build` OK.

## Backlog Fase K bagian K2.5 — Edit/Update pendaftaran oleh wali — 2026-07-18
- [x] ✅ [K2.5] Wali edit/update pendaftaran `[Sedang]` (Selesai)
  * Keputusan user: wali boleh edit (termasuk upload ulang berkas) SELAMA pembayaran belum diverifikasi.
    Setelah bayar diverifikasi = final. Escape-hatch: admin bisa buka izin edit lagi walau proses berkas/seleksi.
    Efek samping (a): dokumen yg diganti reset ke 'pending'; bila form sudah 'diproses_seleksi' & ada berkas
    diganti → mundur ke 'pembayaran_diverifikasi' (uang tetap sah, hanya berkas diverifikasi ulang).
  * Gate: RegistrationForm::canBeEditedByWali() = status submitted/menunggu_bukti OR boleh_edit.
    Migration 2026_07_18_100000: registration_forms +boleh_edit (bool default false); model +fillable +cast +method.
  * Wali: RegistrationController@edit (reuse view register mode edit, prefill $form; abort 403 non-owner;
    redirect status bila tak boleh) + @update (validasi inline reuse Student::alamatRules; file nullable;
    hasFile→ganti+reset pending; boleh_edit selalu false; mundur status bila perlu). Route wali.register.edit/.update.
  * Admin: RegistrationController@allowEdit (boleh_edit=true + audit log). Route admin.registrations.allow-edit.
    Tombol "Buka Izin Edit Wali" di admin/registration-detail (muncul saat pembayaran_diverifikasi/diproses_seleksi).
  * View: register.blade dual-mode ($form/$s/$isEdit; prefill semua field anak+wilayah+alamat; Alpine file-required
    di-skip saat edit; tombol submit "Simpan Perubahan"). wali/status.blade tombol "Edit Pendaftaran" bila boleh.
  * Test: WaliRegistrationTest +3 (edit sebelum verifikasi; tolak 403 setelah verifikasi; admin reopen + reset efek a).
- Verifikasi: `php artisan test` = 94 passed (343 assertions), `npm run build` OK.

## Backlog Fase K bagian K6.2 — Bug dropdown PPDB sidebar admin — 2026-07-18
- [x] ✅ [K6.2] Dropdown PPDB collapse saat buka "Daftar Ulang" `[Trivial]` (Selesai)
  * Root cause: layouts/dashboard.blade $ppdbActive = routeIs('admin.registrations*') saja → saat di halaman
    admin.daftar-ulang, $ppdbActive=false → x-data open diinit false → dropdown tertutup meski sedang di child PPDB.
  * Fix: $ppdbActive += || routeIs('admin.daftar-ulang'). Mobile sidebar (link datar) tak terpengaruh.
- Verifikasi: `npm run build` OK, Blade compile OK (view:cache).

## Backlog Fase K bagian K3 — Halaman Detail & Verifikasi Pendaftaran (admin) — 2026-07-18
- [x] ✅ [K3.1–K3.6] Perbaikan halaman `admin/registration-detail` `[Sedang]` (Selesai)
  * [K3.1] Gate keputusan: tombol Lulus/Gagal HANYA muncul saat `formStatus === 'diproses_seleksi'`
    (bayar terverifikasi + semua dokumen diterima, otomatis via `advanceToSelectionIfReady`). Guard
    server-side di `RegistrationController@decide` (tolak + `withErrors` bila status != diproses_seleksi).
  * [K3.2] Verifikasi dokumen AJAX (tanpa refresh): `verifyDocument()` balas JSON saat `wantsJson()`
    (`ok`, `doc_status`, `form_status`, `boleh_diputuskan`); return type → `RedirectResponse|JsonResponse`.
    Blok Dokumen+Keputusan dibungkus satu Alpine `x-data` (fetch POST X-CSRF-TOKEN; `docs{}` + `formStatus`
    reaktif → badge dokumen & gate tombol keputusan update tanpa reload). Flash inline emerald/rose.
  * [K3.3] Setelah lulus/gagal → tombol "Ubah Keputusan" (`reopenDecision`: form → diproses_seleksi,
    siswa → aktif, audit `ubah_keputusan_seleksi`). Route `admin.registrations.reopen-decision`.
    Verifikasi dokumen tetap bisa diulang selama form belum lulus/gagal (bukan tombol Edit terpisah).
  * [K3.4] Input "Catatan (opsional)" di kartu verifikasi dokumen DIHAPUS (controller tetap terima nullable).
  * [K3.5] Biodata Calon Siswa +baris "Nama Panggilan" (`students.nama_panggilan`).
  * [K3.6] Data Wali Murid +Pekerjaan (`guardian->pekerjaan`) + "Alamat Siswa" jadi rangkaian berjenjang
    (alamat, kelurahan_nama, kecamatan_nama, kota_nama, provinsi_nama dari `students`, bagian kosong dilewati).
    BUGFIX: "Nama Wali" sebelumnya `$wali->nama` (kolom tak ada di `users` sejak Fase D → selalu "-") →
    `$guardian?->nama ?? $wali?->displayName()`.
  * Housekeeping: `$docStatusClass` (blade) dibuang — badge dokumen kini reaktif Alpine.
  * File: app/Http/Controllers/Admin/RegistrationController.php (+JsonResponse import, decide guard,
    reopenDecision, verifyDocument JSON), routes/web.php (+reopen-decision), resources/views/admin/registration-detail.blade.php.
  * Test: `AdminWorkflowTest` +3 (`test_decide_blocked_before_selection_stage`, `test_admin_can_reopen_decision`,
    `test_document_verification_returns_json_for_ajax`).
  * Verifikasi: `php artisan test` = 97 passed (352 assertions), `npm run build` OK, `view:cache` compile OK.
  * SISA Fase K: K4–K10 (belum). K3 semua selesai.

## Polish K3 lanjutan + Berkas/Storage — 2026-07-18
- [x] ✅ K3 UI polish: tombol Ubah (dokumen) + izin edit wali → button proper (bukan hyperlink) `[Trivial]` (Selesai)
  * Verifikasi dokumen K3.3-style: setelah Terima/Tolak, tombol hilang → badge status + tombol "Ubah"
    (reset lokal ke pending, buka Terima/Tolak lagi). Tombol Ubah dokumen + "Buka Izin Edit Wali" +
    badge "Izin Edit Wali Aktif" diganti dari teks-hyperlink → button/badge (border+icon+rounded).
- [x] ✅ Izin edit wali aktif → kunci verifikasi dokumen & keputusan `[Mudah]` (Selesai)
  * Alpine `bolehEdit` + getter `bolehVerifikasiDoc`/`bolehDiputuskan` (+`&& !bolehEdit`); pesan amber "terkunci".
  * Server guard: `verifyDocument` & `decide` tolak bila `$form->boleh_edit` (root-cause 2 titik, cegah request langsung).
  * Test: `AdminWorkflowTest::test_verification_locked_when_wali_edit_allowed`.
- [x] ✅ Thumbnail berkas + lightbox floating (dokumen pendaftaran & bukti) `[Sedang]` (Selesai)
  * Lightbox global di `layouts/dashboard.blade` (event `open-lightbox`): gambar `<img>`, PDF `<object>` inline
    + fallback link (klik luar/ESC tutup). Helper JS `openBerkas(url)` deteksi ekstensi. (IDM user sempat blok PDF—non-bug.)
  * Form wali `register.blade`: 3 blok upload dijadikan `@foreach` (−30 baris duplikat); preview file baru
    (gambar thumbnail / PDF ikon+nama via `URL.createObjectURL`); berkas LAMA saat edit ditampilkan
    ("Berkas saat ini" thumbnail klik-lihat) — sebelumnya kosong total di mode edit (izin-edit & bayar-belum-verified).
  * Admin `registration-detail`: "Lihat Berkas" (link tab) → thumbnail gambar/ikon-PDF klik→lightbox.
- [x] ✅ Bukti bayar rapi per-siswa + nama detail `[Sedang]` (Selesai)
  * `DocumentUploadService::storePaymentProof($file,$jenis,$namaSiswa,$tahunAjaran,$bulan=null)`:
    lokasi `pembayaran/{jenis}/{tahun-ajaran}/{Nama-Siswa}/`; nama `{Label}_{tahun}_[bulan]_{tgl}_{waktu}_{Nama}.ext`
    (Pendaftaran/Daftar-Ulang/SPP; SPP +segmen bulan). Ganti UUID lama. `Str` import dibuang.
  * Caller: StatusController (pendaftaran, load student+academicYear), PaymentController (resolveBill +return bulan;
    student->academicYear->tahun). Test: `WaliViewsTest::test_status_upload_proof_works` +assert path & file exist.
- [x] ✅ Hapus berkas lama saat replace/update (anti-sampah storage) `[Mudah]` (Selesai)
  * Dokumen pendaftaran (RegistrationController@update): hapus file lama sebelum ganti (beda ekstensi sekalipun,
    mis. KK.jpg→KK.pdf). Foto guru web (MasterDataController@updateTeacherWeb): hapus foto lama. CMS SiteContent
    sudah benar sebelumnya (tak diubah). Bukti bayar: TIDAK dihapus per-request (tiap upload=transaksi baru, bukan orphan).
  * Test: `WaliRegistrationTest::test_admin_can_reopen_edit_...` +assert file KK lama missing setelah replace.
- [x] ✅ Command `storage:reset` (bersihkan upload saat reset DB) `[Trivial]` (Selesai)
  * `app/Console/Commands/ResetStorage.php` (signature `storage:reset {--force}`): hapus folder
    `pendaftaran/`,`pembayaran/`,`konten/` di disk public. Jalankan bareng `migrate:fresh` agar path DB
    yang hilang tak menyisakan file yatim (bukti bayar/dokumen/foto konten pengujian).
- Verifikasi: `php artisan test` = 98 passed (362 assertions), `npm run build` OK, `storage:reset --force` hapus folder nyata.

## Backlog Fase K bagian K4 — Pembayaran & verifikasi (bank asal + tanggal/waktu) — 2026-07-18
- [x] ✅ [K4.1] Tampilkan bank asal + tanggal & waktu di semua tabel verifikasi bukti bayar `[Mudah]` (Selesai)
  * +kolom "Bank Asal" (`payment->bank_asal`) & "Tanggal & Waktu" di 3 tabel: `admin/registration-detail`,
    `admin/daftar-ulang`, `admin/spp`. Tanggal = `tanggal_bayar` (date, versi wali); Waktu = jam `created_at`
    ("HH:MM WIB", jam bukti masuk sistem — akurat, tak dipalsukan). `tanggal_bayar` hanya date (input date wali,
    tanpa jam) → zero migrasi. Colspan empty-state tiap tabel disesuaikan (+1).
- [x] ✅ [K4.2] Riwayat pembayaran wali: +Atas Nama anak + bulan SPP `[Mudah]` (Selesai)
  * `wali/payments` riwayat +kolom "Atas Nama" (`student->nama_lengkap`); jenis SPP +bulan tagihan
    (`translatedFormat('F Y')`). Relasi baru `PaymentTransaction::sppBill()` (`referensi_id`→`id_monthly_spp_bills`).
    `PaymentController@index` history eager-load `['student','sppBill']` (hindari N+1). Colspan 5→6.
- [x] ✅ [K4.3] Bukti PPDB nominal statis — tanpa koding (sudah statis, verifikasi cukup).
- [x] ✅ [K4.4] Hilangkan teks "bisa dicicil" di tagihan daftar ulang & SPP `[Trivial]` (Selesai)
  * `info.blade`: badge "Bisa Dicicil" + baris "✓ Boleh diangsur/dicicil" & "✓ Boleh dicicil/parsial" DIHAPUS
    dari kartu Biaya Daftar Ulang & SPP Bulanan (jangan pancing wali nyicil — cicilan hanya utk yg tak mampu, K10.4).
  * `wali/dashboard`: "Unggah bukti cicilan berikutnya" → "Unggah bukti pembayaran berikutnya".
  * `admin/dashboard` teks "cicilan" = mock statik notifikasi (bukan tagihan) → dibiarkan.
- Verifikasi: `php artisan test` = 98 passed (362 assertions), `npm run build` OK, `view:cache` compile OK.
- SISA Fase K: K5–K10 (belum). K0–K4 semua selesai.

## Backlog Fase K bagian K5 — Daftar Ulang & status keluar siswa/guru — 2026-07-18
- [x] ✅ [K5.4] Status keluar/aktif guru (ganti hard-delete) `[Sedang]` (Selesai)
  * Migration `..._110000_add_is_aktif_to_teachers` (bool default true). Teacher fillable+cast `is_aktif`.
  * Nonaktif = ex-guru: (a) DISEMBUNYIKAN dari list default (`teachers()` filter status aktif/nonaktif/semua);
    (b) DIBLOK LOGIN — `AuthController@login` cek role guru + `!is_aktif` → logout+error (root-cause 1 titik).
  * Toggle: `MasterDataController@toggleTeacherStatus` + route `admin.teachers.toggle-status` + tombol
    Nonaktifkan/Aktifkan + badge status + dropdown filter di `admin/data/teachers`. Audit `ubah_status_guru`.
  * Siswa: status (aktif/lulus/nonaktif) sudah bisa diubah via modal Edit existing — tak perlu tambahan (DRY).
- [x] ✅ [K5.1] Siswa PPDB baru muncul di Data Siswa setelah bayar daftar ulang `[Mudah]` (Selesai)
  * `MasterDataController@students`: `whereDoesntHave('registrationForms')->orWhereHas('reRegistrationPayments', terbayar>0)`.
    Siswa lama manual (tanpa formulir) selalu tampil (keputusan user); siswa PPDB sembunyi sampai terbayar>0.
- [x] ✅ [K5.2] Tagihan daftar ulang LUNAS hilang dari rekap `[Trivial]` (Selesai)
  * `RegistrationController@daftarUlang`: `$bills` where `status != 'lunas'`. Judul → "…, Belum Lunas".
- [x] ✅ [K5.3] Pisah tab Bukti vs List Siswa + badge + align-right `[Sedang]` (Selesai)
  * `admin/daftar-ulang`: Alpine tab {bukti|siswa}; badge rose `count($pending)` di tab Bukti; kolom
    Bukti/Aksi (tabel Bukti) & Status (tabel rekap) → `text-right`. Tabel Bukti +Bank Asal & Tanggal&Waktu (K4.1).
- Verifikasi: `php artisan test` = 100 passed (373 assertions), `npm run build` OK, `migrate` OK, `view:cache` compile OK.
  Test baru: `AuthTest::inactive_teacher_cannot_login`, `AdminGuruViewsTest::k5_student_filter_and_teacher_toggle`.
- SISA Fase K: K6–K10 (belum). K0–K5 semua selesai.

## Backlog Fase K bagian K6 — Sidebar & Dashboard admin (notifikasi & pemisahan) — 2026-07-18
- [x] ✅ [K6.4] Hapus tab "Verifikasi Pembayaran" (placeholder mati) + bereskan route ganda `[Trivial]` (Selesai)
  * Halaman placeholder yg cuma redirect ke Kelola Pendaftaran/Laporan → dibuang. Sekalian FIX BUG
    route `admin.payments` terdefinisi 2x (web.php:96 duplikat, konflik dgn `wali.payments`).
  * Hapus: link sidebar desktop (dropdown Pembayaran) + mobile, route placeholder, view
    `admin/payments.blade.php`, bagian `routeIs('admin.payments')` di `$bayarActive`.
  * Verifikasi bayar tetap jalan via registration-detail (PPDB) / daftar-ulang / spp (route `admin.payments.verify` beda, tetap ada).
- [x] ✅ [K6.3] Dashboard: pecah card "Pending Verifikasi" jadi 2 card + rincian jenis `[Sedang]` (Selesai)
  * Keputusan user: Opsi 1 (card terpisah), ukuran card disesuaikan bila padat. Card "Pending PPDB"
    (bukti bayar pendaftaran pending + dokumen KK/Akta/Foto pending) & "Pending Pembayaran" (daftar ulang +
    SPP) dengan RINCIAN jenis (mis. "1 daftar ulang, 3 SPP"). Grid 4→5 card, rounded-3xl/p-6 → 2xl/p-5,
    grid-cols-5, angka font diperkecil. Card PPDB jadi link ke Kelola Pendaftaran.
  * `Admin\DashboardController@index`: ganti `$pendingVerifikasi` tunggal → groupBy jenis
    (`$pendingPpdb`/`$pendingPembayaran`/`$pendingDaftarUlang`/`$pendingSpp`) + count `RegistrationDocument` pending.
  * BONUS (disetujui user): widget "Aktivitas & Log Sistem Terkini" (mock statik 3 baris "Bunda Amira" dst)
    diganti 5 `AuditLog` terbaru dari DB (@forelse, displayName+role+aksi+model_terkait, format tanggal ID).
- [x] ✅ [K6.1] Badge angka perlu-verifikasi per tab sidebar admin `[Sedang]` (Selesai)
  * Badge rose di: header dropdown PPDB (pendaftaran+daftar_ulang), child Pendaftaran Siswa, child Daftar Ulang,
    header dropdown Pembayaran (spp), child Verifikasi SPP — desktop DAN mobile. Badge PPDB termasuk
    dokumen pending (keputusan user).
  * `AppServiceProvider@boot`: `View::composer('layouts.dashboard')` (admin-only, 2 query: bukti bayar
    groupBy jenis + dokumen pending count) → share `$sidebarBadge`. Closure `$badge()` di blade render
    `<span>` rounded-full rose hanya bila count > 0.
- File: routes/web.php, resources/views/layouts/dashboard.blade.php, resources/views/admin/dashboard.blade.php,
  app/Http/Controllers/Admin/DashboardController.php, app/Providers/AppServiceProvider.php.
  Hapus: resources/views/admin/payments.blade.php.
- Test: `AdminGuruViewsTest::test_dashboard_split_and_sidebar_badge` (breakdown card + badge). 101 passed (378 assertions).
- Verifikasi: `php artisan test` = 101 passed, `npm run build` OK, `view:cache` compile OK, smoke browser
  admin (5 card, badge PPDB "5", widget log dari DB — bukan "Bunda Amira").
- Plan: `.hermes/plans/2026-07-18_fase-k6-sidebar-dashboard-admin.md`.
- SISA Fase K: K7–K10 (belum). K0–K6 semua selesai.

## Polish tab/slidebar admin (lanjutan K5.3/K6) — 2026-07-18
- [x] ✅ Slidebar/tab konsisten di 3 halaman Pembayaran/PPDB `[Sedang]` (Selesai)
  * daftar-ulang: SWAP urutan tab → List Siswa (kiri, default) → Bukti Pembayaran (kanan, badge pending).
  * spp: TAMBAH tab (dulu 2 card ditumpuk) → Tagihan SPP Bulanan (kiri, default) → Bukti SPP (kanan, badge pending).
  * settings (Set Pembayaran): TAMBAH tab → Nominal Biaya Dinamis → Rekening Bank Sekolah → Generate SPP.
    Nominal kini DROPDOWN pilih 1 item (Biaya Pendaftaran/Daftar Ulang/SPP/Tgl Generate/Kuota) + 1 input
    dinamis (`:name`/x-model Alpine, prefix Rp hanya utk nominal_*). Hanya item terpilih yang disubmit+disimpan.
  * SettingController@update: semua rule `required`→`sometimes` (simpan subset; hilangkan 5 hidden field di form rekening).
  * Badge sidebar Verifikasi SPP sudah ada sejak K6.1 (dikonfirmasi) — sejajar Pendaftaran Siswa & Daftar Ulang.
- File: resources/views/admin/{daftar-ulang,spp,settings}.blade.php, app/Http/Controllers/Admin/SettingController.php.
- Test: AdminWorkflowTest::test_admin_can_update_settings +assert partial-save (1 field tak sentuh field lain).
- Verifikasi: `php artisan test` = 101 passed (381 assertions), `npm run build` OK, `view:cache` OK, smoke browser 3 halaman.
- [x] ✅ Urutan sidebar admin: tab Pembayaran dipindah setelah Data Kelas (Konten Web kini di bawahnya) `[Trivial]` (Selesai)
  * layouts/dashboard.blade: desktop (dropdown) + mobile (flat) — urutan jadi Data Kelas → Pembayaran → Konten Web → Data Akun. `npm run build` OK, smoke browser OK.
- [x] ✅ Kolom Status tabel Tagihan SPP Bulanan (halaman Verifikasi SPP) → align-right `[Trivial]` (Selesai)
  * admin/spp.blade: header + cell Status `text-right` (selaras Nominal/Terbayar/Sisa; tak tabrakan). `npm run build` OK.

## Fase K7 (Konten Web CMS) + K8.1 (filter Data Siswa) — 2026-07-18
- [x] ✅ [K7.1] Tombol Tambah Curriculum keluar dari card → diperbaiki `[Sedang]` (Selesai)
- [x] ✅ [K7.2] Form konten sempit → modal floating (pola modal pembayaran) `[Sedang]` (Selesai)
  * content.blade.php dirombak: item fitur (statistik/program/kurikulum/misi/persyaratan/alur) kini baris ringkas
    dalam card; tombol "+ Tambah" di header card (tak overflow — fix K7.1); Edit/Hapus per baris. Tombol Tambah &
    Edit membuka MODAL floating lebar (`contentCms()` Alpine: satu modal fitur dual-mode create/edit + satu modal
    "Edit Teks" untuk key-value). Foto kurikulum & preview tampil di modal. Zero perubahan backend/route/test.
  * Verifikasi browser: modal Tambah & Edit render rapi, prefill judul/deskripsi/urutan/aktif benar.
- [x] ✅ [K8.1] Data Siswa: filter "Belum Dapat Kelas" `[Trivial]` (Selesai)
  * MasterDataController@students: id_class='none' → whereNull('id_class'). students.blade: opsi dropdown baru.
  * BAGIAN "nama wali kelas/guru" DI-PENDING (keputusan user): skema guru↔kelas many-to-many, tak ada konsep
    wali kelas tunggal. Perlu keputusan arsitektur (tambah kolom homeroom teacher) → tunda.
- File: resources/views/admin/content.blade.php, resources/views/admin/data/students.blade.php,
  app/Http/Controllers/Admin/MasterDataController.php.
- Verifikasi: `php artisan test` = 101 passed (381 assertions), `npm run build` OK, `view:cache` compile OK.
- SISA Fase K: K8.1 (wali kelas — pending), K9, K10.

## Fase K7 lanjutan (sub-tab + logo CMS) — 2026-07-18
- [x] ✅ [K7.3] Sub-tab (slidebar dalam halaman) per navbar Konten Web `[Sedang]` (Selesai)
  * content.blade: tiap tab utama (Beranda/Profil/Info/Kontak) kini punya baris sub-tab. Beranda = Teks Halaman,
    Statistik, Program/Fasilitas, Curriculum, Logo/Gambar; Profil = Teks + Misi; Info = Teks + Persyaratan + Alur;
    Kontak = Teks. Hanya 1 card yang tampil (x-show sub === id) → hilangkan scroll panjang. Alpine: `tab` (navbar)
    + `sub` (sub-tab); `setTab()` reset sub ke item pertama grup (map subFirst dari PHP).
- [x] ✅ [K7.4] Card Unggah Gambar dipindah + dibuat berguna (logo web dari CMS) `[Sedang]` (Selesai)
  * Card "Unggah Gambar/Logo" DULU tampil di paling bawah (nempel di tiap halaman) & dropdown target KOSONG
    (0 konten tipe image) → kini masuk sub-tab "Logo / Gambar" (hanya Beranda), + pratinjau slot gambar.
  * Slot image baru `home.logo` (SiteContentSeeder, tipe 'image') → dropdown target kini terisi.
  * Logo header+footer web (dulu hardcode kotak "AK") kini render dari CMS: `View::composer('layouts.public')`
    share `$siteLogo` = SiteContent home.logo; layouts/public.blade @if img else fallback "AK".
- File: resources/views/admin/content.blade.php, database/seeders/SiteContentSeeder.php,
  app/Providers/AppServiceProvider.php, resources/views/layouts/public.blade.php.
- Verifikasi: `php artisan test` = 101 passed (381 assertions), `npm run build` OK, `view:cache` OK,
  smoke `curl /` = 200 (logo fallback "AK" render 2x header+footer, no error). Jalankan
  `db:seed --class=SiteContentSeeder` (idempotent) agar slot home.logo tersedia.
- [x] ✅ [K7.5] Posisi tab+sub-tab tetap setelah aksi (tak balik ke Beranda) `[Trivial]` (Selesai)
  * content.blade: tab & sub aktif disimpan di URL QUERY STRING (?tab=&sub=), bukan hash. Init tulis query;
    klik tab/sub → syncUrl (history.replaceState). Form back() pakai Referer yg bawa query → posisi tetap.
  * KOREKSI PENTING: hash (#tab) TAK BISA dipakai — fragment hilang saat form POST & tak masuk Referer.
    Query string wajib. Syarat: controller pakai return back(). Verifikasi browser: edit Teks/Misi → Simpan →
    tetap di tab+sub yg sama (?tab=kontak&sub=teks / ?tab=profile&sub=misi).
  * BUGFIX lanjutan: sub-tab button masih panggil syncHash() (method dihapus saat refactor) → JS error →
    ?sub= tak tersimpan (Info/Profil/Beranda sub-lain balik ke Teks). Fix: ganti ke syncUrl().
- [x] ✅ [K7.6] Helper hashTabs() global untuk semua tab-halaman CMS `[Trivial]` (Selesai)
  * layouts/dashboard.blade: `hashTabs(fallback)` (tab dari QUERY ?tab=, setTab tulis query via replaceState).
    Dipasang di 3 halaman admin tab yg punya aksi submit (dulu reload balik ke tab kiri): daftar-ulang, spp,
    settings. (Nama fungsi 'hashTabs' historis — implementasi kini query string, bukan hash.)
  * Halaman lain aman: wali/status (switch antar-anak, tak submit-reload), login (signup tab),
    guru (semua modal/dropdown, bukan tab-halaman) — tak butuh.

## Fix filter Data Siswa + bug uppercase FK — 2026-07-18
- [x] ✅ Filter kelas Data Siswa auto-submit saat pilih dropdown `[Trivial]` (Selesai)
  * students.blade: dropdown id_class +onchange="this.form.submit()" (selaras dropdown status yg sudah auto).
- [x] ✅ BUGFIX: filter "Belum Dapat Kelas" (id_class=none) tak berfungsi + dropdown reset ke "Semua Kelas" (Selesai)
  * ROOT CAUSE: middleware UppercaseInput meng-uppercase id_class=none → NONE. Efek: controller where(NONE)→0 rows;
    blade @selected(===none) gagal → dropdown tampil "Semua Kelas". Skip *_id tak menangkap id_class (prefix, bukan suffix).
  * FIX: UppercaseInput skip juga prefix `id_` (konvensi FK id_<singular>) selain suffix `*_id`. Root-cause 1 titik
    → semua FK filter/form aman. Test Unit/UppercaseInputTest +assert id_class='none' tak berubah.
- File: app/Http/Middleware/UppercaseInput.php, resources/views/admin/data/students.blade.php,
  resources/views/admin/content.blade.php, resources/views/layouts/dashboard.blade.php, tests/Unit/UppercaseInputTest.php.
- Konvensi UI/UX baru didokumentasikan di rules.md §6 (hash-tab→query, sub-tab, modal, kapital, tanggal, DRY, eager, verifikasi).
- Verifikasi: `php artisan test` = 101 passed (382 assertions), `npm run build` OK, `view:cache` OK.
- SISA Fase K: K8.1 (wali kelas — pending), K9, K10.

## Fase K9 (Format & UX umum) — 2026-07-18
- [x] ✅ [K9.1] Format tanggal → Bahasa Indonesia `[Mudah]` (Selesai)
  * ROOT CAUSE: APP_LOCALE=en → Carbon `translatedFormat()` keluar Inggris ("February"). Semua tanggal
    yang sudah pakai translatedFormat (reports/dashboard/TTL biodata/SPP "F Y") pun sedang tampil Inggris.
  * FIX 1 TITIK: APP_LOCALE en→id (.env + .env.example) → seluruh translatedFormat lama & baru langsung
    Indonesia (Carbon lang 'id' sudah lengkap: "Februari", "Agustus"). `php artisan config:clear`.
  * +Ubah 7 tanggal TAMPILAN numerik format('d/m/Y')/('d M Y') → translatedFormat('d M Y') di:
    admin/registrations, wali/payments, guru/dashboard (catatan siswa), admin/spp, admin/daftar-ulang,
    admin/registration-detail, admin/data/student-profile.
  * TIDAK diubah: `<input type="date">` value tetap format('Y-m-d') (format wajib HTML5, bukan tampilan);
    reports/transactions-pdf tetap format numerik (dokumen cetak). Zero migrasi.
- [x] ✅ [K9.4] Tab "Akun & Profil" guru → sub-tab `[Trivial]` (Selesai)
  * guru/password.blade: 3 card (Profil / Ganti Password / Akun) numpuk → scroll panjang. Dibungkus
    `hashTabs('profil')` (helper global rules §6) + tab-bar 3 tombol; tiap card `x-show="tab==='...'"` x-cloak.
    Hanya 1 card tampil. View-only, zero backend/route/test.
- [x] ✅ [K9.3] Pagination server-side 25/halaman (4 datatable) `[Sedang]` (Selesai)
  * Keputusan user: cara B (server-side) — setelah deploy langsung diisi ratusan data.
  * ->paginate(25)->withQueryString() di: RegistrationController@index ($forms), MasterDataController@students
    ($students) & @teachers ($teachers); ReportController@index audit log ($auditLogs, ->paginate(25)).
    withQueryString() bawa filter/search di link halaman. Audit log dulu limit(50)→paginate.
  * Search Siswa/Guru sudah GET server-side sejak awal (native form request('cari')→where like) → langsung
    kompatibel, tak ada search client-side yg perlu dipindah.
  * Komponen reusable resources/views/components/paginate.blade.php (<x-paginate :paginator>), Tailwind v4,
    ringkas "n–m dari total · ‹ page/last ›". Dipasang di 4 view setelah </table>.
  * BUGFIX bonus (root cause): MasterDataController@teachers search `orWhereHas('user')` tak dibungkus closure
    → bocor keluar grup, mengabaikan filter is_aktif (ex-guru bisa muncul saat search). Dibungkus where(fn).
  * Test baru: AdminGuruViewsTest::test_students_paginated_25_per_page (26 siswa → hal.1 25 baris + link page=2;
    hal.2 tampil siswa ke-26).
- File: app/Http/Controllers/Admin/{RegistrationController,MasterDataController,ReportController}.php,
  resources/views/components/paginate.blade.php (baru),
  resources/views/admin/{registrations,data/students,data/teachers,reports}.blade.php,
  tests/Feature/AdminGuruViewsTest.php.
- [TUNDA] [K9.2] Mobile-friendly — belum (audit visual per halaman, scope terpisah).
- Verifikasi: `php artisan test` = 102 passed (387 assertions), `npm run build` OK, `view:cache` compile OK.
- SISA Fase K: K8.1 (wali kelas — pending), K9.2 (mobile — belum), K10.

## Fase K10.3 (CMS Wali — Data Anak read-only) — 2026-07-18
- [x] ✅ [K10.3] Halaman Data Anak wali + profil & catatan guru (read-only) `[Sedang]` (Selesai)
  * Diskusi: wali butuh jendela ke biodata anak + catatan perkembangan guru (progressNotes) — belum ada
    (dashboard/status cuma status pendaftaran/bayar). Read-only (wali baca, tak balas). Raport online = FUTURE,
    TIDAK discaffold sekarang (YAGNI).
  * DashboardController@children (list anak by id_guardian) & @childProfile (GATE keamanan: abort_unless
    $student->id_guardian === wali → 404, cegah wali intip anak wali lain). Eager progressNotes.teacher.
  * Route wali.children + wali.children.show/{student}. View wali/children.blade (kartu link per anak) +
    wali/child-profile.blade (biodata + catatan guru, tanggal translatedFormat ID). Sidebar +menu Data Anak
    (desktop+mobile) setelah Pembayaran.
- File: app/Http/Controllers/Wali/DashboardController.php, routes/web.php,
  resources/views/wali/{children,child-profile}.blade.php, resources/views/layouts/dashboard.blade.php,
  tests/Feature/WaliViewsTest.php.
- Verifikasi: `php artisan test` = 103 passed (393 assertions), `npm run build` OK, `view:cache` compile OK.
- SISA Fase K: K8.1 (wali kelas — pending), K9.2 (mobile — belum), K10.1 (tutup: no_hp bukan redundant),
  K10.2 & K10.4 (belum dibahas).

## Seragamkan lokasi foto profil (siswa lama & guru) — 2026-07-18
- [x] ✅ Foto CRUD siswa/guru pindah dari konten/ (flat+hash) → profil/{peran}/{Nama}/Foto_{Nama} `[Mudah]` (Selesai)
  * DocumentUploadService +storeProfilePhoto($file, $peran, $nama): profil/{siswa|guru}/{Nama-slug}/Foto_{Nama-slug}.ext
    (seragam pola rules §3; beda dg store() yg butuh tahun-ajaran — profil tak terikat TA).
  * Ganti 5 titik ->store('konten','public') → $uploader->storeProfilePhoto: MasterData storeStudent/updateStudent
    ('siswa'), storeTeacher/updateTeacherWeb ('guru'), GuruController storeStudent ('siswa'). Inject DocumentUploadService.
  * ResetStorage +folder 'profil' (foto profil ikut terhapus saat storage:reset — tak jadi yatim).
  * Foto pendaftaran siswa tetap di pendaftaran/{TA}/{Nama}/Foto_{Nama} (tak berubah). updateTeacher (edit guru non-web)
    memang tak punya slot foto — di luar scope permintaan.
- File: app/Services/DocumentUploadService.php, app/Http/Controllers/Admin/MasterDataController.php,
  app/Http/Controllers/Guru/GuruController.php, app/Console/Commands/ResetStorage.php.
- Verifikasi: `php artisan test` = 104 passed (401 assertions), `npm run build` OK.

## Foto profil siswa & guru — 2026-07-18
- [x] ✅ Pas foto ditampilkan di profil siswa (admin/wali/guru) & guru; upload saat daftar/CRUD `[Sedang]` (Selesai)
  * Migration 2026_07_18_120000: students +foto_path nullable (siswa lama boleh kosong; pola teachers.foto_path).
  * Siswa: pas foto pendaftaran (RegistrationDocument jenis='foto') OTOMATIS jadi foto profil — RegistrationController
    store & update sync path ke student.foto_path (tak upload ulang). CRUD siswa admin (storeStudent/updateStudent)
    & guru (storeStudent) +input file foto opsional → store('konten','public'); update admin hapus foto lama saat ganti.
  * Guru: teachers.foto_path sudah ada (updateTeacher sudah handle). +foto di storeTeacher (tambah guru pertama kali;
    disimpan sebelum DB txn, path masuk Teacher::create). Web daftar pendidik (profile.blade) sudah render foto_path.
  * Tampilan: komponen reusable resources/views/components/avatar.blade.php (<x-avatar :path :name :size> → foto
    bila ada, else inisial). Dipasang: admin/data/student-profile, wali/child-profile, guru/dashboard (thumbnail
    baris tabel siswa), guru/password (Profil Saya). Form modal siswa+guru +enctype=multipart/form-data +input file.
  * Foto CRUD disimpan di storage/public/konten → sudah dicakup command storage:reset.
- File: database/migrations/2026_07_18_120000_add_foto_path_to_students_table.php, app/Models/Student.php,
  app/Http/Controllers/Wali/RegistrationController.php, app/Http/Controllers/Admin/MasterDataController.php,
  app/Http/Controllers/Guru/GuruController.php, resources/views/components/avatar.blade.php,
  resources/views/{admin/data/students,admin/data/teachers,admin/data/student-profile,wali/child-profile,
  guru/dashboard,guru/password}.blade.php, tests/Feature/{WaliRegistrationTest,AdminGuruViewsTest}.php.
- Verifikasi: `php artisan test` = 104 passed (401 assertions), `npm run build` OK, `view:cache` compile OK.
- SISA Fase K: K8.1 (wali kelas — pending), K9.2 (mobile — belum), K10.1 (tutup), K10.2 & K10.4 (belum dibahas).

## Sesi 2026-07-19 — Foto profil (hardening) + Profil guru + K8.1 Wali Kelas + kolom tabel

Rangkaian pekerjaan sesi ini (detail teknis di `.agents/notes.md` tag [FOTO-FIX], [FOTO-FIX2],
[FOTO-DETAIL + PROFIL-TAB], [GURU-TABEL + PROFIL + NUPTK16], [SISWA-FILTER + GURU-PROFIL], [K8.1], [K8.2 kolom tabel]).

- [x] ✅ Foto guru via EDIT tersimpan (bug updateTeacher tak simpan foto) + nama kembar tak saling timpa
  * `storeProfilePhoto`/`store`/`storePaymentProof` +param `$id` (PK) → folder `{Nama-id}`. Semua pemanggil
    (Registration/Payment/Status/MasterData/Guru) teruskan PK; store-baru create dulu → upload pakai PK.
  * Modal edit siswa/guru tampilkan foto lama (`<template x-if="mode==='edit' && f.foto_path">`).
- [x] ✅ Foto tampil di header Detail & Verifikasi Pendaftaran + kartu anak wali/status (reuse `<x-avatar>`).
- [x] ✅ Halaman profil siswa admin dipecah tab (hashTabs: Biodata + Daftar Ulang/SPP/Riwayat).
- [x] ✅ Tabel Data Guru: padding antar-kolom, Kelas Diampu >1 → dropdown `<details>`, +tombol Profil →
  halaman `teacher-profile.blade` (showTeacher + route admin.teachers.show).
- [x] ✅ NUPTK wajib 16 digit — `ValidationRules::nuptk()` di storeTeacher & updateTeacher + form pattern.
- [x] ✅ Filter status Data Siswa default 'aktif' (dulu semua); +opsi "Semua Status".
- [x] ✅ Guru lihat profil siswa (`GuruController@showStudent`, guardStudent, reuse wali/child-profile).
- [x] ✅ **K8.1 Wali Kelas** — FK `classes.id_homeroom_teacher` (migration 2026_07_19_000000). 1 kelas=1 wali,
  1 guru maks 1 kelas (setHomeroom/setTeacherHomeroom lepas yang lama). Hak akses guru: wali kelas →
  tambah+edit+catatan+lihat; guru biasa → lihat+catatan. Gate guardHomeroom di updateStudent+storeStudent.
  Admin assign 2 arah (form Kelas + form Guru). UI guru: tombol Edit/+Tambah hanya untuk wali kelas.
- [x] ✅ Kolom tabel: Data Siswa admin (Nama·NISN·Wali Kelas·Kelas·Orang Tua·No.HP·Status·Aksi, NIK dibuang),
  Data Siswa guru (NIK→NISN + Wali Kelas), Data Guru (Nama→NUPTK→No.HP).
- File utama: migration homeroom, `app/Models/{SchoolClass,Teacher}.php`, `MasterDataController`, `GuruController`,
  `DocumentUploadService`, `ValidationRules`, views `admin/data/{students,teachers,classes,student-profile,
  teacher-profile}`, `guru/dashboard`, `wali/{status,child-profile}`, `admin/registration-detail`.
  Tests: GuruTest (+2), AdminGuruViewsTest (+3), AdminWorkflowTest (+1), WaliRegistrationTest (path).
- Verifikasi: `php artisan test` = **110 passed (432 assertions)**, `npm run build` OK, `view:cache` compile OK.
- SISA Fase K: K9.2 (mobile — audit clean, belum di-uji manual), K10.2 & K10.4 (belum dibahas). **K8.1 SELESAI.**

## Sesi 2026-07-19b — Analisis K10 + K10.1 (tutup) + K10.2 (Kelola Pembayaran)

Detail teknis di `.agents/notes.md` tag [K10.1], [K10.2].

- [x] ✅ **K10.1 [TUTUP]** — hapus `users.no_hp`? Diputuskan TIDAK: bukan redundant. `users.no_hp` =
  identifier login (unique constraint jamin 1 nomor = 1 akun, dipakai AuthController); `guardians/teachers.no_hp`
  = kontak profil. Zero koding — sinkronkan notes.md (session-log & tasklist sudah [TUTUP]).
- [x] ✅ **K10.2** — tab "Verifikasi SPP" → di-rename "Kelola Pembayaran" (skema B: pertahankan struktur
  2-tab existing, perkaya tab Tagihan). Perubahan:
  * `RegistrationController@spp(Request)`: +filter query `bulan` & `tahun` (`when()`), +eager `student.guardian`
    untuk kolom No. HP Wali, +`$bulanOpsi` (distinct bulan yang ada) & `$tahunOpsi` (academic_years) untuk dropdown.
  * `admin/spp.blade`: judul + tab → "Kelola Pembayaran"; tab Tagihan +dropdown filter bulan (format ID
    `translatedFormat('F Y')`) & tahun ajaran (GET + `tab=tagihan` agar posisi tab bertahan, rules §6.1) +tombol
    Reset; +kolom "No. HP Wali" (guardian.no_hp); kolom Bulan `YYYY-MM` → format Indonesia. colspan 7→8.
  * `layouts/dashboard.blade`: label sidebar (desktop+mobile) "Verifikasi SPP" → "Kelola Pembayaran".
  * Zero migrasi, zero route baru. Filter diverifikasi via Request langsung: no-filter=4, bulan=2026-07→1,
    tahun 2025/2026→1 (query controller benar; browser proxy sempat buang query param, bukan bug app).
- Verifikasi: `php artisan test` = **110 passed (432 assertions)**, `npm run build` OK, `view:cache` compile OK.
- SISA Fase K: K9.2 (mobile — audit clean, belum di-uji manual), K10.4 (cicilan opt-in — belum dibahas).

## Sesi 2026-07-19c — Polish keuangan + jabatan guru + profil ortu/wali kelas

Lanjutan iterasi UI/fitur (semua zero-migrasi). Detail teknis di `.agents/notes.md` tag [K10.2] LANJUTAN/#2,
[GURU-JABATAN + FOTO], [PROFIL-ORTU + WALIKELAS + GURU-VIEW].

- [x] ✅ **Tabel Kelola Pembayaran/SPP** — +search (nama/NISN/no_hp), +filter kelas, +kolom Bukti (thumbnail→
  lightbox), +pagination 25/hal. Model `MonthlySppBill::transactions()` (hasMany referensi_id, jenis=spp).
- [x] ✅ **Tabel Daftar Ulang jadi histori** — buang K5.2 (lunas TETAP tampil), +filter TA+status+search,
  +pagination, +Tanggal/Waktu/Bank dari transaksi. `ReRegistrationPayment::transactions()`.
- [x] ✅ **Reorder + rename kolom** kedua tabel per spec user (Siswa·Orang Tua·Tahun Ajaran·[SPP Bulan]·Tanggal·
  Waktu·Bank·Tagihan·Terbayar·Sisa·Bukti(center)·Status); +filter status di SPP.
- [x] ✅ **Kolom Murid tabel guru → center.**
- [x] ✅ **Jabatan guru** — +field jabatan di form guru admin (store/updateTeacher + modal) & profil guru CMS.
- [x] ✅ **Auto-fill jabatan wali kelas** — `setHomeroom`/`setTeacherHomeroom`: assign → jabatan='WALI KELAS',
  lepas → kosongkan HANYA bila masih 'WALI KELAS' (helper `clearWaliKelasJabatan`, jangan hapus jabatan manual).
  Guru wali kelas: field jabatan read-only.
- [x] ✅ **Foto profil guru CMS** — guru bisa upload foto sendiri (updateProfile +foto +enctype, hapus lama).
- [x] ✅ **Profil Orang Tua** — tab baru di profil siswa admin (student-profile) + guru/wali (child-profile);
  nama/HP/email/pekerjaan/TTL wali + alamat siswa. eager +guardian.
- [x] ✅ **Wali Kelas di profil siswa** — baris di biodata admin + header/biodata child-profile.
  eager +schoolClass.homeroomTeacher (3 controller).
- [x] ✅ **Profil guru CMS default = mode LIHAT** (avatar+dl read-only) + tombol Edit → form (x-data editing,
  tombol di kanan sejajar avatar, auto-buka bila validasi gagal).
- [x] ✅ **BUGFIX wali kelas tak tersimpan** — root cause di `classes.blade` `openEdit()`: `id_homeroom_teacher`
  (int) tak match `<option value>` (string) di Alpine x-model → dropdown Edit reset ke "Tanpa Wali Kelas", user
  simpan → null. Fix: `String(homeroom)`. Controller+relasi sudah benar (dibuktikan via simulasi updateClass).
- [x] ✅ **Default tab profil siswa admin** → 'orang-tua' (dulu 'daftar-ulang').
- Verifikasi: `php artisan test` = **110 passed (432 assertions)**, `npm run build` OK, `view:cache` compile OK.
- Catatan: data wali kelas lama tetap NULL — user set ulang lewat form Kelas (fix hanya perbaiki simpan ke depan).
- SISA Fase K: K9.2 (mobile — audit clean, belum di-uji manual), K10.4 (cicilan opt-in — MODE PLAN, lihat di bawah).

## Sesi 2026-07-19d — MODE PLAN K10.4 (belum koding)

Diskusi cicilan opt-in + Halaman Data Orang Tua. **Tidak ada perubahan kode.** Deliverable = plan draft.
Plan: `.hermes/plans/2026-07-19_k10.4-cicilan-optin-halaman-data-ortu.md`.

**Keputusan A — TERKUNCI:**
- Flag `boleh_cicil` di tabel `guardians` (per-wali).
- Bikin Halaman baru **Data Orang Tua** (datatable mirip Data Siswa: search + paginasi 25 + filter kelas +
  filter status). Kolom: Nama Ortu · No.HP · Pekerjaan · Anak · Wali Kelas · Kelas · Status · Aksi. Kolom
  Anak/Wali Kelas/Kelas ditumpuk **sejajar per anak** (dedup bila wali kelas & kelas sama). Aksi: lihat profil
  ortu (alamat + list anak), edit profil ortu, toggle izin cicil.
- Status ortu = turunan status anak: ada ≥1 anak `aktif` → boleh login; else **blokir login** (AuthController,
  pola guru `is_aktif`). Tanpa kolom baru di guardians untuk status.
- Filter kelas = wali punya ≥1 anak di kelas itu.
- Siswa `id_guardian` NULL = tak ada jalur izin cicil rilis ini (default harus-lunas); pakai T9.1 bila perlu.
- Ide user auto-create wali dari NIK→username/NISN→password **DITOLAK** (NIK/NISN identifier publik bukan
  rahasia → akun wali lama menganga sebelum login; duplikasi T9.1 yang sudah aman via no_hp diverifikasi admin).

**BELUM DIBAHAS (sesi berikutnya):**
- Keputusan B: scope tagihan yang dikunci harus-lunas (keduanya / SPP saja / Daftar Ulang saja).
- Keputusan C: bentuk penegakan tanpa izin (field nominal kunci=sisa read-only vs tolak server saat submit).
- Detail rendering kolom tumpuk-sejajar saat anak beda kelas.
- Finalisasi plan → eksekusi.

## Sesi 2026-07-20 — K10.4 EKSEKUSI (cicil per-JENIS) + web badge DU

**Keputusan final (mengganti plan lama):** aturan cicil per-JENIS, bukan per-wali (A1 + C1).
Plan `boleh_cicil` per-wali DIBATALKAN — user konfirmasi update: pendaftaran & SPP wajib lunas, daftar ulang boleh cicil.

- [x] ✅ **K10.4** Cicil per-jenis `[Sedang]` (Selesai 2026-07-20)
  * `Wali\PaymentController@store` — guard SPP: `jenis==='spp' && jumlah < sisa` → tolak.
  * `Wali\StatusController@uploadProof` — guard pendaftaran: `jumlah < nominal_pendaftaran` → tolak.
  * `wali/payments.blade.php` — SPP: tombol "Bayar Lunas", input readonly=sisa + hint; daftar ulang tetap "Bayar Cicilan".
  * `wali/status.blade.php` — input nominal pendaftaran readonly (dikunci ke `$nominalPendaftaran`).
  * `info.blade.php` — badge "Bisa Dicicil" DIKEMBALIKAN di kartu Daftar Ulang (dulu dihapus K4.4). SPP & pendaftaran tanpa badge.
  * Zero migration (tanpa kolom `boleh_cicil`). PaymentVerificationService tak disentuh.
  * Test `FinanceTest`: test_wali_can_pay_spp_in_full, test_partial_spp_payment_is_rejected, test_daftar_ulang_can_be_paid_partially.
- Docs sync: rules §1.3.2, workflow §1.1.5 + §2.2, test-scenarios A5.1/A5.9/A5.10/A5.11, notes.md K10.4 [DONE].
- Verifikasi: `php artisan test` = **112 passed (438 assertions)**, `npm run build` EXIT 0, `view:cache` compile OK.

- [DITUNDA] **K10.5** Halaman Data Orang Tua (opsi B dipilih) — eksekusi ditunda menunggu update user
  soal field profil ortu (nama Ayah/Ibu). Scope tersimpan di notes.md K10.5.

**FASE L — Backlog baru (2026-07-20):** lihat `.agents/notes.md` grup **FASE L** (L1–L9). Satu sumber; tasklist tak duplikasi.

- [x] ✅ **L5.3** Logout → web depan — SUDAH benar sejak awal (logout → route('home')), nol perubahan.
- [x] ✅ **L5.4** Login redirect bila sesi aktif (AuthController@showLogin → redirectByRole). (2026-07-20)
- [x] ✅ **L5.5** Slug URL (Student/Teacher/RegistrationForm). Trait `App\Models\Concerns\HasSlug` (auto-gen saat save,
  suffix `-{PK}` bila nama kembar, getRouteKeyName=slug). Migration +kolom slug 3 tabel + backfill. 9 blade route()
  ganti `->id_*` → model. Test URL segmen ikut ganti ke `->slug`. Self-check RelationshipTest::test_slug_uniqueness_for_namesakes.
  Admin-only models (SchoolClass/PaymentTransaction/User) TIDAK di-slug (PK numerik cukup).
- [x] ✅ **L5.1** UI Data Siswa cms guru → 2 card (chips kelas digabung ke Welcome card, card chips terpisah dibuang). (2026-07-20)
- [x] ✅ **L5.2** UI verifikasi bukti registration-detail: hyperlink → thumbnail+openBerkas (reuse spp.blade), kolom center. (2026-07-20)
- [x] ✅ **L2.3** Guard duplikat bukti bayar (3 layer): server guard PaymentController@store + StatusController@uploadProof;
  UI tombol "Sedang Diverifikasi" saat pending (payments.blade); eager-load transactions (nol N+1). Test x3. (2026-07-20)
- [x] ✅ **L4.2** Filter TA + status server-side (GET onchange) di Kelola Pendaftaran, default TA aktif. Reset dihapus.
  Test test_registrations_filtered_by_academic_year. (2026-07-20)
- [x] ✅ **L2.6** Kuota Pendaftaran dipindah dari tab Settings → modal float di Kelola Pendaftaran (tombol "Kuota (N)"). (2026-07-20)
- [x] ✅ **L2.5** FIX BUG: validasi usia pakai tahun AJARAN (angka pertama TA aktif) bukan now()->year; usiaRule() return
  array (bukan pipe string yg keliru jadi OR). TA 2027/2028 daftar Okt 2026 → cut-off 1 Juli 2027. Test baru. (2026-07-20)
- [x] ✅ **L6.1** Hapus fitur catatan siswa (sementara — tabel/model StudentProgressNote DIBIARKAN). Buang route
  guru.students.notes, GuruController@storeNote + eager progressNotes, UI (guru dashboard modal+button, wali child-profile tab).
- Verifikasi: `php artisan test` = **116 passed (455 assertions)**, `migrate` DONE, `npm run build` EXIT 0.

- [DITUNDA] [L3.3] Halaman Data Orang Tua (=K10.5) — nunggu L1.1 final. **L1.1 sudah final → bisa dikerjakan.**

- [x] ✅ **L1.3** Field guru: +jenis_kelamin + tanggal_mulai_mengajar. Migration 2026_07_21_000000.
  Admin storeTeacher/updateTeacher +validasi+simpan. GuruController updateProfile +validasi.
  Admin modal (teachers.blade) +JK dropdown+tgl mulai. Guru profil view+form +2 field. 125 passed, build ✓. (2026-07-21)
- [x] ✅ **L1.4** Rename "siswa" → "murid" di 18 blade + 4 controller flash/validasi. SKIP: tab-key Alpine,
  $peran storeProfilePhoto('siswa'), identifier kode ($student). 125 passed, build ✓. (2026-07-21)
- [x] ✅ **L1.1** Profil ortu → pisah Ayah & Ibu (flat ayah_*/ibu_* + checkbox ada_*). Kolom lama guardians
  (nama/no_hp/pekerjaan/ttl) DIHAPUS. namaWali()/noHpWali() default ibu→ayah. Alamat PINDAH students→guardians
  (1 set, balik C10). Migration rework_guardians (drop unique index dulu — SQLite fix) + add_profil_students.
  Models Guardian/Student/User. DashboardController updateProfile rewrite. wali/profile.blade 2 sub-form + _alamat-picker.
  RegistrationController search → ayah_no_hp/ibu_no_hp. DemoSeeder wali() → skema baru. 11 test file difix.
  UppercaseInput +enum L1 ke SKIP. 125 passed (479 assertions), build ✓. (2026-07-21)
- [x] ✅ **L1.2** Field murid baru (flat +13 kolom students). Student::profilRules() DRY. required_if cabang
  (sudah_mengaji→ngaji_*, pernah_belajar→belajar_keterangan). 3 form (wali register, admin CRUD, guru CRUD).
  Fix UppercaseInput skip enum mixed-case. Test admin/guru CRUD payload +L1.2. 125 passed, build ✓. (2026-07-21)
- Verifikasi L1 akhir: `php artisan test` = **125 passed (479 assertions)**, `npm run build` EXIT 0, `view:cache` OK.

- [x] ✅ **RENAME guardian→ortu** (2026-07-21) Rename entitas di SELURUH sistem + DB + label demi keterbacaan.
  Tabel `guardians`→`parents`, PK `id_guardians`→`id_parents`, FK `id_guardian`→`id_parent`. Model `App\Models\OrangTua`
  (bukan `Parent` — PHP reserved). Relasi `ortu()` (User+Student). Role `'wali'`→`'ortu'`. Route `wali.`→`ortu.` +
  `portal/wali`→`portal/ortu`. View dir `wali/`→`ortu/`. Middleware `RequireOrtuProfile` (+alias bootstrap/app.php).
  Helper `namaWali()`/`noHpWali()` tetap. Label UI "Wali"→"Orang Tua". `isWaliKelas`/`homeroom` TAK disentuh.
  Verifikasi: `php artisan test` = **125 passed (479)**, `npm run build` OK, `dump-autoload` + `view:cache` OK.

- [x] ✅ **Fix sidebar ortu kosong** (2026-07-21) Role DB `'ortu'` tapi sidebar cek `=== 'orang tua'` (label lama) → menu kosong.
  Fix: `=== 'ortu'` di 2 blok (desktop+mobile) layouts/dashboard.blade + accounts filter value/badge key. Admin/guru tak terdampak.

- [x] ✅ **Validasi client form daftar + cabang** (2026-07-21) (a) Step-1 13 field L1.2 baru → `[required]` + loop `querySelectorAll`.
  (b) pekerjaan_lain `:required` + server `required_if:LAINNYA`. (c) ngaji_dimana/metode/jilid + belajar_keterangan `:required` binding Alpine.
  Server-side `required_if` sudah ada di `Student::profilRules()`. 125 passed, build ✓.

- [x] ✅ **Input numerik-only** (2026-07-21) `inputmode="numeric" pattern` di 10 blade (no_hp/nik/nisn/nis/nuptk).
  1 delegated handler di layouts/dashboard.blade footer: keydown block non-digit + input event strip paste. Build ✓.

- [x] ✅ **Kolom NIS di students** (2026-07-21) Migration `add_nis_to_students_table` (string 18, nullable unique).
  Fillable Student + `digits_between:15,18` unique di MasterData (store+update) + Guru (store+update) + UppercaseInput skip.
  Field NIS di form tambah/edit admin (Alpine `f.nis` + `blank.nis`) & guru. 125 passed, build ✓.

- [x] ✅ **Git init + push ke GitHub** (2026-07-21) Repo github.com/abyan28/telaga (initial commit, 220 files).
  Update via: `git add -A && git commit -m "pesan" && git push`.

- [x] ✅ **L6.2** Biodata lengkap di halaman Detail & Verifikasi Pendaftaran (2026-07-21).
  Card setelah bukti bayar: Biodata Calon Murid full-width (semua field form pendaftaran + NIS + alamat keluarga),
  grid 3 kolom di lg. Di bawahnya 2 card berdampingan: Biodata Ayah & Biodata Ibu (nama, TTL, agama, pendidikan,
  pekerjaan+lainnya, penghasilan, no HP; tampil "tidak diisi" bila ada_ayah/ada_ibu=false).
  Hanya view — nol migration/route/controller. view:cache OK, 21 AdminGuruViewsTest hijau.

- [x] ✅ **L2.1** Calon Murid + Generate NIS + Batal + Pengaturan Sistem (2026-07-21).
  * Halaman Calon Murid (admin.calon-murid): datatable siswa lulus+bayarDU>0+nis null. Kolom NIS setelah Orang Tua.
    Tombol Generate NIS (gated PPDB tutup + NSM 12 digit): sort abjad, nis=NSM+YY+urut3, status→aktif, batch 1 POST.
    Tombol Batal per-baris: refund=max(0,terbayar−denda), denda=(100−persen_refund)%×total. Terbayar<denda → teks
    "Lunasi Rp X dulu" (bukan tombol). Catat PaymentTransaction jenis=refund (nullable bukti_path).
  * Pengaturan Sistem (admin.system): tab sidebar baru bawah Konten Web (desktop+mobile). 3 sub-tab: NSM (12 digit) /
    Refund % / Tahun Ajaran (dipindah dari Set Pembayaran). SettingController@system/updateSystem.
  * Tab Tahun Ajaran dihapus dari Set Pembayaran (sekarang di Pengaturan Sistem).
  * Data Murid: syarat K5.1 diganti → nis IS NOT NULL (bukan terbayar>0; calon belum ber-NIS tak tampil di Data Murid).
  * Gate login ortu: PPDB tutup + tak ada anak aktif/calon-lulus/dibatalkan-utang-denda → blokir. PPDB buka → hidup lagi.
  * Dashboard Keuangan Masuk: exclude jenis='refund'.
  * Migration: +enum refund/dibatalkan di CREATE migrations (SQLite+MySQL fresh) + ALTER MySQL-only untuk DB live;
    bukti_path nullable.
  * Test: 1 test update (K5.1→L2.1 nis-based) + 1 test baru L2.1. 126 passed (493 assertions), build ✓.
  * FIX (2026-07-22): persen_refund = persen DENDA langsung (bukan 100−x). Blade/controller/gate login diselaraskan. Default 30.

- [x] ✅ **L8.1 SELESAI** Auto-akun ortu + Import CSV massal murid lama (2026-07-22/23).
  Form Tambah Murid admin +field "No. HP Orang Tua" (mode create). Diisi → `MasterDataController::linkOrtu()`
  (reuse bareng createOrtuAccount T9.1). Password = NIK anak. Merge kakak-adik by no_hp.
  Import CSV massal: `importForm`/`importStudents`/`downloadTemplate` di MasterDataController.
  Route `admin.students.import(.run/.template)` — SEBELUM `{student}` (cegah slug tertangkap wildcard).
  View `admin/data/import.blade.php`: upload + hasil (✓/⏭/✗) + tabel info kolom.
  Sidebar: "Data Murid" → dropdown (Daftar Murid + Import CSV) desktop+mobile.
  fgetcsv stdlib (nol library). Opsi B skip+report (NIK duplikat dilewati, append-only).
  Template `database/data/template-import-murid.csv`. Auto-akun ortu via `linkOrtu` bila `no_hp_ortu` diisi.
  PITFALL: enum kosong & kolom unik kosong → null sebelum create (SQLite CHECK constraint).
  Test `ImportCsvTest` x6. 134 passed (514 assertions), build ✓.

- [x] ✅ **L8.2 SELESAI** Export CSV/PDF data siswa + laporan SPP (2026-07-23).
  ReportController +6 method + 2 private query helper (studentsQuery/sppQuery, pola ->when() existing).
  Route admin.reports.students(.csv/.pdf) + admin.reports.spp(.csv/.pdf).
  View admin/reports-students.blade + reports-spp.blade + 2 PDF blade (dompdf landscape).
  Sidebar: "Laporan" DROPDOWN (Data Murid + Laporan SPP) di atas "Audit Log" (rename dari Laporan & Audit Log).
  CSV siswa 33 kol (eager ortu+schoolClass.homeroomTeacher). CSV SPP 11 kol (tanggal/waktu dari transaksi diverifikasi).
  PITFALL: status_bayar di-UPPERCASE oleh middleware → strtolower() sebelum compare.
  Test ReportExportTest x6. 139 passed (529), build ✓.

- [x] ✅ **L8.3 SELESAI** Kolom angkatan otomatis dari prefix NIS (2026-07-23).
  Migration add_angkatan_to_students (SMALLINT null). Student +fillable+cast+nisToAngkatan().
  Trigger otomatis: generateNis+storeStudent+updateStudent+importStudents (NIS > manual).
  Form tambah/edit murid +field Angkatan. Profil siswa +NIS+Angkatan. Laporan siswa +filter angkatan.
  PITFALL: closure generateNis butuh use($yy); $data['angkatan'] null-coalesce di import.
  139 passed (529), build ✓.

**SISA backlog WARISAN (belum masuk Fase L):**
- Task 3.C — Deployment awal.
- [L9.1] [=T1.1/T1.2/T1.3] Verifikasi email + lupa sandi (SMTP).

---

## Fase M — CMS Konten, Bug PPDB, UX (2026-07-22)

- [x] ✅ **M1 SELESAI** CMS konten footer/header hardcode → DB (2026-07-22).
  Footer+header `layouts/public.blade.php`: nama sekolah, sub-nama, deskripsi, alamat, telp, email, jam operasional, copyright → `$kontak[...]` dari View::composer (1 query grup home+kontak).
  SiteContentSeeder +4 key (kontak.sub_nama, kontak.deskripsi_footer, kontak.copyright, jam_operasional sudah ada).
  140 passed, build ✓.

- [x] ✅ **M2 SELESAI** Logo & favicon dari CMS lintas 3 layout (2026-07-22).
  `SiteContent::logoUrl()` helper statis. layouts/dashboard (sidebar desktop+mobile), auth/login (header brand), layouts/public — semua pakai logo CMS, fallback "AK". Favicon `<link rel="icon">` di `<head>` ketiga layout.

- [x] ✅ **M3 SELESAI** Bug fix PPDB: status form tidak maju setelah verifikasi bayar (2026-07-22).
  Root cause: `PaymentVerificationService::syncBill()` tidak handle `jenis=pendaftaran`.
  Fix: cabang pendaftaran — approve→pembayaran_diverifikasi; reject→menunggu_bukti. `->first()` hindari stale relasi.
  Test: `test_registration_payment_verification_advances_form`.

- [x] ✅ **M4 SELESAI** Badge PPDB: 1 per pendaftar (bukan per-item) (2026-07-22).
  AppServiceProvider: badge[pendaftaran] = count form status {menunggu_verifikasi,pembayaran_diverifikasi,diproses_seleksi}.
  DashboardController: pendingPpdb = count form. pendingPembayaran += ppdbBayarBelumVerif (form menunggu_verifikasi).
  Card breakdown dashboard +rincian "N PPDB". Test diperbarui.

- [x] ✅ **M5 SELESAI** Bank: CSV → tabel DB + Alpine searchable dropdown (2026-07-22).
  Migration create_banks_table. BankSeeder impor 110 bank via upsert. bank.csv dihapus.
  `Bank::daftarNama()` ganti `PaymentTransaction::daftarBank()` (dihapus). DatabaseSeeder +BankSeeder.
  Component `<x-bank-picker>` (Alpine: filter max 50, nilai bebas). ortu/status + ortu/payments diperbarui.

- [x] ✅ **M6 SELESAI** Show/hide password semua form (2026-07-22).
  Component `<x-password-input>` (Alpine show/hide, eye/eye-off SVG).
  Diterapkan: auth/login (login+signup), ortu/password, guru/password, admin/accounts.

- [x] ✅ **M7 SELESAI** Username + cek realtime di pengaturan akun ortu (2026-07-22).
  `DashboardController::updateAccount` tambah validasi username (Rule::unique ignore self, regex, min:3).
  View ortu/account ditulis ulang: field username + Alpine fetch check-username (debounce 500ms, endpoint existing).
  Tombol disabled saat taken/invalid.

- [x] ✅ **M8 SELESAI** Account-setup gate: akun auto-create ortu wajib isi akun sebelum profil (2026-07-22).
  `User::needsAccountSetup()` — email null OR username===no_hp.
  `RequireOrtuProfile` 3 langkah: (1) ForceChangePassword existing, (2) needsAccountSetup→ortu.account, (3) isComplete→ortu.profile.
  ponytail: proxy username===no_hp; add kolom flag jika proxy miss edge case.

- [x] ✅ **M9 SELESAI** Email wajib saat first login guru (2026-07-22).
  `GuruController::updatePassword` tambah validasi+simpan email (required, unique ignore self).
  Form tab password guru +field Email. Test diperbarui (+email).
  140 passed (533 assertions), build ✓.

## Fase N — Data Orang Tua (2026-07-22)

- [x] ✅ **N1 SELESAI** L3.3 Data Orang Tua (2026-07-22).
  `MasterDataController@parents`: list ortu (whereHas students) + 3 filter (cari ayah/ibu/anak, kelas, status) + paginate 25.
  View `admin/data/parents.blade.php`: rowspan kolom ortu/aksi, anak per-baris. Kolom: Ayah·Ibu·Nama Anak·No.HP·Pekerjaan·Wali Kelas·Kelas·Status·Aksi.
  Sidebar desktop+mobile: "Data Orang Tua" antara Konten Web & Pengaturan Sistem.
  Route `admin.data.parents` reuse grup data.*. Aksi Profil → student-profile tab ortu. Edit → redirect Data Murid (search).
  Test `test_parents_page_lists_and_filters`. 141 passed (540 assertions), build ✓.
  ponytail: no dedicated edit-ortu page, add when diminta.
  L3.1/L3.2 solved by user (tidak dikerjakan).
