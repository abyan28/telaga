# Rencana: CMS Konten Halaman Publik (Beranda, Profil, Info Pendaftaran)

Tanggal: 2026-07-13
Status: USULAN (mode plan) — menunggu persetujuan user sebelum eksekusi.

## 1. Latar Belakang & Temuan

Halaman publik `home.blade.php`, `profile.blade.php`, `info.blade.php` saat ini **meng-hardcode semua konten** di file Blade:
- Beranda: teks hero, statistik (150+ murid, 15+ ustadz, dst), 3 kartu program/fasilitas, teks CTA.
- Info: rincian biaya (Rp 150.000 / 1.500.000 / 250.000 — HARDCODE), persyaratan dokumen, alur pendaftaran.
- Profil: (perlu dicek) visi/misi, sejarah, sambutan, dll.

**Masalah:** Operator sekolah (bukan orang IT) tidak bisa update konten tanpa mengedit file HTML/Blade & deploy ulang. Ini tidak layak untuk pemeliharaan berkala.

**Temuan inkonsistensi penting:** nominal biaya di `info.blade.php` di-hardcode, padahal nominal sudah ada di tabel `settings` (diatur Admin di CMS keuangan). Akibatnya jika Admin mengubah nominal SPP/pendaftaran, halaman publik TIDAK ikut berubah. CMS konten sekaligus menyelesaikan ini (info biaya diambil dari `settings`).

**Kesimpulan:** Belum ada CMS konten publik. Direkomendasikan dibuat.

## 2. Tujuan

Operator (role admin) dapat mengedit konten halaman publik lewat CMS tanpa menyentuh kode:
- Teks & angka yang sering berubah (hero, statistik, sambutan, visi/misi, persyaratan, dsb).
- Biaya di halaman info diambil live dari `settings` (bukan lagi hardcode).

## 3. Pilihan Pendekatan (untuk didiskusikan)

### Opsi A — Key-Value Site Content (REKOMENDASI)
Satu tabel `site_contents` (key, value, tipe, grup). Tiap potongan konten = 1 baris.
Contoh key: `home.hero_title`, `home.hero_subtitle`, `home.stat_murid`, `profile.visi`, `info.persyaratan` (list), dst.
- Kelebihan: sederhana, cepat, fleksibel, cocok untuk teks tersebar. Mirip pola `settings` yang sudah ada (konsisten).
- Kekurangan: konten berulang (mis. daftar program/fasilitas yang jumlahnya bisa nambah) kurang rapi bila hanya key-value.

### Opsi B — Tabel terstruktur per jenis
Tabel khusus: `programs` (kartu fasilitas), `page_sections`, dll + key-value untuk teks tunggal.
- Kelebihan: rapi untuk konten berulang (operator bisa tambah/hapus kartu program).
- Kekurangan: lebih banyak tabel & CRUD.

### Opsi C — Hibrida (REKOMENDASI PRAKTIS)
- `site_contents` (key-value) untuk semua teks/angka tunggal (hero, statistik, sambutan, visi, misi, teks CTA, alamat/kontak).
- `site_features` (tabel baris) untuk item berulang: kartu program/fasilitas beranda, poin persyaratan, langkah alur (agar operator bisa tambah/kurangi item).
- Biaya di info → live dari `settings`.
- Kelebihan: seimbang — sederhana untuk teks, rapi untuk list. Kekurangan: 2 tabel baru.

## 4. Cakupan Konten yang Dapat Diedit (draft — perlu konfirmasi)

BERANDA:
- Badge atas, judul hero (+ highlight), subjudul hero, teks tombol.
- Kartu highlight kurikulum (judul materi unggulan, teks "150+ murid").
- 4 statistik (angka + label): murid aktif, ustadz, kurikulum, ekskul.
- Judul & subjudul bagian "Mengapa memilih".
- 3+ kartu program/fasilitas (judul + deskripsi) — REPEATABLE.
- Teks CTA (judul + paragraf).

PROFIL: (perlu baca profile.blade.php dulu) — kemungkinan: sambutan kepala, visi, misi (list), sejarah singkat, nilai-nilai.

INFO PENDAFTARAN:
- Header (judul + subjudul).
- Biaya (3 kartu) → AMBIL DARI settings (pendaftaran/daftar_ulang/spp), plus deskripsi tiap biaya (editable).
- Persyaratan dokumen (list) — REPEATABLE.
- Alur pendaftaran (list langkah) — REPEATABLE.

KONTAK/UMUM (dipakai lintas halaman/footer): nama sekolah, alamat, no. telp/HP, email, jam operasional, link medsos.

## 5. Desain Teknis (bila Opsi C dipilih)

Migrasi (konvensi PK/FK rules.md §2):
- `site_contents`: id_site_contents (PK), `key` unique, `value` (text), `grup` (home/profile/info/kontak), `label` (deskripsi utk operator), `tipe` (text/textarea/number).
- `site_features`: id_site_features (PK), `grup` (program/persyaratan/alur), `judul`, `deskripsi`, `urutan`, `aktif`.

Model: `SiteContent` (+ helper `SiteContent::get('home.hero_title', default)`), `SiteFeature`.

Controller: `Admin\SiteContentController` (index tampil form per grup, update batch key-value + CRUD features). Route group admin `role:admin`: `admin.content.*`.

View publik diubah: ganti teks hardcode → `SiteContent::get(...)` / loop `SiteFeature`. Biaya info → `Setting::get('nominal_*')`.

View CMS baru: `admin/content.blade.php` — form per grup (tab Beranda/Profil/Info/Kontak), editor list feature (tambah/hapus/urutkan).

