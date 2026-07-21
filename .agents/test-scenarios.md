# Skenario Pengujian Sistem — TELAGA AL KAUTSAR

Dokumen UAT (User Acceptance Testing) manual end-to-end. Disusun berdasarkan
fitur & validasi yang benar-benar ada di kode (routes/web.php, FormRequest,
Controller). Setiap skenario punya jalur SUKSES (happy path) dan jalur GAGAL
(negative test) untuk memastikan sistem mendeteksi kesalahan.

> Terakhir diselaraskan: 2026-07-17 — mencakup Fase D–J, T2.4 & T9.1.
> Diverifikasi via `php artisan test` (86 passed / 295 assertions) + smoke test
> live terhadap MySQL (semua route 3 role → 200, RBAC lintas-role → 403, tanpa 500).

Legenda:
- [OK]   = hasil yang diharapkan pada jalur sukses
- [X]    = hasil yang diharapkan pada jalur gagal (sistem WAJIB menolak)
- Akun demo (setelah `migrate:fresh --seed` + `db:seed --class=DemoSeeder`):
  - Wali A (2 anak): wali@telaga.sch.id / password
  - Wali B (kakak-adik lintas tahun): rizal@telaga.sch.id / password
  - Guru: login pakai email `guru.ahmad@telaga.sch.id` ATAU no. HP `081300000001`;
    password = NUPTK `1234567890123456` (Fase D — BUKAN lagi "nomor induk guru").
  - Admin: di-seed dengan password acak (set manual bila perlu).

> PENTING (Fase D — skema akun): field login TUNGGAL bernama `login` menerima
> email, nomor HP, ATAU username. `users.name` sudah diganti `username`; nama
> asli disimpan di profil (`guardians.nama` / `teachers.nama`). Password awal guru
> = NUPTK (bukan nomor induk). Kolom `nomor_induk_guru` sudah DIHAPUS total.

═══════════════════════════════════════════════════════════════════
BAGIAN A — WALI MURID
═══════════════════════════════════════════════════════════════════

## A1. Registrasi Akun Wali

### A1.1 Sukses — daftar akun baru
- Buka /login → tab Sign Up. Isi USERNAME (min 6, unik, tanpa spasi/tanda hubung),
  email baru, password (>=8), konfirmasi cocok. (Nama wali TIDAK diisi di sini —
  diisi belakangan lewat CMS wali; `guardians.nama` nullable.)
- [OK] Akun dibuat (username → users.username, role dikunci 'wali'), profil guardian
  terbuat, auto-login, diarahkan ke /portal/wali (dashboard).

### A1.2 GAGAL — email sudah terdaftar
- Daftar pakai email yang sudah ada (mis. wali@telaga.sch.id).
- [X] Ditolak, pesan "email sudah digunakan" (rule unique:users,email). Tidak
  ada user/guardian baru terbuat.

### A1.3 GAGAL — password < 8 karakter
- Isi password "123".
- [X] Ditolak (rule min:8 — berlaku di SEMUA jalur set-password: signup wali,
  ganti-password wali/guru, buat-akun admin).

### A1.4 GAGAL — konfirmasi password tidak cocok
- password = "rahasia12", konfirmasi = "rahasia99".
- [X] Ditolak (rule confirmed).

### A1.5 GAGAL — format email tidak valid
- email = "bukan-email".
- [X] Ditolak (rule email).

### A1.6 GAGAL — field wajib kosong
- Kosongkan username / email / password.
- [X] Ditolak (rule required per field).

### A1.7 GAGAL — eskalasi role (keamanan)
- Kirim POST /register dengan field tambahan role=admin (via DevTools/Postman).
- [X] Role tetap 'wali' — input role diabaikan sistem (RegisterController mengunci role).

### A1.8 GAGAL — nomor HP sudah dipakai wali lain (saat signup)
- Isi no_hp yang sudah dimiliki wali lain.
- [X] Ditolak, pesan "Nomor HP ini sudah digunakan oleh wali lain."
  (rule unique:guardians,no_hp — dikonfirmasi di RegisterController).
- Catatan: no_hp saat signup opsional; bila dikosongkan tersimpan NULL dan
  BOLEH lebih dari satu wali tanpa HP (MySQL mengizinkan banyak NULL pada unique index).

### A1.9 Username: constraint + cek ketersediaan live (AJAX)
- Sukses: ketik username valid & belum dipakai → indikator "✓ Username tersedia"
  (endpoint `GET /register/check-username?username=&min=6`, debounce 400ms).
- [X] Ditolak saat submit bila: < 6 char, ada spasi/tanda hubung/simbol
  (regex `^[a-zA-Z0-9_.]+$`), atau sudah dipakai (unique:users,username).
  Indikator live menampilkan "✗ sudah dipakai" / "✗ 6–30 karakter…".

## A2. Login Wali

### A2.1 Sukses — login via email
- Login email+password benar → diarahkan ke dashboard wali.
### A2.2 Sukses — login via nomor HP atau username (Fase D)
- Isi field `login` dengan no. HP (atau username) + password benar.
- [OK] Login berhasil (AuthController mencocokkan no_hp/username lalu attempt by id_users).
### A2.3 GAGAL — password salah
- [X] Ditolak, kembali ke form login dengan pesan "Email/No. HP/Username atau kata sandi salah.", tidak login.
### A2.4 GAGAL — identifier tidak terdaftar
- [X] Ditolak (tidak ada user cocok → attempt gagal).

