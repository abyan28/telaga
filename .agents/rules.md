# Rules (Aturan Sistem & Konvensi) — RA Al Kautsar

Dokumen ini berisi seluruh aturan bisnis, aturan teknis database, serta pedoman pengerjaan proyek yang harus ditaati selama pengembangan sistem.

---

## 1. Aturan Bisnis Utama (Business Rules)

### 1.1 Hak Akses & Pembatasan Role
1. **Wali Murid**:
   * Hanya dapat melihat dan mengelola data pendaftaran miliknya sendiri serta data anaknya.
   * Hanya dapat melihat tagihan, riwayat transaksi, dan mengunggah bukti pembayaran untuk anaknya sendiri.
2. **Admin**:
   * Memiliki akses penuh ke seluruh data sistem (super admin).
   * Berwenang memproses pendaftaran, memverifikasi dokumen, memverifikasi seluruh jenis pembayaran, mengelola data siswa/guru, dan melihat audit log.
3. **Guru**:
   * **Relasi**: Satu orang guru dapat mengampu/mengajar di lebih dari satu kelas (banyak kelas).
   * **Pembatasan**: Hanya berhak melihat dan mengelola data siswa di kelas-kelas yang diampunya saja.
   * Tidak memiliki akses ke data kelas lain, modul keuangan/pembayaran, dan pengaturan hak akses/role.

### 1.2 Pendaftaran & Seleksi Murid Baru
1. **Formulir**: NIK anak wajib diisi saat pendaftaran. KK dan Akta Kelahiran wajib diunggah.
2. **Biaya Pendaftaran**: Pembayaran pendaftaran bersifat **dinamis** dan nominalnya dapat diatur oleh Admin melalui CMS.
3. **Keputusan Seleksi**: Penentuan kelulusan murid baru dilakukan secara manual oleh Admin dengan menekan tombol **Lulus** atau **Gagal** di CMS (tidak menggunakan sistem filtrasi otomatis).

### 1.3 Keuangan & Pembayaran
1. **Verifikasi Manual**: Semua pembayaran (Pendaftaran, Daftar Ulang, SPP) dilakukan secara manual dengan mengunggah bukti transfer, yang kemudian diverifikasi oleh Admin. Admin berhak menolak bukti transfer jika tidak jelas.
2. **Cicilan / Pembayaran Parsial (per-JENIS, K10.4)**:
   * **Biaya Pendaftaran**: WAJIB lunas/dibayar penuh (tidak dapat dicicil).
   * **SPP bulanan**: WAJIB lunas per tagihan bulan (tidak dapat dicicil).
   * **Daftar Ulang**: BOLEH dicicil/parsial untuk semua wali.
   * Rincian sisa tunggakan harus dijabarkan secara jelas (komponen apa saja yang belum dibayarkan dari total tunggakan).
   * Penegakan: server guard di `Wali\PaymentController@store` (tolak SPP bila `jumlah < sisa`) & `Wali\StatusController@uploadProof` (tolak pendaftaran bila `jumlah < nominal`); UI input dikunci ke nominal penuh (read-only) untuk pendaftaran & SPP.
3. **Akumulasi Tunggakan SPP**:
   * Kekurangan pembayaran SPP bulanan tetap tercatat pada bulan yang bersangkutan dengan status **Belum Lunas** atau **Kurang**.
   * Tunggakan tidak otomatis digabungkan atau digeser ke tagihan bulan berikutnya sebagai satu kesatuan tagihan baru, melainkan tetap dipecah per bulan agar riwayatnya transparan.
4. **Sanksi**: Tidak ada denda keterlambatan untuk pembayaran apa pun.
5. **Generate Tagihan SPP Bulanan (Otomatis)**:
   * Tagihan SPP bulanan dibuat otomatis untuk setiap siswa **aktif** pada awal bulan, via Laravel Scheduler + command artisan `spp:generate` (dijadwalkan `monthlyOn(1, ...)`).
   * Nominal SPP bersifat dinamis (diambil dari setting yang diatur Admin). Command wajib idempotent (pakai `firstOrCreate` per `id_student` + `bulan`) agar tidak membuat tagihan dobel.
   * Disediakan juga **tombol "Generate Tagihan Bulan Ini" manual di CMS Admin** (memanggil command yang sama) sebagai cadangan bila cron server belum aktif (mis. saat development).

