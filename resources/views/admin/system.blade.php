@extends('layouts.dashboard')

@section('title', 'Pengaturan Sistem — RA Al Kautsar')
@section('header_title', 'Pengaturan Sistem')

@section('content')
{{-- L2.1: NSM, Refund, Tahun Ajaran (pindahan dari Set Pembayaran). --}}
<div class="max-w-2xl mx-auto space-y-8"
     x-data="Object.assign(hashTabs('nsm'), { modal: null, tujuan: '', scope: '', arah: '',
        buka(s, a, t) { this.scope = s; this.arah = a; this.tujuan = t; this.modal = true; } })">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Tab bar --}}
    <div class="flex gap-2 border-b border-slate-100 overflow-x-auto">
        <button @click="setTab('nsm')" :class="tab === 'nsm' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">Nomor Statistik Madrasah</button>
        <button @click="setTab('refund')" :class="tab === 'refund' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">Refund Pembatalan</button>
        <button @click="setTab('tahun')" :class="tab === 'tahun' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">Tahun Ajaran</button>
    </div>

    {{-- Tab: NSM --}}
    <div x-show="tab === 'nsm'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Nomor Statistik Madrasah (NSM)</h3>
            <p class="text-xs text-slate-400 leading-normal mt-1">Dipakai sebagai prefiks saat generate Nomor Induk Siswa (NIS). Format: 12 digit angka.</p>
        </div>
        <form action="{{ route('admin.system.update') }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NSM Sekolah</label>
                <input type="text" name="nsm_sekolah" inputmode="numeric" pattern="[0-9]{12}" maxlength="12"
                       value="{{ old('nsm_sekolah', $settings['nsm_sekolah']) }}"
                       placeholder="12 digit, contoh: 101234567890"
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-indigo-500">
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan NSM</button>
        </form>
    </div>

    {{-- Tab: Refund --}}
    <div x-show="tab === 'refund'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Persentase Denda Pembatalan DU</h3>
            <p class="text-xs text-slate-400 leading-normal mt-1">
                Denda pembatalan daftar ulang = <span class="font-bold text-slate-600">X%</span> dari total biaya DU.
                Refund = terbayar &minus; X%. Bila terbayar &lt; X% → wajib lunasi kekurangan dulu (tidak ada refund).
            </p>
        </div>
        <form action="{{ route('admin.system.update') }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Persentase Denda (%)</label>
                <div class="relative">
                    <input type="number" name="persen_refund" min="0" max="100"
                           value="{{ old('persen_refund', $settings['persen_refund']) }}"
                           class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:border-indigo-500">
                    <span class="absolute right-4 top-3 text-slate-400 font-bold text-sm">%</span>
                </div>
                <p class="text-3xs text-slate-400">Contoh: nilai 30 → denda 30%, refund = terbayar &minus; 30% total DU.</p>
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Persentase</button>
        </form>
    </div>

    {{-- Tab: Tahun Ajaran (dipindah dari Set Pembayaran) --}}
    <div x-show="tab === 'tahun'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-5">
        <div>
            <h3 class="text-base font-bold text-slate-950">Tahun Ajaran</h3>
            <p class="text-xs text-slate-400 leading-normal mt-1">TA Sistem & TA PPDB independen — geser masing-masing dengan panah.</p>
        </div>
        <div class="flex items-center justify-between gap-3">
            <div>
                <span class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tahun Ajaran Sistem Aktif</span>
                <p class="text-lg font-bold text-slate-900 mt-0.5">{{ $taAktif?->tahun ?? '-' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="buka('aktif','prev','{{ $prevAktif?->tahun ?? '' }}')"
                        @if (! $prevAktif) disabled @endif
                        class="w-10 h-10 rounded-xl border border-slate-200 font-bold text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors">&lsaquo;</button>
                <button type="button" @click="buka('aktif','next','{{ $taAktif?->tahun ?? '' }}')"
                        class="w-10 h-10 rounded-xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition-colors">&rsaquo;</button>
            </div>
        </div>
        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
            <div>
                <span class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tahun Ajaran PPDB</span>
                <p class="text-lg font-bold text-slate-900 mt-0.5">{{ $taPpdb?->tahun ?? '-' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="buka('ppdb','prev','{{ $prevPpdb?->tahun ?? '' }}')"
                        @if (! $prevPpdb) disabled @endif
                        class="w-10 h-10 rounded-xl border border-slate-200 font-bold text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors">&lsaquo;</button>
                <button type="button" @click="buka('ppdb','next','{{ $taPpdb?->tahun ?? '' }}')"
                        class="w-10 h-10 rounded-xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50 transition-colors">&rsaquo;</button>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi navigasi TA (reuse dari settings.blade) --}}
    <div x-show="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
        <div class="bg-white rounded-[2.5rem] p-8 max-w-sm w-full border border-slate-100 shadow-2xl space-y-6" @click.away="modal = false">
            <div class="space-y-1">
                <h3 class="text-lg font-bold text-slate-950">Ubah Tahun Ajaran?</h3>
                <p class="text-xs text-slate-500">
                    Anda akan mengarahkan
                    <span class="font-bold text-slate-800" x-text="scope === 'aktif' ? 'Tahun Ajaran Sistem' : 'Tahun Ajaran PPDB'"></span>
                    ke <span class="font-bold text-slate-800" x-text="tujuan"></span>.
                    Yakin lanjutkan?
                </p>
            </div>
            <div class="flex space-x-3">
                <button type="button" @click="modal = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                <form action="{{ route('admin.settings.academic-year') }}" method="POST" class="flex-1" @submit="modal = false">
                    @csrf
                    <input type="hidden" name="scope" :value="scope">
                    <input type="hidden" name="arah" :value="arah">
                    <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs">Lanjutkan</button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
