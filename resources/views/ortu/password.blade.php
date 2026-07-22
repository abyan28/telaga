@extends('layouts.dashboard')

@section('title', 'Ganti Password — RA Al Kautsar')
@section('header_title', 'Ganti Password')

@section('content')
<div class="max-w-md mx-auto space-y-6">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    @if (auth()->user()->must_change_password)
        <div class="bg-amber-50 border border-amber-100 text-amber-800 rounded-2xl px-5 py-3 text-xs font-semibold">
            Password awal Anda = Nomor HP. Demi keamanan, wajib diganti sebelum melanjutkan.
        </div>
    @endif

    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Ganti Password</h3>
            <p class="text-xs text-slate-400 mt-1">Password awal = Nomor HP. Ganti dengan password pribadi Anda.</p>
        </div>

        <form action="{{ route('ortu.password.update') }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Password Saat Ini</label>
                <x-password-input name="current_password" :required="true" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500" />
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Password Baru (min 8)</label>
                <x-password-input name="password" :required="true" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500" />
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Konfirmasi Password Baru</label>
                <x-password-input name="password_confirmation" :required="true" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500" />
            </div>
            <button type="submit" class="w-full py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Password Baru</button>
        </form>
    </div>

</div>
@endsection
