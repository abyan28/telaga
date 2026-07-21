@extends('layouts.dashboard')

@section('title', 'Detail Pendaftaran — RA Al Kautsar')
@section('header_title', 'Detail & Verifikasi Pendaftaran')

@section('content')
@php
    // Metadata status pendaftaran (label + warna) — kelas statis penuh (Tailwind JIT).
    $statusMap = [
        'submitted' => ['Sudah Submit', 'bg-sky-100 text-sky-700'],
        'menunggu_bukti' => ['Bukti Ditolak – Upload Ulang', 'bg-rose-100 text-rose-700'],
        'menunggu_verifikasi' => ['Menunggu Verifikasi Pembayaran', 'bg-amber-100 text-amber-700'],
        'pembayaran_diverifikasi' => ['Proses Verifikasi Berkas', 'bg-sky-100 text-sky-700'],
        'diproses_seleksi' => ['Proses Seleksi', 'bg-indigo-100 text-indigo-700'],
        'lulus' => ['Lulus Seleksi', 'bg-emerald-100 text-emerald-700'],
        'gagal' => ['Belum Lulus', 'bg-rose-100 text-rose-700'],
    ];
    [$statusLabel, $statusClass] = $statusMap[$form->status] ?? ['-', 'bg-slate-100 text-slate-600'];

    // Label & warna jenis dokumen. (Status dokumen kini reaktif via Alpine — K3.2)
    $jenisDokLabel = ['kk' => 'Kartu Keluarga', 'akta' => 'Akta Kelahiran', 'foto' => 'Pas Foto'];

    // Warna status pembayaran.
    $payStatusClass = [
        'diverifikasi' => 'bg-emerald-100 text-emerald-700',
        'ditolak' => 'bg-rose-100 text-rose-700',
        'pending' => 'bg-amber-100 text-amber-700',
    ];

    // Alur dipisah (P2.3): berkas hanya boleh diverifikasi setelah pembayaran sah.
    $bolehVerifikasiBerkas = in_array($form->status, ['pembayaran_diverifikasi', 'diproses_seleksi', 'lulus', 'gagal'], true);

    $jenisKelaminLabel = ['L' => 'Laki-laki', 'P' => 'Perempuan', 'laki-laki' => 'Laki-laki', 'perempuan' => 'Perempuan'];

    $student = $form->student;
    $ortu = $form->student?->ortu;
    $akun = $form->user;

    // Alamat keluarga (di ortu, L1 revisi C10) — disisipkan ke card biodata murid.
    $alamatLengkap = collect([
        $ortu?->alamat,
        $ortu?->kelurahan_nama,
        $ortu?->kecamatan_nama,
        $ortu?->kota_nama,
        $ortu?->provinsi_nama,
    ])->filter()->implode(', ');

    // Daftar field biodata murid (label → nilai). Semua yang diisi di formulir pendaftaran.
    $rowsMurid = [
        'Nama Lengkap' => $student?->nama_lengkap,
        'Nama Panggilan' => $student?->nama_panggilan,
        'NIK' => $student?->nik,
        'NISN' => $student?->nisn,
        'NIS' => $student?->nis,
        'Jenis Kelamin' => $jenisKelaminLabel[$student?->jenis_kelamin] ?? $student?->jenis_kelamin,
        'Tempat, Tanggal Lahir' => collect([$student?->tempat_lahir, $student?->tanggal_lahir?->translatedFormat('d F Y')])->filter()->implode(', ') ?: null,
        'Agama' => $student?->agama,
        'Anak ke-' => $student?->anak_ke,
        'Jumlah Saudara' => $student?->jumlah_saudara,
        'Warga Negara' => $student?->warga_negara,
        'Bahasa Keseharian' => $student?->bahasa_keseharian,
        'Kondisi Kesehatan' => $student?->kondisi_kesehatan,
        'Ukuran Baju' => $student?->ukuran_baju,
        'Sudah Mengaji' => $student?->sudah_mengaji,
        'Ngaji Di Mana' => $student?->ngaji_dimana,
        'Metode Mengaji' => $student?->ngaji_metode,
        'Jilid' => $student?->ngaji_jilid,
        'Pernah Belajar' => $student?->pernah_belajar,
        'Keterangan Belajar' => $student?->belajar_keterangan,
        'Alamat Keluarga' => $alamatLengkap ?: null,
    ];

    // Field per orang tua (ayah/ibu) — dibangun via loop di bawah.
    $ortuField = fn ($p) => [
        'Nama Lengkap' => $ortu?->{$p.'_nama'},
        'Tempat, Tanggal Lahir' => collect([$ortu?->{$p.'_tempat_lahir'}, optional($ortu?->{$p.'_tanggal_lahir'})->translatedFormat('d F Y')])->filter()->implode(', ') ?: null,
        'Agama' => $ortu?->{$p.'_agama'},
        'Pendidikan Terakhir' => $ortu?->{$p.'_pendidikan'},
        'Pekerjaan' => $ortu?->{$p.'_pekerjaan'} === 'LAINNYA' ? ($ortu?->{$p.'_pekerjaan_lain'} ?: 'LAINNYA') : $ortu?->{$p.'_pekerjaan'},
        'Penghasilan Bulanan' => $ortu?->{$p.'_penghasilan'},
        'No. HP' => $ortu?->{$p.'_no_hp'},
    ];