## A3. Pendaftaran Murid Baru (form multi-step)

### A3.1 Sukses — daftar anak lengkap
- Form 2 langkah (K2.1: data wali TIDAK diisi lagi di sini — sudah wajib lengkap
  via Profil Wali). Step 1 data anak (nama, NIK 16 digit, jenis kelamin L/P,
  tempat & tanggal lahir, ALAMAT + wilayah anak provinsi→kota→kecamatan→kelurahan),
  Step 2 upload KK (JPG/PDF), Akta (JPG/PDF), Foto (JPG). Semua <= 2 MB.
- [OK] Student (termasuk alamat + 8 kolom wilayah) + registration_form (status
  'submitted') + 3 dokumen tersimpan. File masuk folder
  pendaftaran/[tahun-ajaran]/[nama-anak]/. Email "pendaftaran terkirim"
  ter-log (MAIL_MAILER=log). Profil guardian TIDAK ditimpa form daftar.

### A3.1b Gate — profil wali belum lengkap (K2.1)
- Wali baru (profil nama/no_hp/pekerjaan/TTL belum lengkap) buka /portal/wali/register
  atau menu wali mana pun.
- [X] Redirect ke /portal/wali/profile (middleware RequireGuardianProfile). Setelah
  profil dilengkapi → akses normal. Rute guru/admin tetap 403 (bukan redirect).

### A3.1c Validasi input (K1)
- No. HP "081..." → tersimpan "6281..." (normalisasi). Non-angka / <9 / >14 digit ditolak.
- Nama dengan angka/simbol ditolak; "M. RIDWAN" / "SITI NUR'AINI" diterima.
- NIK non-16-digit ditolak (digits:16).

### A3.1d Switcher antar-anak (K2.6)
- Wali dengan >1 anak buka /portal/wali/status.
- [OK] Muncul tab bar per anak; klik tab → tampil blok status anak itu saja (tanpa scroll).

### A3.1e Status pendaftaran per-state (K2.2)
- Setelah upload bukti → "Pembayaran Anda sedang diverifikasi...".
- Setelah admin verifikasi bayar → "Berkas Anda sedang diproses...".

### A3.1f Edit pendaftaran oleh wali (K2.5)
- Prasyarat: pendaftaran status "submitted" atau "menunggu_bukti" (bayar belum diverifikasi).
- [OK] Tombol "Edit Pendaftaran" muncul di /portal/wali/status → buka form register mode edit (data anak ter-prefill).
- [OK] Ubah data anak (mis. nama), simpan tanpa upload ulang berkas → data berubah, dokumen lama dipertahankan.
- [OK] Upload ulang salah satu berkas (mis. KK) → berkas KK diganti & statusnya kembali "pending" (perlu verifikasi ulang).
- [X] Setelah admin verifikasi bayar (status pembayaran_diverifikasi/diproses_seleksi) tombol Edit HILANG;
      akses langsung GET .../edit → redirect ke status dg pesan "sudah diverifikasi"; POST update → 403.
- Escape-hatch admin: di admin/registration-detail klik "Buka Izin Edit Wali" (muncul saat status
  pembayaran_diverifikasi/diproses_seleksi & belum dibuka).
  - [OK] Setelah dibuka, badge "Izin edit wali AKTIF"; wali kembali bisa Edit.
  - [OK] Wali simpan dg ganti berkas saat status "diproses_seleksi" → form MUNDUR ke "pembayaran_diverifikasi",
        dokumen yg diganti jadi "pending", flag izin edit otomatis MATI (admin verifikasi berkas ulang).

### A3.2 GAGAL — NIK bukan 16 digit
- NIK = "123" atau "12345678901234567" (17 digit) atau ada huruf.
- [X] Ditolak, pesan "NIK harus tepat 16 digit angka." (rule digits:16).

### A3.3 GAGAL — NIK duplikat
- Pakai NIK anak yang sudah terdaftar.
- [X] Ditolak, pesan "NIK ini sudah terdaftar." (rule unique:students,nik).

### A3.4 GAGAL — file > 2 MB
- Upload KK berukuran 3 MB.
- [X] Ditolak, pesan "Ukuran file KK maksimal 2 MB." (rule max:2048).

### A3.5 GAGAL — format file salah
- Upload KK berformat .docx / .png, atau Foto berformat .pdf.
- [X] Ditolak (KK/Akta hanya jpg,jpeg,pdf; Foto hanya jpg,jpeg).

### A3.6 GAGAL — dokumen wajib tidak diupload
- Kosongkan salah satu dari KK / Akta / Foto.
- [X] Ditolak (ketiganya required).

### A3.7 GAGAL — jenis kelamin di luar L/P
- Kirim jenis_kelamin = "X".
- [X] Ditolak (rule in:L,P).

### A3.8 GAGAL — field data anak/wali kosong
- Kosongkan nama_anak / tempat_lahir / tanggal_lahir / nama_wali.
- [X] Ditolak (required masing-masing). Catatan: `alamat` & wilayah anak
  bersifat nullable (Student::alamatRules) — boleh kosong, tidak memblokir submit.