### 1.4 Pengelolaan Data Siswa oleh Guru/Admin
1. Guru dan Admin dapat menambahkan dan mengelola data profil siswa (baik siswa baru hasil pendaftaran maupun siswa lama yang dimasukkan manual).
2. Guru dan Admin diperbolehkan menginput dan memperbarui **data catatan perkembangan sederhana** untuk setiap siswa.
3. **Wali Kelas (K8.1)**: seorang guru dapat ditetapkan Admin sebagai **wali kelas** dari satu kelas (`classes.id_homeroom_teacher`; 1 kelas = 1 wali, 1 guru = maks 1 kelas). Hak akses guru berjenjang:
   * **Wali kelas** → boleh **tambah** siswa (langsung tertaut ke kelasnya) & **edit** profil siswa di kelasnya, selain lihat + catatan.
   * **Guru biasa** (hanya mengajar kelas tsb) → **hanya** boleh **lihat** profil siswa & **input catatan perkembangan**; tidak boleh tambah/edit.
   * Penetapan wali kelas dilakukan Admin via form Kelas atau form Guru.

### 1.5 Autentikasi & Pembuatan Akun (Model Auth)
*Sistem menggunakan **satu tabel `users`** dengan kolom pembeda `role` (nilai: `ortu`, `admin`, `guru`). RBAC diterapkan melalui middleware role (mis. `role:admin`, `role:ortu`). Tidak ada tabel/guard login terpisah per role — pemisahan role bersifat logis melalui `role` + middleware.*

1. **Akun Wali Murid (self-signup ATAU dibuat Admin untuk wali lama)**:
   * Wali murid mendaftar sendiri melalui halaman signup publik (email + password).
   * Nilai `role` **dikunci otomatis oleh sistem menjadi `ortu`** — form signup TIDAK menyediakan pilihan role. Ini mencegah eskalasi hak akses.
   * **Wali murid lama** (siswa diinput admin/guru, belum punya akun): Admin membuatkan/menautkan akun dari tab Data Siswa (T9.1). `username = password = no_hp` wali; siswa kakak-adik dengan no_hp sama otomatis satu akun. Akun ini di-flag `must_change_password` → dipaksa ganti password saat login pertama.
2. **Akun Guru (dibuat oleh Admin via CMS)**:
   * Guru **tidak dapat mendaftar sendiri**. Akun guru dibuat oleh Admin melalui menu manajemen di CMS, dengan `role` ditetapkan `guru`.
   * **Password awal guru = NUPTK** yang didaftarkan (T5.10, wajib diisi). Akun di-flag `must_change_password` → **dipaksa** ganti password saat login pertama via middleware `ForceChangePassword` (T9.1).
   * **Login guru boleh pakai email ATAU nomor HP** (T3.2). Admin sering hanya tahu no. HP saat membuat akun, jadi email boleh dikosongkan dan diisi guru sendiri belakangan lewat CMS. `no_hp` wajib (identifier login); `email` opsional & unik bila diisi.
3. **Akun Admin (multi-admin)**:
   * Admin dapat membuat akun Admin lain melalui CMS (mendukung banyak admin untuk membantu pengelolaan sistem), dengan `role` ditetapkan `admin`.
   * **Admin pertama** dibuat melalui database **Seeder** saat setup awal sistem (mengatasi kondisi "ayam & telur"). Kredensial awal admin seeder di-**generate otomatis** dan wajib diganti setelah login pertama.
4. **Prinsip Keamanan**: Role berkuasa (`admin`/`guru`) hanya boleh diberikan oleh pihak yang sudah berwenang (Admin) atau oleh Seeder — tidak pernah melalui pendaftaran publik.

### 1.6 Struktur Data Akun & Profil (Relasi users ↔ profil)
*Tabel `users` menyimpan **hanya data autentikasi** (username, email, no_hp, password, role). Nama asli setiap orang disimpan di tabel profil (T3.2). Data profil spesifik per role dipecah ke tabel terpisah dengan relasi **1-ke-1** ke `users` (FK `id_user`). Ini sesuai rekomendasi PRD §10 dan menjaga tabel `users` tetap ramping tanpa kolom NULL yang tidak relevan.*

