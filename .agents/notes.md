Tambah/Ubah Fungsi dan Fitur
    T1. Fungsi login
        - [T1.1] setelah selesai semua pengecekan, tambahkan verifikasi email saat daftar akun.
        - [T1.2] buat fungsi lupa sandi.
        - [T1.3] letakkan link lupa sandi di bawah form password.

    T2. Form Pendaftaran (CMS wali murid)
        - [T2.1] [DONE] Untuk alamat, buat searchable dropdown untuk mengisi provinsi, kota/kabupaten, kecamatan, desa. Datanya sudah saya siapkah di folder data-indonesia-master dalam bentuk SQL maupun JSON. Menurutmu lebih baik pakai bentuk yg mana?
          (Keputusan: pakai SQL diimpor ke MySQL — data besar (38 prov / 514 kota / 7285 kec / 83762 kel) & hierarki berjenjang
          butuh query+index, bukan baca ribuan file JSON. Hierarki di kolom id (kode BPS): prov=2, kota=4, kec=6, kel=10 digit;
          anak difilter substr(id,1,n)=parent (portabel MySQL+SQLite). Migration 2026_07_17_000000 impor dump per-statement
          (split ';' — 1 unprepared 5MB kena max_allowed_packet; skip di sqlite, MySQL-only); dump sudah bawa PRIMARY KEY(id).
          Migration 000001 tambah 8 kolom wilayah (id+nama snapshot) ke guardians, nullable. WilayahController@index endpoint AJAX
          GET portal/wali/wilayah/{level}?parent=&q= (guard: tanpa parent valid → []; limit 1000; searchable via LIKE). Route
          wali.wilayah (auth+role:wali). wali/register.blade.php: 4 searchable dropdown Alpine (fetch+debounce, pilih kunci id+nama
          hidden & reset child), textarea Alamat jadi "Jalan/RT/RW". StoreRegistrationRequest +8 field nullable; store simpan via
          collect->only. Test WilayahTest (4): list provinsi, filter prefix, guard tanpa-parent, RBAC non-wali.)
          UPDATE 2026-07-17: dropdown wilayah + alamat JUGA ditambah ke PROFIL GURU (guru/password.blade.php "Profil Saya";
          teachers +8 kolom wilayah migration 000002; GuruController@updateProfile validasi +alamat+8 wilayah; Teacher fillable +9).
          Route wilayah dipindah dari role:wali → grup auth polos (name 'wilayah', bukan 'wali.wilayah') agar dipakai wali+guru;
          ref di register.blade disesuaikan. Test WilayahTest kini 5 (+test_guru_can_access, RBAC→guest redirect /login).
          SQL dump dipindah data-indonesia-master/ → database/data/wilayah_indonesia.sql (database_path); folder data-indonesia-master
          DIHAPUS (JSON/PHP/Medoo tak terpakai). migrate:fresh tetap jalan (baca database/data/).)
          UPDATE#2 2026-07-17: dropdown wilayah + alamat JUGA di CMS ADMIN modal guru (admin/data/teachers.blade.php teacherCrud;
          picker tulis ke f.*_id/nama, hidden :value, reset child; Js::from row +alamat+8 wilayah; blank+openEdit generik).
          MasterDataController storeTeacher+updateTeacher validasi+simpan alamat+8 wilayah (collect->only). Test AdminGuruViewsTest
          test_teacher_update_and_destroy +assert provinsi_nama. Profil siswa admin BELUM (belum ada alamat wilayah di students).)
          UPDATE#3 2026-07-17: KOLOM ALAMAT+8 WILAYAH DIPINDAH guardians → students (siswa lama diinput admin/guru tanpa
          akun wali; alamat = atribut siswa). Migration 000003 (students +9, guardians −9). Student fillable +9 + helper
          alamatRules()/alamatData() (DRY dipakai wali-register/admin/guru). Guardian fillable −9. Dropdown+alamat kini di:
          wali/register Step 2 (Data Anak, tulis ke student), admin/data/students modal (x-model f.*), guru/dashboard modal
          tambah + edit (picker lokal). StoreRegistrationRequest+RegistrationController tulis student; RegisterController signup
          buang alamat vestigial; MasterData studentRules()+Student::alamatRules(); GuruController store+updateStudent idem.
          registration-detail baca $student->alamat. Seeder/test guardian buang 'alamat'. Test 81 passed (270 assertions).)
        - [T2.2] [DONE] Di halaman upload bukti pembayaran untuk pendaftaran murid baru,
          tambahkan kolom nama bank beserta rekening bank tujuan sekolah yg diambil dari database.
          Tambahkan juga fungsi input rekening sekolah dari CMS admin (lihat poin no. 5 CMS Admin / [T5.10]).
          (Dikerjakan sepaket dg T5.6g+T8.1. Rekening sekolah dari settings (bank_sekolah/rekening_sekolah/atas_nama),
          tampil di 2 modal upload (wali/status + wali/payments) menggantikan hardcode "Bank Mandiri 123-456-789".)
        - [T2.3] [DONE] buat fungsi pengaturan akun untuk update email dan no. hp.
          (Wali: WaliDashboardController accountForm+updateAccount, route wali.account(.update),
          view wali/account.blade.php (email wajib + no_hp opsional), menu sidebar "Pengaturan Akun".
          Guru: GuruController@updateAccount, route guru.account.update, card ditambah ke guru/password.blade.php
          (email opsional + no_hp wajib—identifier login). Keduanya sinkron users+profil (guardians/teachers.no_hp),
          Rule::unique ignore-self, dibungkus transaksi. Test: WaliViewsTest::account_update, GuruTest::guru_can_update_own_account.)
        - [T2.4] [DONE] Buat untuk semua form pengisian yang diketik oleh user itu berhuruf kapital semua.
          (Middleware global UppercaseInput di web group (bootstrap/app.php): Str::upper tiap input string.
          SKIP field case/format-sensitif (email/password/no_hp/nik/nisn/nuptk/username/login) + enum select
          (status/jenis/jenis_kelamin/role/keputusan/grup) + prose (catatan/catatan_admin) + kolom *_id.
          Skip route CMS konten publik (portal/admin/content*) & field tampil-web guru (teachers/*/web) — copy
          publik bukan data user. Test Unit/UppercaseInputTest + assertion feature-test disesuaikan (nama/alamat uppercase).)

    T3. Akses database
        - [T3.1] [DONE] Cek dulu, apakah sistem sudah pakai fungsi laravel eager loading dkk
          (Audit selesai: eager loading sudah dipakai konsisten di semua controller (with([...])). Satu N+1 ditemukan &
          diperbaiki: AdminRegistrationController@index 'user' → 'user.guardian' (view registrations loop $form->user->guardian->nama).
          Sisa view sudah benar: teachers(classes.students), classes(teachers,students), wali/admin dashboard, guru dashboard,
          student-profile, daftar-ulang, reports semua eager. Verifikasi: php artisan test = 73 passed, npm build OK.)
          yg bisa menghemat jumlah query saat mengambil data dari database? Jika belum dan secara sistem lebih baik
          menggunakan fungsi tersebut, implementasikan di setiap fungsi yg memakai lazy load agar performa sistem terjaga.
        - [T3.2] [DONE] pindahkan kolom name pada tabel users ke masing-masing tabel teachers dan guardians,
          sedangkan kolom name pada tabel users di-rename dengan username. Update juga kodingan yg mengakses tabel2 yang diubah.
          (guardians.nama & teachers.nama ditambah; users.name→username (fallback display); +users.no_hp (unique, login).
          User::displayName()=guardian.nama??teacher.nama??username. Login pakai email ATAU no_hp (AuthController deteksi field).
          Semua controller/seeder/factory/view/test disesuaikan. nomor_induk_guru DIHAPUS total (diganti NUPTK, lihat T5.10).)
        - [T3.3] [DONE] Biar lebih enak aja. Untuk login, bisa menggunakan username/email/no. HP, sebab semuanya unik.
          (AuthController@login: email dideteksi via FILTER_VALIDATE_EMAIL; input lain dicocokkan ke no_hp ATAU username
          lalu attempt by id_users. Label form: "Email / No. HP / Username". Test: test_login_via_username.)

    T4. Verifikasi Pendaftaran (admin)
        - [T4.1] [DONE] Tambahkan fungsi untuk melihat foto bukti pembayaran di kotak/card Verifikasi Bukti Pembayaran
          serta tambahkan button Tolak/Terima.
          (Root cause: tombol sudah ada tapi mati karena view cek 'menunggu' sedangkan DB 'pending'.
          Fix: seragamkan ke 'pending' + tambah link "Lihat Bukti".)

    T5. CMS Admin
        - [T5.1] [DONE] Pisahkan untuk tab Siswa, Guru, dan Kelas. Jangan digabung seperti sekarang ini.
          (users.blade.php dipecah jadi admin/data/{students,teachers,classes}.blade.php + route admin.data.*)
        - [T5.2] [DONE] Pada tab Siswa kan nanti bakal menampilkan list Siswa. Nah, itu kasih filter berdasarkan kelas/alumni/drop out
          dan tambahkan juga kolom pencarian seperti pada tab pendaftaran siswa. Tambahkan juga fungsi CRUD untuk siswa.
          Sebab untuk memasukkan data2 siswa lama sekaligus untuk memasukkan data NISN siswa yg baru jadi setelah masuk sekolah.
          (Filter kelas + status alumni(lulus)/dropout(nonaktif) + search nama/NIK/NISN; CRUD tambah/edit/hapus; kolom NISN baru nullable+unique.)
        - [T5.3] [DONE] Pada profil siswa, nanti muncul riwayat pembayaran/tagihan, baik dari riwayat tagihan daftar ulang dan spp.
          (admin/data/student-profile.blade.php: biodata + tagihan daftar ulang + tagihan SPP + riwayat transaksi. Link "Profil" di tabel siswa.)
        - [T5.4] [DONE] Pada tab Guru tambahkan kolom pencarian sekaligus fungsi CRUD. Tambahkan juga form tempat tanggal lahir dan riwayat pendidikan.
          (Search nama/email/NIG; CRUD edit+hapus (hapus cascade akun); kolom baru tempat_lahir/tanggal_lahir/riwayat_pendidikan; modal Alpine.)
        - [T5.5] [DONE] Pada tab Kelas tambahkan fungsi CRUD.
          (updateClass + destroyClass; edit/hapus per kartu; modal dual-mode; SchoolClass getRouteKeyName ditambah.)
        - [T5.6] [PARTIAL] Untuk CMS pada panel tab sidebar di kiri, lebih baik dibuat seperti ini:
            >Dashboard
            >PPDB (memiliki 2 tab child/dropdown)
             |--> Pendaftaran Siswa (di atas kolom pencarian, kasih fungsi untuk set status buka/tutup pendaftaran beserta untuk input tahun pelajaran baru. Kasih fungsi CRUD juga.)
             |--> Daftar Ulang (berisi list siswa yg lolos pendaftaran dan untuk verifikasi biaya daftar ulang)
            >Data Guru (berisi list data guru beserta relasinya dg kelas dan jumlah murid yg diampu. tambahkan fungsi CRUD.)
            >Data Siswa (berisi list data siswa beserta relasinya dg kelas dan guru yg mengampu. tambahkan fungsi CRUD.)
            >Data Kelas (berisi list data kelas beserta relasinya dg guru dan siswa. tambahkan fungsi CRUD.)
            >Konten Web (berisi form CRUD untuk manage konten web)
            >Pembayaran (memiliki 2 tab child/dropdown)
             |--> Set Pembayaran (set nilai pembayaran untuk masing2 pendaftaran siswa, biaya daftar ulang, dan SPP. set bank dan rekening sekolah)
             |--> SPP (verifikasi pembayaran SPP)
            >Data Akun (untuk CRUD akun admin/guru)
            >Laporan & Audit Log
          Sub-poin turunan [T5.6] (struktur sidebar [DONE]; isi fitur child menyusul Fase C):
            - [T5.6a] [DONE] PPDB > Pendaftaran Siswa: buka/tutup pendaftaran (setting pendaftaran_dibuka, gate 403 di wali create+store) + input tahun ajaran baru (create+aktifkan). CRUD pendaftaran via detail/seleksi existing.
            - [T5.6b] [DONE] PPDB > Daftar Ulang: list tagihan daftar ulang siswa lolos + verifikasi bukti pending (reuse admin.payments.verify).
            - [T5.6c] [DONE] Data Guru: list + relasi kelas + jumlah murid diampu + search + CRUD (TTL & riwayat pendidikan).
            - [T5.6d] [DONE] Data Siswa: list + filter/search + CRUD (relasi kelas tampil; guru pengampu via kelas).
            - [T5.6e] [DONE] Data Kelas: list + relasi guru & siswa + CRUD (edit/hapus).
            - [T5.6f] [DONE] Konten Web: form CRUD manage konten (sudah ada, dipindah ke struktur baru).
            - [T5.6g] [DONE] Pembayaran > Set Pembayaran: set nominal pendaftaran/daftar ulang/SPP [DONE] + set bank & rekening sekolah [DONE via T2.2/T8.1].
            - [T5.6h] [DONE] Pembayaran > SPP: verifikasi pembayaran SPP.
              (AdminRegistrationController@spp: rekap tagihan SPP + bukti SPP pending; view admin/spp.blade.php;
              route admin.spp; verifikasi reuse admin.payments.verify. Sidebar child "Verifikasi SPP" (desktop+mobile).)
            - [T5.6i] [DONE] Data Akun: CRUD akun admin (multi-admin; tolak hapus diri sendiri). Akun guru dikelola di Data Guru.
            - [T5.6j] [DONE] Laporan & Audit Log (sudah ada).
        - [T5.10] [DONE] Saat data guru ditambahkan, auto buat akun dg memanfaatkan nomor HP sbg username dan NUPTK sbg password.
          (Keputusan user 2026-07-16: NIG diganti NUPTK total (wajib); password awal guru = NUPTK; login guru pakai no_hp WAJIB
          atau email OPSIONAL (guru isi email sendiri di CMS belakangan); username = email??no_hp (fallback display).
          rules.md §1.5/§1.6 direvisi. Dikerjakan setelah T3.2 sbg prasyarat. storeTeacher/updateTeacher + form teachers.blade disesuaikan.)

    T6. CMS Guru
        - [T6.1] [DONE] Buatkan fungsi ganti password, sebab untuk user guru baru kan password default-nya nomor induk guru.
          (GuruController passwordForm+updatePassword, verifikasi current_password; guru/password.blade.php + menu sidebar.)
        - [T6.2] [DONE] Buatkan kolom pencarian pada halaman daftar siswa (filter pilih kelas tetap pertahankan).
          (Input search Alpine client-side by nama/NIK, filter kelas existing dipertahankan.)
        - [T6.3] [DONE] Buatkan fungsi CRUD untuk pada halaman daftar siswa agar guru siswa bisa membantu admin dalam menginput data siswa lama.
          (GuruController@storeStudent guard id_class ∈ kelas diampu; modal tambah di dashboard. Edit profil sudah ada; hapus tidak diberi ke guru — kewenangan admin.)
        - [T6.4] [DONE] Buatkan fungsi CRUD untuk akun/profil guru. Sebab nanti setelah akun guru dibuat oleh admin, guru tersebut akan mengupdate profilnya dengan salah satunya mengisi email yg nantinya bisa jadi username untuk login juga (sebelumnya pakai no. HP).
          (GuruController@updateProfile: nama, TTL, riwayat_pendidikan (NUPTK read-only—dikelola admin). Route guru.profile.update.
          Card "Profil Saya" ditambah ke guru/password.blade.php (kini halaman "Akun & Profil" = profil + akun email/HP + password).
          Email-sebagai-login sudah via T2.3/updateAccount. Sidebar guru label → "Akun & Profil".)

    T7. CMS Konten Web
        - [T7.1] [DONE] Pada halaman beranda yg bagian Curriculum Highlight tolong bagian itu diisi dengan foto-foto yg bisa auto slide
          beserta keterangan gambarnya. Dan itu bisa diisi melalui CMS.
          (Reuse site_features grup baru 'kurikulum' (judul=caption, deskripsi=subcaption, foto_path=gambar, urutan, aktif).
          home.blade.php: card Curriculum Highlight jadi carousel Alpine (x-init setInterval 4s auto-slide + dot indicator klik,
          x-transition fade; fallback ke card lama bila 0 slide). SiteContentController storeFeature/updateFeature terima 'foto'
          opsional (image|max:2048 → store konten/public); destroyFeature+updateFeature bersihkan file lama. PublicController@home
          kirim $kurikulum. admin/content.blade.php: tab 'kurikulum'→home, form add/edit multipart + input file + thumbnail
          (kondisional grup kurikulum saja). Seeder 3 slide default tanpa foto. Test: SiteContentCmsTest::test_admin_can_add_kurikulum_slide_with_photo.)

    T8. Pembayaran
        - [T8.1] [DONE] Tiap kali wali murid mau mengirimkan bukti pembayaran (PPDB, daftar ulang, SPP), 
          selain menampilkan bank dan rekening sekolah, wali murid juga memilih daftar bank/e-wallet (dropdown) yg akan digunakan untuk transfer ke rekening sekolah. Datanya sudah saya siapkan di file bank.csv (abaikan sandi bank, karena tidak diperlukan)
          (PaymentTransaction::daftarBank() parse bank.csv (kolom nama, buang header/sandi); kolom baru payment_transactions.bank_asal
          (migration 2026_07_16_020000 + fillable). Dropdown "Bank Asal Transfer" di 2 modal upload; disimpan di store StatusController+PaymentController.
          Validasi bank_asal nullable|string di StorePaymentProofRequest+StoreInstallmentRequest.)

    T9. Akun Wali
        - [T9.1] [DONE] Admin buatkan akun wali murid lama (klaim + ubah password).
          (Opsi A: tombol "+ Akun Wali" per-baris tabel Data Siswa (muncul bila siswa belum punya wali berakun) →
          modal isi nama+no_hp → MasterDataController@createWaliAccount: cari guardian by no_hp (whereHas user),
          belum ada → buat user(role wali, username=password=no_hp, must_change_password=true)+guardian; sudah ada
          (kakak-adik) → tautkan ke akun yang sama. student.id_guardian di-update. Route admin.students.wali-account.
          Paksa ganti password: kolom users.must_change_password (migration 043022); middleware ForceChangePassword
          (web group) redirect wali/guru berflag ke form ganti-password role-nya sampai diganti (guru NUPTK juga kena).
          Form ganti-password WALI baru (WaliDashboardController passwordForm/updatePassword, wali/password.blade.php,
          route wali.password, sidebar link) — clear flag saat ganti. Guru updatePassword juga clear flag.
          Test WaliAccountTest (4): buat akun, kakak-adik 1 akun, wali dipaksa ganti, guru dipaksa ganti.)


Pengecekan dan Perbaikan Fungsi dan Fitur
    P1. Database
        - [P1.1] [DONE] cek apakah nomor hp dan email di-set unique sehingga 1 nomor tidak bisa dimiliki oleh 2 orang.
        - [P1.2] [DONE] cek apakah tabel cache, cache_locks, failed_jobs, job_batches, jobs, digunakan dalam sistem ini.
          (CACHE_STORE/SESSION_DRIVER/QUEUE_CONNECTION semua =database. cache+cache_locks DIPAKAI (backend cache aktif).
          jobs/job_batches/failed_jobs BELUM terpakai—app nol ShouldQueue/dispatch/Cache::; email dikirim sinkron Mail::send.
          Keputusan: JANGAN hapus—migration bawaan 1 file, no cost dibiarkan, siap bila email async (deploy 3.C). Tanpa perubahan kode.)

    P2. Form Pendaftaran (CMS Wali Murid)
        - [P2.1] [DONE] Ketika wali murid mendaftarkan > 2 anak sekaligus,
          pada halaman di tab status pendaftaran dan di tab pembayaran & SPP, anak yg didaftarkan terakhir,
          datanya akan menimpa anak sebelumnya. Sedangkan data anak sebelumnya, mungkin di database masih ada,
          namun tidak ditampilkan di dalam halaman web.
          (Fix: StatusController ambil semua form (bukan ->first()); view loop per anak. Payments sudah benar sejak awal.)
        - [P2.2] [DONE] Ketika wali murid memiliki > 2 anak yg mendaftar/bersekolah sekaligus, seharusnya pada halaman tab pembayaran,
          terdapat semua list anaknya dan wali murid melakukan pembayaran satu-satu dari masing-masing anaknya.
          (Sudah terpenuhi: PaymentController + wali/payments.blade.php sudah loop semua anak.)
        - [P2.3] [DONE] untuk timeline pendaftaran, harusnya setelah wali murid upload bukti pembayaran, itu verifikasi pembayaran,
          baru verifikasi berkas. Jadi bukan digabung.
          (Fix: timeline dipisah 6 tahap; verifikasi berkas di-guard tidak bisa sebelum bayar diverifikasi.)

    P3. Verifikasi Pendaftaran (CMS Admin)
        - [P3.1] [DONE] Kotak/Card Verifikasi Bukti Pembayaran seharusnya ditaruh di paling atas sebelum card Biodata Calon Siswa dan Data Wali Murid.
        - [P3.2] [DONE] Ketika admin sudah klik "terima" ketiga dokumen, status pendaftaran di wali murid tidak berubah ke "proses seleksi calon siswa".
          (Fix: verifyDocument auto-maju form ke 'diproses_seleksi' saat semua dokumen 'diterima'.)

    P4. CMS Admin
        - [P4.1] [DONE] Pada tab halaman dashboard, kan ada 4 card (Pendaftar Baru), (Murid Lolos Seleksi), (Keuangan Masuk), dan (Pending Verifikasi).
          Nah, Apakah nilai-nilai yg ditampilkan pada card tersebut itu diambil dari database? Coba cek, kalau belum, ambil nilai-nilainya
          dari database. Lalu khusus card "Murid Lolos Seleksi", dari mana nilai 60% kuota kelas? Memang ada input berapa kuota siswa dalam
          pendaftarannya? Kalau belum, kasih fungsi set kuota pendaftaran.
          (DashboardController: 4 card dari DB. Kuota via setting kuota_pendaftaran di tab Set Pembayaran; card tampil % dari kuota.)
        - [P4.2] [DONE] Mengapa di cms hanya ada kolom untuk set biaya pendaftaran saja? Kenapa tidak ada kolom untuk set biaya daftar ulang dan spp?
          Apakah lebih baik dibuatkan tab khusus untuk set biaya pendaftaran? Jadi kolom set biaya pendaftaran di tab dashboard dipindah.
          (Tab khusus admin/settings.blade.php: 3 nominal + tanggal generate + tombol generate SPP. Widget mock di dashboard dihapus. Sidebar Pembayaran>Set Pembayaran.)

═══════════════════════════════════════════════════════════════════
Backlog Fase K — Temuan Testing 2026-07-17 (belum dikerjakan)
Dikelompokkan per tema. Prioritas kasar: BUG dulu → validasi → UX → fitur → diskusi.
═══════════════════════════════════════════════════════════════════

    K0. BUG (data hilang / salah simpan — kerjakan lebih dulu)
        - [K0.1] [DONE] Form pendaftaran CMS wali: nama wali & no_hp wali TIDAK masuk ke database. (203)
          (Root cause: RegistrationController@store hanya update no_hp, ABAIKAN nama_wali → guardians.nama
          NULL (kolom kini nullable). Fix: update ['nama'=>nama_wali,'no_hp'=>...]. Test WaliRegistrationTest +assert nama.)
        - [K0.2] [DITUTUP] Bukan item koding — verifikasi tulis-DB = tanggung jawab test + transaksi +
          constraint (sudah ada). Instance teramati = K0.1 (fixed). (205)
        - [K0.3] [DONE] Audit Log kosong. (225)
          (Root cause: tabel Audit Log di admin/reports.blade.php masih MOCK STATIK 4 baris hardcoded —
          data audit_logs tercatat benar tapi tak pernah di-query. Fix: ReportController kirim $auditLogs
          (AuditLog::with(user.guardian/teacher) latest 50); view @forelse dari DB + empty-state. Test +assertSee.)
        - [K0.4] [DIBATALKAN] Hapus siswa lulus meninggalkan jejak record yatim. (236)
          (Keputusan user: BATALKAN fix + HAPUS TOTAL fitur delete data siswa & guru. Alasan: hard-delete
          tak standar di SIS — data terikat riwayat keuangan/akademik/audit. Guru/siswa "keluar" = ubah
          status, bukan hapus. Dibuang: destroyStudent/destroyTeacher + route students.destroy/teachers.destroy
          + tombol Hapus di admin/data/{students,teachers}. Fitur status keluar/pindah → K5.4.)

    K1. Validasi input (trust boundary — jangan disederhanakan)
        - [K1.1] [DONE] No. HP: kolom string bisa diisi non-angka. Validasi angka saja, 9–14 digit.
          Normalisasi awalan 0 → 62 (input "081..." disimpan "6281..."). Terapkan ke SEMUA form HP (wali/admin/guru). (200)
          (Aturan+normalisasi terpusat di app/Support/ValidationRules.php; normalisasi 0→62 di middleware
          UppercaseInput (1 titik, semua form); login AuthController juga normalisasi identifier no_hp.
          Data lama tak dimigrasi. Test Unit/ValidationRulesTest. 90 passed.)
        - [K1.2] [DONE] Nama: hanya huruf/abjad (+spasi). Tolak angka/simbol. Semua form nama. (201)
          (Mode longgar (keputusan user): regex /^[A-Za-z .'\-]+$/ — terima titik/apostrof/hubung untuk
          nama sah (M. RIDWAN / SITI NUR'AINI / ABDUL-AZIZ), tolak angka & simbol lain. Helper ValidationRules::nama().)
        - [K1.3] [DONE] NIK: tak ada validasi angka. Cek & perbaiki semua form NIK. (202)
          (Verifikasi: SEMUA form NIK sudah pakai digits:16 sejak awal (angka murni + tepat 16). Tanpa perubahan kode.)

    K2. Alur pendaftaran & status (wali)
        - [K2.1] [DONE] Wajib isi profil wali (nama, TTL, pekerjaan, dll) setelah login pertama (baik wali baru
          maupun wali lama yg dibuatkan akun) SEBELUM boleh mendaftar/bayar. Gate mirip ForceChangePassword. (204)
          (Migration +tempat_lahir/tanggal_lahir ke guardians; Guardian::isComplete() (nama+no_hp+pekerjaan+TTL).
          Form profil baru wali/profile.blade + DashboardController profileForm/updateProfile (route wali.profile[.update],
          no_hp sinkron ke users, reuse ValidationRules). Middleware RequireGuardianProfile (web group) redirect wali
          berprofil-belum-lengkap ke wali.profile — HANYA rute wali.* (rute guru/admin dibiarkan ke role: →403).
          Sidebar +menu Profil Wali (desktop+mobile). Test WaliAccountTest::wali_forced_to_complete_profile.)
          Follow-up 2026-07-18: form daftar 3→2 langkah — Step 1 "Data Wali" DIBUANG (redundan; profil sudah
          wajib lengkap via gate). register.blade renumber (Data Anak=1, Upload=2). StoreRegistrationRequest
          buang rule nama_wali/no_hp/email; store() berhenti overwrite guardian (ambil apa adanya).
        - [K2.2] [DONE] Teks status pendaftaran: setelah upload bukti → "Pembayaran Anda sedang diverifikasi oleh
          Admin. Mohon menunggu."; setelah bayar diverifikasi → "Berkas Anda sedang diproses…". (208)
          (wali/status.blade: cabang @elseif status==menunggu_verifikasi → pesan pembayaran; @else → berkas diproses.)
        - [K2.3] [DONE 2026-07-18] Rapikan enum status pendaftaran + rename label. Enum kanonik: submitted →
          menunggu_verifikasi → pembayaran_diverifikasi → diproses_seleksi → lulus/gagal (draft DIBUANG;
          menunggu_bukti DIPERTAHANKAN sbg state koreksi "bukti ditolak"). Label: "Menunggu Verifikasi Pembayaran",
          "Proses Verifikasi Berkas", "Proses Seleksi", menunggu_bukti="Bukti Ditolak – Upload Ulang" (rose).
          Timer 30dtk (K2.3b) DIBATALKAN—redundan. 3 statusMap blade + filter + migration enum + status.blade. (213)
        - [K2.4] [DONE] Hapus status "Draft" dari filter Kelola Pendaftaran (sistem tak simpan draft belum-submit). (207)
          (admin/registrations.blade: opsi <option value="draft"> dibuang dari select filter.)
        - [K2.5] [DONE 2026-07-18] Wali edit/update pendaftaran. Gate RegistrationForm::canBeEditedByWali()
          = status submitted/menunggu_bukti (bayar belum diverifikasi) ATAU flag boleh_edit (escape-hatch admin).
          Efek (a): dokumen yg diganti reset ke 'pending'; bila form 'diproses_seleksi' & ada berkas diganti →
          mundur ke 'pembayaran_diverifikasi'; flag boleh_edit selalu dimatikan setelah simpan. Kolom baru
          registration_forms.boleh_edit. register.blade dual-mode (prefill $form, file opsional). Tombol Edit di
          wali/status, tombol "Buka Izin Edit Wali" di admin/registration-detail. 3 test baru. (214)
        - [K2.6] [DONE] Status switcher antar-anak di tab status pendaftaran (wali >1 anak) — hindari scroll panjang. (215)
          (wali/status.blade: Alpine activeChild + tab bar (muncul bila >1 anak); tiap blok anak x-show; <hr> pemisah dibuang.)
        - [K2.7] [DONE] PPDB ditutup: form daftar sudah tak bisa diakses (gate T5.6a OK). Halaman kini
          di DALAM layout CMS (sidebar+navbar), bukan halaman error telanjang. (220, 282)
          (create() render view wali/registration-closed.blade saat ditutup; gate POST dipindah ke
          StoreRegistrationRequest@authorize (pendaftaran_dibuka==1) → submit langsung tetap 403, bukan 422.
          ensurePpdbOpen() controller dihapus—redundant. Test +assertOk/assertSee + POST 403.)

    K3. Verifikasi Pendaftaran (admin — halaman Detail & Verifikasi)
        - [K3.1] [DONE 2026-07-18] Gate lulus: tombol Lulus/Gagal hanya muncul saat form 'diproses_seleksi'
          (bayar terverifikasi + SEMUA dokumen diterima → auto-maju via advanceToSelectionIfReady). Guard
          server-side di decide() tolak bila status != diproses_seleksi. (217 sebagian, 230)
        - [K3.2] [DONE 2026-07-18] Verifikasi dokumen tanpa refresh (AJAX). verifyDocument() balas JSON saat
          wantsJson (doc_status+form_status+boleh_diputuskan); blade dokumen jadi Alpine x-data (fetch POST,
          badge+gate tombol reaktif). (216)
        - [K3.3] [DONE 2026-07-18] Setelah lulus/gagal → tombol "Ubah Keputusan" (reopenDecision: form kembali
          diproses_seleksi, siswa aktif, audit). Dokumen sudah bisa re-verifikasi selama belum lulus/gagal. (217)
        - [K3.4] [DONE 2026-07-18] Hapus input "Catatan (opsional)" di kartu verifikasi dokumen. Controller
          tetap terima catatan nullable (harmless). (212)
        - [K3.5] [DONE 2026-07-18] Biodata Calon Siswa +Nama Panggilan (students.nama_panggilan). (210)
        - [K3.6] [DONE 2026-07-18] Data Wali Murid +Pekerjaan (guardian.pekerjaan) + alamat berjenjang siswa
          (jalan/RT/RW, kel, kec, kota, prov dari students). BUGFIX: "Nama Wali" sebelumnya baca $wali->nama
          (kolom tak ada di users → selalu "-") → kini guardian->nama ?? displayName(). (211)
        - [K3.x] [DONE 2026-07-18] Polish lanjutan (di luar 41 catatan asli): (a) verifikasi dokumen K3.3-style
          (tombol hilang→badge+Ubah); (b) tombol Ubah dokumen & izin-edit-wali → button/badge proper (bukan
          hyperlink); (c) izin edit wali aktif → kunci verifikasi dokumen+keputusan (client getter + server guard
          verifyDocument/decide); (d) thumbnail+lightbox berkas (form wali & admin detail); (e) preview file baru
          + berkas lama tampil saat wali edit; (f) bukti bayar rapi per-siswa; (g) hapus berkas lama saat replace;
          (h) command storage:reset. Detail di tasklist.md "Polish K3 lanjutan + Berkas/Storage 2026-07-18".

    K4. Pembayaran & verifikasi (kolom bank asal + tanggal/waktu — tema berulang)
        - [K4.1] [DONE 2026-07-18] SEMUA tabel/form/halaman verifikasi bukti bayar tampilkan: bank asal transfer, tanggal, waktu.
          Cakup: PPDB detail, Daftar Ulang (222), Verifikasi SPP (235). (gabungan 222+235 + bagian 221)
          (+kolom "Bank Asal" (payment.bank_asal) & "Tanggal & Waktu" (tanggal_bayar date + jam created_at "HH:MM WIB")
          di 3 tabel: admin/registration-detail, admin/daftar-ulang, admin/spp. Keputusan waktu: pakai created_at
          (jam bukti masuk sistem, akurat/tak dipalsukan) — tanggal_bayar hanya date (input date wali, tanpa jam);
          zero migrasi. Colspan empty-state disesuaikan. bank_asal+created_at sudah di model, student sudah eager-load.)
        - [K4.2] [DONE 2026-07-18] Riwayat Pembayaran & Cicilan (wali): +kolom atas-nama-anak, tanggal; bila SPP → bulan apa. (231)
          (wali/payments riwayat +kolom "Atas Nama" (student->nama_lengkap); jenis SPP +bulan tagihan (translatedFormat F Y).
          Relasi baru PaymentTransaction::sppBill() (referensi_id→id_monthly_spp_bills). PaymentController history
          eager-load ['student','sppBill']. Colspan 5→6.)
        - [K4.3] [DONE — tanpa koding] Bukti PPDB: nominal statis (tak bisa overpay, harus lunas) — sudah di-set statis; VERIFIKASI cukup. (229)
        - [K4.4] [DONE 2026-07-18] Hilangkan semua teks/kalimat "bisa dicicil" di tagihan daftar ulang & SPP (jangan pancing
          wali nyicil; cicilan hanya untuk yg benar tak mampu). Terkait fitur opt-in K10.4. (283)
          (info.blade: badge "Bisa Dicicil" + baris "Boleh diangsur/dicicil" dihapus dari kartu Daftar Ulang & SPP.
          wali/dashboard: "Unggah bukti cicilan berikutnya" → "bukti pembayaran berikutnya". admin/dashboard mock statik dibiarkan.)

    K5. Daftar Ulang & status siswa aktif
        - [K5.1] [DONE 2026-07-18] Siswa masuk tab "Data Siswa" HANYA setelah ada pembayaran daftar ulang (nominal berapapun →
          status aktif). Belum bayar daftar ulang → belum muncul. (226)
          (Keputusan user: syarat ini HANYA utk siswa hasil PPDB (punya registration_form). Siswa lama manual
          (whereDoesntHave registrationForms) tetap tampil apa adanya. MasterDataController@students +where(
          whereDoesntHave('registrationForms')->orWhereHas('reRegistrationPayments', jumlah_terbayar>0)).)
        - [K5.2] [DONE 2026-07-18] Siswa yg tagihan daftar ulang LUNAS → hilang dari datatable Tagihan Daftar Ulang (Siswa Lolos). (227)
          (RegistrationController@daftarUlang: $bills where status != 'lunas'. Judul tabel → "…, Belum Lunas".)
        - [K5.3] [DONE 2026-07-18] Halaman PPDB—Daftar Ulang: pisah dg slidebar/tab antara tabel Bukti Pembayaran vs List Siswa;
          badge angka jumlah-belum-verifikasi di tab Bukti; align-right kolom Bukti (tabrakan Jumlah) & Status (tabrakan Sisa). (221)
          (daftar-ulang.blade: Alpine tab {bukti|siswa}; badge rose count($pending) di tab Bukti; kolom Bukti/Aksi/Status
          → text-right. Tabel Bukti +Bank Asal & Tanggal&Waktu (selaras K4.1).)
        - [K5.4] [DONE 2026-07-18] [dari K0.4] Fitur ubah status siswa/guru "keluar/pindah/alumni" (ganti hard-delete yg sudah
          dibuang). Siswa: pakai enum status existing (aktif/nonaktif/lulus/dropout) + filter. Guru: perlu
          kolom status/aktif (belum ada) — DISKUSI dulu skema-nya.
          (Keputusan user: guru +kolom bool is_aktif (migration 110000, default true). Nonaktif=ex-guru: disembunyikan
          dari list default (filter status aktif/nonaktif/semua) + DIBLOK LOGIN (AuthController, root-cause 1 titik).
          Toggle via MasterDataController@toggleTeacherStatus + route teachers.toggle-status + tombol Nonaktifkan/Aktifkan.
          Siswa: status sudah bisa diubah via modal Edit existing (enum aktif/lulus/nonaktif) — tak perlu tambahan.
          Teacher fillable+cast is_aktif. Test AuthTest::inactive_teacher_cannot_login + AdminGuruViewsTest::k5_student_filter_and_teacher_toggle.)

    K6. Sidebar & Dashboard admin (notifikasi & pemisahan)
        - [K6.1] [DONE 2026-07-18] Badge angka jumlah-perlu-verifikasi per tab sidebar (Pendaftaran Siswa,
          Daftar Ulang, Verifikasi SPP + header dropdown PPDB/Pembayaran). Bukti bayar pending per jenis
          (groupBy) + dokumen pending → badge PPDB. View Composer 'layouts.dashboard' (admin-only, 2 query),
          share $sidebarBadge; badge rose closure di blade (desktop+mobile). (223)
        - [K6.2] [DONE 2026-07-18] Bug dropdown PPDB: klik "Daftar Ulang" → PPDB collapse (harusnya tetap buka).
          Root cause: $ppdbActive di layouts/dashboard.blade hanya cek routeIs('admin.registrations*'), tak
          termasuk admin.daftar-ulang → dropdown open=false saat di halaman Daftar Ulang. Fix: +|| routeIs('admin.daftar-ulang').
          Mobile sidebar tak kena (link datar tanpa collapse). (206)
        - [K6.3] [DONE 2026-07-18] Dashboard: card "Pending Verifikasi" dipecah jadi 2 card terpisah —
          "Pending PPDB" (bukti bayar pendaftaran + dokumen pending) & "Pending Pembayaran" (daftar ulang +
          SPP, dengan rincian jenis mis. "1 daftar ulang, 3 SPP"). Grid 4→5 card, ukuran card diperkecil
          (rounded-2xl p-5, grid-cols-5). Card PPDB link ke Kelola Pendaftaran. DashboardController groupBy
          jenis + count dokumen. BONUS: widget "Aktivitas & Log Sistem Terkini" (mock statik "Bunda Amira" dst)
          diganti 5 AuditLog terbaru dari DB (reuse pola ReportController). (224)
        - [K6.4] [DONE 2026-07-18] Hapus tab sidebar Pembayaran › "Verifikasi Pembayaran" (halaman placeholder
          mati yg cuma redirect). Sekalian bereskan BUG route ganda admin.payments (web.php:96 duplikat).
          Hapus: link sidebar desktop+mobile, route placeholder, view admin/payments.blade.php, $bayarActive
          bagian routeIs('admin.payments'). Verifikasi bayar tetap via registration-detail/daftar-ulang/spp. (237)

    K7. Konten Web (CMS admin)
        - [K7.1] [DONE 2026-07-18] Tombol Tambah di Curriculum Highlight (slider foto) keluar dari card — perbaiki layout. (233)
          (content.blade dirombak: tombol Tambah dipindah ke header card, item jadi baris ringkas → tak overflow.)
        - [K7.2] [DONE 2026-07-18] Form konten yg sempit → ubah ke modal floating (seperti modal pembayaran) agar leluasa. (234)
          (Tombol Tambah/Edit membuka modal floating lebar. contentCms() Alpine: 1 modal fitur dual-mode
          create/edit + 1 modal "Edit Teks" key-value. Zero perubahan backend/route.)
        - [K7.3] [DONE 2026-07-18] Sub-tab (slidebar dalam halaman) per navbar konten agar tak scroll panjang.
          (Tiap tab utama Beranda/Profil/Info/Kontak punya sub-tab: Teks Halaman + tiap fitur milik grup +
          (Beranda) Logo/Gambar. Alpine tab+sub; setTab reset sub ke item pertama grup. Hanya 1 card tampil.)
        - [K7.4] [DONE 2026-07-18] Card "Unggah Gambar/Logo" dulu tampil di bawah tiap halaman & dropdown target
          KOSONG (0 slot image) → dipindah ke sub-tab "Logo/Gambar" (Beranda saja) + dibuat berguna: slot
          image baru 'home.logo' (SiteContentSeeder). Logo header+footer web (dulu hardcode kotak "AK")
          kini render dari CMS via View::composer('layouts.public')→$siteLogo, fallback "AK" bila kosong.
        - [K7.5] [DONE 2026-07-18] Tab/sub-tab CMS tetap di posisi setelah aksi submit (tak balik ke Beranda).
          Disimpan di QUERY STRING (?tab=&sub=), BUKAN hash (hash hilang saat POST & tak masuk Referer).
          back()→Referer bawa query. contentCms syncUrl() 2-level; helper global hashTabs() 1-level.
        - [K7.6] [DONE 2026-07-18] hashTabs() global (layouts/dashboard) dipasang di daftar-ulang/spp/settings.

    KFILTER. Fix filter Data Siswa [2026-07-18]
        - [DONE] Dropdown kelas +onchange auto-submit. BUGFIX filter "Belum Dapat Kelas": UppercaseInput
          uppercase id_class=none→NONE (skip *_id tak tangkap prefix id_). Fix: skip juga prefix id_ (FK).
        - Konvensi UI/UX terkait didokumentasikan di rules.md §6.

    K8. Data Siswa & Kelas (admin)
        - [K8.1] [DONE 2026-07-19] Data Siswa +filter "belum dapat kelas" [done]; wali kelas [DONE].
          Skema: FK nullable classes.id_homeroom_teacher (migration 2026_07_19_000000). 1 kelas=1 wali (kolom
          tunggal); 1 guru maks 1 kelas jadi wali (setHomeroom/setTeacherHomeroom lepas yang lama saat reassign).
          Relasi SchoolClass::homeroomTeacher, Teacher::homeroomClass + isHomeroomOf($student). HAK AKSES guru:
          wali kelas → tambah+edit+catatan+lihat; guru biasa (cuma ampu) → lihat+catatan saja. Gate:
          GuruController@updateStudent+storeStudent pakai guardHomeroom/homeroomClass (bukan ampuClassIds);
          show+storeNote tetap guardStudent. UI guru: tombol Edit/+Tambah Siswa + dropdown kelas add hanya muncul
          bila wali kelas ($homeroomIds). ADMIN assign: dropdown "Wali Kelas" di form Kelas (classes.blade) DAN
          "Wali Kelas dari" di form Guru (teachers.blade) — dua arah, keduanya via setHomeroom/setTeacherHomeroom.
          Test: plain_teacher_cannot_add_or_edit_student, homeroom_teacher_assignment_is_exclusive. 110 passed.
        - [K8.2 kolom tabel] [DONE 2026-07-19] Admin Data Siswa: kolom NIK→No.HP Ortu (guardian.no_hp), "Wali"→
          "Orang Tua", +Wali Kelas (schoolClass.homeroomTeacher). Guru dashboard siswa: NIK→NISN, +Wali Kelas.
          Tabel Guru: urut Nama→NUPTK→No.HP (Email/HP dibuang). Eager-load schoolClass.homeroomTeacher (admin+guru).
          Pure view; colspan empty-row +1. Search guru/siswa masih by NIK (tetap, walau kolom hilang).

    K9. Format & UX umum
        - [K9.1] [DONE 2026-07-18] Format tanggal (termasuk tgl lahir) → Bahasa Indonesia. (209)
          (Root cause: APP_LOCALE=en → Carbon translatedFormat keluar "February". Fix 1 titik:
          APP_LOCALE en→id (.env + .env.example) → SEMUA translatedFormat lama (reports/dashboard/TTL/SPP)
          + baru langsung Indonesia. Tanggal tampilan numerik format('d/m/Y')/('d M Y') → translatedFormat('d M Y')
          di: registrations, wali/payments, guru/dashboard (catatan siswa), admin/spp, admin/daftar-ulang,
          admin/registration-detail, admin/data/student-profile. Input <input type=date> tetap format('Y-m-d')
          (value HTML5, WAJIB tak diubah). Zero migrasi. Locale id sudah lengkap (Carbon lang). 101 passed.)
        - [K9.2] [OK 2026-07-18] Mobile-friendly — audit kode: sudah responsif menyeluruh, tak ada yg kelupaan.
          Sidebar CMS hamburger+drawer (md:hidden/hidden md:flex); 12 datatable semua overflow-x-auto; grid form
          semua prefix responsif (grid-cols-1 sm:); halaman publik grid-cols-1 md:. Zero fix. Bila kedapatan
          meleset saat cek HP asli, user lapor halaman spesifik → fix per-kasus.
        - [K9.3] [DONE 2026-07-18] Pagination server-side 25/halaman (keputusan user: cara B — deploy langsung
          ratusan data). 4 datatable: Kelola Pendaftaran ($forms), Data Siswa ($students), Data Guru ($teachers),
          Audit Log ($auditLogs). Controller ->get()/limit(50) → ->paginate(25)->withQueryString() (Registration/
          MasterData) / ->paginate(25) (Report audit). Search Siswa/Guru sudah GET server-side sejak awal (native
          form request('cari')→where like) — tak perlu dipindah, langsung kompatibel. Komponen reusable
          resources/views/components/paginate.blade.php (x-paginate :paginator, Tailwind v4, ‹ n/N ›). BUGFIX
          bonus: search guru orWhereHas tak dibungkus closure → bocor abaikan filter is_aktif; dibungkus where(fn).
          Test test_students_paginated_25_per_page (26 siswa → hal.1 25 baris, hal.2 sisa). 102 passed.
        - [K9.4] [DONE 2026-07-18] CMS guru "Akun & Profil": 3 card (Profil/Ganti Password/Akun) → sub-tab
          hashTabs('profil') (rules §6) agar tak scroll panjang. Hanya guru/password.blade (view-only, zero backend). (238)

    K10. Diskusi dulu (keputusan arsitektur)
        - [FOTO] [DONE 2026-07-18] Pas foto siswa & guru tampil di profil. students +kolom foto_path (migration
          120000, nullable — siswa lama boleh kosong). Siswa: foto pendaftaran (dokumen jenis=foto) OTOMATIS jadi
          foto profil (RegistrationController store+update sync ke student.foto_path); CRUD admin & guru +input file
          foto opsional (DocumentUploadService::storeProfilePhoto → profil/{siswa|guru}/{Nama}/Foto_{Nama}.ext,
          seragam pola rules §3; replace hapus lama). Guru: teachers.foto_path (sudah ada) +input foto
          di storeTeacher (dulu cuma updateTeacher). Tampil: komponen reusable <x-avatar :path :name :size>
          (foto||inisial) di admin/wali profil siswa + baris tabel guru + Profil Saya guru; web daftar pendidik sudah
          pakai foto_path. Form modal siswa/guru +enctype multipart. Test: WaliRegistrationTest (foto→student sync) +
          AdminGuruViewsTest::admin_can_upload_photo_for_student_and_teacher. 104 passed.
        - [FOTO-FIX] [DONE 2026-07-19] BUG: upload foto guru via EDIT (updateTeacher) tak masuk storage/DB —
          method tak validasi/simpan `foto` (form blade sudah punya field + enctype multipart; storeTeacher &
          updateTeacherWeb sudah handle, updateTeacher TERLEWAT). Fix: inject DocumentUploadService, +rule foto,
          simpan dalam txn (hapus lama → storeProfilePhoto). NAMA KEMBAR: storeProfilePhoto slug dulu {Nama} saja →
          dua orang nama sama saling MENIMPA folder/file. Fix: +param $id (PK) → profil/{peran}/{Nama-id}/Foto_{Nama-id}.
          Semua pemanggil teruskan PK; store baru (siswa/guru) kini create dulu → upload pakai PK → update foto_path.
          Test: AdminGuruViewsTest::admin_can_upload_photo_when_editing_teacher_and_twins_dont_collide. 105 passed.
        - [FOTO-FIX2] [DONE 2026-07-19] (a) Suffix PK diperluas ke store() (pendaftaran) & storePaymentProof()
          (pembayaran): +param $id opsional → folder {Nama-id}. Pemanggil (RegistrationController store+update,
          PaymentController, StatusController) teruskan $student->id_students / $form->id_student. WaliRegistrationTest
          assert path disesuaikan ke {Nama-id}. (b) Modal edit siswa/guru kini TAMPILKAN foto lama: +foto_path di
          $row & blank, +<template x-if="mode==='edit' && f.foto_path"><img :src="'/storage/'+f.foto_path"> di modal
          (students.blade + teachers.blade). 105 passed.
        - [FOTO-DETAIL + PROFIL-TAB] [DONE 2026-07-19] (a) Foto tampil di header Detail & Verifikasi Pendaftaran
          (registration-detail: div inisial manual → <x-avatar :path=$student->foto_path>) + kartu anak wali/status.blade
          (idem). profile.blade (guru web) sudah handle foto sebelumnya. $inisial mati dihapus di 2 file. (b) Halaman
          profil siswa admin (student-profile.blade) dipecah jadi tab hashTabs('daftar-ulang'): Biodata selalu tampil,
          3 card (Daftar Ulang/SPP/Riwayat) jadi tab (rules §6.1/6.2). Zero backend/route/test. Build+view:cache hijau.
        - [GURU-TABEL + PROFIL + NUPTK16] [DONE 2026-07-19] (a) Tabel Data Guru: padding antar-kolom (px-3/pr-6/pl-3,
          tak mepet); kolom Kelas Diampu >1 → dropdown native <details>/<summary> ("{n} kelas ▾"), 0/1 kelas tampil
          apa adanya. (b) Aksi +tombol Profil → halaman profil guru baru: showTeacher + teacher-profile.blade
          (biodata+akun+avatar + card Kelas Diampu w/ jml murid), route admin.teachers.show. (c) NUPTK wajib 16 digit:
          ValidationRules::nuptk()=['required','digits:16'] +pesan; dipakai di storeTeacher & updateTeacher (ganti
          'string'); form modal +maxlength=16/inputmode/pattern. Test: nuptk_must_be_16_digits. 106 passed.
        - [SISWA-FILTER + GURU-PROFIL] [DONE 2026-07-19] (a) Filter status Data Siswa default 'aktif' (dulu ''=semua):
          students() $status=query('status','aktif'), 'semua'=tanpa filter; blade opsi "Semua Status" value=semua +
          @selected($status). (b) Guru lihat profil siswa: GuruController@showStudent (guardStudent + load progressNotes),
          route guru.students.show, tombol Profil di dashboard. REUSE view wali/child-profile (+var $backUrl/$backLabel
          opsional). Test: guru_view_student_profile_scoped, student_status_filter_defaults_to_aktif. 108 passed.
        - [K10.1] [TUTUP 2026-07-19] Hapus kolom no_hp di users (redundant dg teachers/guardians)? (228)
          (Keputusan: TIDAK dihapus — bukan redundant. users.no_hp = IDENTIFIER LOGIN dg unique constraint
          (AuthController where no_hp + unique jamin 1 nomor = 1 akun); guardians/teachers.no_hp = KONTAK profil.
          Guru boleh login via email → kontak guru wajib dari teachers.no_hp, bukan users. Menghapus 1 kolom ini
          memaksa resolusi login pindah ke join lintas-tabel + unique constraint global ke aplikasi — refactor besar
          yang menyulitkan alur login yang justru disederhanakan kolom ini. Zero koding.)
        - [K10.2] [DONE 2026-07-19] Tab "Verifikasi SPP" di-rename → "Kelola Pembayaran" (keputusan user:
          skema B — pertahankan struktur 2-tab existing, perkaya tab Tagihan, JANGAN bikin menu sidebar baru
          & JANGAN pisah rekap; cukup ganti nama). Rekap tagihan SPP semua siswa +filter bulan (distinct
          dari tagihan yang ada, format ID) & tahun ajaran (dropdown GET + tab=tagihan → posisi tab bertahan,
          rules §6.1) + kolom baru "No. HP Wali" (guardian.no_hp) + kolom Bulan YYYY-MM → format Indonesia.
          RegistrationController@spp(Request) +when(bulan)/when(tahun) + eager student.guardian + $bulanOpsi/
          $tahunOpsi. Sidebar label (desktop+mobile) diganti. Tab "Bukti SPP" verifikasi tetap. Zero migrasi/
          route baru. Verifikasi via Request: no-filter=4, bulan=2026-07→1, tahun 2025/2026→1. 110 passed. (232)
          LANJUTAN 2026-07-19b: +search (nama/NISN/no_hp wali), +filter kelas (reuse pola 'none'=belum dapat
          kelas), +kolom "Bukti" (thumbnail→openBerkas lightbox global) sebelum Status, +pagination 25/hal
          (dulu ->get(), kini ->paginate(25)->withQueryString() + <x-paginate>). Model MonthlySppBill +relasi
          transactions() (hasMany referensi_id, where jenis=spp) untuk bukti. eager +student.schoolClass+transactions.
          LANJUTAN#2 2026-07-19b (histori DU + reorder): (a) Daftar Ulang dirombak jadi histori: buang K5.2
          where!=lunas (lunas TETAP tampil), +filter TA+status(lunas/kurang/belum_lunas)+search, +pagination,
          +kolom Tanggal/Waktu/Bank dari transaksi terakhir berpath. Kolom: Siswa·Orang Tua·Tahun Ajaran·Tanggal·
          Waktu·Bank·Tagihan·Terbayar·Sisa·Bukti(center)·Status. ReRegistrationPayment +relasi transactions().
          (b) SPP tabel reorder + rename sesuai user: Siswa·Orang Tua·Tahun Ajaran·SPP Bulan·Tanggal·Waktu·Bank·
          Tagihan·Terbayar·Sisa·Bukti(center)·Status; +filter status; kolom Kelas/No.HP dibuang dari tampilan
          (filter kelas+search tetap). Bukti center di kedua tabel. 110 passed.
        - [GURU-JABATAN + FOTO 2026-07-19b] (a) Form guru admin (store/updateTeacher + modal): +field jabatan
          (dulu cuma bisa via tab web updateTeacherWeb). (b) Profil guru CMS (updateProfile + guru/password.blade):
          +field jabatan + upload foto sendiri (+enctype, hapus foto lama saat ganti). (c) Auto-fill jabatan wali
          kelas: root cause di setHomeroom + setTeacherHomeroom (2 titik) → assign set jabatan='WALI KELAS',
          lepas kosongkan HANYA bila masih 'WALI KELAS' (helper clearWaliKelasJabatan, jangan hapus jabatan manual
          spt 'KEPALA SEKOLAH'). Guru wali kelas: field jabatan read-only di profil + updateProfile unset. Kolom
          Murid tabel guru → center. 110 passed.
        - [PROFIL-ORTU + WALIKELAS + GURU-VIEW 2026-07-19b] (a) Profil siswa admin (student-profile) +tab
          "Profil Orang Tua" (nama/no_hp/email/pekerjaan/TTL wali + alamat siswa) + baris "Wali Kelas" di biodata.
          (b) Profil siswa guru+wali (child-profile, dipakai keduanya) dirombak ke tab: Biodata/Profil Orang Tua/
          Catatan; +Wali Kelas di header & biodata. eager +schoolClass.homeroomTeacher+guardian (admin+guru+wali
          controller). (c) Profil guru CMS (guru/password tab Profil) default = mode LIHAT (avatar+dl read-only) +
          tombol Edit → form (x-data editing; auto-buka bila validasi gagal). 110 passed.
        - [K10.3] [DONE 2026-07-18] CMS Wali halaman "Data Anak" (list) + profil per-anak read-only (biodata +
          catatan perkembangan guru). DashboardController@children (list) & @childProfile (gate abort_unless
          id_guardian==wali → 404). Route wali.children(.show), view wali/children + wali/child-profile, sidebar
          +menu Data Anak (desktop+mobile). Read-only (wali cuma baca, tak balas). Raport online = FUTURE, tak
          discaffold sekarang. Test WaliViewsTest::wali_children_list_profile_and_ownership_gate (list+catatan+404
          lintas-wali). 103 passed. (218)
        - [K10.4] [DONE 2026-07-20] Cicil per-JENIS (bukan per-wali). Pendaftaran & SPP wajib lunas; daftar ulang
          boleh dicicil semua wali. Guard: PaymentController@store (tolak spp bila jumlah<sisa), StatusController@
          uploadProof (tolak pendaftaran bila jumlah<nominal). UI: payments.blade SPP input readonly=sisa + tombol
          "Bayar Lunas"; status.blade nominal pendaftaran readonly; info.blade badge "Bisa Dicicil" DIKEMBALIKAN di
          kartu Daftar Ulang saja. Tanpa migration/flag boleh_cicil. Docs sync: rules §1.3.2, workflow §2.2,
          test-scenarios A5.1/A5.9-A5.11. Test FinanceTest: spp_in_full + partial_spp_rejected + du_partial. 112 passed.
          --- HISTORI DISKUSI (arsip, keputusan final di atas) ---
          Plan draft: .hermes/plans/2026-07-19_k10.4-cicilan-optin-halaman-data-ortu.md
          KEPUTUSAN A TERKUNCI (2026-07-19): (A1) flag boleh_cicil di guardians (per-wali) — bukan di students,
          krn skrg ADA tempat set benar. (A2) BIKIN Halaman baru Data Orang Tua (datatable mirip Data Siswa:
          search+paginasi 25+filter kelas+filter status; kolom Nama Ortu·No.HP·Pekerjaan·Anak·Wali Kelas·Kelas·
          Status·Aksi; Anak/WaliKelas/Kelas ditumpuk SEJAJAR per anak, dedup bila sama; Aksi: profil ortu/edit/
          toggle cicil). (A3) status ortu = turunan anak: ada ≥1 anak aktif → boleh login, else BLOKIR login
          (root-cause 1 titik AuthController, pola guru is_aktif) — TANPA kolom is_aktif baru di guardians.
          (A4) filter kelas = wali punya ≥1 anak di kelas itu. (A5) siswa id_guardian NULL = TAK ada jalur izin
          cicil rilis ini (default harus-lunas); admin pakai T9.1 (buat akun wali) bila perlu. Ide user auto-create
          wali NIK→username/NISN→password DITOLAK (NIK/NISN identifier publik bukan rahasia → akun menganga;
          duplikasi T9.1 yg sudah aman via no_hp). BELUM DIBAHAS: Keputusan B (scope tagihan dikunci:
          keduanya/SPP saja/DU saja) & Keputusan C (bentuk penegakan: field kunci=sisa read-only vs tolak-server).
          Ada update-an baru terkait masalah pembayaran. Untuk biaya pendaftaran dan SPP fix tidak bisa dicicil, sedangkan untuk biaya daftar ulang bisa dicicil. Jadi mungkin tidak perlu fitur izin cicil, namun kita bisa diskusikan dulu, takutnya ada efek yg kena akibat dari update-an ini.
          KEPUTUSAN FINAL (2026-07-20): plan lama boleh_cicil per-wali DIBATALKAN. Aturan cicil sekarang per-JENIS,
          bukan per-wali. (A1) Pendaftaran = harus lunas/nominal penuh; SPP = harus lunas per tagihan bulan;
          Daftar Ulang = boleh parsial/cicil untuk SEMUA wali. TANPA migration guardians.boleh_cicil, TANPA toggle,
          TANPA logic izin per-wali. (C1) UI saat wajib-lunas: input nominal dikunci=sisa penuh/read-only + server guard.
          Titik sentuh nanti: Wali\PaymentController@store (tolak jenis=spp bila jumlah<sisa), wali/payments.blade
          (SPP kunci nominal=sisa, daftar_ulang tetap cicil), Wali\StatusController@uploadProof (pendaftaran ≥ nominal
          penuh), StoreInstallmentRequest, StorePaymentProofRequest. PaymentVerificationService TAK disentuh. Sinkron
          docs saat eksekusi: rules §1.3 (SPP+DU boleh cicil → hanya DU), workflow §2.2, test-scenarios A5.
        - [K10.5 Halaman Data Orang Tua] [DITUNDA 2026-07-20] Pilih opsi B (buat halaman) TAPI eksekusi ditunda,
          menunggu update user soal field profil ortu apa saja yg wajib diisi di CMS Wali (lihat catatan.md butir
          "nama Ayah & nama Ibu" — profil ortu akan ditambah kolom, jadi halaman jangan dibangun sebelum skema
          profil ortu final). Scope halaman (tetap, tanpa flag cicil): datatable master data ortu — search +
          paginasi 25 + filter kelas + filter status(anak aktif); kolom Nama Ortu·No.HP·Pekerjaan·Anak·Wali Kelas·
          Kelas·Status·Aksi (anak/walikelas/kelas ditumpuk sejajar per anak, dedup bila sama); Aksi: profil ortu +
          edit profil ortu. PLUS blokir login wali bila tak ada anak aktif (pola guru is_aktif, 1 titik AuthController,
          TANPA kolom is_aktif baru). Ide auto-create wali NIK/NISN tetap DITOLAK (identifier publik, tak aman).

    ═══════════════════════════════════════════════════════════════════
    FASE L — Backlog baru (disetujui 2026-07-20). Grup = tracking; raw brief user di bawahnya (sumber, jangan hapus).
    Urutan eksekusi cheap→expensive: L6 → L5 → L1 → L2 → L4 → L3 → L7 → L8 → L9.
    L1 blocker: skema/form berubah, semua fitur lain (L2/L3/K10.5) tunggu ini. L2.3 bisa paralel (nyentuh PaymentController saja).
    ═══════════════════════════════════════════════════════════════════

    L1. Skema data & form (BLOCKER)
        - [RENAME] [DONE 2026-07-21] guardian→ortu di SELURUH sistem (permintaan user, demi keterbacaan).
          Tabel guardians→parents, PK id_guardians→id_parents, FK id_guardian→id_parent. Model class
          App\Models\OrangTua (BUKAN Parent — PHP reserved word→ParseError). Relasi method ortu() di
          User+Student ($student->ortu). Role users.role 'wali'→'ortu'. Route name+URL wali.→ortu./portal/ortu.
          View dir resources/views/wali/→ortu/. Middleware RequireGuardianProfile→RequireOrtuProfile (+fix
          alias di bootstrap/app.php). Helper namaWali()/noHpWali() TETAP (pilih 1 ayah/ibu). Label UI
          "Wali"→"Orang Tua". JANGAN sentuh: isWaliKelas/WaliKelas/homeroom (wali kelas=guru, beda konsep),
          Alpine var waliOpen/waliAction. PITFALL saat eksekusi: (1) sed lupa dir routes/→role:wali ketinggalan;
          (2) composer classmap + bootstrap alias cache kelas lama→wajib `php composer.phar dump-autoload`;
          (3) label regex \bwali\b→"orang tua" merusak $wali var PHP di blade (jadi "$orang tua"=syntax error)
          & menabrak 2 var beda (registration-detail: $guardian vs $wali→$ortu vs $akun). 125 passed, build ✓.
        - [FIX] [DONE 2026-07-21] Sidebar CMS ortu KOSONG pasca-rename. Root cause: role DB='ortu' tapi
          layouts/dashboard.blade cek `$activeRole === 'orang tua'` (label lama) → blok menu tak pernah render.
          Ada 2 blok (desktop L70 + mobile L320) + accounts.blade filter option value & badge array key masih
          'orang tua'. Fix semua → 'ortu'. CMS admin & guru dicek: aman (role 'admin'/'guru' tak berubah).
        - [FIX] [DONE 2026-07-21] Validasi WAJIB client form daftar murid: (1) step-1 13 field L1.2 baru tak
          ke-validate (validateStep(1) lama cuma cek 4 field) → generic loop querySelectorAll('[required]') di
          $refs.step1 + attr `required` di tiap input wajib + banner error. (2) cabang required_if belum ada
          :required client: ngaji_dimana/ngaji_metode/ngaji_jilid `:required="mengaji === 'Sudah'"`,
          belajar_keterangan `:required="belajar === 'PAUD' || belajar === 'Les'"`, pekerjaan_lain
          `:required` + server `required_if`. Server-side required_if SUDAH ada di Student::profilRules().
        - [FIX] [DONE 2026-07-21] Input numerik-only (no_hp/nik/nisn/nis/nuptk) di 10 blade: `inputmode="numeric"
          pattern="[0-9]{N}"` + maxlength. PLUS 1 delegated handler di layouts/dashboard.blade footer (dipakai
          semua CMS): keydown preventDefault non-digit + input event strip paste/drag. type="number" SENGAJA
          dihindari (stepper arrows + rusak leading-zero). PITFALL: sed nambah attr dobel (inputmode/maxlength)
          bila input sudah punya — cek & strip dup setelah pass; sed \\d escaping rusak → patch manual.
        - [DONE 2026-07-21] Kolom NIS (Nomor Induk Sekolah) students: migration add_nis_to_students (string 18,
          nullable, unique), fillable, `digits_between:15,18` unique di MasterData+Guru store/update, UppercaseInput
          skip. Field NIS di form tambah/edit admin (Alpine studentCrud: WAJIB tambah 'nis' di `blank`!) + guru.
          Form ortu (register) TAK punya NIS — diberikan sekolah setelah diterima.
        - [DONE 2026-07-21] Git init + push GitHub github.com/abyan28/telaga (initial commit 220 file).
          git config user set lokal (dev@telaga.sch.id). Update: git add -A && commit && push.
        - [L1.1] [DONE 2026-07-21] Profil ortu → pisah Ayah & Ibu (flat kolom ayah_*/ibu_* + checkbox ada_*).
          Kolom lama guardians (nama/no_hp/pekerjaan/ttl) DIHAPUS. namaWali()/noHpWali() default ibu→ayah.
          Alamat PINDAH students→guardians (1 set, balik C10). SELESAI penuh: 9b (RegistrationController search
          ayah_no_hp/ibu_no_hp), seeders, 11 test. FIX kritis: (a) migration guardians drop unique index no_hp
          dulu sebelum drop kolom (SQLite :memory: tolak drop kolom ber-index); (b) after('ibu_nama') salah →
          buang after; (c) docblock `ayah_*​/ibu_*` menutup komentar (*/) → PHP ParseError → ganti prose;
          (d) updateProfile ValidationRules::nama() default wajib=true → spread setelah 'nullable' jadi required
          walau ortu tak aktif → ganti $ada?nama():nama(false). 125 passed, build ✓.
        - [L1.2] [DONE 2026-07-21] Field murid baru (flat +13 kolom students, required_if cabang, 3 form done).
          Student::profilRules() DRY. FIX: enum sudah_mengaji/pernah_belajar/ukuran_baju + agama/pendidikan/
          penghasilan (+ayah_/ibu_) DITAMBAH ke SKIP UppercaseInput (di-uppercase → gagal in:Sudah,Belum/Les dst).
          Payload test admin/guru CRUD +field L1.2 wajib. 125 passed, build ✓.
        - [L1.3] [DONE 2026-07-21] Field guru: +jenis_kelamin (enum L/P nullable) + tanggal_mulai_mengajar (date nullable).
          Migration 2026_07_21_000000. Teacher fillable+casts. Admin storeTeacher/updateTeacher +validasi+simpan.
          GuruController updateProfile +validasi. Admin modal (teachers.blade) +JK dropdown+tgl mulai. Guru profil
          (password.blade) view-mode dl +JK+Mulai Mengajar + form edit +2 field. 125 passed, build ✓.
        - [L1.4] [DONE 2026-07-21] Rename "siswa" → "murid" di semua halaman (18 blade + 4 controller flash/validasi).
          SKIP: tab-key Alpine ('siswa' di hashTabs/setTab/tab===), $peran storeProfilePhoto('siswa' folder path),
          identifier kode ($student/id_students/nama_lengkap). Script mask+regex word-boundary. 125 passed, build ✓.

    L2. Alur PPDB & status murid
        - [L2.1] [DONE 2026-07-21] Halaman Calon Murid (tab PPDB—Daftar Ulang, SEBELUM List Siswa): datatable calon yg sudah bayar DU (sebagian/lunas).
          Aksi batal-kelulusan → refund 70% (lunas 1jt→refund 700k; cicil→wajib lunas dulu, verifikasi, baru blokir akun bila tak ada
          anak aktif). Generate NIS (NSM+tahun2digit+urut abjad, mis. 123456789012+26+001) → resmi masuk Data Siswa, Calon Siswa kosong.
          % refund = setting di Set Pembayaran; NSM diinput admin di halaman ini.
          (IMPLEMENTASI FINAL: refund = max(0, terbayar − denda), denda = (100−persen_refund)% × total DU.
          persen_refund default 70 → denda 30%. Contoh bayar 500k dari 1jt → refund 200k; bayar 1jt → refund 700k;
          bayar <30% → form=dibatalkan, akun ortu TAK terblokir sampai lunasi 30% (gate login). Batal HANYA di CMS admin
          (tombol per-baris, tak ada di CMS ortu). Terbayar<denda → tombol batal diganti teks "Lunasi Rp X dulu".
          Generate NIS: GATE ppdb tutup + NSM 12 digit; sort abjad nama; nis=NSM+YY(TA PPDB)+urut3; status→aktif.
          Halaman: RegistrationController@calonMurid/cancelCalon/generateNis; route admin.calon-murid(.cancel/.generate-nis);
          view admin/calon-murid.blade (kolom NIS setelah Orang Tua). NSM+persen_refund pindah ke Pengaturan Sistem (tab
          baru sidebar bawah Konten Web: SettingController@system/updateSystem, view admin/system.blade, 3 sub-tab
          NSM/Refund/Tahun Ajaran — TA DIPINDAH dari Set Pembayaran). enum +refund (payment_transactions.jenis) +dibatalkan
          (registration_forms.status): ditambah di migrasi CREATE (fresh SQLite+MySQL) + migrasi ALTER MySQL-only utk DB live;
          bukti_path jadi nullable (refund tanpa bukti). Data Murid syarat K5.1 GANTI: PPDB tampil bila nis NOT NULL (bukan
          terbayar>0). Gate login ortu (AuthController): PPDB tutup + tak ada anak aktif/lulus-calon/dibatalkan-utang-denda → blokir;
          PPDB buka → gate mati → akun hidup lagi. Dashboard Keuangan Masuk exclude jenis refund. 126 test passed, build ✓.)
        - [L2.2] [DONE 2026-07-20] PPDB ditutup + belum bayar DU sama sekali → blok upload bukti + batalkan status lulus.
          RegistrationForm: method cancelLulusIfPpdbClosedAndUnpaid() (DRY; lazy cancel saat load payment/status oleh wali).
          Guard server: PaymentController@store tolak upload DU bila PPDB tutup & jumlah_terbayar=0. UI: flag 'terkunci' di
          payments.blade → tombol diganti badge merah "Pendaftaran Ditutup — Tidak Dapat Dibayar". 4 test baru. 120 passed (466).
        - [L2.3] [DONE 2026-07-20] Guard duplikat bukti bayar: cegah upload 2x saat masih ada transaksi pending.
          PaymentController@store (spp/daftar_ulang by jenis+referensi_id) + StatusController@uploadProof (pendaftaran
          by id_registration_form). Test FinanceTest x2 + WaliRegistrationTest x1. 114 passed.
        - [L2.4] [DONE 2026-07-21 — REKONSTRUKSI + UI BARU] TA PPDB dipisah dari TA sistem (independen).
          RIKSA: file Admin/RegistrationController.php RUSAK TOTAL (terhenti di jalan, cuma index() telanjang, 11 method hilang) →
          direkonstruksi penuh dari kontrak test (AdminWorkflowTest/AdminGuruViewsTest) + blade + model. SEKARANG:
          (a) Navigasi TA via 2 kartu di Set Pembayaran (D2): "TA Sistem Aktif" (< >) & "Target PPDB" (< >), masing-masing
              INDEPENDEN. Tiap klik → modal Alpine konfirmasi (A2, bukan native confirm). Prev/hak terkunci:
              * TA Sistem: next → auto-create + aktifkan, nonaktifkan TA lama (1 aktif); prev → cari di DB, bila
                belum ada tombol disable (B2, tak boleh bikin TA masa lampau).
              * TA PPDB (C): next → cari di DB, bila belum ada auto-create is_aktif=FALSE + set setting ppdb_ta_target;
                prev → cari di DB (fallback TA aktif bila target belum diset). Route BARU settings.academic-year (scope=aktif|ppdb, arah=prev|next).
          (b) Kelola Pendaftaran: kontrol PPDB jadi READ-ONLY status "TA Aktif: …" + "Target PPDB: …" (D2);
              tombol Buka/Tutup PPDB + modal Kuota tetap.
          (c) Logic lama tetap: pendaftaran + validasi usia baca AcademicYear::ppdbTarget() (settings→fallback TA aktif),
              BUKAN is_aktif; SPP/kelas/siswa sistem tetap is_aktif — nol efek.
          (d) Route lama ppdb.year/ppdb.target DIHAPUS (digerak ke SettingController::setAcademicYear).
          (e) UppercaseInput: scope/arah/dibuka/tab/sub DITAMBAH ke SKIP (nilai enum-navigasi tak boleh di-uppercase →
              gagal validate in:aktif,ppdb / in:prev,next).
          Skenario: PPDB 2027/2028 buka Okt 2026 saat TA sistem masih 2026/2027 — validasi usia baca ppdbTarget().
          Test: test_ppdb_controls_and_daftar_ulang (route diperbarui ke settings.academic-year scope=ppdb) + 124 lainnya, 125 passed.
        - [L2.5] [DONE — fix 2026-07-20] Validasi usia 4–6 th per 1 JULI. FIX: pakai TAHUN AJARAN (angka pertama TA aktif)
          bukan now()->year; usiaRule() return array (bukan pipe string yg keliru jadi OR). TA 2027/2028 daftar Okt 2026
          → cut-off 1 Juli 2027. Test baru: test_age_validation_uses_academic_year_not_calendar_year.
        - [L2.6] [DONE 2026-07-20] Form Kuota Pendaftaran (Target Diterima) → dari tab Settings dipindah ke modal float
          di halaman Kelola Pendaftaran (tombol "Kuota (N)" di card kontrol PPDB). POST ke settings.update (sometimes,
          subset kuota_pendaftaran saja). Dihapus dari dropdown $nominals settings.blade. $kuota di RegistrationController@index.
          Final sesi: 116 passed (455 assertions), build ✓.

    L3. Data wali
        - [L3.1] [SOLVED — User menyatakan tidak perlu dikerjakan 2026-07-22]
        - [L3.2] [SOLVED — User menyatakan tidak perlu dikerjakan 2026-07-22]
        - [L3.3] [DONE 2026-07-22] [=K10.5] Halaman Data Orang Tua (master ortu + filter/rowspan).

    L4. Akun & manajemen
        - [L4.1] [DONE 2026-07-20] Halaman Data Akun: datatable SEMUA role (admin/wali/guru) + search (username/email/HP) + filter role + filter status (aktif/nonaktif) + paginate 25 + edit (username/email/no_hp/password) modal + toggle nonaktif/aktif.
          Migration users.is_aktif (root-cause penonaktifan semua role). AuthController guard login ganti teacher->is_aktif →
          users.is_aktif (semua role). Sinkron: toggle guru di MasterDataController & Data Akun sync kedua arah. Test +7 (125, 481).
        - [L4.2] [DONE 2026-07-20] Filter tahun ajaran di Kelola Pendaftaran, default TA aktif terbaru (dropdown GET onchange;
          reuse pola daftarUlang/spp → `when(tahun)` + `$tahunOpsi` + pagination withQueryString). Test baru: test_registrations_filtered_by_academic_year. 115 passed.

    L5. UX / navigasi
        - [L5.1] [DONE 2026-07-20] UI Data Siswa cms guru → 2 card (chips kelas digabung ke card Welcome, card chips terpisah dibuang).
        - [L5.2] [DONE 2026-07-20] UI verifikasi bukti di registration-detail: hyperlink "Lihat Bukti" → thumbnail+openBerkas (reuse pola spp.blade), kolom center.
        - [L5.3] [DONE 2026-07-20] Logout → web depan — sudah benar sejak awal (logout → route('home')), nol perubahan.
        - [L5.4] [DONE 2026-07-20] Deteksi sesi aktif di halaman login → redirect (AuthController@showLogin → redirectByRole).
        - [L5.5] [DONE 2026-07-20] Slug URL (Student/Teacher/RegistrationForm) via trait HasSlug (auto-gen, suffix -{PK}
          bila nama kembar). Admin-only models (SchoolClass/PaymentTransaction/User) tak di-slug.

    L6. Hapus fitur
        - [L6.1] [DONE 2026-07-20] Hapus fitur catatan siswa oleh guru (SEMENTARA — tabel/model StudentProgressNote
          dibiarkan). Buang route guru.students.notes, storeNote, eager progressNotes, UI modal+tab wali/guru.
        - [L6.2] [DONE 2026-07-22] Biodata lengkap di halaman Detail & Verifikasi Pendaftaran. Card setelah bukti
          bayar: Biodata Calon Murid (full-width, SEMUA field form pendaftaran + NIS + alamat keluarga). Di bawahnya
          2 card berdampingan: Biodata Ayah & Biodata Ibu (masing-masing nama, TTL, agama, pendidikan, pekerjaan
          +lainnya, penghasilan, no HP). view:cache + 21 admin test hijau.

    L7. Data dummy
        - [L7.1] [DONE 2026-07-22] DummyDataSeeder: 83 murid+ortu (status acak: alumni/aktif-DU-lunas/DU-cicil/submitted/menunggu-verif/bayar-diverif/diproses-seleksi/gagal/nonaktif/1ortu-2anak/cicil+SPP) + 20 guru + transaksi keuangan (DU/SPP/pendaftaran diverif/pending/ditolak). Idempoten firstOrCreate, dipanggil dari DatabaseSeeder. PITFALL: (1) enum students.status HANYA aktif/lulus/nonaktif (dropout tak ada) → pakai nonaktif; (2) interpolasi "{$i % 10}" ParseError → concat; (3) guru no_hp offset 081355 hindari bentrok DemoSeeder.

    L8. Import / export (diskusi)
        - [L8.1] [SELESAI 2026-07-22] Auto-akun ortu saat input murid lama + IMPORT CSV MASSAL.
          Form Tambah Murid (admin) +field "No. HP Orang Tua" mode create. Diisi → MasterDataController::linkOrtu()
          buat/tautkan akun: username=no_hp, password=NIK anak, must_change_password, no_hp→ibu_no_hp. Merge kakak-adik
          by users.no_hp (1 akun, sandi=NIK kakak, tak berubah). Kosong → murid saja. linkOrtu dipakai bareng
          createOrtuAccount (T9.1) — buang duplikasi. KEPUTUSAN kredensial: password=NIK anak (bukan no_hp — no_hp bocor
          di grup WA ortu; NIK tak dihafal/tak berformat lebih aman). NISN/NIS ditolak (publik/ketebak).
          IMPORT CSV (2026-07-22): MasterDataController@importForm/importStudents/downloadTemplate + route
          admin.students.import(.run/.template). Halaman admin/data/import.blade (child dropdown "Data Murid" di sidebar,
          desktop+mobile). fgetcsv stdlib (nol library). Opsi B skip+report: NIK duplikat dilewati (append-only re-import),
          baris gagal dikumpulkan "Baris N: pesan". Kolom: nama_lengkap,nama_panggilan,nik,nis,nisn,jenis_kelamin,agama,
          tempat_lahir,tanggal_lahir,anak_ke,jumlah_saudara,warga_negara,bahasa_keseharian,kondisi_kesehatan,tahun_ajaran,
          status,no_hp_ortu. Default: agama ISLAM, TA aktif, status aktif. no_hp_ortu diisi → auto-akun ortu (linkOrtu).
          Template database/data/template-import-murid.csv (tombol unduh). PITFALL: enum kosong (sudah_mengaji/pernah_belajar/
          ukuran_baju) & kolom unik kosong (nis/nisn/nama_panggilan) di-normalkan '' → null sebelum create (SQLite CHECK
          constraint tolak ''). Test ImportCsvTest x6. Total 134 passed (514).
        - [L8.2] [SELESAI 2026-07-23] Export CSV/PDF data siswa + laporan SPP.
          ReportController +6 method (studentsQuery/sppQuery + 4 export). Route admin.reports.students(.csv/.pdf) +
          admin.reports.spp(.csv/.pdf). View admin/reports-students.blade (filter kelas/TA/status/angkatan/murid-baru) +
          reports-spp.blade (filter siswa/kelas/TA/bulan/dari-sampai/status-bayar). PDF dompdf landscape (2 template baru).
          Sidebar: "Laporan" dropdown (Data Murid + Laporan SPP) di atas "Audit Log" (rename). PITFALL: status_bayar
          di-UPPERCASE middleware → strtolower() di controller. Test ReportExportTest x6. 139 passed (529).
        - [L8.3] [SELESAI 2026-07-23] Kolom angkatan otomatis dari prefix NIS.
          Migration add_angkatan_to_students (SMALLINT null). Student::nisToAngkatan() — substr(nis,12,2) → 20YY.
          Trigger: generateNis (batch), storeStudent, updateStudent, importStudents (NIS menang > manual). Form
          tambah/edit admin +field Angkatan. Profil siswa +NIS+Angkatan. Laporan data siswa +filter angkatan distinct DB.
          IMPORT_COLUMNS +angkatan. PITFALL: closure generateNis butuh use($yy); $data['angkatan'] null-coalesce.

    L9. Deploy / email (blocked SMTP)
        - [L9.1] [=T1.1–T1.3] Verifikasi email daftar (***T1.1 SELESAI 2026-07-22***) + lupa sandi (***T1.2/T1.3 SELESAI 2026-07-22***) + link di form.
        - T1.1: OTP 6-digit via Mailjet SMTP, session-based, guard register. Email masuk SPAM (blm punya domain).
        - T1.2/T1.3: Laravel Password broker, ForgotPassword+ResetPassword controller, email Indonesia custom, link login. Email SPAM (blm punya domain).

    ─── RAW BRIEF USER (sumber, jangan hapus) ───
- Untuk fitur catatan siswa yg diinput oleh guru, sementara ini hapus saja untuk fitur tersebut. Update semua halaman yg berkaitan dengan perubahan ini, baik di cms wali, admin, guru.
- tambahkan sekitar 100 (bisa kurang/lebih) data dummy di semua tabel pada data sistem ini untuk dilakukan pengecekan. Datanya meliputi data wali, siswa, guru, kelas, pembayaran, dan segalanya yg berkaitan dengan sistem ini.
- perbaiki UI halaman data siswa di cms guru yg jelek banget di mana filter kelas ada card atas, gak terintegrasi dengan card databel data siswa. Lalu tulisan list kelas yg diampu guru tersebut malah jadi pemisah antara card selamat datang dan card Daftar Siswa yg mana jika dilihat jelek banget. Bisa rapikan UI nya agar enak dilihat? Kalau bisa, cuma ada 2 card saja tanpa pemisah tulisan gitu. List kelasnya entah kamu taruh di mana, yang penting enak dilihat.
- kata siswa rename jadi murid di semua halaman?
- Pada profil orang tua, tambahkan form nama Ayah dan nama Ibu. Sedangkan kolom nama pada tabel guardians tetap dan bisa diisi nama ayah/ibu. Update semuanya yg berkaitan tentang ini ya, Terutama di cms wali bagian isi profil orang tua/wali, cms admin dan guru bagian profil siswa, dan terntunya profil orang tua di cms admin.
- buat slug di semua fungsi yg memanggil id di url-nya.
- sistem deteksi sesi ketika user akses ke halaman login. Jika masih ada sesi yg aktif, maka langsung redirect ke sesi yg aktif tersebut.
- benahi UI verifikasi bukti pembayaran di halaman Detail & Verifikasi Pendaftaran
- setelah keluar dari cms diarahkannya ke halaman login, buka web depan.
- pada cms wali, setelah upload bukti daftar ulang, tidak diberi flag bahwa wali sudah upload. Akibatnya wali bisa upload 2 kali dan muncul di sistem 2 kali juga untuk verifikasi. Misal admin memverifikasi keduanya, maka di sistem akan tercatat dia membayar 2xlipat dari jumlah yg ditentukan. Padahal dia transfer hanya sekali. Cek juga pada bagian upload bukti pembayaran di pendaftaran dan spp.
- untuk pengisian alamat pada form pendaftaran, lebih baik diisi di profil wali murid, agar ketika dia mau mendaftarkan 2 anak atau lebih, maka dia tidak perlu mengisi form alamat lagi.
- Ketika ada wali yg belum membayar daftar ulang sama sekali dan ppdb sudah ditutup, maka harusnya dia sudah gak bisa upload bukti pembayaran lagi dan status kelulusannya dibatalkan.
- apakah tahun ajaran baru bisa di-generate by system, bukan ketik manual?
- Di halaman Kelola Pendaftaran Siswa Baru, buat filter tahun ajaran. Di mana ketika wali generate tahun ajaran baru, maka filter-nya ikut ter-update dan otomatis menjadi default filter tahun ajaran baru yg aktif saat akses halaman Kelola Pendaftaran Siswa Baru.
- Halaman data akun di cms admin menampilkan semua data akun admin, wali, guru. Bisa edit atau nonaktfikan akun juga.
- untuk siswa lama yg belum punya data wali, bagaimana jika data wali auto terbuat ketika nik siswa dan nisn siswa sudah dimasukkan? Jadi nanti nik dikirim ke kolom username pada tabel users dan nisn dikirim ke kolom password pada tabel users. Namun, setelah login wali harus langsung ubah username dan password. (samakan case ini dengan akun guru kalau berhasil agar kolom no_hp di users bisa dihapus)
- validasi usia pendaftaran siswa di form pendaftaran dengan usia 4-6 tahun. Dihitung dari tanggal 1 Juli maksimal apakah di tanggal tersebut di tahun itu sudah masuk umur 4 atau belum. Begitupun juga apakah di tanggal 1 Juli anak tersebut masih umur 6 tahun atau gak. 
- Ada update terkait status siswa. Setelah melakukan pembayaran daftar ulang, baik yg sudah membayar sebagian (dicicil) ataupun yg membayar penuh (lunas), calon siswa tersebut masih belum resmi jadi siswa Al Kautsar, sehingga jangan masukkan ke data siswa dulu, melainkan buat halaman khusus di slide bar PPDB - daftar ulang sebelum slidebar List Siswa. Buat datatabel yg berisi calon siswa yg sudah membayar sebagian/lunas dari biaya daftar ulang. Nah, nanti di halaman tersebut ada aksi untuk membatalkan kelulusan anak tersebut jika ortu calon siswanya gak jadi pingin sekolah di RA AL Kautsar. Untuk pembayaran yg sudah dilakukan, sistem update agar dibuat catatan uang tersebut di-refund 70%. Jadi jika biaya daftar ulang 1 juta, maka jika ortu sudah bayar lunas 1 juta, nanti 700k akan di-refund. Sebaliknya, jika ortu baru bayar sebagian, misal baru bayar 100k, maka dia wajib bayar 200k lagi, setelah pembayaran diverifikasi baru diblokir akunnya jika tidak ada anaknya yg aktif sekolah di sini lagi.  Sedangkan untuk meresmikan semua siswa yg sudah membayar sebagian/lunas biaya daftar ulang, dilakukan dengan cara generate Nomor Induk Sekolah (NIS) di halaman daftar calon siswa ini dg format Nomor Statistik Madrasah+tahun+nomor urut berdasarkan abjad siswa, contoh NSM 123456789012+26+001 --> 12345678901226001. Value besaran persen uang refund bisa diset di form dropdown pada halaman Pembayaran\Set Pembayaran\Nominal Biaya Dinamis. Sedangkan untuk input format NIS diinput di halaman daftar calon siswa. Setelah klik generate NIS, maka semua data calon siswa mendapatkan NIS berdasarkan urutuan nama abjadnya (dari A-Z) dan baru masuk ke halaman data siswa. Sedangkan di halaman data calon siswa jadi kosong.
- Form profil guru tambah jenis kelamin dan tanggal mulai mengajar. Update semua hal yg berkaitan dg data profil guru baik di cms admin/guru.
- Form pendaftaran di cms wali dan form siswa (untuk tambah data siswa lama) di cms admin, ada update-an terbaru terkait data apa saja yg harus diisi pada form-nya. Berikut update-an terbarunya: Nama Lengkap, Nama Panggilan, Jenis Kelamin, Agama (fill auto Islam), Tempat dan Tanggal Lahir, Alamat, Anak ke-, Jumlah saudara, Warga Negara, Bahasa Keseharian, Kondisi Kesehatan Khusus. Semuanya wajib diisi ya dan buat validasi dari masing2 form field-nya jika perlu. Lalu ada tambahan form terkait keterangan anak tambahan agar guru tahu kemampuan anaknya, berikut data yg perlu diisi: Sudah Mengaji (Sudah/Belum [Jika sudah, munculkan field tambahan untuk isi dia ngaji di mana, memakai metode apa, dan jilid berapa]), Sudah Pernah Belajar (PAUD/Les/Belum [Jika dijawab PAUD/Les, munculkan field tambahan untuk isi PAUD apa dan Les apa]), Ukuran Baju (S/M/L/XL/XXL/Jumbo).  
- Di skema terbaru setelah orang tua/wali murid buat akun, ortu tersebut kan wajib langsung isi profil. Nah, ini ada update-an terbaru terkait data apa saja yg perlu diisi pada form profil tersebut. Jadi bakal ada 2 pengisian profil (buat kayak di halaman pendaftaran siswa yg 1 card tapi bisa dilanjutkan gitu) yg terdiri dari profil Ayah dan Ibu. Untuk profil Ayah, berikut data yg perlu diisi: Nama Lengkap, Tempat Tanggal Lahir, Agama (tidak auto fill), Pendidikan Terakhir (S3/S2/S1/D4/D3/D2/D1/SMA/SMP/SD), Pekerjaan (PNS/Swasta/Pedagang/TNI/POLRI/Guru/Lainnya [jika pilih lainnya, diisi]), Penghasilan Bulanan (<1 juta/1 juta - 2 juta/3 juta - 5 juta/> 5juta), No. HP (bisa beda antara no hp ayah dan ibu). Untuk profil Ibu, berikut data yg perlu diisi: Nama Lengkap, Tempat Tanggal Lahir, Agama (tidak auto fill), Pendidikan Terakhir (S3/S2/S1/D4/D3/D2/D1/SMA/SMP/SD), Pekerjaan (PNS/Swasta/Pedagang/TNI/POLRI/Guru/Lainnya [jika pilih lainnya, diisi]), Penghasilan Bulanan (<1 juta/1 juta - 2 juta/3 juta - 5 juta/> 5juta), No. HP (bisa beda antara no hp ayah dan ibu). Form-nya antara profil Ayah dan Ibu mirip.
- Form Kuota Pendaftaran (Target Diterima) pindahkan ke halaman Pendaftaran siswa (buat halaman float saja biar UI nya enak dilihat) 
- tambahkan fitur import data siswa lama melalui file csv di cms admin bagian data siswa. Jadi mekanismenya itu nanti file csv yg disiapkan formatnya harus sesuai dengan data profil siswa, lalu sistem cek apakah datanya sudah sesuai dengan format atau belum (misal formatnya nama lengkap, nik, nisn, ttl, dsb). Untuk alamat (karena harus dipilih secara dropdown) nanti biar ortu yg melengkapi melalui cms wali. fitur ini bisa diskusi dulu.
- Bisa download/export data siswa dalam bentuk csv/pdf dengan filter berdasarkan kelas, tahun ajaran, status aktif, dan siswa baru (diketahui berdasarkan nomor induk sekolah/tahun masuk). Begitupun juga laporan keuangan untuk spp bulanan. Bisa di-download/di-export dalam bentuk csv/pdf dg filter berdasarkan siswa perorangan, ortu, tahun ajaran, bulan, periode waktu yg ditentukan. Laporannya bisa menyangkut yg sudah membayar spp, yang belum membayar spp, dan semua orang baik yg sudah membayar ataupun yg belum bayar.