### A3.9 GAGAL — nomor HP wali bentrok dengan wali lain
- Isi no_hp yang sudah dipakai guardian wali LAIN.
- [X] Ditolak, pesan "Nomor HP ini sudah digunakan oleh wali lain."
  (Rule::unique guardians dengan ignore diri sendiri — dikonfirmasi di StoreRegistrationRequest).

### A3.10 Sukses — wali mendaftarkan anak KEDUA dengan HP sendiri
- Wali yang sudah punya profil (dan no_hp terisi) mendaftarkan anak kedua; no_hp
  tetap nomor miliknya.
- [OK] TIDAK ditolak — validasi unique mengabaikan baris guardian milik wali itu
  sendiri (ignore id_guardians). Ini penting agar multi-anak tidak error.

### A3.11 Dropdown wilayah berjenjang (Fase I/J)
- Sukses: pilih provinsi → kota terisi hanya wilayah dalam provinsi itu →
  kecamatan → kelurahan (endpoint AJAX `GET /wilayah/{level}?parent=`).
- [OK] Tiap level menyaring `substr(id,1,n)=parent`; id + nama tersimpan sebagai
  snapshot di student. Child disabled sampai parent dipilih.
- [X] Buka `/wilayah/kota` TANPA parent → kembalikan `[]` (guard anti bocor
  puluhan ribu baris); `/wilayah` tanpa login → redirect /login (middleware auth).

### A3.12 GAGAL — mendaftar saat PPDB DITUTUP (T5.6a)
- Admin menutup PPDB (setting `pendaftaran_dibuka=0`). Wali buka `/portal/wali/register`
  atau POST langsung ke `/portal/wali/register`.
- [X] HTTP 403 "Pendaftaran sedang ditutup." (ensurePpdbOpen di create DAN store —
  root-cause di 2 entry, tak bisa dilewati via POST langsung).

## A4. Status Pendaftaran & Upload Bukti Bayar Pendaftaran

### A4.1 Sukses — lihat timeline
- Buka /portal/wali/status setelah submit.
- [OK] Timeline status tampil sesuai status form; dokumen terunggah terlihat.

### A4.2 Sukses — upload bukti bayar pendaftaran
- Upload bukti transfer (JPG/PDF <= 2 MB); rekening tujuan sekolah tampil dari
  settings (Fase F); opsional pilih Bank Asal transfer dari dropdown (bank.csv).
- [OK] PaymentTransaction 'pending' terbuat (bank_asal ikut tersimpan bila diisi),
  status form → 'menunggu_verifikasi'.

### A4.3 GAGAL — bukti > 2 MB / format salah
- [X] Ditolak (mimes jpg,jpeg,pdf; max 2048).

## A5. Pembayaran (Cicilan Daftar Ulang & Lunas SPP/Pendaftaran)

### A5.1 Sukses — daftar ulang dicicil sebagian (parsial)
- Anak berstatus lulus/aktif punya tagihan daftar ulang. Bayar sebagian (< sisa), upload bukti.
- [OK] Transaksi 'pending', progress bar & sisa tunggakan terupdate setelah admin verifikasi.
  (K10.4: HANYA daftar_ulang; SPP & pendaftaran ditolak bila kurang — lihat A5.10/A5.11.)

### A5.2 Sukses — pelunasan tepat sisa
- Bayar tepat sebesar sisa tunggakan.
- [OK] Setelah admin verifikasi, status tagihan → lunas.

### A5.3 GAGAL — bayar melebihi sisa tunggakan (overpay)  ← guard penting
- Sisa tunggakan Rp 500.000, coba bayar Rp 700.000.
- [X] Ditolak, pesan "Nominal melebihi sisa tunggakan (Rp 500.000)."
  (PaymentController::store cek $jumlah > $sisa).

### A5.4 GAGAL — bayar tagihan milik anak wali LAIN (kebocoran data)
- Kirim referensi_id tagihan milik siswa wali lain (via Postman).
- [X] HTTP 403 (abort_unless kepemilikan guardian di resolveBill).

### A5.5 GAGAL — nominal <= 0 atau bukan angka
- jumlah = 0 / -100 / "abc".
- [X] Ditolak (rule numeric, min:1; pesan "Nominal pembayaran tidak valid.").

### A5.6 GAGAL — jenis pembayaran tak dikenal
- jenis = "sumbangan".
- [X] Ditolak (rule in:spp,daftar_ulang).

### A5.7 GAGAL — bukti transfer tidak diupload / format salah
- [X] Ditolak (bukti required, mimes jpg,jpeg,pdf, max 2048).

### A5.8 Sukses — riwayat pembayaran wali lengkap (K4.2)
- Buka /portal/wali/payments, lihat tabel "Riwayat Pembayaran & Cicilan".
- [OK] Tiap baris menampilkan kolom "Atas Nama" (nama anak), tanggal; untuk jenis
  SPP kolom Jenis menampilkan bulan tagihan (mis. "Spp Juli 2026") via relasi sppBill.

### A5.9 UX — badge "Bisa Dicicil" hanya di Daftar Ulang (K4.4 + K10.4)
- Buka halaman Info publik & dashboard wali.
- [OK] Kartu biaya Daftar Ulang di info.blade menampilkan badge "Bisa Dicicil".
  Kartu SPP & Pendaftaran TANPA badge cicil. Dashboard wali teks netral.

