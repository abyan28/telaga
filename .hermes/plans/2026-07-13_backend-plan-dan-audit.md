# Plan Backend & Audit - Sistem Informasi RA Al Kautsar

**Goal:** Menyiapkan pengerjaan Tahap 2 (Backend Laravel) di atas frontend mockup yang sudah 100% selesai, sekaligus memperbaiki dulu semua temuan/error dari hasil scan.

**Arsitektur target:** Laravel 13 + MySQL + Tailwind v4/AlpineJS. RBAC via middleware, service layer terpisah (upload, pembayaran parsial, export, email), konvensi PK/FK non-standar sesuai rules.md (id_(nama_tabel) / id_(singular)).

**Status:** Frontend selesai (view mockup semua ada, data hardcoded di Blade/Alpine). Backend = 0% (hanya skeleton default Laravel). Dokumen DISKUSI + rencana, belum ada eksekusi.

---

## BAGIAN A - Kondisi Lingkungan (verifikasi read-only)

- PHP 8.5.8 (composer minta ^8.3 -> cocok)
- Laravel 13.19.0, vendor terinstall
- Ekstensi PHP: pdo_mysql, mysqli, mysqlnd, fileinfo, zip AKTIF. gd TIDAK ada (lihat B-2)
- Node/npm ada
- MySQL server HIDUP, DB `telaga` bisa diakses, migrasi default sudah Ran (Batch 1)
- Vite build sudah ada di public/build
- Terminal bash Hermes RUSAK: semua perintah dialihkan ke wsl.exe (no distro). Workaround: jalankan artisan via subprocess/Python; perlu dibereskan.

---

## BAGIAN B - TEMUAN / ERROR / CATATAN (perbaiki dulu)

### B-1. Nama DB `telaga` = DESAIN, bukan bug [SELESAI]
Klarifikasi user: TELAGA = Teknologi Layanan Akademik & Guru Al Kautsar (nama sistem). DB `telaga` final & benar. DB terverifikasi bersih (hanya 3 migrasi default Laravel, belum ada tabel proyek lain). Dikerjakan: APP_NAME diset "TELAGA AL KAUTSAR" di .env & .env.example; ditambah komentar penjelas di atas baris DB_DATABASE. Verifikasi: config app.name=TELAGA AL KAUTSAR, database=telaga.

### B-2. Ekstensi gd tidak aktif
Hanya perlu jika mau resize/thumbnail gambar upload. PRD cukup simpan+validasi -> belum wajib MVP. Catatan, bukan blocker.

### B-3. database/database.sqlite (0 byte) nyangkut [SELESAI]
Sisa scaffold, proyek pakai MySQL. Dikerjakan: file dihapus.

### B-4. public/storage symlink belum dibuat
Upload butuh symlink. Fix: php artisan storage:link (saat mulai modul upload).

### B-5. .env.example tidak sinkron dengan .env [SELESAI]
Dikerjakan: .env.example diselaraskan ke DB_CONNECTION=mysql, host/port, DB_DATABASE=telaga, user root, password kosong, + APP_NAME "TELAGA AL KAUTSAR".

### B-6. Sisa scaffold default (rapikan bertahap)
DatabaseSeeder masih buat Test User; ExampleTest (Feature+Unit) bawaan; README default Laravel. Ganti saat masuk modul terkait.

### B-7. Font di-load ganda (potensi inkonsistensi)
Layout pakai <link> fonts.bunny.net (CDN) SEKALIGUS ada font woff2 lokal hasil build. Offline -> font CDN gagal/fallback. Rendah prioritas; pilih salah satu.

### B-8. SUDAH BERSIH (audit positif)
- Semua route('...') di view cocok dengan web.php -> tidak ada RouteNotFoundException
- x-cloak sudah punya handler CSS di app.css
- Tidak ada <form method=POST> action mati (semua mock via Alpine)
- laravel/pao BUKAN typo - paket asli v1.1.2 ada di lock
- Koneksi MySQL sukses (error db:show hanya karena performance_schema disabled, non-fungsional)

---

## BAGIAN C - KEPUTUSAN ARSITEKTUR (SEMUA RESOLVED)

Semua poin sudah difinalisasi bersama user & tercatat di rules.md/workflow.md.

