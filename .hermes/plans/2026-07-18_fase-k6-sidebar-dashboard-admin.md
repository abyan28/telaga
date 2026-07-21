# Plan — Fase K6: Sidebar & Dashboard Admin (notifikasi & pemisahan)

Tanggal: 2026-07-18
Status: SELESAI 2026-07-18 — semua keputusan user diterapkan (K6.3 Opsi 1 + rincian jenis; K6.1 termasuk
dokumen; Bonus widget = 5 AuditLog dari DB). Test 101 passed, build OK, smoke browser OK.
Sumber: `.agents/notes.md` blok K6 (K6.1–K6.4). K6.2 sudah DONE.

## Ringkasan sasaran K6
- [K6.1] Badge angka "perlu verifikasi" per tab sidebar admin.
- [K6.2] DONE (dropdown PPDB collapse) — tidak dibahas.
- [K6.3] Dashboard: pisah / rapikan card "Pending Verifikasi".
- [K6.4] Hapus tab sidebar Pembayaran > "Verifikasi Pembayaran" (tak berfungsi).

## Kondisi saat ini (hasil telaah kode)
- `layouts/dashboard.blade.php`
  - Sidebar desktop admin (baris ~110–178): dropdown PPDB (Pendaftaran Siswa, Daftar Ulang),
    dropdown Pembayaran (Set Pembayaran, **Verifikasi Pembayaran**, Verifikasi SPP).
  - Sidebar mobile admin (baris ~330–358): link datar; ADA link "Pembayaran" → `admin.payments` (baris 353).
  - Tidak ada badge angka di mana pun.
- `admin/dashboard.blade.php`: 4 card. Card ke-4 "Pending Verifikasi" = `$pendingVerifikasi`
  (SEMUA `PaymentTransaction` status `pending`, tanpa membedakan jenis).
  Card + blok "Aktivitas & Log Sistem Terkini" bawahnya masih **mock statik 3 baris hardcoded** (Bunda Amira dst).
- `Admin\DashboardController@index`: `$pendingVerifikasi = PaymentTransaction::where('status','pending')->count()`.
- `admin.payments` route TERDEFINISI DUA KALI (BUG):
  - `routes/web.php:62` untuk grup **wali** (`PaymentController@index`, name `wali.payments`) — ini benar, beda prefix/name.
  - `routes/web.php:96` untuk grup **admin** = `fn () => view('admin.payments')` (placeholder kosong).
  - `admin/payments.blade.php` = halaman placeholder "Verifikasi Pembayaran Manual" yang cuma
    mengarahkan user ke Kelola Pendaftaran / Laporan. TIDAK melakukan apa pun → inilah yang K6.4 minta dibuang.
- Jenis transaksi: `pendaftaran` | `daftar_ulang` | `spp` (dikonfirmasi di StatusController & RegistrationController).
- Dokumen pendaftaran (KK/Akta/Foto) diverifikasi terpisah (status doc), bukan lewat PaymentTransaction.

---

## K6.4 — Hapus tab "Verifikasi Pembayaran" (paling aman, kerjakan dulu)
Halaman ini placeholder mati. Membuangnya sekaligus membereskan route ganda `admin.payments`.

Langkah:
1. `routes/web.php:96` — hapus baris route `admin.payments` (placeholder). Verifikasi tak ada
   `route('admin.payments')` yang tersisa memanggilnya (setelah link sidebar dibuang).
2. Sidebar desktop `layouts/dashboard.blade.php:166` — hapus `<a>` "Verifikasi Pembayaran".
3. Sidebar mobile `layouts/dashboard.blade.php:353–355` — hapus link "Pembayaran".
4. `$bayarActive` (baris 115) — buang bagian `routeIs('admin.payments')` (tak relevan lagi).
5. Hapus file `resources/views/admin/payments.blade.php`.

Risiko: rendah. Verifikasi bayar pendaftaran memang sudah lewat `admin/registration-detail`,
daftar ulang lewat `admin/daftar-ulang`, SPP lewat `admin/spp`. Tidak ada fungsi yang hilang.

---

## K6.1 — Badge angka per tab sidebar
Tampilkan jumlah item "menunggu verifikasi" sebagai badge kecil di sisi kanan tab sidebar admin, agar admin tahu ada yang perlu ditindak tanpa membuka tiap halaman.

Angka yang dihitung (semua = `PaymentTransaction` status `pending`, per jenis):
- **PPDB > Pendaftaran Siswa**: `jenis='pendaftaran'` pending. (opsi: + dokumen pending — lihat catatan)
- **PPDB > Daftar Ulang**: `jenis='daftar_ulang'` pending.
- **Pembayaran > Verifikasi SPP**: `jenis='spp'` pending.
- Badge di header dropdown PPDB = jumlah anak PPDB (pendaftaran + daftar ulang).
- Badge di header dropdown Pembayaran = jumlah SPP pending.

Di mana menghitungnya — 2 opsi:
- **Opsi A (rekomendasi): View Composer** untuk `layouts.dashboard`, hanya jalan saat user admin.
  Satu query grup: `PaymentTransaction::where('status','pending')->groupBy('jenis')->selectRaw('jenis, count(*) c')`.
  Bersih, tak menyentuh tiap controller, tak ada N+1. File baru:
  `app/Providers/AppServiceProvider.php` (tambah `View::composer`) atau composer terpisah.
- Opsi B: `@php ... @endphp` query langsung di blade (guard `$activeRole==='admin'`). Lebih "kotor"
  (query di view) tapi zero file baru. Ponytail-nya condong ke B untuk kelaziman, tapi query-di-blade
  melanggar konvensi Laravel; **A lebih tepat & tetap ringkas** (satu closure).