### A5.10 GAGAL — SPP dicicil (K10.4)
- Coba bayar SPP bulanan dengan jumlah < sisa.
- [X] Ditolak server, pesan "Pembayaran SPP harus lunas (tidak dapat dicicil)."
  (PaymentController@store guard `jenis === 'spp' && jumlah < sisa`.)

### A5.11 GAGAL — biaya pendaftaran dibayar kurang (K10.4)
- Coba upload bukti pendaftaran dengan jumlah < nominalPendaftaran.
- [X] Ditolak server, pesan "Biaya pendaftaran harus dibayar penuh …."
  (StatusController@uploadProof guard `jumlah < nominal`.)

## A6. Pengaturan Akun Mandiri & Ganti Password (T2.3 / T9.1)

### A6.1 Sukses — wali ubah email & no. HP sendiri
- Buka /portal/wali/account, ubah email (wajib) + no_hp (opsional), simpan.
- [OK] `users` + `guardians.no_hp` tersinkron dalam satu transaksi; unique
  ignore-self (email/no_hp lintas users + no_hp profil).
### A6.2 GAGAL — email/HP bentrok akun lain
- Isi email/no_hp yang sudah dipakai user lain.
- [X] Ditolak (Rule::unique ignore diri sendiri saja).
### A6.3 Sukses — wali ganti password
- Buka /portal/wali/password, isi password lama + baru (>=8, confirmed).
- [OK] Password terganti; bila akun berflag must_change_password, flag dibersihkan.
### A6.4 Paksa ganti password awal (ForceChangePassword — T9.1)
- Login dengan akun yang dibuat admin (wali: password awal = no_hp; guru:
  password awal = NUPTK) → `must_change_password=true`.
- [OK] Setiap request selain form ganti-password (+update) & logout di-REDIRECT
  ke form ganti-password sampai diganti. Guru sekaligus menetapkan username di form
  ini (min 6). Setelah diganti, flag clear & akses normal.
- [X] Wali/guru berflag mencoba buka dashboard/menu lain → dialihkan balik ke
  form ganti-password (tidak bisa dilewati).

═══════════════════════════════════════════════════════════════════
BAGIAN B — ADMIN (CMS)
═══════════════════════════════════════════════════════════════════

## B1. Verifikasi Dokumen & Pembayaran Pendaftaran

### B1.1 Sukses — setujui dokumen
- Buka detail pendaftaran, verifikasi KK/Akta/Foto = "diterima".
- [OK] Status dokumen berubah, teraudit di audit_logs.
### B1.2 Sukses — tolak dokumen + catatan
- Tolak dokumen buram, isi catatan perbaikan.
- [OK] Status dokumen "ditolak" + catatan tersimpan, wali bisa lihat catatan.
### B1.3 Sukses — verifikasi bukti bayar pendaftaran
- Approve bukti bayar → saldo tersinkron (SUM transaksi 'diverifikasi'),
  status form maju sesuai workflow.
- [OK] Email "pembayaran diverifikasi" ter-log.
### B1.4 Sukses — tolak bukti bayar
- [OK] Transaksi ditolak TIDAK dihitung ke jumlah_terbayar (source of truth saldo).
### B1.5 Sukses — kolom Bank Asal + Tanggal & Waktu tampil di tabel verifikasi (K4.1)
- Buka detail pendaftaran / PPDB Daftar Ulang / Verifikasi SPP.
- [OK] Tabel bukti bayar menampilkan kolom "Bank Asal" (dari `bank_asal`, "-" bila kosong)
  & "Tanggal & Waktu" (tanggal = `tanggal_bayar` versi wali; waktu = jam `created_at` "HH:MM WIB").

## B2. Keputusan Seleksi (Lulus / Gagal)

### B2.1 Sukses — tandai Lulus
- Klik Lulus pada detail pendaftaran.
- [OK] Status form 'lulus', siswa aktif, tagihan daftar ulang OTOMATIS terbuka
  (idempotent, nominal dari setting), email kelulusan ter-log.
### B2.2 Sukses — tandai Gagal
- [OK] Status 'gagal', siswa nonaktif, email ter-log.
### B2.3 GAGAL — decide dobel (idempotensi daftar ulang)
- Klik Lulus dua kali.
- [X] Tagihan daftar ulang TIDAK terduplikasi (firstOrCreate idempotent).

## B3. Pengaturan Nominal Biaya

### B3.1 Sukses — ubah nominal pendaftaran/SPP/daftar ulang (tab Set Pembayaran)
- Nominal Biaya Dinamis kini DROPDOWN pilih 1 item (pendaftaran/daftar ulang/SPP/tgl generate/kuota) + 1 input; hanya item terpilih tersimpan.
- [OK] Setting tersimpan; nominal baru dipakai di halaman info publik & tagihan baru.
  (rule integer min:0; form kirim subset — SettingController@update pakai `sometimes`.)
### B3.2 GAGAL — nominal negatif / bukan angka
- Isi nominal = -5000 / "gratis" / 15000.50 (desimal).
- [X] Ditolak validasi (rule integer + min:0 saat field dikirim — dikonfirmasi di SettingController).
### B3.3 GAGAL — tanggal generate SPP di luar 1–28
- Isi tanggal_generate_spp = 0 / 31 / "awal".
- [X] Ditolak (rule integer + between:1,28 — dikonfirmasi di SettingController).
### B3.4 Sukses — simpan sebagian (partial save)
- Kirim 1 field saja (mis. nominal_spp) via dropdown; field lain tak disentuh.
- [OK] Hanya field terkirim tersimpan; sisanya tetap (rule `sometimes` — AdminWorkflowTest::test_admin_can_update_settings).

