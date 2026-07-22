@extends('layouts.dashboard')

@section('title', 'Profil Guru — RA Al Kautsar')
@section('header_title', 'Profil Guru')

@section('content')
@php
    // Alamat guru dirangkai dari kolom wilayah (yang terisi saja).
    $alamat = collect([
        $teacher->alamat, $teacher->kelurahan_nama, $teacher->kecamatan_nama,
        $teacher->kota_nama, $teacher->provinsi_nama,
    ])->filter()->implode(', ');
@endphp

<div class="max-w-4xl mx-auto space-y-8">

    <a href="{{ route('admin.data.teachers') }}" class="inline-flex items-center space-x-1 text-xs font-bold text-slate-500 hover:text-indigo-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        <span>Kembali ke Data Guru</span>
    </a>

    {{-- Biodata + akun --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <div class="flex items-center gap-4">
            <x-avatar :path="$teacher->foto_path" :name="$teacher->nama" size="w-20 h-20" />
            <div>
                <h3 class="text-base font-bold text-slate-950">{{ $teacher->nama }}</h3>
                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-4xs font-bold uppercase tracking-wider {{ $teacher->is_aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">{{ $teacher->is_aktif ? 'Aktif' : 'Keluar' }}</span>
            </div>
        </div>
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs">
            @foreach ([
                'NUPTK' => $teacher->nuptk ?? '-',
                'Jabatan' => $teacher->jabatan ?? '—',
                'Jenis Kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'][$teacher->jenis_kelamin] ?? '-',
                'Tempat, Tgl Lahir' => trim(($teacher->tempat_lahir ?? '-').', '.optional($teacher->tanggal_lahir)->translatedFormat('d F Y'), ', '),
                'Mulai Mengajar' => optional($teacher->tanggal_mulai_mengajar)->translatedFormat('d F Y') ?: '—',
                'No. HP' => $teacher->no_hp ?? '-',
                'Email' => $teacher->user?->email ?? '—',
                'Tampil di Web' => $teacher->tampil_di_web ? 'Ya' : 'Tidak',
                'Riwayat Pendidikan' => $teacher->riwayat_pendidikan ?? '—',
                'Alamat' => $alamat ?: '—',
            ] as $label => $val)
                <div>
                    <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">{{ $label }}</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ $val }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Kelas yang diampu --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-950">Kelas yang Diampu</h3>
        @forelse ($teacher->classes as $kelas)
            <div class="flex flex-wrap justify-between items-center gap-2 border border-slate-100 rounded-xl p-4 text-xs">
                <span class="font-bold text-slate-800">{{ $kelas->nama_kelas }}</span>
                <span class="text-slate-500">T.A. {{ $kelas->academicYear?->tahun ?? '-' }}</span>
                <span class="font-bold text-slate-800">{{ $kelas->students->count() }} murid</span>
            </div>
        @empty
            <p class="text-xs text-slate-400">Belum mengampu kelas.</p>
        @endforelse
    </div>

</div>
@endsection
