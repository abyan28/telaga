@extends('layouts.dashboard')

@section('title', 'Data Anak — RA Al Kautsar')
@section('header_title', 'Data Anak')

@section('content')
{{-- K10.3: daftar anak orang tua (read-only) → tiap kartu buka profil & catatan guru. --}}
<div class="space-y-6">

    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs">
        <h3 class="text-base font-bold text-slate-950">Data Anak</h3>
        <p class="text-xs text-slate-400 mt-1">Lihat biodata & catatan perkembangan yang ditulis guru kelas.</p>
    </div>

    @forelse ($students as $s)
        <a href="{{ route('ortu.children.show', $s) }}"
           class="flex items-center justify-between gap-4 bg-white border border-slate-100 rounded-[2rem] p-5 md:p-6 shadow-xs hover:border-sky-200 hover:shadow-md transition-all">
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-slate-900 truncate">{{ $s->nama_lengkap }}</h4>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $s->schoolClass?->nama_kelas ?? 'Belum ada kelas' }}
                    · {{ $s->academicYear?->tahun ?? '-' }}
                </p>
            </div>
            <span class="shrink-0 text-xs font-bold text-sky-600">Lihat &rsaquo;</span>
        </a>
    @empty
        <div class="bg-white border border-slate-100 rounded-[2rem] p-8 text-center text-sm text-slate-400">Belum ada data anak.</div>
    @endforelse

</div>
@endsection
