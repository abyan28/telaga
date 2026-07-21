<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use App\Models\SiteFeature;
use Illuminate\Database\Seeder;

/**
 * SiteContentSeeder — mengisi konten default halaman publik = teks/angka yang
 * ADA SAAT INI di mockup, agar penerapan CMS tidak mengubah tampilan (rules.md §1.8).
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        // --- Teks/angka tunggal (key-value) ---
        $contents = [
            // Beranda
            ['home.logo', null, 'home', 'Logo (header & footer web)', 'image'],
            ['home.badge', 'Penerimaan Murid Baru Online', 'home', 'Badge atas hero', 'text'],
            ['home.hero_title', 'Membentuk Generasi Islami & Mandiri Sejak Dini', 'home', 'Judul hero', 'textarea'],
            ['home.hero_highlight', 'Islami & Mandiri', 'home', 'Kata yang di-highlight pada judul', 'text'],
            ['home.hero_subtitle', 'RA Al Kautsar berkomitmen membimbing anak didik dengan kasih sayang dan nilai-nilai Islami untuk melahirkan generasi yang cerdas, berakhlak mulia, dan siap menyongsong masa depan.', 'home', 'Subjudul hero', 'textarea'],
            ['home.kurikulum_judul', 'Tahfidz Quran & Doa Harian Anak', 'home', 'Judul kartu kurikulum', 'text'],
            ['home.kurikulum_murid', 'Bergabung dengan 150+ murid aktif lainnya', 'home', 'Teks murid pada kartu kurikulum', 'text'],
            ['home.mengapa_judul', 'Mengapa Memilih RA Al Kautsar?', 'home', 'Judul bagian keunggulan', 'text'],
            ['home.mengapa_subjudul', 'Kami merancang lingkungan belajar yang merangsang motorik, sosial, dan rohani anak.', 'home', 'Subjudul keunggulan', 'textarea'],
            ['home.cta_judul', 'Siap Mengantarkan Ananda Menjadi Generasi Emas Islami?', 'home', 'Judul CTA bawah', 'textarea'],
            ['home.cta_teks', 'Pendaftaran Murid Baru telah dibuka. Daftarkan putra-putri Anda secara online dengan mudah, cepat, dan transparan.', 'home', 'Teks CTA bawah', 'textarea'],
            // Profil
            ['profile.header_subjudul', 'Mengenal lebih dekat visi, misi, sejarah, serta jajaran pengajar hebat di RA Al Kautsar.', 'profile', 'Subjudul header profil', 'textarea'],
            ['profile.sejarah', "RA Al Kautsar didirikan atas dasar kepedulian yang mendalam terhadap kualitas pendidikan agama Islam bagi anak-anak usia dini di wilayah perkotaan. Lahir pada tahun 2018, lembaga ini berkomitmen mengintegrasikan pengajaran umum dengan penanaman karakter Islami yang kuat sejak usia keemasan.\n\nDengan fasilitas yang terus berkembang, RA Al Kautsar kini menjadi salah satu Raudhatul Athfal terpercaya yang memadukan sarana modern dengan guru-guru berpengalaman untuk memantau tumbuh kembang fisik dan rohani anak secara maksimal.", 'profile', 'Teks sejarah (paragraf)', 'textarea'],
            ['profile.visi', 'Menjadi lembaga pendidikan anak usia dini percontohan yang membentuk generasi berakhlak mulia berbasis Al-Quran, cerdas, kreatif, mandiri, dan berjiwa kepemimpinan sejak usia dini.', 'profile', 'Isi Visi', 'textarea'],
            // Info
            ['info.header_subjudul', 'Persyaratan, rincian biaya pendidikan, serta alur pendaftaran murid baru online RA Al Kautsar.', 'info', 'Subjudul header info', 'textarea'],
            ['info.biaya_pendaftaran_desc', 'Dibayarkan saat mengirim formulir pendaftaran untuk memulai proses verifikasi dokumen.', 'info', 'Deskripsi biaya pendaftaran', 'textarea'],
            ['info.biaya_daftar_ulang_desc', 'Dibayarkan setelah anak dinyatakan lulus seleksi. Meliputi seragam, paket buku, dan dana sarana awal.', 'info', 'Deskripsi biaya daftar ulang', 'textarea'],
            ['info.biaya_spp_desc', 'Iuran rutin bulanan untuk menunjang kegiatan operasional belajar anak di kelas.', 'info', 'Deskripsi SPP', 'textarea'],
            // Kontak / footer
            ['kontak.nama_sekolah', 'RA Al Kautsar', 'kontak', 'Nama sekolah', 'text'],
            ['kontak.alamat', 'Jl. Pendidikan No. 1, Bandung', 'kontak', 'Alamat', 'textarea'],
            ['kontak.telepon', '022-1234567', 'kontak', 'Telepon', 'text'],
            ['kontak.email', 'info@telaga.sch.id', 'kontak', 'Email', 'text'],
            ['kontak.jam_operasional', 'Senin–Jumat, 07.00–14.00 WIB', 'kontak', 'Jam operasional', 'text'],
        ];
        foreach ($contents as [$key, $value, $grup, $label, $tipe]) {
            SiteContent::updateOrCreate(['key' => $key], compact('value', 'grup', 'label', 'tipe'));
        }

        // --- Item berulang (features) ---
        $features = [
            // Statistik beranda
            ['statistik', '150+', 'Murid Aktif', 1],
            ['statistik', '15+', 'Ustadz & Ustadzah', 2],
            ['statistik', '100%', 'Kurikulum Islam', 3],
            ['statistik', '5+', 'Program Ekstrakurikuler', 4],
            // Program/fasilitas beranda
            ['program', 'Pendidikan Akhlak', 'Pembiasaan sholat dhuha berjamaah, doa harian, adab sopan santun, serta pengenalan kisah teladan Rasulullah SAW.', 1],
            ['program', 'Kreativitas & Motorik', 'Melalui aktivitas melukis, melipat kertas (origami), bernyanyi lagu Islami, dan senam ceria untuk merangsang motorik.', 2],
            ['program', 'Lingkungan Bermain Aman', 'Ruang kelas full-AC yang bersih, area playground indoor/outdoor berstandar keselamatan anak, serta ruang audio-visual.', 3],
            // Curriculum Highlight slider beranda (T7.1) — foto diunggah admin via CMS
            ['kurikulum', 'Tahfidz Quran & Doa Harian', 'Menghafal surat pendek dan doa harian sejak dini.', 1],
            ['kurikulum', 'Kreativitas & Seni', 'Melukis, origami, dan bernyanyi lagu Islami.', 2],
            ['kurikulum', 'Bermain Sambil Belajar', 'Aktivitas motorik di playground yang aman.', 3],
            // Misi profil
            ['misi', 'Menyelenggarakan pembelajaran berbasis nilai keislaman dan Al-Quran.', null, 1],
            ['misi', 'Membiasakan akhlak mulia dan kemandirian anak dalam kehidupan sehari-hari.', null, 2],
            ['misi', 'Mengembangkan potensi motorik dan kreativitas anak melalui bermain aktif.', null, 3],
            ['misi', 'Membangun kolaborasi harmonis dengan orang tua untuk mengoptimalkan tumbuh kembang anak.', null, 4],
            // Persyaratan info
            ['persyaratan', 'Kartu Keluarga (KK)', 'Format JPG/PDF, maksimal 2 MB', 1],
            ['persyaratan', 'Akta Kelahiran', 'Format JPG/PDF, maksimal 2 MB', 2],
            ['persyaratan', 'Foto Anak', 'Format JPG, maksimal 2 MB', 3],
            ['persyaratan', 'NIK Anak', 'Wajib mencantumkan NIK anak yang valid', 4],
            // Alur info
            ['alur', 'Registrasi & Login Akun', 'Wali murid membuat akun portal menggunakan email aktif.', 1],
            ['alur', 'Isi Formulir & Unggah Dokumen', 'Mengisi profil lengkap anak, data wali, NIK wajib, dan mengunggah KK & Akta.', 2],
            ['alur', 'Transfer Biaya & Verifikasi', 'Mengunggah bukti pembayaran pendaftaran. Admin memproses verifikasi berkas.', 3],
            ['alur', 'Hasil Seleksi & Daftar Ulang', 'Admin menetapkan kelulusan. Calon murid yang lulus dapat melakukan daftar ulang.', 4],
        ];
        foreach ($features as [$grup, $judul, $deskripsi, $urutan]) {
            SiteFeature::updateOrCreate(
                ['grup' => $grup, 'judul' => $judul],
                ['deskripsi' => $deskripsi, 'urutan' => $urutan, 'aktif' => true],
            );
        }

        $this->command?->info('SiteContentSeeder selesai: '.count($contents).' konten + '.count($features).' item.');
    }
}
