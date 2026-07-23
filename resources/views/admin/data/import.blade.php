@extends('layouts.dashboard')

@section('title', 'Import CSV Murid — RA Al Kautsar')
@section('header_title', 'Import Data Murid dari CSV')

@section('content')
<div class="space-y-8">

    {{-- Success --}}
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Hasil import --}}
    @isset($ok)
        <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-slate-950">Hasil Import</h3>
            <div class="flex flex-wrap gap-4 text-sm">
                <span class="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl font-bold">✓ {{ $ok }} sukses</span>
                <span class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold">⏭ {{ $skip }} sudah ada</span>
                <span class="px-4 py-2 bg-rose-50 text-rose-700 rounded-xl font-bold">✗ {{ count($errors) }} gagal</span>
            </div>
            @if ($errors)
                <div class="text-xs text-rose-700 space-y-1 max-h-60 overflow-y-auto bg-rose-50 rounded-xl p-4">
                    @foreach ($errors as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
                <p class="text-xs text-slate-500">Perbaiki baris yang gagal di file CSV, lalu upload ulang untuk mengimpornya. Data yang sudah berhasil tidak perlu dihapus — sistem akan melewati NIK yang sudah ada.</p>
            @endif
            <a href="{{ route('admin.students.import') }}" class="inline-block px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Import Lagi</a>
        </div>
    @endisset

    {{-- Form upload --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h3 class="text-base font-bold text-slate-950">Upload File CSV</h3>
            <a href="{{ route('admin.students.import.template') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">📥 Unduh Template</a>
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.students.import.run') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih file CSV</label>
                <input type="file" name="file" accept=".csv,.txt" required
                    class="block w-full text-xs text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-2xs text-slate-400 mt-1.5">Format CSV (koma). Gunakan template di atas sebagai acuan. Ukuran maksimal 5 MB.</p>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">Import</button>
        </form>
    </div>

    {{-- Info kolom --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h4 class="text-sm font-bold text-slate-950">Informasi Kolom CSV</h4>
        <div class="overflow-x-auto text-xs text-slate-600">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-2 pr-4">Kolom</th>
                        <th class="py-2 pr-4">Wajib</th>
                        <th class="py-2">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr><td class="py-2 pr-4 font-mono">nama_lengkap</td><td class="py-2 pr-4">✓</td><td class="py-2">Nama lengkap murid</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">nama_panggilan</td><td class="py-2 pr-4">✓</td><td class="py-2">Nama panggilan murid</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">nik</td><td class="py-2 pr-4">✓</td><td class="py-2">16 digit, unik</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">nis</td><td class="py-2 pr-4"></td><td class="py-2">15–18 digit, unik</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">nisn</td><td class="py-2 pr-4"></td><td class="py-2">10 digit, unik</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">jenis_kelamin</td><td class="py-2 pr-4">✓</td><td class="py-2">L / P</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">agama</td><td class="py-2 pr-4"></td><td class="py-2">Default ISLAM bila kosong</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">tempat_lahir</td><td class="py-2 pr-4">✓</td><td class="py-2">Contoh: BANDUNG</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">tanggal_lahir</td><td class="py-2 pr-4">✓</td><td class="py-2">Format YYYY-MM-DD</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">anak_ke</td><td class="py-2 pr-4">✓</td><td class="py-2">Angka, min 1</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">jumlah_saudara</td><td class="py-2 pr-4">✓</td><td class="py-2">Angka, min 0</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">warga_negara</td><td class="py-2 pr-4">✓</td><td class="py-2">Contoh: INDONESIA</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">bahasa_keseharian</td><td class="py-2 pr-4">✓</td><td class="py-2">Contoh: SUNDA</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">kondisi_kesehatan</td><td class="py-2 pr-4">✓</td><td class="py-2">Tulis TIDAK ADA bila sehat</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">tahun_ajaran</td><td class="py-2 pr-4"></td><td class="py-2">Format 2026/2027; default TA aktif</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">status</td><td class="py-2 pr-4"></td><td class="py-2">calon / aktif / alumni / nonaktif; default aktif</td></tr>
                    <tr><td class="py-2 pr-4 font-mono">no_hp_ortu</td><td class="py-2 pr-4"></td><td class="py-2">Diisi → auto-buat akun ortu (sandi = NIK anak)</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endSection