Seeder: `SiteContentSeeder` mengisi nilai default = teks yang SEKARANG ada di Blade (agar tampilan tidak berubah setelah migrasi).

Menu: tambah item "Kelola Konten Web" di sidebar dashboard admin.

Test: konten ter-render dari DB; update via CMS mengubah tampilan publik; biaya info sinkron dgn settings; RBAC (hanya admin).

## 6. Dampak ke Dokumen Proyek (bila fix)
- prd.md: tambah fitur "Manajemen Konten Website (CMS Publik)" di lingkup Admin.
- rules.md: aturan konten (siapa boleh edit = admin; biaya publik bersumber dari settings; konten default via seeder).
- workflow.md: alur operator mengedit konten.
- tasklist.md: Tahap baru / sub-task Fase CMS konten + progress.

## 7. KEPUTUSAN FINAL (dikonfirmasi user 2026-07-13)
1. Pendekatan: **C (Hibrida)** — `site_contents` (key-value) + `site_features` (baris, dinamis).
2. Item berulang: **(b) DINAMIS** — operator bisa tambah/hapus/urutkan (program, persyaratan, alur, statistik).
3. Biaya di halaman info: **live dari `settings`** (hapus hardcode). ✅
4. **Upload gambar/logo lewat CMS**: ya (logo sekolah, gambar hero opsional, foto guru). ✅
5. Pengelola: **admin saja**. ✅
6. **Tenaga pendidik di halaman profil diambil dari DATABASE** (tabel `teachers` + `users`), bukan konten statik.

## 8. Cakupan Final Per Halaman (hasil baca ketiga view)

BERANDA (home.blade.php):
- site_contents: badge, hero_title, hero_highlight, hero_subtitle, teks 2 tombol, kartu kurikulum (label+judul+teks murid), 4 statistik (angka+label masing-masing → atau jadikan features grup 'statistik'), judul+subjudul "Mengapa memilih", CTA judul+paragraf.
- site_features grup 'program': 3 kartu program (judul+deskripsi) — DINAMIS.
- (opsi) statistik sebagai features grup 'statistik' (angka+label) agar bisa tambah/kurang.

PROFIL (profile.blade.php):
- site_contents: header judul+subjudul, Sejarah (2 paragraf/textarea), Visi (textarea).
- site_features grup 'misi': poin misi (list) — DINAMIS.
- **Tenaga Pendidik: DARI DB** — query teachers (join users utk nama). Perlu tambah kolom ke tabel `teachers`: `jabatan` (mis. "Kepala Sekolah", "Wali Kelas A"), `foto_path` (nullable), `tampil_di_web` (boolean, default false). Admin atur di CMS guru (users.blade.php) mana yang tampil + jabatan + foto. Inisial dipakai bila tak ada foto.

INFO (info.blade.php):
- site_contents: header judul+subjudul, deskripsi tiap biaya (3), teks penutup.
- **Biaya (3 nominal): dari `settings`** (nominal_pendaftaran, nominal_daftar_ulang, nominal_spp).
- site_features grup 'persyaratan': poin dokumen — DINAMIS.
- site_features grup 'alur': langkah pendaftaran — DINAMIS.

KONTAK/UMUM (footer + layouts/public.blade.php): site_contents grup 'kontak' — nama sekolah, alamat, telp, email, jam operasional, medsos; + logo (media).

## 9. Desain Teknis Final

Migrasi baru (konvensi rules.md §2):
- `site_contents`: id_site_contents PK, key unique, value(text null), grup, label, tipe(text/textarea/number/image).
- `site_features`: id_site_features PK, grup, judul, deskripsi(text null), ikon(null), foto_path(null), urutan(int), aktif(bool).
- ALTER `teachers`: + jabatan(string null), + foto_path(string null), + tampil_di_web(bool default 0).
- `media`/upload: simpan file ke storage/app/public/konten/ (logo, hero, foto guru) via DocumentUploadService yang sudah ada (generalisasi) atau service baru MediaUploadService.

Model: SiteContent (helper get/set), SiteFeature (scope grup+aktif+urut). Update Teacher (fillable + kolom baru).

Controller: Admin\SiteContentController (index tab per grup; updateContents batch; storeFeature/updateFeature/deleteFeature/reorder; uploadMedia). Update MasterDataController utk field guru web (jabatan/foto/tampil).

Routes admin (role:admin): admin.content.* (index, contents.update, features.store/update/destroy, media.upload).

Public controller: buat PublicController@home/profile/info yang inject SiteContent + SiteFeature + Setting + Teacher::where('tampil_di_web',true). Ganti closure route publik.

Views: ubah home/profile/info -> render dari DB. Buat admin/content.blade.php (tab Beranda/Profil/Info/Kontak + editor features dinamis + upload). Tambah menu "Kelola Konten Web" di sidebar admin.

Seeder: SiteContentSeeder + SiteFeatureSeeder = isi default persis teks/gambar SEKARANG (tampilan tak berubah). Update DemoSeeder: set jabatan+tampil_di_web utk guru contoh.

Test: konten publik render dari DB; update CMS ubah tampilan; biaya info == settings; guru tampil_di_web muncul di profil; RBAC admin-only; upload media tervalidasi (image <=2MB).

## 10. Estimasi Tahap (untuk tasklist.md)
Fase CMS-1 Migrasi+Model+Seeder (site_contents, site_features, ALTER teachers) → CMS-2 Public rendering (controller+bind 3 view+footer) → CMS-3 CMS admin (form konten+editor features dinamis+upload media) → CMS-4 Guru web fields (jabatan/foto/tampil) → CMS-5 Test+verifikasi.
