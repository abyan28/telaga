@extends('layouts.dashboard')

@section('title', 'Set Pembayaran — RA Al Kautsar')
@section('header_title', 'Set Pembayaran & Biaya')

@section('content')
@php
    $nominals = [
        'nominal_pendaftaran' => 'Biaya Pendaftaran',
        'nominal_daftar_ulang' => 'Biaya Daftar Ulang',
        'nominal_spp' => 'SPP per Bulan',
        'tanggal_generate_spp' => 'Tanggal Generate SPP Otomatis (1–28)',
    ];
@endphp

{{-- Tab: Nominal → Rekening → Generate SPP. --}}
<div class="max-w-2xl mx-auto space-y-8" x-data="hashTabs('nominal')">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Tab bar --}}
    <div class="flex gap-2 border-b border-slate-100 overflow-x-auto">
        <button @click="setTab('nominal')" :class="tab === 'nominal' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">Nominal Biaya Dinamis</button>
        <button @click="setTab('rekening')" :class="tab === 'rekening' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">Rekening Bank Sekolah</button>
        <button @click="setTab('generate')" :class="tab === 'generate' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">Generate SPP</button>
    </div>

    {{-- Tab: Nominal biaya — dropdown pilih 1 field, hanya yang dipilih tersimpan --}}
    <div x-show="tab === 'nominal'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Nominal Biaya Dinamis</h3>
            <p class="text-xs text-slate-400 leading-normal mt-1">Pilih item yang ingin diubah, lalu simpan. Nilai dipakai di halaman Info publik & saat menerbitkan tagihan.</p>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4"
              x-data="{ field: 'nominal_pendaftaran', values: {{ Js::from(collect($nominals)->keys()->mapWithKeys(fn ($k) => [$k => $settings[$k]])) }} }">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Item Biaya</label>
                <select x-model="field" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:border-indigo-500">
                    @foreach ($nominals as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nilai</label>
                <div class="relative">
                    <span x-show="field.startsWith('nominal_')" class="absolute left-4 top-3 text-slate-400 font-bold text-sm">Rp</span>
                    <input type="number" :name="field" x-model="values[field]" min="0"
                           :class="field.startsWith('nominal_') ? 'pl-10' : 'pl-4'"
                           class="w-full pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Item Terpilih</button>
        </form>
    </div>

    {{-- Tab: Rekening bank sekolah (T5.6g / T2.2) --}}
    <div x-show="tab === 'rekening'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Rekening Bank Sekolah</h3>
            <p class="text-xs text-slate-400 leading-normal mt-1">Ditampilkan ke orang tua murid sebagai rekening tujuan transfer pembayaran.</p>
        </div>
        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
            @csrf
            @php
                $bankFields = [
                    'bank_sekolah' => ['Nama Bank', 'Bank Mandiri'],
                    'rekening_sekolah' => ['Nomor Rekening', '123-456-789'],
                    'atas_nama' => ['Atas Nama', 'RA Al Kautsar'],
                ];
            @endphp
            @foreach ($bankFields as $key => [$label, $ph])
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">{{ $label }}</label>
                    <input type="text" name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" placeholder="{{ $ph }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-indigo-500">
                </div>
            @endforeach
            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Rekening</button>
        </form>
    </div>

    {{-- Tab: Generate SPP manual (cadangan bila cron belum aktif — rules.md §1.3.5) --}}
    <div x-show="tab === 'generate'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <div>
            <h3 class="text-base font-bold text-slate-950">Generate Tagihan SPP Bulan Ini</h3>
            <p class="text-xs text-slate-400 leading-normal mt-1">Terbitkan tagihan SPP untuk seluruh murid aktif bulan berjalan (idempotent, aman dijalankan berulang).</p>
        </div>
        <form action="{{ route('admin.settings.generate-spp') }}" method="POST" onsubmit="return confirm('Terbitkan tagihan SPP bulan ini untuk semua murid aktif?')">
            @csrf
            <button type="submit" class="px-5 py-2.5 border border-indigo-200 hover:bg-indigo-50 text-indigo-600 font-bold rounded-xl text-xs transition-colors">Generate Sekarang</button>
        </form>
    </div>

</div>
@endsection
