<?php

namespace Database\Seeders;

use App\Models\{AcademicYear, OrangTua, MonthlySppBill, PaymentTransaction,
    RegistrationDocument, RegistrationForm, ReRegistrationPayment,
    SchoolClass, Setting, Student, Teacher, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DummyDataSeeder — ~80 murid+ortu berbagai status + 20 guru + transaksi keuangan.
 *
 * Idempoten (firstOrCreate). Jalankan: php artisan db:seed --class=DummyDataSeeder
 *
 * Skenario status: alumni TA lalu (8), aktif DU lunas (15), DU cicil (8),
 * submitted baru (8), menunggu verifikasi (8), bayar diverifikasi (5),
 * diproses seleksi (5), gagal (6), dropout (5), ortu 2 anak (10 dari 5 ortu),
 * aktif cicil+SPP (8). Total: 80 murid, 75 ortu.
 */
class DummyDataSeeder extends Seeder
{
    private AcademicYear $taLalu;
    private AcademicYear $taAktif;
    /** @var SchoolClass[] */
    private array $k;
    private int $nikN = 0;
    private int $hpN  = 0;
    private int $nN   = 0; // NIS sequence
    private int $ntN  = 0; // NUPTK sequence

    private static array $boys  = ['Ahmad','Muhammad','Rizky','Farhan','Dzaky','Rafka','Alfarizi','Zaidan','Khalil','Fathan','Naufal','Fauzan','Iqbal','Arkan','Rafli','Haikal','Rasya','Raihan','Danish','Farel','Azka','Kenzo','Rayyan','Abdurrahman','Faiz'];
    private static array $girls = ['Aisyah','Zahra','Fatimah','Salma','Nayla','Naura','Qonita','Haura','Aqila','Syifa','Khadija','Amira','Sakina','Nabila','Zahira','Alesha','Kayla','Nayyara','Shafira','Almira','Azzahra','Nindi','Salwa','Syahla','Rania'];
    private static array $ayah  = ['Ahmad Santoso','Budi Pratama','Dedi Kurniawan','Eko Prasetyo','Fajar Nugroho','Gilang Saputra','Hendra Wijaya','Indra Lesmana','Joko Susanto','Kurniawan Hakim','Lukman Hakim','Maman Suryaman','Nugroho Adi','Omar Faruk','Purnomo Sidi','Rudi Hartono','Surya Darma','Taufik Hidayat','Ujang Suryana','Wahyu Firmansyah','Adi Cahyono','Bayu Setiawan','Cecep Supriadi','Dani Firmansyah','Eka Rahmanto'];
    private static array $ibu   = ['Siti Aminah','Dewi Sartika','Fitri Handayani','Indah Permata','Kartika Dewi','Lestari Ningrum','Maya Angelina','Nur Aisyah','Putri Maharani','Ratna Sari','Sari Ningsih','Tini Sumarni','Umi Kalsum','Vina Panduwinata','Wati Rahayu','Yanti Mulyani','Ani Susanti','Bunga Citra','Citra Kirana','Dian Sastro','Evi Tamala','Feni Rose','Gita Gutawa','Hana Saraswati','Intan Permata'];
    private static array $bank  = ['BANK BRI','BANK MANDIRI','BANK BNI','BANK BCA'];

    public function run(): void
    {
        $this->taLalu = AcademicYear::firstOrCreate(['tahun' => '2025/2026'], ['is_aktif' => false]);
        $this->taAktif = AcademicYear::firstOrCreate(['tahun' => '2026/2027'], ['is_aktif' => true]);

        Setting::set('pendaftaran_dibuka', '1');
        Setting::set('nominal_pendaftaran', '150000');
        Setting::set('nominal_daftar_ulang', '1500000');
        Setting::set('nominal_spp', '250000');
        Setting::set('nsm_sekolah', '123456789012');
        Setting::set('kuota_pendaftaran', '40');

        $this->k = [
            'lA' => SchoolClass::firstOrCreate(['id_academic_year' => $this->taLalu->id_academic_years, 'nama_kelas' => 'Kelas A - Ikhlas']),
            'aA' => SchoolClass::firstOrCreate(['id_academic_year' => $this->taAktif->id_academic_years, 'nama_kelas' => 'Kelas A - Rahmat']),
            'aB' => SchoolClass::firstOrCreate(['id_academic_year' => $this->taAktif->id_academic_years, 'nama_kelas' => 'Kelas B - Syukur']),
            'aC' => SchoolClass::firstOrCreate(['id_academic_year' => $this->taAktif->id_academic_years, 'nama_kelas' => 'Kelas C - Sabar']),
        ];

        $teachers = $this->seedTeachers();

        $idx = 0;

        // ─── A. Alumni TA lalu (8): lulus, DU lunas, SPP 3 bln lunas ───
        for ($i = 0; $i < 8; $i++) {
            $u = $this->ortu($idx);
            $nis = '12345678901225'.str_pad(++$this->nN, 3, '0', STR_PAD_LEFT);
            $s = $this->murid($u, $this->k['lA'], $this->taLalu, $idx, 'lulus', $nis, 2025);
            $f = $this->form($u, $s, $this->taLalu, 'lulus', 'diterima');
            $this->reReg($s, $this->taLalu, 1400000, 1400000, 'lunas');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2025-05-10', $f->id_registration_forms);
            $this->trx($s, $u, 'daftar_ulang', 1400000, 'diverifikasi', '2025-06-20');
            foreach (['2025-07','2025-08','2025-09'] as $b) {
                $this->spp($s, $this->taLalu, $b, 250000, 250000, 'lunas');
                $this->trx($s, $u, 'spp', 250000, 'diverifikasi', "$b-05");
            }
            $idx++;
        }

        // ─── B. Aktif DU lunas (15): lulus, DU lunas, SPP 2 bln lunas, kelas assigned ───
        for ($i = 0; $i < 15; $i++) {
            $u = $this->ortu($idx);
            $nis = '12345678901226'.str_pad(++$this->nN, 3, '0', STR_PAD_LEFT);
            $kelas = $this->k[$i < 8 ? 'aA' : ($i < 13 ? 'aB' : 'aC')];
            $s = $this->murid($u, $kelas, $this->taAktif, $idx, 'aktif', $nis, 2026);
            $f = $this->form($u, $s, $this->taAktif, 'lulus', 'diterima');
            $this->reReg($s, $this->taAktif, 1500000, 1500000, 'lunas');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2026-05-15', $f->id_registration_forms);
            $this->trx($s, $u, 'daftar_ulang', 1500000, 'diverifikasi', '2026-06-20');
            foreach (['2026-07','2026-08'] as $b) {
                $this->spp($s, $this->taAktif, $b, 250000, 250000, 'lunas');
                $this->trx($s, $u, 'spp', 250000, 'diverifikasi', "$b-03");
            }
            $idx++;
        }

        // ─── C. DU cicil (8): lulus, DU sebagian, kelas assigned, NIS=null ───
        for ($i = 0; $i < 8; $i++) {
            $u = $this->ortu($idx);
            $kelas = $this->k[$i < 4 ? 'aA' : 'aB'];
            $s = $this->murid($u, $kelas, $this->taAktif, $idx, 'aktif');
            $f = $this->form($u, $s, $this->taAktif, 'lulus', 'diterima');
            $bayar = 500000 + ($i * 100000);
            $this->reReg($s, $this->taAktif, 1500000, $bayar, 'kurang');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2026-06-01', $f->id_registration_forms);
            $this->trx($s, $u, 'daftar_ulang', $bayar, 'diverifikasi', '2026-07-10');
            $idx++;
        }

        // ─── D. Submitted baru (8): baru submit, belum bayar ───
        for ($i = 0; $i < 8; $i++) {
            $u = $this->ortu($idx);
            $s = $this->murid($u, null, $this->taAktif, $idx, 'aktif');
            $this->form($u, $s, $this->taAktif, 'submitted', 'pending');
            $idx++;
        }

        // ─── E. Menunggu verifikasi (8): sudah upload bukti bayar ───
        for ($i = 0; $i < 8; $i++) {
            $u = $this->ortu($idx);
            $s = $this->murid($u, null, $this->taAktif, $idx, 'aktif');
            $f = $this->form($u, $s, $this->taAktif, 'menunggu_verifikasi', 'pending');
            $this->trx($s, $u, 'pendaftaran', 150000, 'pending', '2026-07-20', $f->id_registration_forms);
            $idx++;
        }

        // ─── F. Bayar diverifikasi (5): bayar ok, berkas pending ───
        for ($i = 0; $i < 5; $i++) {
            $u = $this->ortu($idx);
            $s = $this->murid($u, null, $this->taAktif, $idx, 'aktif');
            $f = $this->form($u, $s, $this->taAktif, 'pembayaran_diverifikasi', 'pending');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2026-07-18', $f->id_registration_forms);
            $idx++;
        }

        // ─── G. Diproses seleksi (5): bayar+berkas ok ───
        for ($i = 0; $i < 5; $i++) {
            $u = $this->ortu($idx);
            $s = $this->murid($u, null, $this->taAktif, $idx, 'aktif');
            $f = $this->form($u, $s, $this->taAktif, 'diproses_seleksi', 'diterima');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2026-07-15', $f->id_registration_forms);
            $idx++;
        }

        // ─── H. Gagal seleksi (6) ───
        for ($i = 0; $i < 6; $i++) {
            $u = $this->ortu($idx);
            $s = $this->murid($u, null, $this->taAktif, $idx, 'nonaktif');
            $f = $this->form($u, $s, $this->taAktif, 'gagal', 'diterima');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2026-07-01', $f->id_registration_forms);
            $idx++;
        }

        // ─── I. Nonaktif/keluar (5): dulu aktif thn lalu, sekarang keluar ───
        for ($i = 0; $i < 5; $i++) {
            $u = $this->ortu($idx);
            $s = $this->murid($u, $this->k['lA'], $this->taLalu, $idx, 'nonaktif');
            $f = $this->form($u, $s, $this->taLalu, 'lulus', 'diterima');
            $this->reReg($s, $this->taLalu, 1400000, 1400000, 'lunas');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2025-05-20', $f->id_registration_forms);
            $this->trx($s, $u, 'daftar_ulang', 1400000, 'diverifikasi', '2025-06-25');
            $idx++;
        }

        // ─── J. 1 ortu punya 2 anak (5 ortu × 2 = 10 murid) ───
        for ($i = 0; $i < 5; $i++) {
            $u = $this->ortu($idx);
            // Anak 1: murid aktif DU lunas
            $nis1 = '12345678901226'.str_pad(++$this->nN, 3, '0', STR_PAD_LEFT);
            $s1 = $this->murid($u, $this->k['aA'], $this->taAktif, $idx, 'aktif', $nis1, 2026);
            $f1 = $this->form($u, $s1, $this->taAktif, 'lulus', 'diterima');
            $this->reReg($s1, $this->taAktif, 1500000, 1500000, 'lunas');
            $this->trx($s1, $u, 'pendaftaran', 150000, 'diverifikasi', '2026-05-10', $f1->id_registration_forms);
            $this->trx($s1, $u, 'daftar_ulang', 1500000, 'diverifikasi', '2026-06-15');
            $idx++;
            // Anak 2: submitted baru (adik)
            $s2 = $this->murid($u, null, $this->taAktif, $idx, 'aktif');
            $this->form($u, $s2, $this->taAktif, 'submitted', 'pending');
            $idx++;
        }

        // ─── K. Bonus aktif cicil+SPP (5): DU cicil + SPP belum lunas ───
        for ($i = 0; $i < 5; $i++) {
            $u = $this->ortu($idx);
            $s = $this->murid($u, $this->k['aC'], $this->taAktif, $idx, 'aktif');
            $f = $this->form($u, $s, $this->taAktif, 'lulus', 'diterima');
            $this->reReg($s, $this->taAktif, 1500000, 800000, 'kurang');
            $this->spp($s, $this->taAktif, '2026-08', 250000, 0, 'belum_lunas');
            $this->trx($s, $u, 'pendaftaran', 150000, 'diverifikasi', '2026-06-01', $f->id_registration_forms);
            $this->trx($s, $u, 'daftar_ulang', 800000, 'diverifikasi', '2026-07-15');
            $idx++;
        }

        $this->command?->info("DummyDataSeeder selesai: {$idx} murid + 20 guru + transaksi.");
    }

    // ─── helpers ───

    private function nik(): string
    {
        return '3273015001'.str_pad(++$this->nikN, 6, '0', STR_PAD_LEFT);
    }

    private function hp(): string
    {
        return '082100'.str_pad(++$this->hpN, 6, '0', STR_PAD_LEFT);
    }

    private function ortu(int $i): User
    {
        $hp = $this->hp();
        $email = "ortu{$i}@dummy.test";
        $u = User::firstOrCreate(['email' => $email], [
            'username' => $email, 'no_hp' => $hp, 'role' => 'ortu',
            'password' => Hash::make('password'),
        ]);
        $aN = self::$ayah[$i % 25];
        $iN = self::$ibu[$i % 25];
        $bln = str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT);
        $tgl = str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT);

        OrangTua::firstOrCreate(['id_user' => $u->id_users], [
            'ada_ayah' => true, 'ada_ibu' => true,
            'ayah_nama' => strtoupper($aN),
            'ayah_tempat_lahir' => 'BANDUNG', 'ayah_tanggal_lahir' => '198'.($i % 10).'-'.$bln.'-'.$tgl,
            'ayah_agama' => 'ISLAM',
            'ayah_pendidikan' => ['S1','S2','SMA','D3','SMP'][$i % 5],
            'ayah_pekerjaan' => ['PNS','SWASTA','PEDAGANG','WIRASWASTA','TNI','POLRI','GURU'][$i % 7],
            'ayah_penghasilan' => ['< 1 JUTA','1 - 2 JUTA','3 - 5 JUTA','> 5 JUTA'][$i % 4],
            'ayah_no_hp' => '082200'.str_pad($i + 1, 6, '0', STR_PAD_LEFT),
            'ibu_nama' => strtoupper($iN),
            'ibu_tempat_lahir' => 'BANDUNG', 'ibu_tanggal_lahir' => '199'.($i % 10).'-'.$bln.'-'.$tgl,
            'ibu_agama' => 'ISLAM',
            'ibu_pendidikan' => ['SMA','S1','D3','S2','SMP'][$i % 5],
            'ibu_pekerjaan' => ['WIRASWASTA','GURU','PNS','SWASTA','PEDAGANG'][$i % 5],
            'ibu_penghasilan' => ['< 1 JUTA','1 - 2 JUTA','3 - 5 JUTA','> 5 JUTA'][$i % 4],
            'ibu_no_hp' => $hp,
            'alamat' => 'Jl. '.self::$boys[$i % 25].' No. '.($i + 1).', Bandung',
        ]);

        return $u;
    }

    private function murid(User $ortu, ?SchoolClass $k, AcademicYear $ta, int $i, string $status, ?string $nis = null, ?int $angkatan = null): Student
    {
        $jk = $i % 3 === 0 ? 'P' : 'L';
        $first = ($jk === 'L' ? self::$boys[$i % 25] : self::$girls[$i % 25]);
        $nama = strtoupper($first.' '.['Putra','Putri'][$jk === 'L' ? 0 : 1]);
        $thn = 2019 + ($i % 5);
        $bln = str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT);
        $tgl = str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT);

        return Student::firstOrCreate(['nik' => $this->nik()], [
            'id_parent' => $ortu->ortu->id_parents,
            'id_class' => $k?->id_classes,
            'id_academic_year' => $ta->id_academic_years,
            'nama_lengkap' => $nama,
            'nama_panggilan' => strtoupper($first),
            'jenis_kelamin' => $jk,
            'tempat_lahir' => 'BANDUNG',
            'tanggal_lahir' => "{$thn}-{$bln}-{$tgl}",
            'agama' => 'ISLAM',
            'anak_ke' => ($i % 3) + 1,
            'jumlah_saudara' => $i % 4,
            'warga_negara' => 'WNI',
            'bahasa_keseharian' => 'INDONESIA',
            'kondisi_kesehatan' => 'SEHAT',
            'sudah_mengaji' => 'Sudah',
            'ukuran_baju' => ['S','M','L'][$i % 3],
            'status' => $status,
            'nis' => $nis,
            'angkatan' => $angkatan,
        ]);
    }

    private function form(User $ortu, Student $s, AcademicYear $ta, string $status, string $doc): RegistrationForm
    {
        $f = RegistrationForm::firstOrCreate(['id_student' => $s->id_students], [
            'id_user' => $ortu->id_users, 'id_academic_year' => $ta->id_academic_years, 'status' => $status,
        ]);
        foreach (['kk', 'akta', 'foto'] as $j) {
            RegistrationDocument::firstOrCreate(
                ['id_registration_form' => $f->id_registration_forms, 'jenis' => $j],
                ['path' => "pendaftaran/{$ta->tahun}/{$s->nama_lengkap}/{$j}.jpg", 'status' => $doc],
            );
        }

        return $f;
    }

    private function reReg(Student $s, AcademicYear $ta, int $total, int $bayar, string $status): void
    {
        ReRegistrationPayment::firstOrCreate(
            ['id_student' => $s->id_students, 'id_academic_year' => $ta->id_academic_years],
            ['total_biaya' => $total, 'jumlah_terbayar' => $bayar, 'status' => $status],
        );
    }

    private function spp(Student $s, AcademicYear $ta, string $bulan, int $nominal, int $bayar, string $status): void
    {
        MonthlySppBill::firstOrCreate(
            ['id_student' => $s->id_students, 'bulan' => $bulan],
            ['id_academic_year' => $ta->id_academic_years, 'nominal' => $nominal, 'jumlah_terbayar' => $bayar, 'status' => $status],
        );
    }

    private function trx(Student $s, User $ortu, string $jenis, int $jumlah, string $status, string $tgl, ?int $refId = null): void
    {
        PaymentTransaction::firstOrCreate(
            ['id_student' => $s->id_students, 'jenis' => $jenis, 'jumlah' => $jumlah, 'tanggal_bayar' => $tgl],
            [
                'id_user' => $ortu->id_users,
                'id_registration_form' => $refId,
                'referensi_id' => $refId,
                'bukti_path' => "pembayaran/{$jenis}/dummy-{$s->id_students}-{$jumlah}.jpg",
                'bank_asal' => self::$bank[crc32($tgl) % 4],
                'status' => $status,
            ],
        );
    }

    /** 20 guru dengan berbagai jabatan & atribut. */
    private function seedTeachers(): array
    {
        $jabatan = ['Kepala Sekolah','Guru Kelas','Guru Tahfidz','Guru Bahasa Arab','Guru Olahraga','Guru Seni','Guru PAI','Guru TK/RA','Wakil Kepala Sekolah','Guru Bimbingan'];
        $pendidikan = ['S1 PGMI','S1 Pendidikan Agama Islam','S1 PGSD','S2 Pendidikan','D3 PAUD','S1 BAHASA ARAB','S1 PJOK','S1 Pendidikan Seni'];
        $nama = [
            'Ustadz Ahmad Fauzi, S.Pd.I','Ustadzah Siti Nurhaliza, S.Pd','Ustadz Muhammad Ridwan, S.Ag',
            'Ustadzah Fatimah Azzahra, S.Pd.I','Ustadz Abdul Rahman, S.Pd','Ustadzah Khadijah Ummu, S.Ag',
            'Ustadz Hasan Basri, S.Pd.I','Ustadzah Maryam Siddiq, M.Pd','Ustadz Ibrahim Khalil, S.Pd',
            'Ustadzah Aminah Hasanah, S.Pd.I','Ustadz Yusuf Mansur, S.Ag','Ustadzah Zahra Rahma, S.Pd',
            'Ustadz Ali Akbar, S.Pd.I','Ustadzah Hana Syafiqah, S.Pd','Ustadz Hamzah Fansuri, S.Ag',
            'Ustadzah Salma Nurjannah, M.Pd','Ustadz Isa Al-Mahdi, S.Pd','Ustadzah Qonita Luthfiyya, S.Pd.I',
            'Ustadz Salman Al-Farisi, S.Ag','Ustadzah Nabila Husna, S.Pd',
        ];

        $teachers = [];
        for ($i = 0; $i < 20; $i++) {
            $nuptk = '1234567890123'.str_pad(++$this->ntN, 3, '0', STR_PAD_LEFT);
            $hp = '081355'.str_pad($i + 1, 6, '0', STR_PAD_LEFT);
            $email = "guru{$i}@dummy.test";
            $u = User::firstOrCreate(['email' => $email], [
                'username' => $email, 'no_hp' => $hp, 'role' => 'guru',
                'password' => Hash::make($nuptk),
            ]);
            $jk = $i < 10 ? ($i % 2 === 0 ? 'L' : 'P') : ($i % 2 === 0 ? 'P' : 'L');
            $tgl = '199'.($i % 10).'-0'.(($i % 9) + 1).'-'.str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT);
            $t = Teacher::firstOrCreate(['id_user' => $u->id_users], [
                'nama' => $nama[$i],
                'nuptk' => $nuptk,
                'no_hp' => $hp,
                'jabatan' => $jabatan[$i % count($jabatan)],
                'jenis_kelamin' => $jk,
                'tempat_lahir' => ['BANDUNG','JAKARTA','SURABAYA','YOGYAKARTA','SOLO'][$i % 5],
                'tanggal_lahir' => $tgl,
                'tanggal_mulai_mengajar' => '201'.($i % 10).'-0'.(($i % 9) + 1).'-01',
                'riwayat_pendidikan' => $pendidikan[$i % count($pendidikan)],
                'tampil_di_web' => $i < 6,
                'is_aktif' => true,
                'alamat' => 'Jl. Guru No. '.($i + 1).', Bandung',
            ]);
            $teachers[] = $t;
        }

        return $teachers;
    }
}
