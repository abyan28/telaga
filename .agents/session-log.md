# Session Log — TELAGA AL KAUTSAR

> Dokumen konteks untuk sesi Hermes/agent BARU. Baca ini + `tasklist.md` dulu sebelum kerja.
> Terakhir diperbarui: 2026-07-22 (Sesi L6.2 + L2.1 + L8.1-akun-ortu — **SELESAI** — 127 test passed, build ✓)
>
> ## Sesi 2026-07-22 — Biodata detail (L6.2) + Calon Murid/NIS (L2.1) + auto-akun ortu (L8.1)
> Semua verified: 127 test passed (502), npm run build ✓, migrate:fresh --seed ✓.
> 1. **L6.2 Biodata lengkap** di admin/registration-detail. Card full-width Biodata Calon Murid (SEMUA field form pendaftaran + NIS + alamat keluarga, grid 3 kol) → 2 card berdampingan Biodata Ayah + Biodata Ibu (nama/TTL/agama/pendidikan/pekerjaan+lain/penghasilan/no HP; "tidak diisi" bila ada_*=false). Pure view, nol backend.
> 2. **L2.1 Calon Murid + NIS + Batal.** Halaman admin.calon-murid (sub-menu PPDB dropdown, setelah Daftar Ulang): datatable siswa lulus+bayarDU>0+nis null, kolom NIS setelah Orang Tua.
>    - **Generate NIS** (batch, gate PPDB tutup + NSM 12 digit): sort abjad nama → nis=NSM+YY(TA PPDB)+urut3, status→aktif. 1 POST.
>    - **Batal** per-baris: refund = max(0, terbayar − denda), denda = persen_refund% × total DU. Terbayar<denda → tombol jadi teks "Lunasi Rp X dulu". Catat PaymentTransaction jenis=refund (bukti_path nullable). form→dibatalkan, siswa→nonaktif.
>    - **persen_refund = persen DENDA langsung** (bukan 100−x). Default 30. Set di Pengaturan Sistem.
> 3. **Pengaturan Sistem** (tab sidebar BARU < Konten Web, desktop+mobile): admin.system, 3 sub-tab NSM / Refund% / Tahun Ajaran (TA DIPINDAH dari Set Pembayaran). SettingController@system/updateSystem.
> 4. **Data Murid syarat baru** (ganti K5.1): siswa PPDB tampil di Data Murid HANYA bila `nis IS NOT NULL` (bukan lagi terbayar>0). Calon belum ber-NIS = di tab Calon Murid saja. Siswa lama manual (tanpa form) tetap tampil.
> 5. **Gate login ortu** (AuthController): PPDB tutup + tak ada anak aktif/lulus-calon/dibatalkan-utang-denda → blokir. PPDB buka → gate mati → akun hidup lagi. Nol kolom flag baru.
> 6. **Dashboard Keuangan Masuk** exclude jenis=refund.
> 7. **Enum baru**: payment_transactions.jenis +refund; registration_forms.status +dibatalkan; bukti_path→nullable. Ditambah di migrasi CREATE (fresh SQLite+MySQL) + migrasi ALTER MySQL-only utk DB live (SQLite skip — tak support MODIFY COLUMN).
> 8. **L8.1 auto-akun ortu saat input murid lama.** Form Tambah Murid +field "No. HP Orang Tua" (mode create). Diisi → helper `MasterDataController::linkOrtu()` buat/tautkan akun: username=no_hp, **password=NIK anak** (kakak bila kakak-adik), must_change_password, no_hp→kontak Ibu. Merge kakak-adik by users.no_hp (nomor sama → 1 akun, sandi tak berubah). Kosong → murid saja (akun via T9.1 nanti). `linkOrtu` dipakai bareng storeStudent + createOrtuAccount (buang duplikasi ~20 baris). KEPUTUSAN kredensial: no_hp=username OK (identifier, must_change) TAPI password pakai NIK anak — NIK tak dihafal/tak berformat, lebih aman dari no_hp (bocor di grup WA ortu). NIK/NISN/NIS sebagai username DITOLAK konsisten (identifier publik/ketebak).
>
> ---
>
> ## Sesi 2026-07-21b — RENAME guardian→ortu + fix pasca-rename (ponytail)
> Semua verified: 125 test passed (479), npm run build ✓. Repo di-push ke github.com/abyan28/telaga (initial commit).
> 1. **Rename `ppdb_ta_target`→`ta_ppdb`** + method `ppdbTarget()`→`taPpdb()` (semantik: "target"=goal, keliru). Setting key, controller var, blade label "Target PPDB"→"Tahun Ajaran PPDB". DB key nol row (belum diset live).
> 2. **Fix fallback TA PPDB**: view registrations pakai `$tahunBerikutnya` (TA+1) saat null, model `taPpdb()` fallback TA aktif → beda. Seragamkan: view pakai `AcademicYear::taPpdb()`, buang `$tahunBerikutnya` dead code.
> 3. **RENAME BESAR guardian→ortu** (detail lengkap di rules §1.5/1.6, C4, memory). Tabel guardians→parents, Model OrangTua (Parent=PHP reserved), relasi ortu(), role wali→ortu, route+view dir wali→ortu, middleware RequireOrtuProfile. Helper namaWali()/noHpWali() TETAP.
> 4. **Fix sidebar role ortu**: rename bikin role DB='ortu' tapi sidebar cek `=== 'orang tua'` (label lama) → menu kosong. 2 blok (desktop+mobile) + accounts filter/badge key. Admin/guru tak terdampak.
> 5. **Validasi client form**: (a) step-1 daftar murid — 13 field L1.2 baru tak ke-validate → `[required]` + loop `querySelectorAll` di validateStep(1); (b) pekerjaan_lain `required_if:LAINNYA` (server+`:required`); (c) cabang mengaji/belajar `:required` ikut state.
> 6. **Input numerik-only** (no_hp/nik/nisn/nis/nuptk): `inputmode="numeric" pattern` di 10 blade + 1 delegated handler di layout footer (keydown block + input strip paste). Server-side `digits`/`regex` sudah ada.
> 7. **Kolom NIS** (students, 15-18 digit, nullable unique): migration + fillable + validasi `digits_between:15,18` di MasterData+Guru store/update + field di form tambah/edit admin (Alpine f.nis) & guru.
>
> ---
>
> Terakhir diperbarui sebelumnya: 2026-07-21 (Sesi L1.1+L1.2 — **SELESAI 100%** — 125 test passed, build ✓)
> - PENGERJAAN L1.1+L1.2 (1 paket — Q8). YANG SELESAI (~95%):
>   * Migration (2), Models (Guardian/Student/User), TestCase helper
>   * Wali DashboardController updateProfile + wali/profile.blade (2 sub-form Ayah/Ibu + checkbox Ada + _alamat-picker).
>   * StoreRegistrationRequest (profilRules) + wali/register.blade (L1.2 fields + cabang, alamat dibuang).
>   * MasterDataController studentRules + createWaliAccount (ada_ayah/ada_ibu).
>   * Admin students.blade modal (L1.2 fields + cabang, alamat dibuang).
>   * GuruController store/updateStudent (profilRules) + guru/dashboard.blade modal (L1.2).
>   * 11 display/ripple blade — batch swap namaWali/noHpWali + 3 detail blade dirombak manual (loop ibu/ayah, alamat guardian).
>   * Auth/RegisterController — unique:guardians,no_hp dibuang, Guardian::create(ada_ayah=false/ada_ibu=true).
- YANG BELUM (~5%):
  * ~~Admin/RegistrationController — 2× whereHas('guardian',no_hp) → orWhere ayah_no_hp/ibu_no_hp.~~ DONE.
  * ~~Seeders (DemoSeeder+DatabaseSeeder) + 11 test files.~~ DONE.
  * ~~migrate:fresh --seed + php artisan test + npm run build.~~ DONE.