1. **Profil Orang Tua/Wali** → tabel `parents` (FK `id_user` → `users.id_users`). Menyimpan data ayah/ibu (prefix `ayah_`/`ibu_`), no. HP, pekerjaan, alamat keluarga, dll. Model `App\Models\OrangTua` (BUKAN `Parent` — PHP reserved word). Relasi: `User hasOne OrangTua` via method `ortu()`.
2. **Profil Guru** → tabel `teachers` (FK `id_user` → `users.id_users`). Menyimpan `nama`, `nuptk` (juga password awal, lihat §1.5), no. HP (wajib), alamat, dll. Relasi: `User hasOne Teacher`.
3. **Admin**: cukup memakai baris di `users` saja, **tidak** memiliki tabel profil terpisah. Nama admin disimpan di kolom `users.username`.
4. **Siswa** (`students`) BUKAN akun login — berdiri sendiri (tidak menempel ke `users`), menyimpan NIK, nama, kelas, dll. Relasi ke wali/kelas diatur lewat FK sesuai konvensi §2.
5. **Nama tampilan (T3.2)**: `users.username` hanya fallback (untuk wali/guru diisi email/HP; untuk admin diisi nama). Helper `User::displayName()` mengembalikan `guardian.nama ?? teacher.nama ?? username`. Login memakai email ATAU no_hp (bukan username).

### 1.7 Tahun Ajaran (Academic Years)
*Sistem beroperasi per tahun ajaran melalui tabel `academic_years`. Banyak entitas terikat ke tahun ajaran (pendaftaran, kelas, tagihan SPP, path folder upload).*

1. **Format**: tahun ajaran ditulis `YYYY/YYYY`, contoh `2026/2027`. Untuk nama folder upload (rules.md §3), garis miring diganti strip menjadi `2026-2027` (karena `/` tidak valid pada nama folder).
2. **Satu Aktif**: hanya **satu** tahun ajaran yang berstatus aktif (`is_aktif = true`) pada satu waktu. Semua data baru (pendaftaran, tagihan SPP, kelas) otomatis dikaitkan ke tahun ajaran aktif.
3. **Arsip**: menaikkan tahun ajaran = menandai tahun baru sebagai aktif dan menonaktifkan yang lama; data lama tetap tersimpan sebagai arsip (tidak dihapus).
4. **Pengelolaan**: Admin menambah/mengaktifkan tahun ajaran via CMS. **Seeder** membuat 1 tahun ajaran aktif awal agar sistem langsung dapat dipakai.

### 1.8 Konten Website Publik (CMS)
*Konten halaman publik (Beranda, Profil, Info Pendaftaran, Kontak/Footer) TIDAK boleh di-hardcode di Blade — wajib dari database agar operator (Admin) dapat memperbaruinya tanpa mengubah kode (PRD §7.15).*

1. **Pengelola**: hanya **Admin** yang boleh mengubah konten publik (route CMS di bawah middleware `role:admin`).
2. **Dua penyimpanan (pendekatan hibrida)**:
   - `site_contents` (key-value) untuk teks/angka **tunggal** (mis. `home.hero_title`, `profile.visi`, `kontak.alamat`). Helper `SiteContent::get(key, default)`.
   - `site_features` (baris) untuk item **berulang & dinamis** — dibedakan kolom `grup` (`program`, `misi`, `persyaratan`, `alur`, `statistik`). Operator dapat **tambah/hapus/urutkan** (kolom `urutan`, `aktif`).
3. **Biaya di halaman publik = sumber tunggal `settings`**: nominal pendaftaran/daftar ulang/SPP di halaman Info WAJIB diambil dari `settings` (bukan disalin ke `site_contents`), supaya konsisten dengan modul keuangan.
4. **Tenaga pendidik = dari `teachers`**: daftar guru di halaman Profil diambil dari tabel `teachers` yang `tampil_di_web = true`. Kolom pendukung: `jabatan`, `foto_path`, `tampil_di_web`. Bukan konten statik.
5. **Media/upload**: gambar/logo/foto guru divalidasi konsisten dengan §3 (format gambar, maks 2 MB), disimpan di `storage/app/public/konten/`.
6. **Nilai default via seeder**: seeder mengisi konten awal = teks/gambar mockup yang ada saat ini, sehingga penerapan CMS tidak mengubah tampilan.