## B4. Generate SPP Bulanan

### B4.1 Sukses — generate manual
- Klik tombol generate SPP di CMS.
- [OK] Tagihan monthly_spp_bills terbuat untuk siswa AKTIF pada bulan berjalan.
### B4.2 Sukses — idempotensi
- Jalankan generate dua kali untuk bulan sama.
- [X] Tidak ada tagihan dobel (firstOrCreate per id_student+bulan).
### B4.3 GAGAL — siswa nonaktif tidak digenerate
- [X] Siswa berstatus nonaktif TIDAK mendapat tagihan.

## B5. Manajemen Data (Siswa / Guru / Kelas)

### B5.1 Sukses — tambah guru (Fase D)
- Isi nama, no. HP (wajib — dipakai login), NUPTK (wajib), email (opsional).
- [OK] User role 'guru' (username = email ?? no_hp) + profil teacher terbuat,
  password awal = NUPTK, must_change_password=true (dipaksa ganti saat login pertama).
### B5.2 GAGAL — email guru duplikat
- [X] Ditolak bila email diisi & sudah dipakai (rule unique:users,email opsional).
### B5.3 GAGAL — NUPTK duplikat
- [X] Ditolak, pesan "NUPTK ini sudah terdaftar." (rule unique:teachers,nuptk).
  Catatan: kolom lama `nomor_induk_guru` sudah DIHAPUS (Fase D).
### B5.4 Sukses — tambah kelas
- [OK] Kelas terbuat terhubung tahun ajaran aktif.
### B5.5 Sukses — tugaskan guru ke banyak kelas
- Assign 1 guru ke Kelas A + Kelas B.
- [OK] Pivot class_teacher sync many-to-many; guru mengampu 2 kelas.
### B5.6 GAGAL — field wajib kosong saat tambah guru/kelas
- [X] Ditolak (required: nama/no_hp/nuptk untuk guru; nama_kelas untuk kelas).
### B5.7 GAGAL — tugaskan guru ke kelas yang tidak ada (id palsu)
- Kirim class_ids berisi id_classes yang tidak ada di DB.
- [X] Ditolak (rule exists:classes,id_classes — dikonfirmasi di storeTeacher & assignClasses).
### B5.8 GAGAL — nama kelas duplikat pada tahun ajaran aktif
- Tambah kelas "Kelas A" padahal sudah ada "Kelas A" di tahun ajaran aktif.
- [X] Ditolak, pesan "Nama kelas ini sudah ada pada tahun ajaran aktif."
  (Rule::unique nama_kelas scoped id_academic_year — dikonfirmasi di storeClass).
  Catatan: nama sama BOLEH di tahun ajaran berbeda (arsip antar-tahun).
### B5.9 GAGAL — nomor HP guru sudah dipakai (users atau guru lain)
- Isi no_hp yang sudah dimiliki user/guru lain saat tambah guru.
- [X] Ditolak (rule unique:users,no_hp + unique:teachers,no_hp — dikonfirmasi di storeTeacher).
  Catatan: no_hp guru WAJIB (Fase D — dipakai sebagai identifier login).
### B5.10 Sukses — alamat + wilayah guru dari CMS admin (Fase I/J)
- Di modal tambah/edit guru, pilih wilayah berjenjang + isi alamat.
- [OK] 8 kolom wilayah + alamat tersimpan di `teachers` (via collect->only).
### B5.11 Sukses — admin buatkan/tautkan akun wali siswa lama (T9.1)
- Di Data Siswa, siswa TANPA akun wali → tombol "+ Akun Wali" → isi nama + no_hp.
- [OK] Guardian by no_hp: belum ada → buat user (role wali, username=password=no_hp,
  must_change_password=true) + guardian; sudah ada (kakak-adik dgn no_hp sama) →
  TAUTKAN ke akun wali yang sama (tidak buat dobel).
### B5.12 Sukses — nonaktifkan / aktifkan guru keluar (K5.4)
- Di Data Guru, klik "Nonaktifkan" pada seorang guru.
- [OK] `teachers.is_aktif=false`, guru hilang dari list default (filter Guru Aktif),
  muncul di filter "Nonaktif (Keluar)". Login guru nonaktif ditolak ("Akun guru ini sudah nonaktif").
  Data guru tetap tersimpan (arsip). Klik "Aktifkan" mengembalikan.
### B5.13 Sukses — Data Siswa hanya tampilkan siswa PPDB yg sudah bayar daftar ulang (K5.1)
- Siswa hasil PPDB (punya formulir) belum bayar daftar ulang → TIDAK muncul di Data Siswa.
- Setelah ada pembayaran daftar ulang diverifikasi (terbayar>0) → muncul.
- [OK] Siswa lama manual (tanpa formulir) SELALU tampil apa adanya.
### B5.14 Sukses — Daftar Ulang: histori penuh + filter (2026-07-19c, K5.2 dibatalkan)
- Buka PPDB › Daftar Ulang.
- [OK] Dua tab (List Siswa default / Bukti Pembayaran); badge angka pending di tab Bukti.
- [OK] List Siswa = HISTORI PENUH: lunas TETAP tampil (K5.2 dibatalkan). Filter TA + status
  (Lunas/Kurang/Belum Bayar) + search (nama/NISN/no_hp) + pagination 25/hal.
