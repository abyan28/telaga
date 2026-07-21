<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * DocumentUploadService — menangani penyimpanan berkas pendaftaran secara aman.
 *
 * Menerapkan aturan rules.md §3: file disimpan di
 * storage/app/public/pendaftaran/[tahun-ajaran]/[nama-anak]/ dengan penamaan
 * baku (KK_, Akta-Kelahiran_, Foto_) dan spasi pada nama anak diganti tanda hubung.
 * Validasi jenis/ukuran file dilakukan di layer request (FormRequest), service ini
 * fokus pada penempatan & penamaan file.
 */
class DocumentUploadService
{
    // Pemetaan jenis dokumen -> prefix nama file (rules.md §3)
    private const PREFIX = [
        'kk' => 'KK',
        'akta' => 'Akta-Kelahiran',
        'foto' => 'Foto',
    ];

    /**
     * Menyimpan satu berkas dokumen pendaftaran dan mengembalikan path relatifnya.
     *
     * @param  UploadedFile  $file       Berkas yang diunggah.
     * @param  string        $jenis      Jenis dokumen: 'kk' | 'akta' | 'foto'.
     * @param  string        $tahunAjaran Tahun ajaran, mis. "2026/2027".
     * @param  string        $namaAnak   Nama lengkap anak (untuk folder & nama file).
     * @return string  Path relatif pada disk 'public' (siap disimpan ke DB).
     */
    public function store(UploadedFile $file, string $jenis, string $tahunAjaran, string $namaAnak, int|string|null $id = null): string
    {
        // Ubah "2026/2027" -> "2026-2027" (garis miring tidak valid untuk folder)
        $folderTahun = str_replace('/', '-', $tahunAjaran);

        // Ganti spasi pada nama anak dengan tanda hubung (rules.md §3) + suffix PK (nama kembar)
        $slugAnak = str_replace(' ', '-', trim($namaAnak));
        if ($id !== null && $id !== '') {
            $slugAnak .= '-'.$id;
        }

        // Folder tujuan relatif terhadap disk 'public'
        $folder = "pendaftaran/{$folderTahun}/{$slugAnak}";

        // Nama file baku: <Prefix>_<nama-anak>.<ekstensi>
        $prefix = self::PREFIX[$jenis];
        $ext = strtolower($file->getClientOriginalExtension());
        $namaFile = "{$prefix}_{$slugAnak}.{$ext}";

        // Simpan ke disk 'public' dan kembalikan path relatif
        return $file->storeAs($folder, $namaFile, 'public');
    }

    // Pemetaan jenis pembayaran -> label prefix nama file bukti bayar
    private const LABEL_BAYAR = [
        'pendaftaran' => 'Pendaftaran',
        'daftar_ulang' => 'Daftar-Ulang',
        'spp' => 'SPP',
    ];

    /**
     * Menyimpan berkas bukti pembayaran, dirapikan per-siswa & bernama detail.
     *
     * Lokasi:  pembayaran/{jenis}/{tahun-ajaran}/{Nama-Siswa}/
     * Nama:    {Label}_{tahun}_[bulan]_{tgl}_{jam}_{Nama-Siswa}.{ext}
     *   contoh: Daftar-Ulang_2026-2027_18-07-2026_13-45-02_Budi-Haryanto.jpg
     *           SPP_2026-2027_2026-06_18-07-2026_13-45-02_Budi-Haryanto.jpg
     *
     * @param  UploadedFile  $file         Berkas bukti transfer.
     * @param  string        $jenis        'pendaftaran' | 'daftar_ulang' | 'spp'.
     * @param  string        $namaSiswa    Nama lengkap siswa (folder + nama file).
     * @param  string        $tahunAjaran  Tahun ajaran, mis. "2026/2027".
     * @param  string|null   $bulan        Bulan SPP "YYYY-MM" (hanya untuk jenis spp).
     * @return string  Path relatif pada disk 'public'.
     */
    public function storePaymentProof(UploadedFile $file, string $jenis, string $namaSiswa, string $tahunAjaran, ?string $bulan = null, int|string|null $id = null): string
    {
        $folderTahun = str_replace('/', '-', $tahunAjaran);
        $slugSiswa = str_replace(' ', '-', trim($namaSiswa)) ?: 'tanpa-nama';
        if ($id !== null && $id !== '') {
            $slugSiswa .= '-'.$id; // suffix PK: nama kembar tak numpuk folder
        }
        $label = self::LABEL_BAYAR[$jenis] ?? ucfirst($jenis);

        // Segmen waktu unik agar bukti tiap transaksi tak saling menimpa
        $stamp = now()->format('d-m-Y_H-i-s');
        $bulanSeg = $jenis === 'spp' && $bulan ? "{$bulan}_" : '';
        $ext = strtolower($file->getClientOriginalExtension());

        $folder = "pembayaran/{$jenis}/{$folderTahun}/{$slugSiswa}";
        $namaFile = "{$label}_{$folderTahun}_{$bulanSeg}{$stamp}_{$slugSiswa}.{$ext}";

        return $file->storeAs($folder, $namaFile, 'public');
    }

    /**
     * Menyimpan pas foto profil (siswa lama input CRUD / guru) — seragam & bernama.
     *
     * Lokasi:  profil/{peran}/{Nama[-id]}/Foto_{Nama[-id]}.{ext}
     *   contoh: profil/siswa/Budi-Haryanto-12/Foto_Budi-Haryanto-12.jpg
     *           profil/guru/Ahmad-Fauzi-7/Foto_Ahmad-Fauzi-7.jpg
     * Beda dg store() (butuh tahun-ajaran) — profil tak terikat tahun ajaran.
     *
     * $id (PK record) di-suffix ke slug agar dua orang bernama SAMA tak saling
     * menimpa foto/folder. Wajib diisi untuk record yang sudah punya PK (update /
     * setelah create). Tanpa $id (mis. store baru sebelum PK ada) folder pakai
     * nama saja — pemanggil sebaiknya re-simpan dengan $id setelah create.
     *
     * @param  UploadedFile  $file   Berkas pas foto.
     * @param  string        $peran  'siswa' | 'guru' (segmen folder).
     * @param  string        $nama   Nama lengkap (folder + nama file).
     * @param  int|string|null $id    PK record (pembeda nama kembar). Opsional.
     * @return string  Path relatif pada disk 'public'.
     */
    public function storeProfilePhoto(UploadedFile $file, string $peran, string $nama, int|string|null $id = null): string
    {
        $slug = str_replace(' ', '-', trim($nama)) ?: 'tanpa-nama';
        // Suffix PK agar nama kembar (mis. dua guru "Ahmad Fauzi") tak saling timpa.
        if ($id !== null && $id !== '') {
            $slug .= '-'.$id;
        }
        $ext = strtolower($file->getClientOriginalExtension());

        return $file->storeAs("profil/{$peran}/{$slug}", "Foto_{$slug}.{$ext}", 'public');
    }
}
