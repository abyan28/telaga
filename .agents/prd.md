# PRD — Sistem Informasi Sekolah RA Al Kautsar

**Platform:** Web responsif untuk desktop dan mobile  
**Tech stack target:** Laravel + MySQL + TailwindCSS  
**Mode pengerjaan:** Front end dulu, lalu back end  
**Versi dokumen:** 1.1 (Diperbarui)

### Referensi Dokumen Terkait
* **Aturan & Konvensi Sistem**: [rules.md](file:///d:/Laravel/Al%20Kautsar/.agents/rules.md)
* **Alur Kerja Pengguna**: [workflow.md](file:///d:/Laravel/Al%20Kautsar/.agents/workflow.md)

---

## 1. Latar Belakang

RA Al Kautsar membutuhkan sistem informasi sekolah berbasis web yang modern, dinamis, dan responsif agar proses pendaftaran murid baru, verifikasi pembayaran, pengelolaan data siswa, pengelolaan data guru, dan pelacakan pembayaran SPP dapat dilakukan secara terpusat dan efisien.

Sistem ini juga harus memudahkan wali murid memantau status pendaftaran dan pembayaran anaknya, serta memudahkan admin dan guru dalam pengelolaan data sekolah.

---

## 2. Tujuan Produk

1. Menyediakan website sekolah yang informatif dan modern.
2. Menyediakan alur pendaftaran murid baru secara online dengan verifikasi data yang terstruktur.
3. Memudahkan upload dan verifikasi dokumen pendaftaran (KK, Akta Kelahiran, Foto).
4. Memudahkan pencatatan, pemantauan, dan verifikasi pembayaran.
5. Menyediakan sistem pembayaran SPP bulanan dan Daftar Ulang dengan dukungan pembayaran parsial/cicilan.
6. Menyediakan CMS admin sekolah untuk pengelolaan data, pembagian kelas, dan transaksi keuangan.
7. Menyediakan akses khusus bagi guru untuk mengelola data siswa pada kelas-kelas yang diampunya.
8. Menyediakan laporan rekapitulasi transaksi dan data yang dapat diekspor dalam format CSV/PDF.

---

## 3. Ruang Lingkup

### In Scope
* Website publik sekolah (Profil, Informasi Pendaftaran)
* Pendaftaran murid baru online (Login/Signup wali murid)
* Login Admin & Guru
* Upload dokumen pendaftaran & bukti pembayaran manual
* Pengaturan nominal biaya pendaftaran yang dinamis oleh Admin
* Penentuan status kelulusan secara manual oleh Admin
* Pembayaran Daftar Ulang dan SPP bulanan yang dapat dicicil (parsial)
* Manajemen data siswa (termasuk penginputan catatan perkembangan sederhana)
* Manajemen data guru (mendukung 1 guru mengampu banyak kelas)
* Role management (Wali Murid, Admin, Guru)
* Audit log pencatatan aksi penting
* Pencarian, filter, dan export data ke CSV/PDF
* Notifikasi email otomatis

### Out of Scope
* Payment gateway otomatis (semua pembayaran diverifikasi manual)
* Integrasi langsung/otomatis dengan mutasi rekening bank
* Aplikasi mobile native (iOS / Android)
* Absensi kehadiran siswa
* Pengelolaan raport akademik dan penilaian pembelajaran terperinci
* E-learning / modul materi belajar online

---

## 4. Prinsip Desain Produk

* **Modern & Clean**: Minimalis, dinamis, dan responsif (khususnya mudah diakses dari perangkat mobile).
* **Clear Status**: Informasi status pendaftaran dan pembayaran harus jelas, transparan, dan mudah dipantau.
* **Validated Form**: Validasi input form yang ketat dan pesan error yang informatif.
* **Auditable**: Setiap tindakan krusial (seperti verifikasi pembayaran atau perubahan data) harus terekam dalam audit log.
* **Warna Tema**: Warna utama putih dan biru langit/cerah, mencerminkan citra sekolah yang bersih dan bersahabat.

---

## 5. Role dan Hak Akses

### 5.1 Wali Murid
* Mengisi formulir pendaftaran murid baru (wajib mengisi NIK anak).
* Mengunggah dokumen wajib (KK, Akta Kelahiran) dan dokumen opsional (Foto Anak).
* Melihat status pendaftaran, status seleksi, dan status verifikasi pembayaran pendaftaran.
* Melihat tagihan, sisa tunggakan yang dirinci per komponen, serta progress bar pembayaran.
* Mengunggah bukti transfer pembayaran pendaftaran, daftar ulang, dan SPP bulanan (bisa dicicil).
* Melihat riwayat pembayaran dan data profil anaknya.

### 5.2 Admin
* Mengelola seluruh data pendaftaran murid baru dan memverifikasi dokumen.
* Mengatur nominal biaya pendaftaran secara dinamis melalui CMS.
* Menentukan status kelulusan (lulus/gagal) calon siswa secara manual via CMS.
* Memverifikasi bukti pembayaran pendaftaran, daftar ulang, dan SPP (baik lunas maupun parsial).
* Mengelola data guru, pembagian kelas (menugaskan guru ke kelas-kelas yang diampu), dan data siswa.
* Mengatur role pengguna dan memantau audit log sistem.
* Melakukan pencarian, filter, dan mengekspor rekap transaksi/tunggakan ke CSV/PDF.
* Mengelola konten halaman publik (Beranda, Profil Sekolah, Info Pendaftaran, Kontak/Footer) melalui CMS tanpa menyentuh kode — teks, angka, gambar/logo, serta item berulang (program, persyaratan, alur) yang dapat ditambah/dihapus/diurutkan.

### 5.3 Guru
* Melihat daftar siswa di kelas-kelas yang diampunya saja (1 guru dapat mengampu banyak kelas).
* Menambahkan data siswa lama atau memperbarui profil siswa di kelas yang diampu.
* Menginput dan memperbarui catatan perkembangan sederhana untuk siswa di kelasnya.
* *Tidak dapat melihat data kelas lain, tidak memiliki akses ke keuangan/pembayaran, dan tidak dapat mengubah role.*

---

## 6. Struktur Halaman / Sitemap

### Publik
* Beranda (Halaman Utama)
* Profil & Tentang Sekolah
* Informasi Pendaftaran
* Login / Sign Up Wali Murid

### Private & CMS
* Login Admin / Guru (diarahkan ke CMS dashboard)

### Dashboard Wali Murid
* Halaman Dashboard (Ringkasan Status & Tagihan)
* Formulir Pendaftaran Murid Baru & Upload Dokumen
* Halaman Detail & Status Pendaftaran
* Halaman Pembayaran Pendaftaran (Upload Bukti)
* Halaman Pembayaran Daftar Ulang (Upload Bukti Cicilan)
* Halaman Pembayaran SPP Bulanan (Upload Bukti Cicilan & Detail Sisa Tunggakan)
* Halaman Riwayat Transaksi & Profil Anak

### Dashboard Admin
* Dashboard Utama (Statistik Ringkas)
* Daftar Pendaftaran & Detail Formulir/Dokumen
* Modul Verifikasi Keuangan (Pendaftaran, Daftar Ulang, SPP)
* Manajemen Data Siswa & Data Guru
* Manajemen Kelas & Penugasan Guru
* Manajemen Akun & Role
* Laporan Transaksi & Tunggakan SPP
* Audit Log & Menu Export (CSV/PDF)

### Dashboard Guru
* Dashboard Guru
* Daftar Kelas & Siswa yang Diampu
* Form Edit Profil Siswa & Catatan Perkembangan Sederhana

---

## 7. Kebutuhan Fungsional

### 7.1 Halaman Utama / Website Sekolah
* Menampilkan profil sekolah, visi-misi, dan informasi pendaftaran.
* Menyediakan tombol navigasi yang jelas untuk Login / Sign Up wali murid.
* Seluruh konten halaman publik dikelola melalui CMS (lihat §7.15) — tidak di-hardcode.

### 7.15 Manajemen Konten Website (CMS Publik)
* Admin dapat mengelola konten halaman **Beranda, Profil Sekolah, Info Pendaftaran, dan Kontak/Footer** melalui CMS tanpa mengubah kode.
* **Teks & angka tunggal** (judul hero, statistik, sambutan, visi, sejarah, kontak, dll) disimpan sebagai key-value (`site_contents`).
* **Item berulang** (kartu program/fasilitas, poin misi, persyaratan dokumen, langkah alur pendaftaran) disimpan sebagai baris (`site_features`) dan dapat **ditambah, dihapus, dan diurutkan** oleh operator.
* **Biaya di halaman Info** (pendaftaran, daftar ulang, SPP) diambil **langsung dari pengaturan nominal (`settings`)** yang sama dengan modul keuangan — konsisten, tidak ada angka ganda.
* **Tenaga pendidik** di halaman Profil diambil dari **data guru (`teachers`)**; admin menandai guru mana yang tampil publik (`tampil_di_web`), beserta `jabatan` dan foto.
* Mendukung **upload gambar/logo** (logo sekolah, gambar pendukung, foto guru) dengan validasi format & ukuran (maks 2 MB, konsisten §7.4).
* Nilai awal konten diisi via seeder = konten mockup yang sudah ada, sehingga migrasi tidak mengubah tampilan.

### 7.2 Registrasi dan Login
* **Wali Murid**: Registrasi menggunakan email dan password. Email digunakan untuk pengiriman notifikasi otomatis.
* **Admin & Guru**: Menggunakan login terpisah dengan middleware Role-Based Access Control (RBAC).

### 7.3 Pendaftaran Murid Baru
* Form pendaftaran wajib memuat data wali, kontak, alamat, dan data anak.
* **NIK anak wajib diisi**.
* Upload KK (JPG/PDF) dan Akta Kelahiran (JPG/PDF) bersifat **wajib**. Upload Foto Anak (JPG) disarankan wajib.
* Agama (pasti Islam) dan asal sekolah (murid belum sekolah) tidak ditanyakan dalam formulir.

### 7.4 Upload Dokumen
* Ukuran file maksimal **2 MB**.
* Admin dapat mengunduh dokumen atau menolaknya jika gambar tidak jelas.
* *Detail ketentuan format penamaan file dan penyimpanan diatur di [rules.md](file:///d:/Laravel/Al%20Kautsar/.agents/rules.md).*

### 7.5 Status Pendaftaran & Seleksi
* Status pendaftaran: *Draft, Sudah Submit, Menunggu Bukti Pembayaran, Menunggu Verifikasi Pembayaran, Pembayaran Diverifikasi, Sedang Diproses Seleksi, Lulus, Gagal*.
* Penentuan kelulusan dilakukan secara **manual oleh Admin** dengan menekan tombol **Lulus** atau **Gagal** pada halaman detail pendaftaran di CMS.

### 7.6 Pembayaran Pendaftaran
* Nominal biaya pendaftaran diatur secara **dinamis** oleh Admin melalui CMS.
* Alur verifikasi pembayaran dilakukan secara manual melalui unggahan bukti transfer oleh wali murid.
* *Detail alur verifikasi dipindahkan ke [workflow.md](file:///d:/Laravel/Al%20Kautsar/.agents/workflow.md).*

### 7.7 Daftar Ulang
* Calon siswa yang dinyatakan **Lulus** akan mendapatkan akses ke menu pembayaran Daftar Ulang.
* Biaya Daftar Ulang **dapat dicicil / dibayar secara parsial**.
* Admin memverifikasi bukti bayar cicilan dan memperbarui sisa tunggakan yang harus dibayar.

### 7.8 SPP Bulanan
* Admin menetapkan nominal SPP bulanan. Tagihan digenerate secara otomatis setiap awal bulan pada tanggal yang ditentukan Admin.
* SPP **dapat dicicil / dibayar secara parsial** tanpa denda keterlambatan.
* Jika dibayar sebagian, sisa tunggakan tetap tercatat pada bulan yang bersangkutan sebagai status **Belum Lunas / Kurang** (tidak otomatis bergeser atau digabungkan ke bulan berikutnya).
* Dashboard menampilkan progress bar pembayaran dan rincian sisa tunggakan per komponen secara detail agar transparan.

### 7.9 Data Siswa & Catatan Perkembangan
* Admin dan Guru dapat mengelola profil siswa serta menambahkan **data catatan perkembangan sederhana** untuk memantau kemajuan murid.
* Guru dibatasi hanya dapat mengelola data siswa di kelas yang diampunya saja.

### 7.10 Data Guru & Penugasan Kelas
* Admin dapat mengelola data guru, akun, role, dan menugaskan guru ke kelas.
* Mendukung kondisi di mana **satu orang guru mengampu banyak kelas**.

### 7.11 Manajemen Role dan Akses
* Pembatasan hak akses yang ketat antara Wali Murid, Admin, dan Guru menggunakan middleware Laravel.

### 7.12 Search, Filter, dan Export Data
* Fitur pencarian dan filter data pendaftaran, siswa, guru, dan transaksi berdasarkan tanggal, status, kelas, tahun ajaran, nama, dan jenis pembayaran.
* Ekspor data transaksi keuangan, data siswa, dan rekap tunggakan ke format **CSV** dan **PDF**.

### 7.13 Notifikasi Email
* Notifikasi email dikirim otomatis pada saat: pendaftaran berhasil dikirim, bukti transfer diunggah, pembayaran terverifikasi/ditolak, status kelulusan diperbarui, dan saat tagihan SPP baru diterbitkan.

### 7.14 Audit Log
* Sistem mencatat aktivitas login, perubahan data profil, verifikasi pembayaran, perubahan status pendaftaran, serta data sebelum/sesudah perubahan (bila memungkinkan).

---

## 8. Kebutuhan Non-Fungsional

* **Responsiveness**: Berjalan optimal di browser desktop maupun layar smartphone.
* **Security**: Enkripsi password, proteksi upload file (hanya ekstensi JPG/PDF yang diizinkan), dan pencegahan akses URL tanpa otorisasi (Role Guard).
* **Performance**: Kecepatan pemuatan halaman yang optimal dan optimalisasi query untuk penanganan data transaksional.
* **Maintainability**: Struktur kode Laravel yang rapi, relasi model yang jelas, dan kemudahan proses backup database.

---

## 9. Kebutuhan Teknis

### Front End
* Pembuatan prototipe layout, navigasi responsif, sitemap publik, dashboard masing-masing role, serta form transaksi.
* Styling menggunakan **TailwindCSS** dengan skema warna cerah (putih & biru langit).

### Back End
* Framework **Laravel** & Database **MySQL**.
* Auth terpisah atau dengan guard role yang didefinisikan dengan jelas via middleware.
* Layanan (Service) terpisah untuk pengunggahan berkas, pemrosesan pembayaran parsial, ekspor PDF/CSV, dan pengiriman notifikasi email.

---

## 10. Rekomendasi Struktur Data

Entitas inti yang dibutuhkan:
* `users`
* `roles`
* `parents` / `guardians`
* `students` (menyimpan data profil, NIK, dan kelas)
* `teachers` (menyimpan data guru)
* `classes` (data kelas)
* `class_teacher` (tabel pivot karena 1 guru bisa mengampu banyak kelas)
* `registration_forms`
* `registration_documents`
* `payment_transactions` (mencatat semua riwayat transaksi cicilan/lunas)
* `monthly_spp_bills` (tagihan SPP bulanan)
* `re_registration_payments` (pembayaran daftar ulang)
* `student_progress_notes` (catatan perkembangan siswa sederhana)
* `audit_logs`
* `academic_years`

*(Aturan baku mengenai penamaan Primary & Foreign Key pada tabel-tabel di atas dapat dilihat langsung pada [rules.md](file:///d:/Laravel/Al%20Kautsar/.agents/rules.md)).*

---

## 11. Acceptance Criteria (Kriteria Penerimaan)

Sistem dianggap selesai untuk fase MVP jika:
* Website sekolah dapat diakses secara publik dan responsif di mobile.
* Wali murid dapat mendaftar dengan mengisi NIK anak, mengunggah KK & Akta, serta mengunggah bukti bayar pendaftaran.
* Admin dapat memverifikasi berkas dan pembayaran, serta mengubah status kelulusan secara manual via CMS.
* Wali murid yang lulus dapat mencicil biaya Daftar Ulang & SPP bulanan, dengan tampilan sisa tunggakan yang detail dan terperinci.
* Guru hanya dapat mengelola data profil siswa dan mengisi catatan perkembangan siswa di kelas yang diampunya saja.
* Admin dapat melihat laporan keuangan, mendownload PDF/CSV, dan memantau audit log aktivitas pengguna.
* Notifikasi email terkirim dengan sukses pada trigger yang ditentukan.

---

## 12. Prioritas Pengerjaan

### Tahap 1 — Front End
* Desain halaman publik, halaman login/signup, dashboard masing-masing role.
* Desain formulir pendaftaran, halaman unggah berkas, dan modul pembayaran cicilan SPP/Daftar Ulang.
* Desain tabel data siswa, guru, filter pencarian, dan progress bar keuangan.

### Tahap 2 — Back End
* Implementasi autentikasi, role middleware, dan migrasi database beserta relasinya.
* Implementasi logika upload berkas aman dengan penamaan terstruktur.
* Implementasi workflow verifikasi pembayaran pendaftaran, cicilan daftar ulang, dan SPP bulanan.
* Implementasi integrasi email notifikasi, pencatatan audit log, dan ekspor CSV/PDF.

### Tahap 3 — Penyempurnaan & Pengujian
* Validasi formulir lebih lanjut, pemolesan animasi/transisi UI.
* Pengujian fungsionalitas sistem secara menyeluruh (UAT) dan perbaikan bug.
* Deployment awal.

---

## 13. Risiko & Antisipasi

* **Kesalahan Saldo Keuangan**: Alur pembayaran cicilan harus didesain dengan presisi tinggi agar sisa tunggakan tidak salah akumulasi. Solusinya adalah mencatat setiap transaksi parsial di tabel transaksi dan menghitung sisa tunggakan secara dinamis berdasarkan total biaya dikurangi jumlah terverifikasi.
* **Keamanan File Upload**: Potensi file berbahaya diunggah oleh pengguna. Diantisipasi dengan validasi ekstensi berkas ketat di sisi Laravel (hanya JPG dan PDF) serta pembatasan kapasitas maksimal 2 MB.
* **Kebocoran Data Antar Kelas**: Perlu pengetatan hak akses di level query agar guru benar-benar dibatasi hanya bisa menarik data dari tabel siswa yang terasosiasi dengan kelas yang dia ampu.
