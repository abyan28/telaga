@extends('layouts.public')

@section('title', 'Lupa Kata Sandi — RA Al Kautsar')

@section('content')
<main class="flex-1 flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 py-16">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-[2rem] p-8 md:p-10 shadow-xl shadow-slate-200/50 border border-slate-100">
            <div class="text-center mb-8">
                <h1 class="text-xl font-extrabold text-slate-950 tracking-tight">Lupa Kata Sandi?</h1>
                <p class="text-xs text-slate-400 mt-2">Masukkan email yang terdaftar. Kami akan mengirim link untuk mengatur ulang kata sandi Anda.</p>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs text-emerald-700 font-semibold">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-600 font-semibold">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div class="space-y-1">
                    <label for="email" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" required
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all">
                </div>
                <button type="submit" class="w-full py-4 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-lg shadow-sky-100 hover:shadow-sky-200 transition-all hover:-translate-y-0.5">
                    Kirim Link Reset
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-xs font-bold text-sky-600 hover:text-sky-700">&lsaquo; Kembali ke Login</a>
            </div>
        </div>
    </div>
</main>
@endsection