@endphp

<div class="max-w-4xl mx-auto space-y-8">

    {{-- Flash / error --}}
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Kembali --}}
    <a href="{{ route('admin.registrations') }}" class="inline-flex items-center space-x-1 text-xs font-bold text-slate-500 hover:text-indigo-600 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        <span>Kembali ke Daftar Pendaftaran</span>
    </a>

    <!-- Header: identitas calon murid -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div class="flex items-center space-x-4">
            <x-avatar :path="$student?->foto_path" :name="$student?->nama_lengkap ?? ''" size="w-14 h-14" class="shrink-0 !rounded-2xl !text-lg" />
            <div class="space-y-1 min-w-0">
                <span class="text-3xs font-extrabold tracking-widest text-slate-400 uppercase">REG-{{ str_pad($form->id_registration_forms, 4, '0', STR_PAD_LEFT) }} · Calon Murid Baru</span>
                <h3 class="text-xl font-bold text-slate-900 leading-tight truncate">{{ $student?->nama_lengkap ?? '-' }}</h3>
                <span class="text-xs text-slate-400 block mt-0.5">NIK: {{ $student?->nik ?? '-' }}</span>
            </div>
        </div>
        <div class="flex flex-col items-end gap-2 shrink-0">
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-2xs font-bold uppercase tracking-widest {{ $statusClass }}">{{ $statusLabel }}</span>
            {{-- K2.5: buka izin edit bila sudah lewat verifikasi bayar & belum dibuka. --}}
            @if (in_array($form->status, ['pembayaran_diverifikasi', 'diproses_seleksi'], true) && ! $form->boleh_edit)
                <form action="{{ route('admin.registrations.allow-edit', $form) }}" method="POST" onsubmit="return confirm('Buka izin edit untuk orang tua? Orang Tua dapat mengubah data & berkas pendaftaran ini.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold rounded-xl text-2xs transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Buka Izin Edit Orang Tua</span>
                    </button>
                </form>
            @elseif ($form->boleh_edit)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 text-2xs font-bold uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Izin Edit Orang Tua Aktif
                </span>
            @endif
        </div>
    </div>

    <!-- Bukti pembayaran (P3.1: paling atas) -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <h3 class="text-base font-bold text-slate-950">Verifikasi Bukti Pembayaran</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">No. Ref</th>
                        <th class="py-4">Jenis</th>
                        <th class="py-4">Bank Asal</th>
                        <th class="py-4">Tanggal &amp; Waktu</th>
                        <th class="py-4">Jumlah</th>
                        <th class="py-4">Bukti</th>
                        <th class="py-4">Status</th>
                        <th class="py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($payments as $payment)
                        @php $pClass = $payStatusClass[$payment->status] ?? 'bg-slate-100 text-slate-600'; @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors">
                            <td class="py-4 font-bold text-slate-800">TX-{{ str_pad($payment->id_payment_transactions, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-4 font-semibold">{{ ucfirst(str_replace('_', ' ', $payment->jenis)) }}</td>
                            <td class="py-4 text-slate-500">{{ $payment->bank_asal ?: '-' }}</td>
                            <td class="py-4 text-slate-400">
                                {{ $payment->tanggal_bayar?->translatedFormat('d M Y') ?? '-' }}
                                <span class="block text-4xs text-slate-300">{{ $payment->created_at?->format('H:i') }} WIB</span>
                            </td>
                            <td class="py-4 font-bold text-slate-900">Rp {{ number_format((int) $payment->jumlah, 0, ',', '.') }}</td>
                            <td class="py-4 text-center">
                                @if ($payment->bukti_path)
                                    <button type="button" onclick="openBerkas('{{ asset('storage/'.$payment->bukti_path) }}')"
                                            class="inline-flex w-9 h-9 rounded border border-slate-200 overflow-hidden hover:border-indigo-400 transition-colors items-center justify-center bg-slate-50">
                                        @if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $payment->bukti_path))
                                            <img src="{{ asset('storage/'.$payment->bukti_path) }}" class="w-full h-full object-cover" alt="Bukti">
                                        @else
                                            <span class="text-3xs font-black text-rose-500">PDF</span>
                                        @endif
                                    </button>
                                @else
                                    <span class="text-4xs text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-4xs font-bold uppercase tracking-wider {{ $pClass }}">{{ ucfirst($payment->status) }}</span>
                            </td>
                            <td class="py-4 text-right">
                                @if ($payment->status === 'pending')
                                    <form action="{{ route('admin.payments.verify', $payment->id_payment_transactions) }}" method="POST" class="inline-flex gap-1">
                                        @csrf
                                        <button type="submit" name="status" value="ditolak" class="px-2.5 py-1.5 border border-rose-200 hover:bg-rose-50 text-rose-600 font-bold rounded-lg transition-colors">Tolak</button>
                                        <button type="submit" name="status" value="diverifikasi" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg transition-colors">Verifikasi</button>
                                    </form>
                                @else
                                    <span class="text-4xs text-slate-400 font-bold">Tindakan Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-slate-400">Belum ada bukti pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Biodata Calon Murid (full-width, di bawah bukti bayar) -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-950">Biodata Calon Murid</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-3 text-xs">
            @foreach ($rowsMurid as $label => $nilai)
                <div class="flex justify-between gap-3 sm:flex-col sm:gap-1">
                    <dt class="text-slate-400 font-semibold uppercase tracking-wide shrink-0">{{ $label }}</dt>
                    <dd class="font-bold text-slate-800 text-right sm:text-left break-words">{{ $nilai ?? '-' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <!-- Biodata Orang Tua: Ayah & Ibu berdampingan -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach (['ayah' => 'Biodata Ayah', 'ibu' => 'Biodata Ibu'] as $p => $judul)
            @php $fields = $ortuField($p); $ada = $ortu?->{'ada_'.$p}; @endphp
            <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-slate-950">{{ $judul }}</h3>
                @if ($ada)
                    <dl class="space-y-3 text-xs">
                        @foreach ($fields as $label => $nilai)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400 font-semibold uppercase tracking-wide shrink-0">{{ $label }}</dt>
                                <dd class="font-bold text-slate-800 text-right break-words">{{ $nilai ?? '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="text-xs text-slate-400 italic">Data {{ $p === 'ayah' ? 'Ayah' : 'Ibu' }} tidak diisi.</p>
                @endif
            </div>
        @endforeach
    </div>

    {{--
        K3.1/K3.2/K3.3: dokumen + keputusan dibungkus satu Alpine root agar
        verifikasi dokumen (AJAX, tanpa refresh) langsung memperbarui gate tombol
        keputusan secara reaktif.
        - docs: state per-dokumen (status terkini).
        - formStatus: status form terkini (menentukan gate Lulus/Gagal & Ubah Keputusan).
        - bolehVerifikasiBerkas: berkas hanya bisa diverifikasi setelah bayar sah (P2.3).
    --}}
    <div
        x-data="{
            csrf: '{{ csrf_token() }}',
            formStatus: @js($form->status),
            bolehEdit: @js((bool) $form->boleh_edit),
            bolehVerifikasiBerkas: @js($bolehVerifikasiBerkas),
            docs: @js($form->documents->mapWithKeys(fn ($d) => [$d->id_registration_documents => $d->status])->all()),
            busy: null,
            flash: '',
            flashError: false,
            get semuaDiterima() {
                const vals = Object.values(this.docs);
                return vals.length > 0 && vals.every(s => s === 'diterima');
            },
            get bolehVerifikasiDoc() { return this.bolehVerifikasiBerkas && ! this.bolehEdit; },
            get bolehDiputuskan() { return this.formStatus === 'diproses_seleksi' && ! this.bolehEdit; },
            get sudahDiputuskan() { return this.formStatus === 'lulus' || this.formStatus === 'gagal'; },
            async verify(docId, status) {
                this.busy = docId; this.flash = ''; this.flashError = false;
                try {
                    const res = await fetch(`{{ url('portal/admin/documents') }}/${docId}/verify`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.ok) { this.flashError = true; this.flash = data.message || 'Gagal memperbarui dokumen.'; return; }
                    this.docs[docId] = data.doc_status;
                    this.formStatus = data.form_status;
                    this.flash = data.message;
                } catch (e) {
                    this.flashError = true; this.flash = 'Terjadi kesalahan jaringan.';
                } finally { this.busy = null; }
            },
        }"
        class="space-y-8"
    >

    <!-- Dokumen -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-bold text-slate-950">Verifikasi Dokumen</h3>
            {{-- Flash AJAX (K3.2) --}}
            <span x-show="flash" x-cloak x-transition
                  class="text-4xs font-bold px-3 py-1.5 rounded-lg"
                  :class="flashError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'"
                  x-text="flash"></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse ($form->documents as $doc)
                @php $jenisLabel = $jenisDokLabel[$doc->jenis] ?? ucfirst($doc->jenis); @endphp
                <div class="border border-slate-100 rounded-2xl p-5 space-y-3">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-xs font-bold text-slate-800">{{ $jenisLabel }}</span>
                        {{-- Badge status reaktif (K3.2) --}}
                        <span class="inline-block px-2 py-0.5 rounded text-4xs font-bold uppercase tracking-wide"
                              :class="{
                                  'bg-emerald-100 text-emerald-700': docs[{{ $doc->id_registration_documents }}] === 'diterima',
                                  'bg-rose-100 text-rose-700': docs[{{ $doc->id_registration_documents }}] === 'ditolak',
                                  'bg-amber-100 text-amber-700': docs[{{ $doc->id_registration_documents }}] === 'pending',
                              }"
                              x-text="({ diterima: 'Diterima', ditolak: 'Ditolak', pending: 'Pending' })[docs[{{ $doc->id_registration_documents }}]] ?? docs[{{ $doc->id_registration_documents }}]"></span>
                    </div>
                    @if ($doc->path)
                        @php $isGambar = preg_match('/\.(jpe?g|png|gif|webp)$/i', $doc->path); @endphp
                        <button type="button" onclick="openBerkas('{{ asset('storage/'.$doc->path) }}')"
                                class="block w-full rounded-xl border border-slate-200 overflow-hidden hover:border-indigo-400 transition-colors group">
                            @if ($isGambar)
                                <img src="{{ asset('storage/'.$doc->path) }}" alt="Berkas {{ $jenisLabel }}" class="w-full h-28 object-cover group-hover:opacity-90">
                            @else
                                <div class="w-full h-28 flex flex-col items-center justify-center gap-1 bg-slate-50 text-slate-400">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="text-4xs font-bold uppercase">Lihat PDF</span>
                                </div>
                            @endif
                        </button>
                    @endif
                    {{-- K3.2: aksi AJAX. Setelah diverifikasi → tampil status + tombol Ubah (K3.3-style). --}}
                    <template x-if="bolehVerifikasiDoc && docs[{{ $doc->id_registration_documents }}] === 'pending'">
                        <div class="flex gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="verify({{ $doc->id_registration_documents }}, 'ditolak')"
                                    :disabled="busy === {{ $doc->id_registration_documents }}"
                                    class="flex-1 py-2 border border-rose-200 hover:bg-rose-50 text-rose-600 font-bold rounded-lg text-4xs transition-colors disabled:opacity-40">Tolak</button>
                            <button type="button" @click="verify({{ $doc->id_registration_documents }}, 'diterima')"
                                    :disabled="busy === {{ $doc->id_registration_documents }}"
                                    class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-4xs transition-colors disabled:opacity-40">Terima</button>
                        </div>
                    </template>
                    <template x-if="bolehVerifikasiDoc && docs[{{ $doc->id_registration_documents }}] !== 'pending'">
                        <div class="pt-2 border-t border-slate-100">
                            <button type="button" @click="docs[{{ $doc->id_registration_documents }}] = 'pending'"
                                    class="w-full inline-flex items-center justify-center gap-1.5 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-lg text-4xs transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Ubah</span>
                            </button>
                        </div>
                    </template>
                    <template x-if="! bolehVerifikasiBerkas">
                        <p class="text-4xs text-slate-400 pt-2 border-t border-slate-100">Verifikasi bukti pembayaran dahulu.</p>
                    </template>
                    <template x-if="bolehVerifikasiBerkas && bolehEdit">
                        <p class="text-4xs text-amber-600 pt-2 border-t border-slate-100">Izin edit orang tua aktif — verifikasi terkunci sampai orang tua selesai.</p>
                    </template>
                </div>
            @empty
                <p class="col-span-full text-xs text-slate-400 text-center py-4">Belum ada dokumen diunggah.</p>
            @endforelse
        </div>
    </div>

    <!-- Keputusan kelulusan -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <div class="text-center space-y-1">
            <span class="text-3xs font-extrabold uppercase tracking-widest text-slate-400 block">Keputusan Seleksi Manual</span>
            <p class="text-xs text-slate-500">Tetapkan hasil kelulusan calon murid ini. Keputusan akan dikirim otomatis ke email orang tua murid.</p>
        </div>

        {{-- K3.1: tombol Lulus/Gagal hanya muncul saat form 'diproses_seleksi'
             (bayar terverifikasi + semua dokumen diterima). --}}
        <template x-if="bolehDiputuskan">
            <form action="{{ route('admin.registrations.decide', $form) }}" method="POST" class="flex gap-3 max-w-md mx-auto">
                @csrf
                <button type="submit" name="keputusan" value="gagal" onclick="return confirm('Yakin nyatakan calon murid TIDAK LULUS?')" class="flex-1 py-3 border border-rose-200 hover:bg-rose-50 text-rose-600 font-bold rounded-xl text-xs transition-colors flex items-center justify-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Nyatakan Gagal</span>
                </button>
                <button type="submit" name="keputusan" value="lulus" onclick="return confirm('Yakin nyatakan calon murid LULUS?')" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors flex items-center justify-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>Nyatakan Lulus</span>
                </button>
            </form>
        </template>

        {{-- K3.3: setelah lulus/gagal → tampilkan hasil + tombol Ubah Keputusan. --}}
        <template x-if="sudahDiputuskan">
            <div class="max-w-md mx-auto text-center space-y-3">
                <p class="text-xs font-bold" :class="formStatus === 'lulus' ? 'text-emerald-700' : 'text-rose-700'"
                   x-text="formStatus === 'lulus' ? 'Calon murid dinyatakan LULUS.' : 'Calon murid dinyatakan TIDAK LULUS.'"></p>
                <form action="{{ route('admin.registrations.reopen-decision', $form) }}" method="POST"
                      onsubmit="return confirm('Buka kembali keputusan? Status akan kembali ke Proses Seleksi.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-xl text-xs transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Ubah Keputusan</span>
                    </button>
                </form>
            </div>
        </template>

        {{-- Belum sampai tahap seleksi: beri tahu syarat (K3.1). --}}
        <template x-if="! bolehDiputuskan && ! sudahDiputuskan">
            <p class="text-2xs text-slate-400 text-center max-w-md mx-auto">Keputusan kelulusan dapat ditetapkan setelah pembayaran diverifikasi dan seluruh dokumen diterima.</p>
        </template>
    </div>

    </div>{{-- /Alpine root dokumen+keputusan --}}

</div>
@endsection
