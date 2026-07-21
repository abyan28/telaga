@extends('layouts.dashboard')

@section('title', $student->nama_lengkap.' — RA Al Kautsar')
@section('header_title', 'Profil Anak')

@section('content')
{{-- K10.3: profil biodata + orang tua + catatan perkembangan guru (READ-ONLY untuk orang tua). --}}
<div class="space-y-6 max-w-2xl" x-data="hashTabs('biodata')">

    <a href="{{ $backUrl ?? route('ortu.children') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-slate-600">&lsaquo; {{ $backLabel ?? 'Kembali ke Data Anak' }}</a>

    {{-- Header ringkas (selalu tampil) --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs">
        <div class="flex items-center gap-4">
            <x-avatar :path="$student->foto_path" :name="$student->nama_lengkap" size="w-20 h-20" />
            <div>
                <h3 class="text-base font-bold text-slate-950">{{ $student->nama_lengkap }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ $student->schoolClass?->nama_kelas ?? 'Belum ada kelas' }} · {{ $student->academicYear?->tahun ?? '-' }}</p>
                <p class="text-3xs text-slate-400 mt-0.5">Wali Kelas: <span class="font-bold text-slate-600">{{ $student->schoolClass?->homeroomTeacher?->nama ?? '-' }}</span></p>
            </div>
        </div>
    </div>

    {{-- Tab bar (rules §6.2) --}}
    <div class="flex gap-2 border-b border-slate-100 overflow-x-auto">
        @foreach (['biodata' => 'Biodata', 'orang-tua' => 'Profil Orang Tua'] as $key => $label)
            <button @click="setTab('{{ $key }}')" :class="tab === '{{ $key }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Biodata --}}
    <div x-show="tab === 'biodata'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs">
        <dl class="grid grid-cols-2 gap-4 text-xs">
            @php
                $bio = [
                    'Nama Panggilan' => $student->nama_panggilan ?: '-',
                    'NISN' => $student->nisn ?: '-',
                    'Jenis Kelamin' => $student->jenis_kelamin === 'L' ? 'Laki-laki' : ($student->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                    'Tempat, Tgl Lahir' => trim(($student->tempat_lahir ?: '').', '.optional($student->tanggal_lahir)->translatedFormat('d F Y'), ', '),
                    'Wali Kelas' => $student->schoolClass?->homeroomTeacher?->nama ?? '-',
                    'Status' => ucfirst($student->status),
                ];
            @endphp
            @foreach ($bio as $label => $val)
                <div>
                    <dt class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">{{ $label }}</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ $val }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Profil Orang Tua / Orang Tua --}}
    <div x-show="tab === 'orang-tua'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs">
        @if ($student->ortu)
            @php $g = $student->ortu; @endphp
            <div class="space-y-5">
                @foreach (['ibu' => 'Ibu', 'ayah' => 'Ayah'] as $p => $sebutan)
                    @if ($g->{"ada_$p"} && $g->{"{$p}_nama"})
                        <div>
                            <h4 class="text-3xs font-extrabold uppercase tracking-widest text-sky-500 mb-2">Data {{ $sebutan }}</h4>
                            <dl class="grid grid-cols-2 gap-4 text-xs">
                                @foreach ([
                                    'Nama' => $g->{"{$p}_nama"} ?: '-',
                                    'No. HP' => $g->{"{$p}_no_hp"} ?: '-',
                                    'Pekerjaan' => ($g->{"{$p}_pekerjaan"} === 'LAINNYA' ? $g->{"{$p}_pekerjaan_lain"} : $g->{"{$p}_pekerjaan"}) ?: '-',
                                    'Penghasilan' => $g->{"{$p}_penghasilan"} ?: '-',
                                ] as $label => $val)
                                    <div>
                                        <dt class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">{{ $label }}</dt>
                                        <dd class="font-bold text-slate-800 mt-0.5">{{ $val }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                @endforeach
                <div>
                    <dt class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Alamat Keluarga</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ trim(($g->alamat ?: '').' '.collect([$g->kelurahan_nama, $g->kecamatan_nama, $g->kota_nama, $g->provinsi_nama])->filter()->implode(', ')) ?: '-' }}</dd>
                </div>
            </div>
        @else
            <p class="text-xs text-slate-400">Murid ini belum tertaut ke akun orang tua.</p>
        @endif
    </div>

</div>
@endsection