1. [RESOLVED] Nama DB = telaga (TELAGA = Teknologi Layanan Akademik & Guru Al Kautsar). APP_NAME = "TELAGA AL KAUTSAR". [rules bkn perlu]
2. [RESOLVED - C2] Auth = SATU tabel users + kolom role (wali/admin/guru) + middleware role (Opsi A). Wali self-signup (role dikunci 'wali'); Guru dibuat Admin via CMS, password awal = Nomor Induk Guru; Admin multi-admin, admin pertama via Seeder (kredensial di-generate). -> rules.md §1.5, workflow.md §3.
3. [RESOLVED - C3] Ikuti konvensi PK/FK rules.md: PK id_(tabel), FK id_(singular). Model wajib set $table/$primaryKey; relasi & migration FK eksplisit; tiap relasi diuji. -> rules.md §2.1-2.3.
4. [RESOLVED - C4] Profil terpisah relasi 1-1: guardians (wali) & teachers (guru) FK id_user -> users. Admin tanpa tabel profil. students berdiri sendiri (bukan akun login). -> rules.md §1.6.
5. [RESOLVED - C5] Email notifikasi: MAIL_MAILER=log saat dev, SMTP asli saat produksi. -> rules.md §2.4.
6. [RESOLVED - C6] Generate SPP: Laravel Scheduler + command `spp:generate` (monthlyOn(1), idempotent firstOrCreate) + tombol manual di CMS sebagai cadangan. -> rules.md §1.3.5, §2.4.
7. [RESOLVED - C7] Export PDF pakai barryvdh/laravel-dompdf; CSV native (tanpa paket). -> rules.md §2.4.
8. [RESOLVED - C8] Mock role switcher (?role=) diganti login asli setelah auth jadi (frontend diadaptasi).
9. [RESOLVED - C9] academic_years: format "2026/2027" (folder upload jadi "2026-2027"); hanya SATU aktif per waktu (is_aktif); dikelola Admin via CMS + Seeder buat 1 tahun awal. -> rules.md §1.7.

---

ackend & Audit - Sistem Informasi RA Al Kautsar

**Goal:** Menyiapkan pengerjaan Tahap 2 (Backend Laravel) di atas frontend mockup yang sudah 100% selesai, sekaligus memperbaiki dulu semua temuan/error dari hasil scan.

**Arsitektur target:** Laravel 13 + MySQL + Tailwind v4/AlpineJS. RBAC via middleware, service layer terpisah (upload, pembayaran parsial, export, email), konvensi PK/FK non-standar sesuai rules.md (id_(nama_tabel) / id_(singular)).

**Status:** Frontend selesai (view mockup semua ada, data hardcoded di Blade/Alpine). Backend = 0% (hanya skeleton default Laravel). Dokumen DISKUSI + rencana, belum ada eksekusi.

---

## BAGIAN A - Kondisi Lingkungan (verifikasi read-only)

- PHP 8.5.8 (composer minta ^8.3 -> cocok)
- Laravel 13.19.0, vendor terinstall
- Ekstensi PHP: pdo_mysql, mysqli, mysqlnd, fileinfo, zip AKTIF. gd TIDAK ada (lihat B-2)
- Node/npm ada
- MySQL server HIDUP, DB `telaga` bisa diakses, migrasi default sudah Ran (Batch 1)
- Vite build sudah ada di public/build
- Terminal bash Hermes RUSAK: semua perintah dialihkan ke wsl.exe (no distro). Workaround: jalankan artisan via subprocess/Python; perlu dibereskan.

---

## BAGIAN B - TEMUAN / ERROR / CATATAN (perbaiki dulu)

### B-1. Nama DB `telaga` = DESAIN, bukan bug [SELESAI]
Klarifikasi user: TELAGA = Teknologi Layanan Akademik & Guru Al Kautsar (nama sistem). DB `telaga` final & benar. DB terverifikasi bersih (hanya 3 migrasi default Laravel, belum ada tabel proyek lain). Dikerjakan: APP_NAME diset "TELAGA AL KAUTSAR" di .env & .env.example; ditambah komentar penjelas di atas baris DB_DATABASE. Verifikasi: config app.name=TELAGA AL KAUTSAR, database=telaga.

