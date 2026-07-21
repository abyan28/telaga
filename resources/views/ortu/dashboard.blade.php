@extends('layouts.dashboard')

@section('title', 'Dashboard Orang Tua — TELAGA AL KAUTSAR')
@section('header_title', 'Dashboard Orang Tua')

@section('content')
@php
    // Metadata status pendaftaran untuk badge (label + warna).
    $statusMap = [
        'submitted' => ['Sudah Submit', 'bg-sky-100 text-sky-700'],
        'menunggu_bukti' => ['Bukti Ditolak – Upload Ulang', 'bg-rose-100 text-rose-700'],
        'menunggu_verifikasi' => ['Menunggu Verifikasi Pembayaran', 'bg-amber-100 text-amber-700'],
        'pembayaran_diverifikasi' => ['Proses Verifikasi Berkas', 'bg-sky-100 text-sky-700'],
        'diproses_seleksi' => ['Proses Seleksi', 'bg-indigo-100 text-indigo-700'],
        'lulus' => ['Lulus Seleksi', 'bg-emerald-100 text-emerald-700'],
        'gagal' => ['Belum Lulus', 'bg-rose-100 text-rose-700'],
    ];
@endphp

<div class="space-y-8">

    <!-- Welcome Card -->
    <div class="bg-gradient-to-r from-sky-500 to-blue-600 text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full"></div>
        <div class="relative space-y-3 max-w-xl">
            <h3 class="text-xl sm:text-2xl font-black">Selamat Datang, {{ auth()->user()->displayName() }}!</h3>
            <p class="text-sky-100 text-xs sm:text-sm leading-relaxed">
                @if ($students->isNotEmpty())
                    Pantau perkembangan pendaftaran, tagihan SPP bulanan, dan berkas penting
                    {{ $students->count() > 1 ? $students->count().' ananda' : 'Ananda '.$students->first()->nama_lengkap }}
                    dari satu dashboard terintegrasi.
                @else
                    Anda belum mendaftarkan murid. Silakan lengkapi formulir pendaftaran murid baru untuk memulai.
                @endif
            </p>
            @if ($students->isNotEmpty())
                <div class="pt-2 text-sky-100 text-xs">
                    Total sisa tunggakan seluruh ananda:
                    <span class="font-black text-white text-base">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</span>
                </div>
            @else
                <a href="{{ route('ortu.register') }}" class="inline-flex items-center space-x-1 mt-2 px-4 py-2 bg-white text-sky-600 font-bold rounded-xl text-xs hover:bg-sky-50 transition-all">
                    <span>Daftar Murid Baru</span><span>&rarr;</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Satu blok ringkasan per anak (mendukung banyak anak) --}}
    @foreach ($students as $student)
        @php
            $registration = $student->registrationForms->first();
            $statusKey = $registration?->status ?? 'submitted';
            [$statusLabel, $statusClass] = $statusMap[$statusKey] ?? ['-', 'bg-slate-100 text-slate-600'];
            $tunggakanAnak = $student->monthlySppBills->sum(fn ($b) => $b->sisa())
                + $student->reRegistrationPayments->sum(fn ($r) => $r->sisa());
        @endphp

        <div class="space-y-6">
            {{-- Judul anak (bila lebih dari satu, beri penanda) --}}
            @if ($students->count() > 1)
                <div class="flex items-center space-x-2">
                    <span class="w-1.5 h-5 bg-sky-500 rounded-full"></span>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">{{ $student->nama_lengkap }}</h3>
                </div>
            @endif

            <!-- Overview Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Registration Status Card -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-6">
                    <div class="flex justify-between items-start gap-3">
                        <div class="space-y-1 min-w-0">
                            <span class="block text-2xs font-bold uppercase tracking-wider text-slate-400">Pendaftaran</span>
                            <h4 class="text-base font-bold text-slate-900 leading-tight truncate">{{ $student->nama_lengkap }}</h4>
                        </div>
                        <span class="inline-flex items-center shrink-0 max-w-[45%] text-right px-2.5 py-1 rounded-full text-4xs font-bold uppercase tracking-widest leading-tight {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-xs text-slate-400">Lihat status & berkas</span>
                        <a href="{{ route('ortu.status') }}" class="text-xs font-bold text-sky-600 hover:text-sky-500 flex items-center space-x-1">
                            <span>Detail</span><span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- Total Tunggakan Card (per anak) -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-6">
                    <div class="flex justify-between items-center gap-3">
                        <div class="space-y-1 min-w-0">
                            <span class="text-2xs font-bold uppercase tracking-wider text-slate-400">Sisa Tunggakan</span>
                            <h4 class="text-2xl font-black text-slate-900 leading-tight truncate">Rp {{ number_format($tunggakanAnak, 0, ',', '.') }}</h4>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-xs text-slate-400">SPP & Daftar Ulang</span>
                        <a href="{{ route('ortu.payments') }}" class="text-xs font-bold text-sky-600 hover:text-sky-500 flex items-center space-x-1">
                            <span>Bayar</span><span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- Class Information Card -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-6">
                    <div class="flex justify-between items-start gap-3">
                        <div class="space-y-1 min-w-0">
                            <span class="block text-2xs font-bold uppercase tracking-wider text-slate-400">Kelas Aktif</span>
                            <h4 class="text-base font-bold text-slate-900 leading-tight truncate">{{ $student->schoolClass?->nama_kelas ?? 'Belum ditempatkan' }}</h4>
                        </div>
                        <span class="inline-flex items-center shrink-0 max-w-[45%] text-right px-2.5 py-1 rounded-full text-4xs font-bold uppercase tracking-widest leading-tight bg-sky-100 text-sky-700">
                            T.A. {{ $student->academicYear?->tahun ?? '-' }}
                        </span>
                    </div>
                    <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-xs text-slate-400 truncate">
                            Guru: {{ $student->schoolClass?->teachers->first()?->nama ?? 'Belum ada' }}
                        </span>
                    </div>
                </div>

            </div>

            <!-- Payment Breakdown (per anak) -->
            <div class="bg-white border border-slate-100 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-950">Rincian & Progress Pembayaran</h3>
                    <span class="text-2xs text-slate-400">T.A. {{ $student->academicYear?->tahun ?? '-' }}</span>
                </div>

                <div class="space-y-6">
                    {{-- Daftar Ulang --}}
                    @foreach ($student->reRegistrationPayments as $r)
                        @php $pct = $r->total_biaya > 0 ? min(100, round($r->jumlah_terbayar / $r->total_biaya * 100, 1)) : 0; @endphp
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-xs gap-2">
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-800">Biaya Daftar Ulang</span>
                                    <span class="font-bold ml-2 {{ $r->status === 'lunas' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst(str_replace('_', ' ', $r->status)) }}</span>
                                </div>
                                <span class="font-semibold text-slate-500 shrink-0 whitespace-nowrap">Rp {{ number_format($r->jumlah_terbayar, 0, ',', '.') }} / Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-sky-500 h-full rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            @if ($r->sisa() > 0)
                                <p class="text-4xs text-slate-400 leading-normal">* Sisa kekurangan: <strong>Rp {{ number_format($r->sisa(), 0, ',', '.') }}</strong>. Unggah bukti pembayaran berikutnya di menu pembayaran.</p>
                            @endif
                        </div>
                    @endforeach

                    {{-- SPP bulanan --}}
                    @forelse ($student->monthlySppBills as $bill)
                        @php $pct = $bill->nominal > 0 ? min(100, round($bill->jumlah_terbayar / $bill->nominal * 100, 1)) : 0; @endphp
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-xs gap-2">
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-800">SPP {{ \Illuminate\Support\Carbon::parse($bill->bulan.'-01')->translatedFormat('F Y') }}</span>
                                    <span class="font-bold ml-2 {{ $bill->status === 'lunas' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst(str_replace('_', ' ', $bill->status)) }}</span>
                                </div>
                                <span class="font-semibold text-slate-500 shrink-0 whitespace-nowrap">Rp {{ number_format($bill->jumlah_terbayar, 0, ',', '.') }} / Rp {{ number_format($bill->nominal, 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-sky-500 h-full rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            @if ($bill->sisa() > 0)
                                <p class="text-4xs text-slate-400 leading-normal">* Sisa kekurangan: <strong>Rp {{ number_format($bill->sisa(), 0, ',', '.') }}</strong>.</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada tagihan SPP. Tagihan akan muncul otomatis tiap awal bulan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