Desain badge: `<span>` rounded-full bg-rose-500 text-white text-[10px], hanya render bila count > 0.
Terapkan di sidebar desktop DAN mobile (K6.2 mengingatkan mobile sering terlupa).

Catatan diskusi: apakah badge Pendaftaran Siswa perlu ikut menghitung **dokumen** yang belum
diverifikasi (bukan cuma bukti bayar)? Default lazy: **cukup bukti bayar pending** (paling actionable;
dokumen diverifikasi setelah bayar diverifikasi, jadi jarang berdiri sendiri). Bisa ditambah bila diminta.

---

## K6.3 — Dashboard: card "Pending Verifikasi" (yang kamu ingin didiskusikan)
Masalah: card ke-4 sekarang menggabung SEMUA jenis pending jadi 1 angka. notes.md K6.3 aslinya minta
"pisah card Pending PPDB vs Pending Pembayaran". Kamu menyebut ingin "semacam slidebar biar enak dilihat".

Kandidat opsi (silakan pilih / gabung):

- **Opsi 1 — Dua card terpisah (paling literal ke notes.md).**
  Grid dashboard jadi 5 card, atau ubah jadi `lg:grid-cols-3` 2 baris. Card A "Pending PPDB"
  (pendaftaran pending), Card B "Pending Pembayaran" (daftar_ulang + spp pending). Sederhana, jelas,
  tanpa interaksi. Kekurangan: 5 card agak padat di layar kecil.

- **Opsi 2 — Satu card breakdown.** Tetap 1 slot card "Pending Verifikasi" dengan angka total besar,
  lalu 2 baris kecil di bawahnya: "PPDB: N" dan "Pembayaran: M". Hemat ruang, tetap informatif,
  tanpa JS. Rapi. (Ini kemungkinan yang paling mendekati "enak dilihat" tanpa gimmick.)

- **Opsi 3 — Card carousel/slider (interpretasi "slidebar").** Satu card yang isinya auto-slide
  bergantian: slide 1 = Pending PPDB, slide 2 = Pending Pembayaran, (opsional slide 3 = dokumen).
  Pakai Alpine + setInterval (pola sama dgn Curriculum Highlight yang sudah ada). Lebih "hidup"
  tapi angka jadi tak terlihat sekaligus — kurang ideal untuk data yang butuh dilihat cepat.
  Ponytail: gimmick untuk data status = anti-pattern; angka pending harus langsung kebaca, bukan disembunyikan di balik animasi.

- **Opsi 4 — Card + panel rincian scroll.** Card angka tetap, tambah panel di bawah cards berisi
  LIST transaksi/dokumen pending yang bisa di-scroll, tiap baris klik → halaman verifikasi terkait.
  Ini juga peluang membereskan blok "Aktivitas & Log" yang MASIH MOCK STATIK (lihat bonus di bawah).

Rekomendasi lazy saya: **Opsi 2** (satu card breakdown, zero JS, langsung kebaca) — memenuhi maksud
notes.md ("pisah PPDB vs Pembayaran") tanpa menambah slot atau animasi yang menyembunyikan angka.
Jika kamu ingin efek visual bergerak, Opsi 3 bisa, tapi saya sarankan tidak untuk data status.

Perubahan controller (untuk Opsi 1/2/4): `DashboardController@index` ganti `$pendingVerifikasi` tunggal
→ `$pendingPpdb` (jenis pendaftaran) + `$pendingPembayaran` (daftar_ulang + spp), satu query groupBy.

---

## Bonus (temuan, di luar 4 poin K6 — minta keputusan)
Blok **"Aktivitas & Log Sistem Terkini"** di `admin/dashboard.blade.php` (baris 85–112) MASIH mock
statik 3 baris hardcoded ("Bunda Amira", "Ustadzah Fatimah", dst). Ini mirip bug K0.3 (Audit Log
mock) yang dulu diperbaiki di halaman reports, tapi widget dashboard ini terlewat.
Opsi: (a) ganti jadi 3–5 audit log terbaru dari DB (`AuditLog::latest`), reuse pola ReportController;
(b) hapus widget; (c) biarkan (di luar scope K6). Rekomendasi: (a), murah & menghilangkan data palsu.
Ini nyambung ke K6.3 Opsi 4 bila kamu pilih panel rincian.

---

## Urutan eksekusi yang diusulkan (cheap → expensive)
1. K6.4 (hapus tab mati + bereskan route ganda) — trivial, tanpa risiko.
2. K6.3 (pilih opsi dulu) — kecil, controller + 1 card.
3. Bonus widget aktivitas (bila disetujui) — kecil.
4. K6.1 (badge sidebar via View Composer) — sedang, sentuh layout desktop+mobile.

## Verifikasi (tiap langkah)
- `php artisan test` (target tetap 100 passed; tambah test bila ada logika hitung baru).
- `npm run build` + `php artisan view:cache && php artisan view:clear` (Blade compile).
- Smoke: login admin, cek badge muncul saat ada transaksi pending, hilang saat 0.
- Update `.agents/tasklist.md` + tandai K6.1/K6.3/K6.4 [DONE] di `.agents/notes.md` per item selesai.

## Keputusan yang saya butuhkan dari user
1. K6.3: pilih Opsi 1 / 2 / 3 / 4 (rekomendasi: 2).
2. K6.1: badge Pendaftaran Siswa cukup bukti-bayar pending, atau + dokumen pending?
3. Bonus widget aktivitas dashboard: perbaiki dari DB (a) / hapus (b) / biarkan (c)?