### B-2. Ekstensi gd tidak aktif
Hanya perlu jika mau resize/thumbnail gambar upload. PRD cukup simpan+validasi -> belum wajib MVP. Catatan, bukan blocker.

### B-3. database/database.sqlite (0 byte) nyangkut [SELESAI]
Sisa scaffold, proyek pakai MySQL. Dikerjakan: file dihapus.

### B-4. public/storage symlink belum dibuat
Upload butuh symlink. Fix: php artisan storage:link (saat mulai modul upload).

### B-5. .env.example tidak sinkron dengan .env [SELESAI]
Dikerjakan: .env.example diselaraskan ke DB_CONNECTION=mysql, host/port, DB_DATABASE=telaga, user root, password kosong, + APP_NAME "TELAGA AL KAUTSAR".

### B-6. Sisa scaffold default (rapikan bertahap)
DatabaseSeeder masih buat Test User; ExampleTest (Feature+Unit) bawaan; README default Laravel. Ganti saat masuk modul terkait.

### B-7. Font di-load ganda (potensi inkonsistensi)
Layout pakai <link> fonts.bunny.net (CDN) SEKALIGUS ada font woff2 lokal hasil build. Offline -> font CDN gagal/fallback. Rendah prioritas; pilih salah satu.

### B-8. SUDAH BERSIH (audit positif)
- Semua route('...') di view cocok dengan web.php -> tidak ada RouteNotFoundException
- x-cloak sudah punya handler CSS di app.css
- Tidak ada <form method=POST> action mati (semua mock via Alpine)
- laravel/pao BUKAN typo - paket asli v1.1.2 ada di lock
- Koneksi MySQL sukses (error db:show hanya karena performance_schema disabled, non-fungsional)

---

## BAGIAN C - POIN DISKUSI SEBELUM BUILD

1. [RESOLVED] Nama DB = telaga (TELAGA = Teknologi Layanan Akademik & Guru Al Kautsar). APP_NAME = TELAGA AL KAUTSAR.
2. Auth: satu tabel users + kolom role (enum wali/admin/guru) + middleware role [usulan], ATAU guard/tabel terpisah? PRD sebut "login terpisah dengan RBAC".
3. Konvensi PK/FK non-standar (rules.md): id_users, id_students, FK id_user, id_class. Melawan default Laravel -> tiap model perlu $primaryKey/$table + relasi eksplisit. Konfirmasi tetap ikut aturan ini?
4. Relasi users <-> parents/teachers: tabel profil terpisah 1-1 (usulan) atau kolom di users?
5. Email notifikasi: MAIL_MAILER=log / Mailtrap dulu (usulan) atau SMTP asli?
6. Generate tagihan SPP bulanan via Laravel Scheduler (command spp:generate)? 
7. Export PDF pakai barryvdh/laravel-dompdf (usulan); CSV native. Setuju tambah dependency?
8. Mock role switcher (?role=) diganti login asli setelah auth jadi. Konfirmasi boleh dicopot/adaptasi.
9. academic_years: satu aktif per waktu? Format "2026/2027"? Dipakai di path upload.

---

## BAGIAN D - URUTAN PENGERJAAN BACKEND (draf, dipecah bite-sized setelah diskusi)

Tiap task: update .agents/tasklist.md (rules.md §4) + komentar tiap fungsi (rules.md §5).

Fase 0 - Perbaikan & Fondasi (Bagian B)
- 0.1 Perbaiki .env (DB name, APP_NAME), buat DB, hapus sqlite leftover, sinkron .env.example
- 0.2 storage:link; bersihkan seeder/test/README default
- 0.3 Bereskan shell terminal agar artisan lancar

Fase 1 - Database & Model (konvensi PK/FK)
- Migrations+Model+relasi: academic_years, role, users, guardians, teachers, classes, class_teacher (pivot), students, registration_forms, registration_documents, payment_transactions, monthly_spp_bills, re_registration_payments, student_progress_notes, audit_logs, settings (biaya dinamis)
- Seeder: role + akun admin/guru/wali contoh + tahun ajaran

Fase 2 - Auth & RBAC
- Login/registrasi wali, login admin/guru, middleware role, ganti mock switcher