---

## 2. Aturan Teknis & Konvensi Database

Untuk menjaga konsistensi dan kemudahan pemeliharaan, seluruh tabel database harus mengikuti konvensi penamaan berikut:

### 2.1 Primary Key
* Format penamaan: `id_(nama_tabel)`
* Contoh:
  * Tabel `users` -> Primary Key: `id_users`
  * Tabel `students` -> Primary Key: `id_students`
  * Tabel `teachers` -> Primary Key: `id_teachers`
  * Tabel `registration_forms` -> Primary Key: `id_registration_forms`

### 2.2 Foreign Key
* Format penamaan: `id_(nama_foreign_key_singular)`
* Contoh:
  * Referensi ke `users` -> `id_user`
  * Referensi ke `students` -> `id_student`
  * Referensi ke `classes` -> `id_class`
  * Referensi ke `academic_years` -> `id_academic_year`

*Aturan penamaan ini wajib diterapkan pada migration, model, controller, relasi Eloquent, dan query database.*

### 2.3 Konsekuensi Implementasi (WAJIB dipatuhi)

Karena konvensi di atas **berbeda dari default Laravel** (default: PK = `id`, FK = `<tabel>_id`), Laravel tidak dapat menebak otomatis. Maka setiap kali membuat kode WAJIB:

1. **Di setiap Model**: definisikan `$table` dan `$primaryKey` secara eksplisit.
   ```php
   class Student extends Model
   {
       protected $table = 'students';
       protected $primaryKey = 'id_students'; // WAJIB, karena bukan 'id'
   }
   ```
2. **Di setiap relasi Eloquent**: sebutkan nama FK dan PK tujuan secara eksplisit (argumen ke-2 & ke-3), jangan mengandalkan tebakan Laravel.
   ```php
   // FK 'id_class' di tabel ini menunjuk PK 'id_classes' di tabel classes
   public function kelas()
   {
       return $this->belongsTo(Classes::class, 'id_class', 'id_classes');
   }
   // Sisi sebaliknya (hasMany): FK 'id_class' di students, PK 'id_classes' lokal
   public function siswa()
   {
       return $this->hasMany(Student::class, 'id_class', 'id_classes');
   }
   ```
3. **Di setiap migration**: definisikan kolom FK manual lalu deklarasikan constraint dengan `references()` + `on()` eksplisit.
   ```php
   $table->unsignedBigInteger('id_class');
   $table->foreign('id_class')->references('id_classes')->on('classes');
   ```
4. **Verifikasi relasi**: setiap relasi antar-model harus diuji (feature/unit test) untuk memastikan FK/PK tersambung benar — ini area paling rawan bug pada konvensi non-standar.

### 2.4 Dependency & Tooling Backend
* **Export PDF**: gunakan paket `barryvdh/laravel-dompdf` (render Blade/HTML → PDF). Dipakai untuk laporan transaksi, rekap tunggakan, dan data siswa.
* **Export CSV**: dibuat **native** dengan Laravel (streamed response), tanpa paket tambahan.
* **Penjadwalan (SPP)**: gunakan **Laravel Scheduler** + command artisan `spp:generate` (lihat §1.3 poin 5). Produksi memakai cron `php artisan schedule:run` tiap menit; development boleh mengandalkan tombol manual di CMS.
* **Email notifikasi**: pada tahap dev gunakan `MAIL_MAILER=log` (email ditulis ke log), diganti SMTP asli saat produksi.

---

