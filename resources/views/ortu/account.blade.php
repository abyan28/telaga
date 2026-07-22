@extends('layouts.dashboard')

@section('title', 'Pengaturan Akun — RA Al Kautsar')
@section('header_title', 'Pengaturan Akun')

@section('content')
<div class="max-w-md mx-auto space-y-6">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Pengaturan Akun</h3>
            <p class="text-xs text-slate-400 mt-1">Perbarui username, email & nomor HP. Ketiganya bisa dipakai untuk login.</p>
        </div>

        <form action="{{ route('ortu.account.update') }}" method="POST" class="space-y-4" x-data="{
            username: '{{ addslashes($user->username ?? '') }}',
            uStatus: '',
            uMsg: '',
            async checkU(e) {
                const v = e.target.value;
                this.username = v;
                if (v.length < 3) { this.uStatus = ''; this.uMsg = 'Minimal 3 karakter.'; return; }
                if (!/^[a-zA-Z0-9_.]+$/.test(v)) { this.uStatus = 'invalid'; this.uMsg = 'Hanya huruf, angka, _ dan .'; return; }
                this.uStatus = 'checking'; this.uMsg = 'Mengecek…';
                try {
                    const r = await fetch('/register/check-username?username=' + encodeURIComponent(v) + '&ignore={{ $user->id_users }}');
                    const d = await r.json();
                    this.uStatus = d.available ? 'avail' : 'taken';
                    this.uMsg = d.available ? 'Tersedia ✓' : 'Sudah digunakan ✗';
                } catch { this.uStatus = ''; this.uMsg = ''; }
            }
        }">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Username</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}"
                       @input.debounce.500ms="checkU"
                       :class="{'border-sky-500': uStatus === 'avail', 'border-rose-400': uStatus === 'taken' || uStatus === 'invalid'}"
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                <p class="text-4xs mt-0.5" :class="{
                    'text-emerald-600': uStatus === 'avail',
                    'text-rose-600': uStatus === 'taken' || uStatus === 'invalid',
                    'text-slate-400': uStatus === 'checking' || uStatus === ''
                }" x-text="uMsg"></p>
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Email</label>
                <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nomor HP</label>
                <input type="text" name="no_hp" inputmode="numeric" pattern="[0-9]{9,14}" value="{{ old('no_hp', $user->no_hp) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
            </div>
            <button type="submit" :disabled="uStatus === 'taken' || uStatus === 'invalid'"
                    class="w-full py-3 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Perubahan</button>
        </form>
    </div>

</div>
@endsection
