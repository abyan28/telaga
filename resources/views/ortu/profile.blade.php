@extends('layouts.dashboard')

@section('title', 'Profil Orang Tua — TELAGA AL KAUTSAR')
@section('header_title', 'Profil Orang Tua')

@section('content')
@php
    // Opsi dropdown (L1.1). Value = label (uppercase mengikuti UppercaseInput di-skip? tidak — enum ini disimpan apa adanya).
    $agamaOpsi = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU'];
    $pendidikanOpsi = ['S3', 'S2', 'S1', 'D4', 'D3', 'D2', 'D1', 'SMA', 'SMP', 'SD'];
    $pekerjaanOpsi = ['PNS', 'SWASTA', 'PEDAGANG', 'TNI', 'POLRI', 'GURU', 'LAINNYA'];
    $penghasilanOpsi = ['< 1 JUTA', '1 - 2 JUTA', '3 - 5 JUTA', '> 5 JUTA'];
    // helper prefill
    $v = fn ($key) => old($key, $ortu?->{$key});
@endphp

<div class="max-w-2xl mx-auto space-y-6">
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('ortu.profile.update') }}" method="POST"
          x-data="{ adaAyah: {{ old('ada_ayah', $ortu?->ada_ayah ?? true) ? 'true' : 'false' }}, adaIbu: {{ old('ada_ibu', $ortu?->ada_ibu ?? true) ? 'true' : 'false' }} }"
          class="space-y-6">
        @csrf

        @foreach (['ibu' => 'Data Ibu', 'ayah' => 'Data Ayah'] as $p => $judul)
            <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="ada_{{ $p }}" value="1" x-model="ada{{ ucfirst($p) }}"
                           class="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span class="text-base font-bold text-slate-950">{{ $judul }}</span>
                    <span class="text-3xs text-slate-400">(hilangkan centang bila tidak ada)</span>
                </label>

                <div x-show="ada{{ ucfirst($p) }}" x-cloak class="space-y-4"
                     x-data="{ pekerjaan: '{{ $v($p.'_pekerjaan') }}' }">
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Lengkap</label>
                        <input type="text" name="{{ $p }}_nama" value="{{ $v($p.'_nama') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tempat Lahir</label>
                            <input type="text" name="{{ $p }}_tempat_lahir" value="{{ $v($p.'_tempat_lahir') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Lahir</label>
                            <input type="date" name="{{ $p }}_tanggal_lahir" value="{{ old($p.'_tanggal_lahir', optional($ortu?->{$p.'_tanggal_lahir'})->format('Y-m-d')) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Agama</label>
                            <select name="{{ $p }}_agama" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                                <option value="">-</option>
                                @foreach ($agamaOpsi as $o)<option value="{{ $o }}" @selected($v($p.'_agama') === $o)>{{ $o }}</option>@endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pendidikan Terakhir</label>
                            <select name="{{ $p }}_pendidikan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                                <option value="">-</option>
                                @foreach ($pendidikanOpsi as $o)<option value="{{ $o }}" @selected($v($p.'_pendidikan') === $o)>{{ $o }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pekerjaan</label>
                            <select name="{{ $p }}_pekerjaan" x-model="pekerjaan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                                <option value="">-</option>
                                @foreach ($pekerjaanOpsi as $o)<option value="{{ $o }}" @selected($v($p.'_pekerjaan') === $o)>{{ $o }}</option>@endforeach
                            </select>
                        </div>
                        <div class="space-y-1" x-show="pekerjaan === 'LAINNYA'" x-cloak>
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pekerjaan (Lainnya)</label>
                            <input type="text" name="{{ $p }}_pekerjaan_lain" :required="pekerjaan === 'LAINNYA'" value="{{ $v($p.'_pekerjaan_lain') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Penghasilan Bulanan</label>
                            <select name="{{ $p }}_penghasilan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                                <option value="">-</option>
                                @foreach ($penghasilanOpsi as $o)<option value="{{ $o }}" @selected($v($p.'_penghasilan') === $o)>{{ $o }}</option>@endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">No. HP</label>
                            <input type="text" name="{{ $p }}_no_hp" inputmode="numeric" pattern="[0-9]{9,14}" value="{{ $v($p.'_no_hp') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Alamat keluarga (1 set) — dropdown wilayah berjenjang. --}}
        <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-slate-950">Alamat Keluarga</h3>
            <p class="text-xs text-slate-400">Diisi sekali — dipakai untuk semua anak yang didaftarkan.</p>
            @include('ortu._alamat-picker', ['g' => $ortu])
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Alamat Lengkap (Jalan, RT/RW, No. Rumah)</label>
                <textarea name="alamat" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">{{ old('alamat', $ortu?->alamat) }}</textarea>
            </div>
        </div>

        <button type="submit" class="w-full py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Profil</button>
    </form>
</div>
@endsection
