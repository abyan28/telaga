<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\AcademicYear;
use App\Models\RegistrationDocument;
use App\Models\RegistrationForm;
use App\Models\Setting;
use App\Models\Student;
use App\Services\DocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationNotification;
use Illuminate\View\View;

/**
 * RegistrationController (Wali) — alur pendaftaran murid baru (PRD §7.3–§7.5).
 *
 * Menyimpan formulir + data siswa + dokumen unggahan dalam satu transaksi,
 * mengikuti konvensi PK/FK rules.md §2 dan aturan upload rules.md §3.
 */
class RegistrationController extends Controller
{
    /**
     * Menampilkan halaman form pendaftaran murid baru.
     * K2.7: bila PPDB ditutup, tampilkan info di dalam layout CMS (bukan 403 telanjang).
     */
    public function create(): View
    {
        if (Setting::get('pendaftaran_dibuka', '1') !== '1') {
            return view('ortu.registration-closed');
        }

        return view('ortu.register');
    }

    /**
     * Menyimpan pendaftaran murid baru beserta dokumen.
     *
     * Membuat student, registration_form (status 'submitted'), dan 3 dokumen
     * (KK, Akta, Foto) secara atomik. Berkas disimpan via DocumentUploadService.
     */
    public function store(StoreRegistrationRequest $request, DocumentUploadService $uploader): RedirectResponse
    {
        $data = $request->validated();
        $user = Auth::user();

        // L2.4: pendaftaran mengacu ke TA TARGET PPDB (bukan TA sistem aktif) &
        // jadi path upload (rules.md §1.7). Sekolah bisa buka PPDB TA depan saat
        // TA sekarang masih berjalan.
        $tahunAjaran = AcademicYear::taPpdb();

        DB::transaction(function () use ($data, $user, $tahunAjaran, $uploader, $request) {
            // Profil wali (ortu) sudah lengkap via gate K2.1 — tak perlu diisi ulang.
            $ortu = $user->ortu;

            // Buat data siswa. Field profil L1.2 dari validated (kecuali nama_anak → nama_lengkap & berkas).
            $profil = collect($data)->except(['nama_anak', 'kk', 'akta', 'foto'])->all();
            $student = Student::create([
                'id_parent' => $ortu->id_parents,
                'id_academic_year' => $tahunAjaran->id_academic_years,
                'nama_lengkap' => $data['nama_anak'],
            ] + $profil);

            // Buat formulir pendaftaran berstatus 'submitted'
            $form = RegistrationForm::create([
                'id_user' => $user->id_users,
                'id_student' => $student->id_students,
                'id_academic_year' => $tahunAjaran->id_academic_years,
                'status' => 'submitted',
            ]);

            // Simpan 3 berkas dokumen wajib
            foreach (['kk', 'akta', 'foto'] as $jenis) {
                $path = $uploader->store(
                    $request->file($jenis),
                    $jenis,
                    $tahunAjaran->tahun,
                    $data['nama_anak'],
                    $student->id_students,
                );
                RegistrationDocument::create([
                    'id_registration_form' => $form->id_registration_forms,
                    'jenis' => $jenis,
                    'path' => $path,
                    'status' => 'pending',
                ]);
                // Pas foto juga jadi foto profil siswa (ditampilkan di profil).
                if ($jenis === 'foto') {
                    $student->update(['foto_path' => $path]);
                }
            }
        });

        // Notifikasi email: pendaftaran berhasil dikirim (PRD §7.13)
        Mail::to($user->email)->send(new RegistrationNotification(
            'Pendaftaran Berhasil Dikirim',
            'Pendaftaran murid baru atas nama '.$data['nama_anak'].' telah kami terima. Silakan lanjutkan pembayaran biaya pendaftaran.',
        ));

        return redirect()->route('ortu.status')
            ->with('success', 'Pendaftaran berhasil dikirim. Silakan lanjutkan pembayaran pendaftaran.');
    }