- **L1.1+L1.2 SELESAI 100%** (125 test passed, 479 assertions, npm run build ✓)

> - L4.1 Halaman Data Akun SEMUA role (admin/wali/guru) + search + filter + paginate + edit + toggle nonaktif.
>   Migration users.is_aktif (root-cause guard login semua role, ganti teacher->is_aktif). Sinkron 2 arah.
>   Sidebar label → "Data Akun". Test +7 (125, 481).
> - L2.2 PPDB ditutup + belum bayar daftar ulang sama sekali → status lulus DIBATALKAN (form gagal, siswa nonaktif)
>   + blok upload bukti DU. Root-cause: RegistrationForm@cancelLulusIfPpdbClosedAndUnpaid (dipanggil lazy di
>   Wali PaymentController@index & StatusController@index). Guard server di PaymentController@store; UI payments.blade
>   badge merah "Pendaftaran Ditutup — Tidak Dapat Dibayar".
> Sesi Fase L — batch UX/PPDB (116 test):
> - L6.1 Hapus fitur catatan siswa guru (SEMENTARA — tabel/model StudentProgressNote dibiarkan; buang route+controller+UI).
> - L5.4 Login redirect bila sesi aktif (AuthController@showLogin → redirectByRole). L5.3 logout→web depan (sudah benar, nol perubahan).
> - L5.5 Slug URL (Student/Teacher/RegistrationForm) via trait App\Models\Concerns\HasSlug (auto-gen saat save, suffix -{PK}
>   bila nama kembar, getRouteKeyName=slug). Migration +slug 3 tabel + backfill. 9 blade route()→model, test URL→->slug.
>   Admin-only models (SchoolClass/PaymentTransaction/User) TIDAK di-slug.
> - L5.1 Data Siswa guru → 2 card (chips kelas di Welcome card kanan, filter+search+tambah di header tabel).
> - L5.2 UI verifikasi bukti registration-detail: hyperlink→thumbnail+openBerkas (reuse spp.blade); kolom Jumlah left-align.
> - L2.3 Guard duplikat bukti bayar (3 layer): server guard PaymentController@store + StatusController@uploadProof; UI tombol
>   "Sedang Diverifikasi" saat pending (payments.blade); eager-load transactions (nol N+1).
> - L4.2 Filter TA + status server-side (GET onchange) di Kelola Pendaftaran, default TA aktif. Reset dihapus.
> - L2.6 Kuota Pendaftaran dipindah dari tab Settings → modal float di Kelola Pendaftaran (tombol "Kuota (N)").
> - L2.5 FIX BUG: validasi usia pakai tahun AJARAN (angka pertama TA aktif) bukan now()->year; rule return array (bukan
>   pipe string yg keliru OR). Contoh: TA 2027/2028 daftar Okt 2026 → cut-off 1 Juli 2027, bukan 2026.
Sebelumnya: 2026-07-20 (116 test; L5.1/L5.2/L5.4/L5.5/L6.1 done)

