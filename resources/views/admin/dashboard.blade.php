@extends('layouts.dashboard')

@section('title', 'Admin Dashboard CMS — RA Al Kautsar')
@section('header_title', 'Dashboard Administrator')

@section('content')
<div class="space-y-8">
    
    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        
        <!-- Stat 1: Total Applicants -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-2xs hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider block">Pendaftar Baru</span>
                    <span class="text-2xl font-black text-slate-900 block">{{ $pendaftarBaru }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-4xs font-bold bg-emerald-100 text-emerald-700">
                        Belum diputuskan
                    </span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600 shrink-0">
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
        </div>

        <!-- Stat 2: Graduated/Accepted -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-2xs hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider block">Murid Lulus Seleksi</span>
                    <span class="text-2xl font-black text-slate-900 block">{{ $lulus }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-4xs font-bold bg-sky-100 text-sky-700">
                        {{ $kuota > 0 ? $persenKuota.'% dari kuota '.$kuota : 'Kuota belum diatur' }}
                    </span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 shrink-0">
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.42a12.05 12.05 0 01.84 6.68A3 3 0 0012 20a3 3 0 00-3-3H3"/></svg>
                </div>
            </div>
        </div>

        <!-- Stat 3: Total Revenue -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-2xs hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider block">Keuangan Masuk</span>
                    <span class="text-lg font-black text-slate-900 block">Rp {{ number_format($keuanganMasuk, 0, ',', '.') }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-4xs font-bold bg-amber-100 text-amber-700">
                        Terverifikasi
                    </span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <!-- Stat 4: Pending Verifikasi PPDB (K6.3) -->
        <a href="{{ route('admin.registrations') }}" class="bg-white border border-slate-100 rounded-2xl p-5 shadow-2xs hover:shadow-md transition-shadow block">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider block">Pending PPDB</span>
                    <span class="text-2xl font-black text-slate-900 block">{{ $pendingPpdb }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-4xs font-bold bg-amber-100 text-amber-700 @if($pendingPpdb) animate-pulse @endif">
                        {{ $pendingPpdb ? 'Perlu Verifikasi' : 'Tidak Ada' }}
                    </span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </a>

        <!-- Stat 5: Pending Verifikasi Pembayaran (K6.3) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-2xs hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider block">Pending Pembayaran</span>
                    <span class="text-2xl font-black text-slate-900 block">{{ $pendingPembayaran }}</span>
                    {{-- Rincian jenis pembayaran yang perlu dikonfirmasi (mis. "1 daftar ulang, 3 SPP"). --}}
                    @php
                        $rincian = [];
                        if ($ppdbBayarBelumVerif) $rincian[] = $ppdbBayarBelumVerif.' PPDB';
                        if ($pendingDaftarUlang) $rincian[] = $pendingDaftarUlang.' daftar ulang';
                        if ($pendingSpp) $rincian[] = $pendingSpp.' SPP';
                    @endphp
                    <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-4xs font-bold bg-rose-100 text-rose-700 @if($pendingPembayaran) animate-pulse @endif">
                        {{ $rincian ? implode(', ', $rincian) : 'Tidak ada' }}
                    </span>
                </div>
                <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 shrink-0">
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 2h3m2 3H9m12-5a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Recent Activities (Audit Log terbaru dari DB) -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-xs space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-900">Aktivitas & Log Sistem Terkini</h3>
            <a href="{{ route('admin.reports') }}" class="text-xs text-indigo-600 font-bold hover:underline">Lihat Seluruh Log &rarr;</a>
        </div>

        <div class="space-y-4">
            @forelse ($auditLogs as $log)
                <div class="flex items-start space-x-3 text-xs leading-normal">
                    <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 mt-1 shrink-0"></div>
                    <div class="flex-1">
                        <p class="text-slate-700">
                            <strong>{{ $log->user?->displayName() ?? 'Sistem' }}</strong>
                            ({{ ucfirst($log->user?->role ?? 'sistem') }})
                            — {{ str_replace('_', ' ', $log->aksi) }}{{ $log->model_terkait ? ' pada '.$log->model_terkait : '' }}.
                        </p>
                        <span class="text-4xs text-slate-400 block mt-0.5">{{ $log->created_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 text-center py-4">Belum ada aktivitas tercatat.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