Fase 3 - Alur Wali
- Form pendaftaran (validasi NIK 16 digit) -> simpan
- Upload dokumen (service: validasi JPG/PDF <=2MB, penamaan+path rules.md §3)
- Status pendaftaran (state machine), upload bukti bayar pendaftaran

Fase 4 - Alur Admin
- Verifikasi dokumen & pembayaran (setuju/tolak+catatan), set biaya dinamis, tombol Lulus/Gagal
- Manajemen siswa/guru/kelas + penugasan guru multi-kelas

Fase 5 - Keuangan (cicilan)
- Service pembayaran parsial: tunggakan dinamis (total - terverifikasi), progress bar, rincian per komponen/bulan
- Daftar ulang cicilan + SPP bulanan (generate otomatis scheduler, cicilan, tanpa denda)

Fase 6 - Guru
- Dashboard dibatasi kelas diampu (query guard anti kebocoran), edit profil siswa, catatan perkembangan

Fase 7 - Lintas-fitur
- Audit log (login, verifikasi bayar, ubah status, before/after)
- Email notifikasi (PRD §7.13)
- Search/filter + export CSV/PDF

Fase 8 - Tahap 3: validasi lanjut, UAT, bugfix, deploy awal

---

## Risiko utama
- Konvensi PK/FK non-standar -> rawan bug relasi; eksplisit + diuji
- Akurasi saldo cicilan (PRD §13) -> hitung dinamis dari transaksi terverifikasi, jangan simpan saldo mutable
- Kebocoran data antar kelas guru -> enforce di level query/scope
- Shell terminal rusak -> hambat artisan/migrate; bereskan di Fase 0

## Verifikasi
- php artisan migrate:fresh --seed sukses di DB baru
- php artisan test hijau (test asli ganti ExampleTest)
- npm run build sukses
- Uji manual tiap alur role + RBAC (akses lintas-role ditolak)


---

# LAMPIRAN: Detail Fase 1 - Database & Model (task bite-sized)

> Konvensi wajib (rules.md §2): PK `id_<tabel>`, FK `id_<singular>`, tiap Model set `$table`+`$primaryKey`, relasi & FK migration eksplisit, komentari tiap fungsi (§5). Setelah tiap task: update .agents/tasklist.md (§4).
> Urutan tabel mengikuti dependensi FK (parent dibuat sebelum child).
> Verifikasi global akhir Fase 1: `php artisan migrate:fresh --seed` sukses + `php artisan test` hijau.

## Urutan & skema tabel (16 tabel)