## Apa ini
Sistem Informasi Sekolah **RA Al Kautsar**, nama aplikasi **"TELAGA AL KAUTSAR"** (Teknologi Layanan Akademik & Guru Al Kautsar). Laravel 13 + MySQL + TailwindCSS v4 + AlpineJS + Vite. Lokasi: `D:\Laravel\Al Kautsar`. DB: `telaga` (BUKAN typo — singkatan resmi).

## Status pengerjaan (ringkas — detail di tasklist.md)
- Tahap 1 Front End (mockup): 100% selesai.
- Tahap 2 Back End (Fase 0–7): 100% selesai — auth+RBAC, alur wali/admin/guru, keuangan cicilan, generate SPP, audit, email, export CSV/PDF.
- Tahap 2.5 CMS Konten Publik: selesai — halaman beranda/profil/info dari DB, editable oleh admin.
- Tahap 3: 3.A (binding view) & 3.B (pengujian) selesai. **SISA: Task 3.C Deployment awal.**
- **Tahap 4 Backlog (dari .agents/notes.md, ID [T#]/[P#]):** dikerjakan bertahap.
  - Fase A [SELESAI] bug fix alur inti: multi-anak status (P2.1/P2.2), state machine bayar→berkas→seleksi (P2.3/P3.2), card bukti bayar (P3.1), enum pending + Lihat Bukti (T4.1).
  - Fase B [SELESAI] restrukturisasi sidebar admin (T5.6) + pisah tab Siswa/Guru/Kelas (T5.1).
  - Fase C [SELESAI] CRUD & fitur: Siswa CRUD+filter+NISN (T5.2/T5.6d), Set Pembayaran 3 nominal (P4.2), Dashboard cards dari DB + kuota (P4.1), Profil siswa+riwayat bayar (T5.3), Guru CRUD+TTL+riwayat (T5.4/T5.6c), Kelas CRUD (T5.5/T5.6e), PPDB buka/tutup + tahun ajaran + Daftar Ulang (T5.6a/b), Data Akun admin (T5.6i), Guru ganti password (T6.1), Guru search+tambah siswa (T6.2/T6.3).
  - **DITUNDA — butuh keputusan user: [T5.10]** — SUDAH SELESAI (Fase D). Abaikan catatan ini.
  - Fase D [SELESAI] skema akun: users.name→username, nama ke profil, login email/HP/username, NUPTK (T3.2/T5.10/T3.3).
  - Fase E [SELESAI] pengaturan akun mandiri wali & guru (update email+HP) — T2.3.
  - Fase F [SELESAI] rekening bank sekolah dari DB + dropdown bank asal searchable dari bank.csv — T2.2/T5.6g/T8.1.
  - Fase G [SELESAI] tab Verifikasi SPP terpisah + guru edit profil sendiri — T5.6h/T6.4.
  - Fase H [SELESAI] Curriculum Highlight slider foto auto-slide editable via CMS (site_features grup 'kurikulum') — T7.1.
  - Fase I [SELESAI] dropdown alamat wilayah berjenjang (prov→kota→kec→desa). Keputusan: SQL diimpor ke MySQL (bukan JSON;
    ~99k baris + hierarki butuh query/index). Endpoint `wilayah` (substr(id,1,n) filter, DB-agnostik). Dipasang di form daftar
    wali, profil guru (mandiri + CMS admin). SQL dump direlokasi ke `database/data/wilayah_indonesia.sql`; folder
    `data-indonesia-master` DIHAPUS (91k JSON tak terpakai) — T2.1.
  - Fase J [SELESAI] kolom `alamat`+8 wilayah DIPINDAH `guardians` → `students` (siswa lama diinput admin/guru tanpa akun wali;
    alamat = atribut siswa). Dropdown+alamat di form daftar wali (Step 2), CRUD siswa admin & guru. Helper DRY
    `Student::alamatRules()`/`alamatData()`. Plan: `.hermes/plans/2026-07-17_pindah-alamat-guardian-ke-student.md`.
  - Housekeeping [SELESAI] 2026-07-17: dropdown wilayah siswa (guru) tambah indikator "Memuat…"; kolom `latitude`/`longitude`
    dibuang dari `t_kecamatan`/`t_kelurahan` (SQL dump); `bank.csv` −kolom `sandi_bank`, dipindah root → `database/data/bank.csv`
    (`daftarBank()` baca `database_path`).
  - T2.4 [SELESAI] semua input teks form user → HURUF KAPITAL via middleware global `UppercaseInput` (web group).
    SKIP kredensial/enum/prose + kolom `*_id`; skip route CMS konten publik & jabatan tampil-web guru.
  - T9.1 [SELESAI] admin buatkan/tautkan akun wali siswa lama (tombol per-baris Data Siswa → firstOrCreate guardian by no_hp;
    username=password=no_hp). Paksa ganti password awal: kolom `users.must_change_password` + middleware `ForceChangePassword`
    (wali & guru). Form ganti-password wali baru. Plan: `.hermes/plans/2026-07-17_t9.1-akun-wali-lama.md`.
  - Username akun [SELESAI 2026-07-17]: signup wali kini isi USERNAME (bukan Nama; nama wali diisi belakangan di CMS →
    `guardians.nama` nullable). Form paksa-ganti-password guru +field username. Constraint username: unik, 6–30 char,
    regex `^[a-zA-Z0-9_.]+$` (tanpa spasi/tanda hubung), min 6. Endpoint AJAX `GET /register/check-username?username=&min=`
    (default 3, guru/wali kirim min=6) + Alpine debounce 400ms cek ketersediaan live. Password semua akun user-set sudah
    min 8 (RegisterController/GuruController/WaliDashboardController/AccountController). Password auto guru=NUPTK & wali lama=no_hp
    dipaksa ganti via ForceChangePassword.
  - Bugfix + K0 [SELESAI 2026-07-17]: (1) tombol "Langkah Selanjutnya" form pendaftaran mati — `$refs.alamat`
    hantu di Step 1 (alamat sudah pindah Step 2 Fase J) → TypeError. (2) K0.1 nama wali tak tersimpan (store abaikan
    nama_wali). (3) K0.3 Audit Log tabel mock statik → query DB. (4) K0.4 DIBATALKAN: fitur DELETE data siswa & guru
    DIHAPUS TOTAL (hard-delete tak standar SIS; keluar/pindah = ubah status, ditunda K5.4). K0.2 ditutup (bukan item koding).
  - Backlog Fase K (temuan testing user 2026-07-17): 41 catatan bebas dikelompokkan jadi K0–K10 di `.agents/notes.md`
    (K0 BUG→K1 validasi→K2–K8 fitur/UX→K9 UX umum→K10 diskusi). K0, K1, K2 & K3 SELESAI. K4+ belum.
    - Fase K1 [SELESAI 2026-07-18] validasi trust-boundary: no_hp angka 9–14 digit + normalisasi 0→62
      (di middleware UppercaseInput, 1 titik; login ikut dinormalkan), nama regex longgar (huruf+spasi+.'-),
      NIK sudah digits:16 (verifikasi). Helper baru app/Support/ValidationRules.php (DRY, pola alamatRules).
    - Housekeeping [2026-07-18] no_hp varchar(255)→(20). Catatan: no_hp/nik/nisn sengaja varchar (digit
      identitas, bukan angka aritmetika); tanggal=date, uang=decimal(12,2) — tipe DB vs form semua sudah tepat.
    - Fase K2 [SEBAGIAN 2026-07-18] K2.1 gate wajib-isi-profil wali (guardians +TTL, Guardian::isComplete(),
      form wali/profile, middleware RequireGuardianProfile) + form daftar 3→2 langkah (Step Data Wali dibuang,
      profil sudah lengkap via gate). K2.2 teks status per-state. K2.4 hapus opsi Draft. K2.6 switcher antar-anak
      (Alpine activeChild). K2.7 halaman PPDB-ditutup dalam layout CMS (gate POST → StoreRegistrationRequest@authorize).
      K2.3 [2026-07-18] enum status dirapikan: draft DIBUANG, menunggu_bukti dipertahankan sbg state koreksi
      "Bukti Ditolak – Upload Ulang" (Opsi B); label baru "Menunggu Verifikasi Pembayaran"/"Proses Verifikasi
      Berkas"/"Proses Seleksi"; timer 30dtk (K2.3b) dibatalkan—redundan.
      SISA K2: (kosong — K2.1–K2.7 semua selesai).
      K2.5 [2026-07-18] wali edit/update pendaftaran: gate canBeEditedByWali() (bayar belum diverifikasi ATAU
      flag boleh_edit admin); efek (a) dokumen diganti→pending, form diproses_seleksi→mundur pembayaran_diverifikasi,
      flag dimatikan tiap simpan. Kolom registration_forms.boleh_edit; register.blade dual-mode; tombol Edit (wali)
      + Buka Izin Edit (admin registration-detail); route allowEdit. 3 test baru (94 passed).
      K6.2 [2026-07-18] bug dropdown PPDB sidebar admin: $ppdbActive (layouts/dashboard) hanya cek
      routeIs('admin.registrations*') → collapse saat di halaman Daftar Ulang. Fix: +|| routeIs('admin.daftar-ulang').
    - Fase K3 [SELESAI 2026-07-18] halaman admin/registration-detail: K3.1 gate tombol Lulus/Gagal (hanya saat
      diproses_seleksi + guard decide), K3.2 verifikasi dokumen AJAX tanpa refresh (verifyDocument→JSON, Alpine
      x-data reaktif), K3.3 tombol "Ubah Keputusan" (reopenDecision), K3.4 hapus input catatan dokumen, K3.5
      +nama panggilan biodata, K3.6 +pekerjaan wali + alamat berjenjang siswa + BUGFIX $wali->nama (selalu "-")
      → guardian->nama??displayName(). Route +admin.registrations.reopen-decision. 3 test baru.
    - Fase K4 [SELESAI 2026-07-18] pembayaran & verifikasi: K4.1 tampilkan bank asal + tanggal & waktu
      (created_at jam) di 3 tabel verifikasi (registration-detail/daftar-ulang/spp). K4.2 riwayat wali +Atas Nama
      anak + bulan SPP (relasi PaymentTransaction::sppBill). K4.3 tanpa koding. K4.4 hapus teks "bisa dicicil"
      (info.blade + wali/dashboard). Zero migrasi.
    - Fase K5 [SELESAI 2026-07-18] daftar ulang & status keluar: K5.4 guru +kolom bool is_aktif (migration
      110000) — nonaktif=ex-guru: disembunyikan dari list default + DIBLOK LOGIN (AuthController). Toggle
      teachers.toggle-status; siswa status via Edit existing. K5.1 siswa PPDB muncul di Data Siswa setelah bayar
      daftar ulang (terbayar>0); siswa lama manual selalu tampil. K5.2 tagihan lunas hilang dari rekap.
      K5.3 daftar-ulang pisah tab Bukti/List + badge pending + align-right.
    - Fase K6 [SELESAI 2026-07-18] sidebar & dashboard admin: K6.1 badge angka perlu-verifikasi per tab
      sidebar (View Composer 'layouts.dashboard' admin-only, bukti bayar groupBy jenis + dokumen pending →
      $sidebarBadge; desktop+mobile). K6.3 card "Pending Verifikasi" dipecah 2 card (Pending PPDB = bayar
      pendaftaran+dokumen; Pending Pembayaran = daftar ulang+SPP dgn rincian jenis); grid 4→5, card diperkecil;
      DashboardController groupBy. BONUS: widget aktivitas dashboard (mock "Bunda Amira") → 5 AuditLog dari DB.
      K6.4 hapus tab "Verifikasi Pembayaran" (placeholder mati) + FIX bug route ganda admin.payments +
      hapus view admin/payments.blade. (K6.2 sudah selesai sebelumnya.) SISA Fase K: K7–K10.
    - Fase K7 + K8.1 [2026-07-18] K7.1/K7.2 CMS Konten Web dirombak ke modal floating (contentCms() Alpine:
      modal fitur dual-mode + modal Edit Teks); tombol Tambah kini di header card (tak overflow). K8.1 filter
      "Belum Dapat Kelas" (id_class='none'→whereNull); bagian "wali kelas/guru" DIPENDING (many-to-many, tak ada
      wali kelas tunggal). Zero perubahan backend/route/test. SISA Fase K: K8.1-walikelas, K9, K10.
    - Fase K7 lanjutan [2026-07-18] sub-tab per navbar Konten Web (tab+sub Alpine, hilangkan scroll panjang) +
      card Unggah Gambar dipindah ke sub-tab "Logo/Gambar" Beranda (dulu nempel di tiap halaman, dropdown kosong).
      Logo header+footer web kini dari CMS: slot image home.logo (SiteContentSeeder) + View::composer layouts.public
      $siteLogo, fallback "AK". Jalankan db:seed --class=SiteContentSeeder agar slot home.logo tersedia.
    - Persistensi tab + fix filter [2026-07-18] (K7.5/K7.6 + bug filter):
      * Tab-halaman CMS tetap di posisi setelah aksi submit — disimpan di QUERY STRING (?tab=&sub=), BUKAN hash
        (hash hilang saat form POST & tak masuk Referer). Helper global hashTabs() di layouts/dashboard untuk tab
        1-level (daftar-ulang/spp/settings); contentCms() syncUrl() untuk 2-level (Konten Web). Syarat: back().
      * BUGFIX filter "Belum Dapat Kelas": UppercaseInput meng-uppercase id_class=none→NONE (skip *_id tak
        menangkap prefix id_). Root-cause fix: middleware skip juga prefix id_ (konvensi FK). +assert unit test.
      * Filter kelas Data Siswa +onchange auto-submit (selaras dropdown status).
      * rules.md §6 BARU: konvensi UI/UX wajib-otomatis (tab-persisten via query, sub-tab, modal, kapital,
        tanggal ID, validasi DRY, eager-load, verifikasi) — agar diterapkan langsung di fitur berikutnya.
      Test 101 passed (382 assertions).
    - Polish tab/slidebar admin [2026-07-18] (di luar 4 poin K6): (a) daftar-ulang tab di-swap → List Siswa
      (default) lalu Bukti; (b) spp diberi tab → Tagihan SPP Bulanan (default) lalu Bukti SPP + kolom Status
      tabel Tagihan align-right; (c) settings 3 tab (Nominal→Rekening→Generate SPP), Nominal jadi dropdown
      pilih-1-item + input dinamis, SettingController@update rule required→sometimes (simpan subset saja);
      (d) urutan sidebar admin: tab Pembayaran dipindah setelah Data Kelas (Konten Web kini di bawahnya),
      desktop+mobile; (e) card dashboard: PPDB kuning, Pembayaran merah, keduanya animate-pulse.
    - Fase K9 [SEBAGIAN 2026-07-18] format & UX umum: K9.1 tanggal Bahasa Indonesia (root cause APP_LOCALE
      en→id di .env → semua translatedFormat langsung ID; +ubah 7 tanggal tampilan numerik d/m/Y→translatedFormat;
      input date tetap Y-m-d). K9.3 [DONE] pagination server-side 25/halaman di 4 datatable (Kelola Pendaftaran/
      Data Siswa/Data Guru/Audit Log) via ->paginate(25)->withQueryString() + komponen x-paginate reusable
      (components/paginate.blade, Tailwind v4); +bugfix search guru orWhereHas bocor abaikan filter is_aktif.
      K9.4 tab Akun&Profil guru → sub-tab hashTabs (3 card). K9.2 [OK] mobile-friendly — audit kode: sudah
      responsif menyeluruh (sidebar hamburger+drawer, 12 tabel overflow-x-auto, grid prefix responsif), zero fix.
    - Fase K10 [SEBAGIAN 2026-07-18] K10.1 [TUTUP] hapus users.no_hp — bukan redundant (identifier login,
      unique constraint); users=login, profil=kontak, keduanya sengaja ada. K10.3 [DONE] CMS Wali "Data Anak":
      list + profil per-anak read-only (biodata + catatan perkembangan guru); gate abort_unless id_guardian→404.
      DashboardController@children/@childProfile, wali/children+child-profile, route wali.children(.show). SISA
      K10: K10.2 (restrukturisasi tab Pembayaran) & K10.4 (cicilan opt-in) — belum dibahas.
    - Foto profil [DONE 2026-07-18] students +foto_path (migration 120000, nullable). Foto pendaftaran auto→foto
      profil siswa; CRUD siswa (admin+guru) & guru (storeTeacher) +upload foto opsional. Lokasi seragam via
      DocumentUploadService::storeProfilePhoto → profil/{siswa|guru}/{Nama}/Foto_{Nama}.ext (bukan konten/hash).
      Komponen <x-avatar> (foto||inisial) di profil siswa admin/wali + tabel guru + Profil Saya guru; web daftar
      pendidik sudah pakai. ResetStorage +folder profil. SISA Fase K: K8.1-walikelas, K10.2, K10.4.
    - Sesi [2026-07-19] Foto (hardening) + Profil guru + K8.1 Wali Kelas + kolom tabel — lihat tasklist.md sesi 2026-07-19:
      * Foto: suffix PK di semua folder upload (nama kembar tak timpa); bug updateTeacher tak simpan foto → fix;
        modal edit tampilkan foto lama; foto di header Detail & Verifikasi + kartu anak wali/status.
      * Profil siswa admin → tab (hashTabs). Halaman profil guru baru (teacher-profile). Guru lihat profil siswa.
      * Tabel Guru: kelas>1 dropdown <details> + tombol Profil; kolom Nama→NUPTK→No.HP. NUPTK wajib 16 digit.
      * Filter status Data Siswa default 'aktif'. Kolom Data Siswa admin & guru dirapikan (+Wali Kelas).
      * **K8.1 Wali Kelas SELESAI** — lihat "Keputusan arsitektur C11" di bawah. SISA Fase K: K10.2, K10.4.
    - Sesi [2026-07-19b/c] K10.1–K10.2 + polish keuangan/guru/profil — lihat tasklist.md sesi 2026-07-19b & 2026-07-19c:
      * K10.1 [TUTUP] hapus users.no_hp → TIDAK (identifier login, bukan redundant). Zero koding.
      * K10.2 [SELESAI] tab "Verifikasi SPP" → "Kelola Pembayaran"; rekap SPP semua siswa +search+filter
        (kelas/bulan/tahun/status)+kolom Bukti(thumbnail lightbox)+pagination 25/hal.
      * Daftar Ulang → histori penuh (lunas TETAP tampil, K5.2 dibatalkan) +filter TA/status/search+pagination
        +kolom Tanggal/Waktu/Bank. Kedua model +relasi transactions() (referensi_id → bill, per jenis).
      * Reorder+rename kolom kedua tabel per spec user; Bukti center. Kolom Murid tabel guru center.
      * Jabatan guru: +field di form admin (store/updateTeacher) & profil guru CMS. Auto-fill "WALI KELAS" saat
        di-assign wali kelas (setHomeroom/setTeacherHomeroom, clearWaliKelasJabatan lepas — jaga jabatan manual).
      * Profil guru CMS: guru upload foto sendiri; tab Profil default = mode LIHAT + tombol Edit → form.
      * Profil siswa (admin+guru+wali): +tab "Profil Orang Tua" + baris "Wali Kelas". eager +guardian+homeroomTeacher.
      * BUGFIX: wali kelas tak tersimpan — classes.blade openEdit() int↔string mismatch di Alpine x-model → dropdown
        Edit reset ke "Tanpa Wali Kelas". Fix String(homeroom). Data lama NULL → set ulang lewat form.
      * SISA Fase K: K9.2 (mobile audit clean), K10.4 (cicilan opt-in — MODE PLAN, Keputusan A terkunci,
        B & C belum dibahas; lihat .hermes/plans/2026-07-19_k10.4-cicilan-optin-halaman-data-ortu.md).
    - Sesi [2026-07-19d] MODE PLAN K10.4 (belum koding). Keputusan A TERKUNCI: flag boleh_cicil di guardians
      (per-wali) + BIKIN Halaman Data Orang Tua (datatable: search+paginasi+filter kelas+filter status; kolom
      Nama Ortu·No.HP·Pekerjaan·Anak·WaliKelas·Kelas·Status·Aksi, kolom anak/walikelas/kelas ditumpuk sejajar
      per anak; Aksi profil/edit/toggle cicil). Status ortu = turunan anak (≥1 anak aktif→boleh login, else
      blokir di AuthController). Siswa tanpa wali (id_guardian NULL) = default harus-lunas, pakai T9.1 bila perlu.
      Ide auto-create wali dari NIK/NISN DITOLAK (identifier publik, tak aman). SISA: kunci Keputusan B (scope
      tagihan) & C (bentuk penegakan) → finalisasi plan → eksekusi.
  - Audit [SELESAI] P1.2 (tabel cache/jobs — diputuskan jangan hapus), T3.1 (eager-load — 1 N+1 diperbaiki).
  - SISA backlog belum dikerjakan: T1.1/T1.2/T1.3 (verifikasi email + lupa sandi — nyerempet deploy 3.C butuh SMTP asli).
    (T2.1/T2.4/T7.1/T9.1 SUDAH selesai.)
- Test: `php artisan test` = **127 passed (502 assertions)**. Verify: `npm run build`.


## Lingkungan (WINDOWS — quirk penting, hemat waktu)
- Terminal Hermes lewat **git-bash** (`C:\Program Files\Git\bin\bash.exe`). Pakai sintaks POSIX, path `/d/Laravel/Al Kautsar`.
- **Composer** rusak via wrapper. Jalankan: `php "C:\ProgramData\ComposerSetup\bin\composer.phar" <cmd>`. Step autoload bisa timeout >180s SETELAH install sukses → lalu `php <composer.phar> dump-autoload` + `php artisan package:discover`.
- PHP ext yang sudah diaktifkan di `C:\php\php.ini`: `pdo_sqlite`, `sqlite3` (test :memory:), `gd` (upload gambar), `pdo_mysql`.
- Server MySQL hidup (Laragon/XAMPP); `mysql` CLI tak di PATH.
- Browser tool (Chromium/agent-browser) sudah terpasang untuk cek UI. Catatan: ref elemen di halaman login cepat basi karena Alpine re-render — verifikasi UI lebih andal via feature test.

## Keputusan arsitektur (FINAL — jangan diubah tanpa konfirmasi user)
- **C2 Auth**: satu tabel `users` + kolom `role` (`ortu`/`admin`/`guru`) + middleware `role:`. Ortu self-signup (role dikunci `ortu`); guru dibuat admin (password awal = nomor induk guru); multi-admin; admin pertama via seeder. Lihat rules.md §1.5.
- **C3 Konvensi PK/FK non-standar** (rules.md §2): PK = `id_<tabel>` (mis. `id_users`), FK = `id_<singular>` (mis. `id_user`). Tiap model set `$table`+`$primaryKey`, relasi eksplisit, `getRouteKeyName()` untuk binding. Uang = `decimal(12,2)`.
- **C4**: profil terpisah 1-1 (`parents`, `teachers`); admin tanpa profil; `students` bukan akun login. Model `App\Models\OrangTua` (bukan `Parent` — PHP reserved word). Relasi `ortu()` di User+Student.
- **C10 Alamat & Wilayah (2026-07-17, Fase I/J)**: alamat + 8 kolom wilayah (provinsi/kota/kecamatan/kelurahan × id+nama)
  ada di `students` & `teachers` (BUKAN `parents` — alamat siswa terpisah karena siswa lama tanpa akun ortu). Data referensi
  wilayah di 4 tabel `t_provinsi/t_kota/t_kecamatan/t_kelurahan` (kode BPS, read-only, di luar konvensi PK/FK — seperti bank.csv).
  Diimpor migration `..._000000` dari `database/data/wilayah_indonesia.sql`. Endpoint AJAX `wilayah` (name global, middleware auth)
  filter `substr(id,1,n)=parent`. Dropdown pakai helper DRY `Student::alamatRules()`/`alamatData()`. Folder `data-indonesia-master` sudah DIHAPUS.
- **C6**: generate SPP via command `spp:generate` (idempotent) + Scheduler bulanan + tombol manual CMS.
- **C7**: PDF via `barryvdh/laravel-dompdf`; CSV native.
- **C9**: tahun ajaran format `2026/2027` (folder `2026-2027`), satu aktif.
- **C11 Wali Kelas (2026-07-19, K8.1)**: guru↔kelas TETAP many-to-many (`class_teacher`, "mengajar"). Wali kelas
  = FK tunggal nullable `classes.id_homeroom_teacher` → `teachers` (Opsi A). 1 kelas=1 wali (kolom tunggal jamin);
  1 guru maks 1 kelas jadi wali (ditegakkan di app: `setHomeroom`/`setTeacherHomeroom` melepas jabatan lama saat
  reassign — BUKAN constraint DB). Hak akses guru: **wali kelas** → tambah+edit siswa (guardHomeroom/isHomeroomOf);
  **guru biasa** (cuma mengajar) → hanya lihat profil + catatan perkembangan (guardStudent, tetap by ampuClassIds).
  Admin assign 2 arah: form Kelas & form Guru. Relasi: `SchoolClass::homeroomTeacher`, `Teacher::homeroomClass`.
- **CMS konten** (rules.md §1.8): pendekatan hibrida — `site_contents` (key-value) + `site_features` (item berulang dinamis). Biaya halaman info dari `settings`. Tenaga pendidik dari `teachers.tampil_di_web`. Upload gambar ke `storage/app/public/konten`. Pengelola admin saja.

## Model kelas penting (nama non-obvious)
- Kelas sekolah → model `SchoolClass` (bukan `Class`, keyword PHP). Tabel `classes`.
- `sessions.user_id` dipertahankan default Laravel (driver hardcoded) — pengecualian sah dari konvensi.

## Akun demo (setelah `migrate:fresh --seed` + `db:seed --class=DemoSeeder`)
- Admin: `admin@telaga.sch.id` / `admin123` (di-set manual tiap sesi; seeder aslinya generate acak).
- Wali A (2 anak): `wali@telaga.sch.id` / `password`
- Wali B (kakak 2025/2026 + adik 2026/2027): `rizal@telaga.sch.id` / `password`
- Guru: `guru.ahmad@telaga.sch.id` atau `081300000001` (no_hp) / `1234567890123456` (NUPTK = password awal).
- Signup wali baru: form minta USERNAME (bukan nama), min 6, unik, tanpa spasi/tanda hubung; nama wali diisi via CMS.

## Perintah rutin
- Reset DB + data demo: `php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder && php artisan storage:reset --force`
- **`php artisan storage:reset [--force]`**: hapus folder upload (`pendaftaran/`,`pembayaran/`,`konten/`,`profil/`) di disk public.
  Jalankan tiap reset DB — path bukti bayar/dokumen/foto di DB hilang saat fresh, filenya jadi yatim bila tak dihapus.
- Test: `php artisan test`  · Build: `npm run build`  · Server: `php artisan serve` (:8000)
- Verifikasi Blade compile: `php artisan view:cache && php artisan view:clear`

## Catatan/utang teknis
- **Berkas upload (2026-07-18, suffix PK 2026-07-19)**: bukti bayar di `pembayaran/{jenis}/{tahun-ajaran}/{Nama-Siswa-id}/`, nama
  `{Label}_{tahun}_[bulan SPP]_{tgl}_{waktu}_{Nama}.ext` (DocumentUploadService::storePaymentProof). Dokumen
  pendaftaran `pendaftaran/{tahun}/{Nama-Anak-id}/` (rules §3). Semua folder upload di-suffix PK siswa (nama kembar
  tak numpuk); `store`/`storePaymentProof`/`storeProfilePhoto` semua terima param `$id` opsional. Replace berkas
  (dokumen edit wali, foto guru web) MENGHAPUS file lama (beda ekstensi sekalipun). Lightbox global di layouts/dashboard
  (event `open-lightbox`, helper `openBerkas()`): gambar overlay, PDF `<object>` inline. Reset DB → jalankan `storage:reset --force`.
- **Foto profil (2026-07-18, fix 2026-07-19)**: `students.foto_path` + `teachers.foto_path`. Foto pendaftaran (dokumen jenis=foto)
  auto jadi foto profil siswa. Foto CRUD siswa/guru & guru web disimpan seragam via
  `DocumentUploadService::storeProfilePhoto($file,$peran,$nama,$id)` → `profil/{siswa|guru}/{Nama-id}/Foto_{Nama-id}.ext`.
  Param `$id` (PK) WAJIB agar nama kembar tak saling menimpa; store baru create record dulu → upload pakai PK → update foto_path.
  updateTeacher (edit guru CRUD) SEBELUMNYA tak menyimpan foto (bug 2026-07-19, kini diperbaiki). Ditampilkan lewat
  komponen `<x-avatar :path :name :size>` (foto||inisial). Folder `profil/` ikut `storage:reset`.
- **`database/data/bank.csv`** (git-tracked): sumber daftar bank dropdown (T8.1), dibaca `PaymentTransaction::daftarBank()` via `database_path('data/bank.csv')`. Satu kolom `nama_bank` (kolom `sandi_bank` dibuang 2026-07-17 — tak dipakai). Sengaja TIDAK masuk DB (statis). Ikut ter-deploy.
- **`database/data/wilayah_indonesia.sql`**: kolom `latitude`/`longitude` di `t_kecamatan`/`t_kelurahan` dibuang 2026-07-17 (tak dipakai; `t_kota`/`t_provinsi` memang tak punya). Tabel kini `id, nama` saja.
- Rekening bank sekolah disimpan di `settings` (bank_sekolah/rekening_sekolah/atas_nama), diatur admin di tab Set Pembayaran.
- Halaman `admin/payments` masih minimal (verifikasi bayar utamanya lewat `admin/registration-detail`). Verifikasi SPP kini punya tab sendiri (`admin.spp`).
- Tahap 3.C Deployment belum: env production, MAIL SMTP asli (skrg `log`), cron `schedule:run`, `storage:link` di server, `php artisan optimize`.
- Aturan wajib: update `tasklist.md` tiap task selesai; komentari tiap fungsi (rules.md §5).