- [OK] Kolom: Siswa · Orang Tua · Tahun Ajaran · Tanggal · Waktu · Bank · Tagihan · Terbayar · Sisa · Bukti(center) · Status.
  Tanggal/Waktu/Bank dari transaksi terakhir berpath; Bukti = thumbnail → lightbox.
### B5.15 Sukses — assign wali kelas + auto-fill jabatan (K8.1 + 2026-07-19c)
- Set wali kelas via form Kelas (dropdown "Wali Kelas") ATAU form Guru (dropdown "Wali Kelas dari").
- [OK] classes.id_homeroom_teacher terisi; card kelas & tabel siswa & profil siswa tampilkan nama wali kelas.
- [OK] jabatan guru auto jadi "WALI KELAS" saat di-assign; lepas → kosong (jabatan manual spt "KEPALA SEKOLAH" tak terhapus).
- [X] 1 guru maks 1 kelas: assign guru yang sudah jadi wali di kelas lain → jabatan lama otomatis lepas.
  (Test: AdminWorkflowTest::homeroom_teacher_assignment_is_exclusive.)
- [OK] BUGFIX: buka Edit kelas yang sudah punya wali kelas → dropdown ter-preselect benar (int↔string fix),
  simpan tak meng-null-kan wali kelas.
### B5.16 GAGAL — NUPTK bukan 16 digit (K-lanjutan)
- Isi NUPTK 10 digit / berhuruf saat tambah/edit guru.
- [X] Ditolak "NUPTK harus tepat 16 digit angka." (ValidationRules::nuptk = required|digits:16).
  (Test: AdminGuruViewsTest::nuptk_must_be_16_digits.)
### B5.17 Sukses — tabel Data Siswa kolom baru
- [OK] Urutan: Nama · NISN · Wali Kelas · Kelas · Orang Tua · No. HP Ortu · Status · Aksi
  (NIK dibuang dari tampilan; masih dipakai di pencarian). "Orang Tua" = guardian.nama (beda dari wali kelas).
### B5.18 Sukses — tabel Data Guru kolom
- [OK] Urutan: Nama · NUPTK · Jabatan · Kelas Diampu · Jml Murid · No. HP · Status · Aksi.
  Kelas Diampu >1 → dropdown <details> ("{n} kelas"). Aksi +tombol "Profil" (halaman profil guru).
### B5.19 Sukses — filter status Data Siswa default 'aktif'
- Buka Data Siswa tanpa query.
- [OK] Hanya siswa aktif tampil; pilih "Semua Status" untuk lihat lulus/nonaktif.
  (Test: AdminGuruViewsTest::student_status_filter_defaults_to_aktif.)
### B5.20 Sukses — foto profil siswa/guru
- Upload foto saat tambah/edit siswa/guru; edit lagi → foto lama tampil di modal.
- [OK] Tersimpan profil/{siswa|guru}/{Nama-id}/ (suffix PK: nama kembar tak saling timpa).
  Foto pendaftaran auto jadi foto profil siswa. Tampil di header Detail & Verifikasi + semua halaman profil.
### B5.21 Sukses — Kelola Pembayaran (SPP) rekap + filter (2026-07-19c)
- Pembayaran › Kelola Pembayaran, tab Tagihan.
- [OK] Rekap SPP semua siswa; filter search (nama/NISN/no_hp) + kelas + bulan + tahun + status (Lunas/Kurang/Belum Bayar);
  pagination 25/hal. Kolom: Siswa · Orang Tua · Tahun Ajaran · SPP Bulan · Tanggal · Waktu · Bank · Tagihan · Terbayar · Sisa · Bukti(center) · Status.
- [OK] Bukti = thumbnail → lightbox; tab kedua "Bukti SPP" = verifikasi pending.
### B5.22 Sukses — jabatan guru di form (2026-07-19c)
- Tambah/edit guru (form admin) atau profil guru CMS → isi field Jabatan.
- [OK] Tersimpan teachers.jabatan; tampil di tabel guru & profil.
- [OK] Guru wali kelas: field jabatan read-only (auto "WALI KELAS", dikelola admin).
### B5.23 Sukses — Profil Orang Tua di profil siswa (2026-07-19c)
- Buka profil siswa (admin: Data Siswa › Profil; guru: dashboard › Profil).
- [OK] Tab "Profil Orang Tua" (paling kiri, admin default): nama/HP/email/pekerjaan/TTL wali + alamat siswa.
- [OK] Biodata tampilkan "Wali Kelas". Siswa tanpa akun wali → "belum tertaut ke akun wali".
### B5.24 Sukses — profil guru CMS view/edit (2026-07-19c)
- Buka Akun & Profil (guru), tab Profil.
- [OK] Default = mode LIHAT (avatar + data read-only) + tombol Edit (kanan, sejajar avatar).
- [OK] Klik Edit → form (nama/foto/TTL/riwayat/jabatan/alamat); Batal kembali ke lihat; validasi gagal → form tetap terbuka.

## B6. Laporan & Export