## 3. Ketentuan File & Upload
1. **Ukuran Maksimum**: Batas ukuran setiap file dokumen yang diunggah (KK, Akta, Bukti Pembayaran) adalah **2 MB**.
2. **Penyimpanan**: File disimpan di folder `storage/app/public/pendaftaran/[tahun-ajaran]/[nama-anak]/`.
3. **Format Penamaan File**:
   * Akta: `Akta-Kelahiran_[nama-anak].[ekstensi]`
   * KK: `KK_[nama-anak].[ekstensi]`
   * Foto: `Foto_[nama-anak].[ekstensi]`
   *(Spasi pada nama anak diganti dengan tanda hubung `-`)*

---

## 4. Pedoman Pengerjaan AI (Tasklist Rules)

Setiap kali selesai mengerjakan satu tugas/fitur, AI wajib memperbarui file `.agents/tasklist.md` sebelum melaporkan hasil pengerjaan kepada user dengan ketentuan:
1. Tandai task yang selesai dengan centang `[x]`.
2. Tambahkan emoji ✅ di depan task.
3. Update progress keseluruhan proyek (misal: `Progress: 35%`).
4. Tambahkan catatan singkat di bawah task mengenai file apa saja yang dibuat/diubah.
   *Contoh:*
   ```markdown
   - [x] ✅ Task 2.3 - Membuat Room Migration `[Mudah]` (Selesai)
     * Membuat file `database/migrations/xxxx_create_students_table.php`
   ```
5. update tasklist.md setiap selesai 1 task.
6. kasih summary jelas di akhir setiap task.
7. kalau mulai limit, berhenti di check point yg rapi
8. jadi nanti next agent tinggal baca task list dan tahu tepat mana yang dilanjut.

## 5. Pedoman Penulisan Coding

Berikan komentar untuk setiap fungsi kodingan yg dibuat, sehingga memudahkan programmer untuk memahami kodingannya.

## 6. Konvensi UI/UX (WAJIB diterapkan otomatis di fitur baru & perubahan)

Pola berikut sudah jadi standar proyek. Terapkan LANGSUNG saat membuat/mengubah fitur — jangan tunggu diminta.

1. **Tab-halaman persisten setelah reload** — halaman CMS dengan tab (bukan sidebar utama kiri) yang punya
   aksi submit (`back()` reload) WAJIB menyimpan tab aktif di URL **query string** (`?tab=`) agar tak balik
   ke tab default. (JANGAN pakai hash `#tab` — fragment hilang saat form POST & tak masuk Referer, sia-sia.
   Query string ikut Referer → controller `back()` redirect balik dgn query → tab tetap.)
   - 1 level tab: helper global `hashTabs('defaultTab')` (`layouts/dashboard.blade`). Tombol pakai `setTab('x')`.
   - Multi-level (tab + sub-tab): `?tab=&sub=` (lihat `admin/content.blade` `contentCms()` → `syncUrl()`).
   - `init()` tulis query saat load agar submit modal (tanpa klik tab dulu) tetap bawa posisi via Referer.
   - Syarat: controller pakai `return back()` (Referer-based). TIDAK perlu untuk switch tanpa submit-reload
     (mis. wali/status antar-anak), modal, dropdown.

2. **Sub-tab (slidebar dalam halaman) bila konten menumpuk** — jika satu halaman menampung banyak card/section
   sehingga scroll panjang, pecah jadi sub-tab (hanya 1 section tampil via `x-show`). Contoh: `admin/content.blade`.

3. **Form leluasa = modal floating** — form isian panjang/sempit pakai modal floating (pola modal pembayaran),
   bukan form inline sempit. Tombol pemicu (Tambah/Edit) di header card, tak overflow.

4. **Input teks user = HURUF KAPITAL** otomatis via middleware `UppercaseInput` (kecualikan kredensial/enum/prose/`*_id`).

5. **Tanggal tampil = Bahasa Indonesia** (`translatedFormat`), uang = `Rp x.xxx.xxx` (`number_format(...,0,',','.')`).

6. **Validasi terpusat & DRY** — aturan nama/no_hp/NIK dst di `app/Support/ValidationRules.php`; alamat via
   `Student::alamatRules()`. Jangan tulis ulang regex/rule per form.

7. **Eager loading** — query yg dipakai di loop view WAJIB `with([...])` (cegah N+1).

8. **Verifikasi sebelum selesai** — `php artisan test` + `npm run build` + `view:cache` hijau; smoke browser untuk perubahan UI.