1. **academic_years** (C9) - PK id_academic_years; kolom: tahun (string "2026/2027"), is_aktif (bool, default false), timestamps. Aturan: hanya 1 is_aktif=true.
2. **users** (modifikasi migration bawaan) - PK harus jadi id_users; kolom tambah: role enum('wali','admin','guru'). name,email,password,remember_token,timestamps. CATATAN: migration users bawaan pakai $table->id() (=> kolom 'id'); ubah ke ->id('id_users') dan sesuaikan sessions.user_id.
3. **guardians** (C4) - PK id_guardians; FK id_user -> users.id_users (unique, 1-1); kolom: no_hp, alamat, pekerjaan (nullable), timestamps.
4. **teachers** (C4) - PK id_teachers; FK id_user -> users.id_users (unique, 1-1); kolom: nomor_induk_guru (unique), no_hp, alamat (nullable), timestamps.
5. **classes** - PK id_classes; FK id_academic_year -> academic_years; kolom: nama_kelas, timestamps. (nama model: SchoolClass, hindari keyword 'Class').
6. **class_teacher** (pivot, guru banyak kelas) - PK id_class_teacher; FK id_class -> classes, id_teacher -> teachers; unique(id_class,id_teacher); timestamps.
7. **students** - PK id_students; FK id_guardian -> guardians (nullable, utk siswa lama manual), id_class -> classes (nullable), id_academic_year; kolom: nik (16 digit, unique), nama_lengkap, nama_panggilan, jenis_kelamin enum(L,P), tempat_lahir, tanggal_lahir (date), status enum('aktif','lulus','nonaktif') default aktif, timestamps.
8. **settings** (biaya dinamis, C6) - PK id_settings; kolom: key (unique), value; seed: nominal_pendaftaran, nominal_spp, nominal_daftar_ulang, tanggal_generate_spp.
9. **registration_forms** - PK id_registration_forms; FK id_user (wali pendaftar), id_student (nullable, terisi saat lulus), id_academic_year; kolom: status enum (draft, submitted, menunggu_bukti, menunggu_verifikasi, pembayaran_diverifikasi, diproses_seleksi, lulus, gagal), catatan_admin (nullable), timestamps.
10. **registration_documents** - PK id_registration_documents; FK id_registration_form; kolom: jenis enum('kk','akta','foto'), path, status enum('pending','diterima','ditolak'), catatan (nullable), timestamps.
11. **payment_transactions** (semua cicilan/lunas) - PK id_payment_transactions; FK id_student (nullable), id_registration_form (nullable), id_user; kolom: jenis enum('pendaftaran','daftar_ulang','spp'), referensi_id (nullable, mis id_monthly_spp_bill), jumlah (decimal), bukti_path, status enum('pending','diverifikasi','ditolak'), catatan (nullable), tanggal_bayar, verified_by (nullable FK users), timestamps.
12. **monthly_spp_bills** (C6) - PK id_monthly_spp_bills; FK id_student, id_academic_year; kolom: bulan (string "2026-08"), nominal (decimal), jumlah_terbayar (decimal, default 0 - dihitung dari transaksi terverifikasi), status enum('belum_lunas','kurang','lunas'); unique(id_student,bulan). CATATAN risiko: sisa = nominal - SUM(transaksi terverifikasi), hitung dinamis.
13. **re_registration_payments** (daftar ulang) - PK id_re_registration_payments; FK id_student, id_academic_year; kolom: total_biaya, jumlah_terbayar (default 0), status enum('belum_lunas','kurang','lunas'), timestamps.
14. **student_progress_notes** - PK id_student_progress_notes; FK id_student, id_teacher (penulis); kolom: catatan (text), tanggal, timestamps.
15. **audit_logs** - PK id_audit_logs; FK id_user (nullable); kolom: aksi, model_terkait (nullable), data_sebelum (json nullable), data_sesudah (json nullable), ip_address (nullable), timestamps.
16. (users sudah di #2) - pastikan cache/jobs migration bawaan tetap.

## Pola tiap task (contoh Task 1.x)

### Task F1-1: Migration + Model academic_years
- Create: database/migrations/xxxx_create_academic_years_table.php
- Create: app/Models/AcademicYear.php (protected $table='academic_years'; $primaryKey='id_academic_years')
- Step: tulis migration (id_academic_years, tahun, is_aktif, timestamps)
- Step: `php artisan migrate` -> verifikasi tabel terbuat (php artisan db:table academic_years)
- Step: Model + relasi hasMany(SchoolClass/Student, 'id_academic_year','id_academic_years')
- Step: commit "feat(db): academic_years migration + model"
- Step: update tasklist.md

(Setiap tabel #1-#15 mengikuti pola yang sama: migration -> migrate -> model+relasi eksplisit -> commit -> update tasklist.)

## Task F1-Seed: DatabaseSeeder
- Ganti Test User default. Seed: 1 academic_year aktif (2026/2027); settings (4 nominal); 1 admin (role admin, password di-generate & dicetak ke console); 1 guru contoh (password=nomor_induk); beberapa kelas; opsional 1 wali+student contoh.
- Verifikasi: `php artisan migrate:fresh --seed` sukses, login manual admin/guru berhasil.

## Task F1-Test: Relationship tests
- tests/Feature/RelationshipTest.php: assert tiap relasi FK/PK tersambung (User->guardian, User->teacher, Teacher belongsToMany classes via class_teacher, Student->class/guardian, MonthlySppBill->student, dst).
- Verifikasi: `php artisan test` hijau.

## Risiko spesifik Fase 1
- Migration users bawaan pakai 'id' + sessions.user_id -> saat ubah ke id_users, sesuaikan juga tabel sessions & foreignId agar tidak putus.
- Nama model 'Class' bentrok keyword PHP -> pakai SchoolClass (atau ClassRoom) dengan $table='classes'.
- decimal untuk uang: gunakan decimal(12,2), jangan float (presisi saldo, PRD §13).