### B6.1 Sukses — filter transaksi (status/jenis/tanggal)
- [OK] Tabel terfilter sesuai kriteria.
### B6.2 Sukses — export CSV
- [OK] File CSV terunduh berisi baris data sesuai filter (escape PHP 8.4 fix).
### B6.3 Sukses — export PDF
- [OK] File PDF valid (dompdf) terunduh.
### B6.4 Sukses — CMS konten website publik
- Ubah teks hero / tambah-hapus-urut item program / upload logo.
- [OK] Perubahan tampil di halaman publik; biaya di /info tetap dari settings (tidak dobel).
### B6.5 Sukses — Curriculum Highlight slider (Fase H)
- Tambah/edit/hapus slide grup `kurikulum` (judul, subjudul, upload foto, urutan, aktif).
- [OK] Slide tampil sebagai carousel auto-slide 4 dtk di beranda; foto lama dihapus
  saat di-update/hapus (tak menumpuk). Belum ada slide → fallback card statik.
### B6.6 Sukses — PPDB buka/tutup + tahun ajaran + Set Pembayaran (Fase F/T5.6)
- Toggle PPDB, buat tahun ajaran baru (aktifkan, nonaktif lama), atur 3 nominal
  + kuota + tanggal generate + rekening bank sekolah (bank_sekolah/rekening/atas_nama).
- [OK] Tersimpan; PPDB ditutup memblokir pendaftaran wali (lihat A3.12); rekening
  tampil di modal upload bukti wali.
- [X] Nominal negatif/desimal/non-angka ditolak (integer|min:0); tanggal generate
  di luar 1–28 ditolak (between:1,28). Lihat B3.2/B3.3.

═══════════════════════════════════════════════════════════════════
BAGIAN C — GURU
═══════════════════════════════════════════════════════════════════

## C1. Login & Dashboard Guru

### C1.1 Sukses — login guru
- [OK] Diarahkan ke /portal/guru; hanya tampil kelas yang diampu.
### C1.2 Sukses — lihat siswa per kelas
- [OK] Tabel siswa difilter whereIn id_class ∈ kelas yang diampu (anti-kebocoran).
  Kolom: Nama · NISN · Kelas · Wali Kelas · Status · Aksi.
### C1.3 Sukses — lihat profil siswa (read-only)
- Klik "Profil" pada baris siswa kelas yang diampu.
- [OK] Halaman biodata + catatan perkembangan (reuse wali/child-profile).
  Guru biasa (bukan wali kelas) tetap bisa lihat & tambah catatan.

## C2. Wali Kelas — hak akses penuh (K8.1)

*Jabatan wali kelas: 1 guru = maks 1 kelas, di-assign admin (form Kelas / form Guru).
Wali kelas boleh tambah + edit siswa; guru biasa (cuma mengajar) hanya lihat + catatan.*

### C2.1 Sukses — wali kelas edit profil siswa kelasnya
- [OK] Profil siswa terupdate (guardHomeroom lolos).
### C2.2 Sukses — wali kelas tambah siswa lama ke kelasnya
- Tombol "+ Tambah Siswa" muncul (dropdown kelas hanya kelas yang diwalikan).
- [OK] Siswa terbuat & tertaut id_class kelas wali.
### C2.3 Sukses — semua guru tambah catatan perkembangan
- [OK] student_progress_note tersimpan (guardStudent = semua guru ampu, bukan hanya wali).
### C2.4 GAGAL — guru BIASA (bukan wali kelas) tambah/edit siswa
- Guru mengajar kelas X tapi bukan wali kelas X → PUT/POST student.
- [X] HTTP 403 (guardHomeroom). Tombol Edit/+Tambah tak muncul di UI. Lihat & catatan tetap boleh.
  (Test: GuruTest::plain_teacher_cannot_add_or_edit_student.)

## C3. GAGAL — Keamanan Akses Antar-Kelas (guard kritis PRD §13)

### C3.1 GAGAL — edit siswa di kelas yang TIDAK diampu
- Kirim PUT /portal/guru/students/{id} untuk siswa kelas lain (via Postman).
- [X] HTTP 403 (guardHomeroom abort 403).
### C3.2 GAGAL — tambah catatan untuk siswa kelas lain
- [X] HTTP 403 (guardStudent).
### C3.3 GAGAL — guru akses menu keuangan / role management
- Guru tidak punya route keuangan; akses langsung URL admin.
- [X] Ditolak middleware role (403 / redirect).

## C4. Akun & Profil Guru (T2.3 / T6.4 / C.9)

### C4.1 Sukses — guru ganti password + tetapkan username (login pertama)
- /portal/guru/password: isi USERNAME (min 6, unik, tanpa spasi/tanda hubung —
  cek live via `check-username?min=6`) + password lama + baru (>=8, confirmed).
- [OK] Username & password tersimpan, must_change_password clear (login pertama
  guru dipaksa ke form ini oleh ForceChangePassword sampai selesai).
- [X] username < 6 / ada spasi-hubung / sudah dipakai → ditolak.
### C4.2 Sukses — guru update email & no. HP sendiri (T2.3)
- [OK] `users` + `teachers.no_hp` tersinkron (no_hp WAJIB — identifier login guru;
  email opsional); Rule::unique ignore-self.
### C4.3 Sukses — guru update profil sendiri (T6.4)
- Ubah nama, TTL, riwayat pendidikan, alamat + wilayah.
- [OK] Tersimpan. NUPTK read-only (identitas resmi, dikelola admin).

═══════════════════════════════════════════════════════════════════
BAGIAN D — RBAC & KEAMANAN LINTAS-ROLE (middleware role:)
═══════════════════════════════════════════════════════════════════