    /**
     * K2.5: form edit pendaftaran (reuse view register dalam mode edit).
     * Hanya boleh bila RegistrationForm::canBeEditedByWali() (bayar belum
     * diverifikasi, atau admin membuka flag boleh_edit).
     */
    public function edit(RegistrationForm $form): View|RedirectResponse
    {
        abort_unless($form->id_user === Auth::id(), 403);

        if (! $form->canBeEditedByWali()) {
            return redirect()->route('ortu.status')
                ->withErrors(['edit' => 'Pendaftaran sudah diverifikasi dan tidak dapat diubah.']);
        }

        $form->load('student', 'documents');

        return view('ortu.register', ['form' => $form]);
    }

    /**
     * K2.5: simpan perubahan pendaftaran. Dokumen opsional — hanya file yang
     * diunggah ulang yang diganti. Efek (a): dokumen yang diganti direset ke
     * 'pending'; bila form sudah melewati verifikasi bayar, mundurkan ke
     * 'pembayaran_diverifikasi' agar Admin verifikasi berkas ulang. Flag
     * boleh_edit selalu dimatikan setelah tersimpan.
     */
    public function update(Request $request, RegistrationForm $form, DocumentUploadService $uploader): RedirectResponse
    {
        abort_unless($form->id_user === Auth::id(), 403);
        abort_unless($form->canBeEditedByWali(), 403);

        $profil = Student::profilRules();
        $profil['nama_anak'] = $profil['nama_lengkap'];
        unset($profil['nama_lengkap']);
        $profil['nik'] = ['required', 'digits:16', 'unique:students,nik,'.$form->id_student.',id_students'];
        $data = $request->validate([
            ...$profil,
            // Dokumen opsional saat edit (kosong = pertahankan file lama).
            'kk' => ['nullable', 'file', 'mimes:jpg,jpeg,pdf', 'max:2048'],
            'akta' => ['nullable', 'file', 'mimes:jpg,jpeg,pdf', 'max:2048'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg', 'max:2048'],
        ], \App\Support\ValidationRules::messages());

        // Path upload edit mengikuti TA form itu sendiri (bukan TA aktif — form
        // bisa dibuat untuk TA target PPDB berbeda dari TA sistem).
        $tahunAjaran = $form->academicYear ?? AcademicYear::taPpdb();

        DB::transaction(function () use ($data, $form, $uploader, $request, $tahunAjaran) {
            $profilData = collect($data)->except(['nama_anak', 'kk', 'akta', 'foto'])->all();
            $form->student->update(['nama_lengkap' => $data['nama_anak']] + $profilData);

            // Ganti hanya dokumen yang di-upload ulang; reset statusnya ke 'pending' (efek a).
            $adaDokumenGanti = false;
            foreach (['kk', 'akta', 'foto'] as $jenis) {
                if (! $request->hasFile($jenis)) {
                    continue;
                }
                $adaDokumenGanti = true;
                $path = $uploader->store($request->file($jenis), $jenis, $tahunAjaran->tahun, $data['nama_anak'], $form->id_student);
                $doc = $form->documents()->where('jenis', $jenis)->first();
                // Hapus berkas lama (beda ekstensi sekalipun) agar tak menyampah storage.
                if ($doc?->path && $doc->path !== $path) {
                    \Storage::disk('public')->delete($doc->path);
                }
                $doc?->update(['status' => 'pending', 'path' => $path, 'catatan' => null]);
                // Pas foto diganti → perbarui foto profil siswa juga.
                if ($jenis === 'foto') {
                    $form->student->update(['foto_path' => $path]);
                }
            }

            // Bila form sudah melewati verifikasi bayar & ada berkas diganti,
            // mundurkan ke 'pembayaran_diverifikasi' agar Admin verifikasi ulang.
            $update = ['boleh_edit' => false];
            if ($adaDokumenGanti && $form->status === 'diproses_seleksi') {
                $update['status'] = 'pembayaran_diverifikasi';
            }
            $form->update($update);
        });

        return redirect()->route('ortu.status')
            ->with('success', 'Pendaftaran berhasil diperbarui.');
    }
}