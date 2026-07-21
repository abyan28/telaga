# Rencana FASE A — Bug Fix Alur Inti Pendaftaran & Pembayaran

Tanggal: 2026-07-16
Status: USULAN (mode plan) — menunggu keputusan user pada 3 poin di §Keputusan.
Referensi notes: P2.1, P2.2, P2.3, P3.1, P3.2, T4.1 (lihat .agents/notes.md).

## Temuan verifikasi kode (bukan asumsi — sudah dibaca)

1. **Enum status TIDAK konsisten (root cause tersembunyi).**
   - Controller & DB menyimpan status pembayaran/dokumen = `'pending'`
     (StatusController:66, PaymentController:73), status dok default `'pending'`.
   - View `admin/registration-detail.blade.php` mengecek `=== 'menunggu'`
     (baris 26, 33, 190). Karena `'pending' !== 'menunggu'`, tombol Verifikasi
     bukti bayar & badge warna TIDAK PERNAH tampil.
   - => Ini menjelaskan T4.1 ("tambah tombol tolak/terima"): tombolnya ADA tapi
     mati. Fix = samakan enum (pilih `'pending'` sbg sumber, perbaiki view), bukan
     tambah fitur baru. Cek juga migration registration_documents &
     payment_transactions untuk nilai enum resmi sebelum menyeragamkan.

2. **P3.2 terkonfirmasi:** `verifyDocument` (RegistrationController) hanya meng-
   update 1 dokumen; tidak ada transisi form -> `diproses_seleksi` saat 3 dok
   diterima. Status di sisi wali macet di `pembayaran_diverifikasi`.

3. **P2.1 terkonfirmasi:** `StatusController::index` pakai `->first()` (1 form
   terbaru). Wali >1 anak hanya lihat anak terakhir. Data anak lain ada di DB
   tapi tak tampil.

4. **P2.2 sebagian sudah ada:** `PaymentController::index` SUDAH mengambil semua
   `$students` milik wali (bukan first). Perlu cek `wali/payments.blade.php`
   apakah view-nya melooping semua anak atau hanya render satu. (BELUM dibaca —
   task A1 wajib baca dulu.)

5. **P2.3:** timeline `wali/status` sudah mengurutkan Bukti Bayar (tahap 1)
   sebelum Verifikasi (tahap 2), tapi tahap verifikasi masih MENGGABUNG berkas +
   bukti dalam 1 langkah. Perlu keputusan seberapa jauh dipisah (lihat Keputusan).

6. **P3.1:** card "Verifikasi Bukti Pembayaran" saat ini di posisi ke-4 (setelah
   biodata & dokumen). Pindah ke atas = murni reorder blok Blade.

## Task bite-sized

### A1 — Multi-anak di Status & Payments (P2.1, P2.2)
- `StatusController::index`: ganti `->first()` -> ambil koleksi SEMUA form milik
  wali (`->get()`), eager load `student.documents`-nya. Kirim `$forms` (jamak).
- `wali/status.blade.php`: bungkus blok kartu+timeline+dokumen dalam
  `@foreach ($forms as $form)`. Empty-state bila koleksi kosong.
- `wali/payments.blade.php`: baca dulu; pastikan loop semua `$students`. Perbaiki
  bila hanya render satu.
- Verifikasi: MultiChildTest & WaliViewsTest tetap hijau (mungkin perlu update
  asersi karena kini banyak blok).

### A2 — State machine + seragamkan enum (P2.3, P3.2)
- Baca migration registration_documents & payment_transactions -> tetapkan enum
  kanonik (kemungkinan `pending|diterima|ditolak` & `pending|diverifikasi|ditolak`).
- Perbaiki `registration-detail.blade.php`: `'menunggu'` -> `'pending'` (3 tempat).
- `RegistrationController::verifyDocument`: setelah update, cek apakah semua
  dokumen form = `diterima` DAN status form sudah `pembayaran_diverifikasi`;
  bila ya set form -> `diproses_seleksi` (+ audit log). Idempotent.
- Urutan (P2.3): pastikan `verifyPayment` mendahului verifikasi berkas dalam alur
  status. Lihat Keputusan #1 untuk seberapa ketat menegakkannya.

### A3 — Reorder card bukti bayar ke atas (P3.1)
- `registration-detail.blade.php`: pindah blok "Verifikasi Bukti Pembayaran"
  (baris ~163-207) ke atas, sebelum biodata (baris ~75). Murni pemindahan.

### A4 — Foto bukti bayar + tombol aktif (T4.1)
- Di tabel bukti bayar, tambah kolom/link "Lihat Bukti"
  (`asset('storage/'.$payment->bukti_path)`, target _blank) — pola sama seperti
  dokumen (baris 142-147).
- Tombol Tolak/Verifikasi jadi aktif otomatis setelah fix enum A2 (kondisi
  `=== 'pending'`).

## Verifikasi akhir Fase A
- `php artisan test` hijau (jalankan via php artisan test).
- `npm run build` sukses.
- Update `.agents/tasklist.md` (§4 rules) + `.agents/notes.md` tandai item selesai.

## Keputusan yang diminta ke user
1. **P2.3 seberapa ketat?** Opsi (a) MINIMAL: cukup perbaiki agar status mengalir
   benar (bayar diverifikasi -> berkas -> seleksi), timeline tetap 1 langkah
   gabungan. Opsi (b) PISAH PENUH: pecah jadi 2 tahap timeline terpisah
   "Verifikasi Pembayaran" lalu "Verifikasi Berkas", dan admin tak bisa verifikasi
   berkas sebelum bayar diverifikasi (enforce urutan). (a) lebih lazy & cukup
   untuk bug; (b) sesuai bunyi notes literal.
2. **Enum kanonik:** setuju pakai `'pending'` (yg dipakai controller/DB sekarang)
   dan perbaiki view? Atau ada preferensi lain.
3. **A4 tampilan bukti:** cukup link "Lihat Bukti" buka tab baru (lazy, konsisten
   dgn dokumen), atau perlu preview gambar inline di card?
