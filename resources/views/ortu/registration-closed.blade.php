@extends('layouts.dashboard')

@section('title', 'Pendaftaran Ditutup — TELAGA AL KAUTSAR')
@section('header_title', 'Pendaftaran Murid Baru')

@section('content')
    <div class="max-w-lg mx-auto mt-10">
        <div class="bg-white border border-slate-100 rounded-[2rem] p-8 sm:p-10 shadow-sm text-center space-y-4">
            <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-600 mx-auto">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900">Pendaftaran Sedang Ditutup</h3>
            <p class="text-sm text-slate-500 leading-relaxed">
                Pendaftaran murid baru (PPDB) untuk saat ini belum dibuka oleh sekolah.
                Silakan kembali lagi nanti atau hubungi pihak sekolah untuk informasi jadwal pendaftaran.
            </p>
            <a href="{{ route('ortu.dashboard') }}" class="inline-block px-6 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md transition-all text-sm">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
@endsection