### D1. GAGAL — wali akses portal admin
- Login wali, buka /portal/admin/registrations.
- [X] HTTP 403 (middleware role:admin).
### D2. GAGAL — wali akses portal guru
- [X] HTTP 403 (role:guru).
### D3. GAGAL — guru akses portal wali/admin
- [X] HTTP 403.
### D4. GAGAL — tamu (belum login) akses portal apa pun
- Buka /portal/wali tanpa login.
- [X] Diarahkan ke /login (middleware auth).
### D5. Sukses — logout
- [OK] Sesi berakhir, diarahkan keluar; akses portal butuh login lagi.
### D6. Audit — aksi krusial tercatat
- Login/logout, verifikasi bayar, keputusan seleksi.
- [OK] audit_logs mencatat user, aksi, dan data sebelum/sesudah bila ada.

═══════════════════════════════════════════════════════════════════
BAGIAN E — FITUR LINTAS (Global)
═══════════════════════════════════════════════════════════════════

### E1. Uppercase otomatis input teks form (T2.4)
- Isi form (nama/alamat/nama_kelas/nama guru dll) dengan huruf kecil.
- [OK] Tersimpan HURUF KAPITAL (middleware global UppercaseInput di web group).
### E2. Field yang DIKECUALIKAN dari uppercase (harus tetap apa adanya)
- Kredensial/identifier (email, password, no_hp, nik, nisn, nuptk, username, login),
  select enum (status/jenis/jenis_kelamin/role/keputusan/grup), prose
  (catatan/catatan_admin), kolom `*_id`, route CMS konten publik & jabatan tampil-web.
- [OK] Nilai TIDAK diubah jadi kapital (mis. email & password login tetap
  case-sensitive; kalau di-uppercase login akan gagal).

═══════════════════════════════════════════════════════════════════
CATATAN VERIFIKASI CEPAT
═══════════════════════════════════════════════════════════════════
- Skenario negatif via UI form biasanya cukup; sebagian (A1.7, A5.4, C3.x, D1-D4)
  perlu tools seperti browser DevTools / Postman untuk mengirim request "nakal"
  (bypass JS front-end) — inilah yang menguji apakah backend benar-benar aman,
  bukan hanya validasi klien Alpine.

- [Terverifikasi di kode] SettingController@update — pengaturan biaya divalidasi:
  nominal_pendaftaran / nominal_spp / nominal_daftar_ulang ber-rule `integer|min:0`
  (menolak negatif, non-angka, desimal), tanggal_generate_spp `integer|between:1,28`.
  Semua rule `sometimes` (form dropdown kirim subset — hanya field terkirim disimpan/divalidasi).
  Maka B3.2/B3.3 GAGAL definitif saat field dikirim; B3.4 = partial-save yang sah.

- [Terverifikasi di kode, diperbarui 2026-07-17] MasterDataController@storeTeacher
  — pembuatan akun guru divalidasi: `nama` required, `no_hp` required +
  `unique:users,no_hp` + `unique:teachers,no_hp`, `nuptk` required +
  `unique:teachers,nuptk`, email opsional + `unique:users,email`, class_ids.*
  `exists:classes,id_classes`. Password awal = NUPTK, must_change_password=true.
  Maka B5.1, B5.3, B5.6, B5.7, B5.9 definitif. assignClasses pakai guard `exists` sama.

- [Ditambahkan 2026-07-14] Keunikan nomor HP diterapkan: unique index DB pada
  guardians.no_hp & teachers.no_hp (migration 2026_07_14_000000) + validasi form
  di RegisterController, StoreRegistrationRequest (ignore diri sendiri agar
  multi-anak tidak error), dan MasterDataController@storeTeacher. Kolom
  guardians.no_hp dijadikan nullable; no_hp kosong disimpan NULL (bukan '')
  sehingga banyak wali tanpa HP tidak saling bentrok (guru: no_hp WAJIB sejak
  Fase D). Lihat A1.8, A3.9, A3.10, B5.9.

- [Ditambahkan 2026-07-14] nama_kelas dijadikan unik per tahun ajaran aktif di
  storeClass (Rule::unique scoped id_academic_year). Lihat B5.8.

- [Diperbarui 2026-07-17] Perubahan skema Fase D–J & fitur baru sudah tercakup:
  login email/HP/username (A2.2), alamat+wilayah pindah ke student (A3.1/A3.8/A3.11),
  gate PPDB ditutup (A3.12), bank_asal + rekening sekolah (A4.2/B6.6), pengaturan
  akun mandiri + force-change-password (A6/C4), akun wali siswa lama (B5.11),
  curriculum slider (B6.5), uppercase input (E1/E2).
  Username akun: signup wali & ganti-password guru (min 6, unik, tanpa spasi/hubung) +
  cek ketersediaan live (A1.9/C4.1); password semua akun user-set min 8 (A1.3).

- Verifikasi otomatis (2026-07-17): `php artisan test` = 87 passed (309 assertions);
  `npm run build` OK; `php artisan migrate:fresh --seed` + DemoSeeder sukses;
  smoke test live MySQL: seluruh route 3 role → 200, RBAC lintas-role → 403,
  login (email + HP+NUPTK guru) → 302, login salah → 302 (bukan 500), tanpa error baru di log.
