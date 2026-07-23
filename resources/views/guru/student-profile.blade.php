@extends('layouts.dashboard')

@section('title', $student->nama_lengkap.' — RA Al Kautsar')
@section('header_title', 'Profil Murid')

@section('content')
<div class="space-y-8 max-w-3xl mx-auto" x-data="hashTabs('biodata')">

    <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-slate-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        <span>Kembali ke Dashboard</span>
    </a>

    {{-- Tab bar (daftar-ulang style) --}}
    <div class="flex gap-2 border-b border-slate-100">
        <button @click="setTab('biodata')" :class="tab === 'biodata' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="px-4 py-3 border-b-2 font-bold text-xs transition-colors">Profil Murid</button>
        <button @click="setTab('orang-tua')" :class="tab === 'orang-tua' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="px-4 py-3 border-b-2 font-bold text-xs transition-colors">Profil Orang Tua</button>
    </div>

    {{-- Tab: Profil Murid (biodata lengkap + alamat keluarga) --}}
    <div x-show="tab === 'biodata'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <div class="flex items-center gap-4">
            <x-avatar :path="$student->foto_path" :name="$student->nama_lengkap" size="w-20 h-20" />
            <h3 class="text-base font-bold text-slate-950">{{ $student->nama_lengkap }}</h3>
        </div>
        @php
            $biodata = [
                'Nama Panggilan' => $student->nama_panggilan ?? '—',
                'NIK' => $student->nik,
                'NISN' => $student->nisn ?? '—',
                'NIS' => $student->nis ?? '—',
                'Angkatan' => $student->angkatan ?? '—',
                'Jenis Kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'][$student->jenis_kelamin] ?? '-',
                'Tempat, Tgl Lahir' => $student->tempat_lahir.', '.optional($student->tanggal_lahir)->translatedFormat('d F Y'),
                'Agama' => $student->agama ?? '—',
                'Anak ke-' => $student->anak_ke ?? '—',
                'Jumlah Saudara' => $student->jumlah_saudara ?? '—',
                'Warga Negara' => $student->warga_negara ?? '—',
                'Bahasa Keseharian' => $student->bahasa_keseharian ?? '—',
                'Kondisi Kesehatan' => $student->kondisi_kesehatan ?? '—',
                'Ukuran Baju' => $student->ukuran_baju ?? '—',
                'Sudah Mengaji' => $student->sudah_mengaji ?? '—',
            ];
            if (($student->sudah_mengaji ?? '') === 'Sudah') {
                $biodata['Ngaji Di Mana'] = $student->ngaji_dimana ?? '—';
                $biodata['Metode Mengaji'] = $student->ngaji_metode ?? '—';
                $biodata['Jilid'] = $student->ngaji_jilid ?? '—';
            }
            $biodata['Pernah Belajar'] = $student->pernah_belajar ?? '—';
            if (($student->pernah_belajar ?? '') !== 'Belum' && ($student->pernah_belajar ?? '') !== null) {
                $biodata['Keterangan Belajar'] = $student->belajar_keterangan ?? '—';
            }
            $biodata += [
                'Kelas' => $student->schoolClass?->nama_kelas ?? 'Belum Terbagi',
                'Wali Kelas' => $student->schoolClass?->homeroomTeacher?->nama ?? '-',
                'Orang Tua' => $student->ortu?->namaWali() ?? '-',
                'Tahun Ajaran' => $student->academicYear?->tahun ?? '-',
                'Status' => ucfirst($student->status),
            ];
        @endphp
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs">
            @foreach ($biodata as $label => $val)
                <div>
                    <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">{{ $label }}</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ $val }}</dd>
                </div>
            @endforeach
        </dl>
        <div>
            <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">Alamat Keluarga</dt>
            <dd class="font-bold text-slate-800 mt-0.5">{{ $student->ortu ? trim(($student->ortu->alamat ?: '').' '.collect([$student->ortu->kelurahan_nama, $student->ortu->kecamatan_nama, $student->ortu->kota_nama, $student->ortu->provinsi_nama])->filter()->implode(', ')) : '-' }}</dd>
        </div>
    </div>

    {{-- Tab: Profil Orang Tua (lengkap tanpa alamat) --}}
    <div x-show="tab === 'orang-tua'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-950">Profil Orang Tua</h3>
        @if ($student->ortu)
            @php $g = $student->ortu; @endphp
            <div class="space-y-5">
                @foreach (['ibu' => 'Ibu', 'ayah' => 'Ayah'] as $p => $sebutan)
                    @if ($g->{"ada_$p"} && $g->{"{$p}_nama"})
                        <div>
                            <h4 class="text-3xs font-extrabold uppercase tracking-widest text-indigo-500 mb-2">Data {{ $sebutan }}</h4>
                            <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                                @foreach ([
                                    'Nama' => $g->{"{$p}_nama"} ?: '-',
                                    'No. HP' => $g->{"{$p}_no_hp"} ?: '-',
                                    'Tempat, Tgl Lahir' => trim(($g->{"{$p}_tempat_lahir"} ?: '').', '.optional($g->{"{$p}_tanggal_lahir"})->translatedFormat('d F Y'), ', ') ?: '-',
                                    'Agama' => $g->{"{$p}_agama"} ?: '-',
                                    'Pendidikan' => $g->{"{$p}_pendidikan"} ?: '-',
                                    'Pekerjaan' => ($g->{"{$p}_pekerjaan"} === 'LAINNYA' ? $g->{"{$p}_pekerjaan_lain"} : $g->{"{$p}_pekerjaan"}) ?: '-',
                                    'Penghasilan' => $g->{"{$p}_penghasilan"} ?: '-',
                                ] as $label => $val)
                                    <div>
                                        <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">{{ $label }}</dt>
                                        <dd class="font-bold text-slate-800 mt-0.5">{{ $val }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                @endforeach
                <div>
                    <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">Email Akun</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ $g->user?->email ?: '-' }}</dd>
                </div>
            </div>
        @else
            <p class="text-xs text-slate-400">Murid ini belum tertaut ke akun orang tua.</p>
        @endif
    </div>

</div>
@endsection
