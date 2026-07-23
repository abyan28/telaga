@extends('layouts.dashboard')

@section('title', 'Status Pendaftaran Murid Baru — TELAGA AL KAUTSAR')
@section('header_title', 'Status Pendaftaran')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">

    {{-- Flash message --}}
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($forms->isEmpty())
        {{-- Empty-state: belum ada pendaftaran --}}
        <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm text-center space-y-4">
            <h3 class="text-lg font-bold text-slate-900">Belum Ada Pendaftaran</h3>
            <p class="text-sm text-slate-500">Anda belum mengajukan pendaftaran murid baru. Silakan mulai dari formulir pendaftaran.</p>
            <a href="{{ route('ortu.register') }}" class="inline-flex items-center space-x-1 px-6 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md text-sm">
                <span>Daftar Murid Baru</span><span>&rarr;</span>
            </a>
        </div>
    @else

    {{-- K2.6: switcher antar-anak (hindari scroll panjang bila orang tua >1 anak). --}}
    <div x-data="{ activeChild: 0 }" class="space-y-8">
        @if ($forms->count() > 1)
        <div class="flex flex-wrap gap-2 bg-white border border-slate-100 rounded-2xl p-2 shadow-sm">
            @foreach ($forms as $i => $f)
            <button type="button" @click="activeChild = {{ $i }}"
                :class="activeChild === {{ $i }} ? 'bg-sky-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all">
                {{ $f->student?->nama_lengkap ?? 'Anak '.($i + 1) }}
            </button>
            @endforeach
        </div>
        @endif

    {{-- Satu blok status + timeline per anak (P2.1: dukung orang tua banyak anak) --}}
    @foreach ($forms as $form)
    @php
        // Peta status formulir -> indeks tahap timeline (0-based). Alur dipisah penuh (P2.3):
        // 0 form, 1 bukti bayar, 2 verifikasi pembayaran, 3 verifikasi berkas, 4 seleksi, 5 keputusan.
        $status = $form->status ?? 'submitted';
        $tahapMap = [
            'submitted' => 1,
            'menunggu_bukti' => 1,
            'menunggu_verifikasi' => 2,
            'pembayaran_diverifikasi' => 3,
            'diproses_seleksi' => 4,
            'lulus' => 5,
            'gagal' => 5,
        ];
        $tahap = $tahapMap[$status] ?? 0;
        $isLulus = $status === 'lulus';
        $isGagal = $status === 'gagal';
        // Butuh unggah bukti bila belum ada pembayaran diverifikasi/menunggu.
        $butuhBukti = in_array($status, ['submitted', 'menunggu_bukti'], true);
        $reReg = $form->student?->reRegistrationPayments->first();
        $duLunas = $reReg && $reReg->sisa() <= 0;
    @endphp

    <div x-data="{ showPaymentModal: false }" x-show="activeChild === {{ $loop->index }}" class="space-y-8">
        <!-- Student Detail Card -->
        <div class="bg-white border border-slate-100 rounded-[2rem] p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div class="flex items-center space-x-4">
                <x-avatar :path="$form->student?->foto_path" :name="$form->student?->nama_lengkap ?? ''" size="w-14 h-14" class="shrink-0 !rounded-2xl !text-lg" />
                <div class="space-y-1 min-w-0">
                    <span class="text-3xs font-extrabold tracking-widest text-slate-400 uppercase">Calon Murid Baru</span>
                    <h3 class="text-xl font-bold text-slate-900 leading-tight truncate">{{ $form->student?->nama_lengkap ?? '-' }}</h3>
                    <span class="text-xs text-slate-400 block mt-0.5">NIK: {{ $form->student?->nik ?? '-' }}</span>
                </div>
            </div>

            <!-- Registration Actions depending on state -->
            <div class="shrink-0 flex flex-col gap-2">
                @if ($form->canBeEditedByWali())
                    <a href="{{ route('ortu.register.edit', $form) }}" class="w-full sm:w-auto px-6 py-2.5 border border-sky-200 text-sky-700 hover:bg-sky-50 font-bold rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit Pendaftaran</span>
                    </a>
                @endif
                @if ($butuhBukti)
                    <button @click="showPaymentModal = true" class="w-full sm:w-auto px-6 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Upload Bukti Pendaftaran</span>
                    </button>
                @elseif ($isLulus && !$duLunas)
                    <a href="{{ route('ortu.payments') }}" class="w-full sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span>Bayar Daftar Ulang</span>
                    </a>
                @elseif ($isLulus && $duLunas)
                    <div class="text-xs text-emerald-700 font-bold bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-center sm:text-left max-w-xs">
                        Pembayaran daftar ulang sudah lunas.
                    </div>
                @elseif ($isGagal)
                    <div class="text-xs text-rose-600 font-bold bg-rose-50 border border-rose-100 rounded-xl px-4 py-3 text-center sm:text-left max-w-xs">
                        Mohon maaf, calon murid dinyatakan belum lulus seleksi pada periode ini.
                    </div>
                @elseif ($status === 'menunggu_verifikasi')
                    <div class="text-xs text-blue-600 font-bold bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-center sm:text-left max-w-xs">
                        Pembayaran Anda sedang diverifikasi oleh Admin. Mohon menunggu.
                    </div>
                @else
                    <div class="text-xs text-blue-600 font-bold bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-center sm:text-left max-w-xs">
                        Berkas Anda sedang diproses oleh Admin. Mohon menunggu.
                    </div>
                @endif
            </div>
        </div>

        <!-- Timeline Card -->
        <div class="bg-white border border-slate-100 rounded-[2rem] p-6 sm:p-8 shadow-sm space-y-8">
            <h3 class="text-base font-bold text-slate-950">Timeline Status Pendaftaran</h3>

            <div class="relative pl-8 border-l-2 border-slate-100 space-y-8">
                @php
                    // Definisi 6 tahap timeline (judul + deskripsi). Verifikasi pembayaran
                    // dan verifikasi berkas dipisah jadi 2 tahap terpisah (P2.3).
                    $steps = [
                        ['Mengisi Formulir Pendaftaran', 'Profil anak dan orang tua berhasil diunggah dengan data lengkap.'],
                        ['Bukti Pembayaran Pendaftaran', 'Biaya pendaftaran: Rp '.number_format($nominalPendaftaran, 0, ',', '.').'. Transfer manual ke rekening sekolah lalu unggah buktinya.'],
                        ['Verifikasi Pembayaran', 'Admin meninjau keabsahan bukti transfer biaya pendaftaran.'],
                        ['Verifikasi Berkas', 'Setelah pembayaran sah, Admin meninjau kelengkapan dokumen KK, Akta, dan Foto.'],
                        ['Proses Seleksi Calon Murid', 'Dokumen lengkap sedang ditinjau untuk penentuan kelulusan berdasarkan kuota kelas.'],
                        ['Pengumuman Kelulusan Seleksi', 'Hasil kelulusan final yang diputuskan secara manual oleh Admin Sekolah.'],
                    ];
                    $tahapAkhir = count($steps) - 1;
                @endphp

                @foreach ($steps as $i => [$judul, $deskripsi])
                    <div class="relative">
                        @php
                            // Tentukan tampilan bulatan tahap: selesai / aktif / belum.
                            if ($i < $tahap) {
                                $dot = 'bg-emerald-500 border-emerald-500 text-white shadow-md'; $mark = '✓';
                            } elseif ($i === $tahap) {
                                if ($isGagal && $i === $tahapAkhir) { $dot = 'bg-rose-500 border-rose-500 text-white shadow-md'; $mark = '✕'; }
                                elseif ($isLulus && $i === $tahapAkhir) { $dot = 'bg-emerald-500 border-emerald-500 text-white shadow-md'; $mark = '✓'; }
                                else { $dot = 'bg-sky-500 border-sky-500 text-white ring-4 ring-sky-100'; $mark = (string) ($i + 1); }
                            } else {
                                $dot = 'bg-white border-slate-200 text-slate-400'; $mark = (string) ($i + 1);
                            }
                        @endphp
                        <span class="absolute -left-11 top-0 w-6 h-6 rounded-full flex items-center justify-center font-bold text-3xs border transition-colors duration-300 {{ $dot }}">{{ $mark }}</span>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-slate-900 leading-tight">{{ $judul }}</h4>
                            <p class="text-xs text-slate-500">{{ $deskripsi }}</p>
                            @if ($i === $tahap && ! $isLulus && ! $isGagal)
                                <span class="inline-block mt-2 px-2.5 py-0.5 rounded text-4xs font-bold bg-amber-100 text-amber-700 uppercase tracking-wide">Tahap Saat Ini</span>
                            @endif
                            @if ($i === $tahapAkhir && $isLulus)
                                <span class="inline-block mt-2 px-2.5 py-0.5 rounded text-4xs font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wide">Selamat, Dinyatakan Lulus!</span>
                            @endif
                            @if ($i === $tahapAkhir && $isGagal)
                                <span class="inline-block mt-2 px-2.5 py-0.5 rounded text-4xs font-bold bg-rose-100 text-rose-700 uppercase tracking-wide">Dinyatakan Tidak Lulus</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Dokumen terunggah -->
        @if ($form->documents->isNotEmpty())
            <div class="bg-white border border-slate-100 rounded-[2rem] p-6 sm:p-8 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-950">Berkas Terunggah</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($form->documents as $doc)
                        @php
                            $jenisLabel = ['kk' => 'Kartu Keluarga', 'akta' => 'Akta Kelahiran', 'foto' => 'Pas Foto'][$doc->jenis] ?? ucfirst($doc->jenis);
                            $docClass = ['diterima' => 'bg-emerald-100 text-emerald-700', 'ditolak' => 'bg-rose-100 text-rose-700'][$doc->status] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <div class="border border-slate-100 rounded-2xl p-4 space-y-2">
                            <span class="text-xs font-bold text-slate-800 block">{{ $jenisLabel }}</span>
                            <span class="inline-block px-2 py-0.5 rounded text-4xs font-bold uppercase tracking-wide {{ $docClass }}">{{ ucfirst($doc->status) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Upload Bukti Modal (form asli POST) -->
        @if ($butuhBukti)
        <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white rounded-[2.5rem] p-8 max-w-md w-full border border-slate-100 shadow-2xl space-y-6" @click.away="showPaymentModal = false">
                <div class="flex justify-between items-start">
                    <h3 class="text-lg font-bold text-slate-950">Upload Bukti Pembayaran</h3>
                    <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('ortu.status.proof') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="id_registration_form" value="{{ $form->id_registration_forms }}">

                    <div class="text-xs text-slate-600 space-y-2">
                        <p>Silakan transfer sebesar <strong>Rp {{ number_format($nominalPendaftaran, 0, ',', '.') }}</strong> ke rekening sekolah:</p>
                        @if ($rekening['bank'] || $rekening['nomor'])
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-0.5">
                                <p class="font-bold text-slate-800">{{ $rekening['bank'] ?: '—' }}</p>
                                <p class="text-slate-500">No. Rek: <strong>{{ $rekening['nomor'] ?: '—' }}</strong> a.n. <strong>{{ $rekening['atas_nama'] ?: 'RA Al Kautsar' }}</strong></p>
                            </div>
                        @else
                            <p class="text-3xs text-amber-600">Rekening sekolah belum diatur admin.</p>
                        @endif
                    </div>

                    <div class="space-y-1">
                        <label class="text-2xs font-bold uppercase tracking-wider text-slate-500">Nominal Dibayar (Rp)</label>
                        <input type="number" name="jumlah" value="{{ old('jumlah', $nominalPendaftaran) }}" min="{{ $nominalPendaftaran }}" max="{{ $nominalPendaftaran }}" readonly required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100 text-slate-600 cursor-not-allowed focus:outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-2xs font-bold uppercase tracking-wider text-slate-500">Tanggal Transfer</label>
                        <input type="date" name="tanggal_bayar" value="{{ old('tanggal_bayar', date('Y-m-d')) }}" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-sky-200 focus:border-sky-400 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-2xs font-bold uppercase tracking-wider text-slate-500">Bank Asal Transfer</label>
                        <x-bank-picker :banks="$daftarBank" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-sky-200 focus:border-sky-400 outline-none" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-2xs font-bold uppercase tracking-wider text-slate-500">Bukti Transfer (JPG/PDF, maks 2 MB)</label>
                        <input type="file" name="bukti" accept=".jpg,.jpeg,.pdf" required class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="showPaymentModal = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600 transition-colors">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Unggah Bukti</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
    @endforeach
    </div>{{-- /activeChild switcher --}}

    @endif
</div>
@endsection
