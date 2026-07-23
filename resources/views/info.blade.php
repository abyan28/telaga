@extends('layouts.public')

@section('title', 'Informasi Pendaftaran — ' . (\App\Models\SiteContent::get('kontak.nama_sekolah', 'RA Al Kautsar')))

@section('content')
<!-- Header Page -->
<section class="bg-gradient-to-br from-sky-500 to-blue-600 text-white py-16 lg:py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-slate-900/10"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center space-y-4">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">Informasi Pendaftaran</h1>
        <p class="text-sky-100 max-w-xl mx-auto text-sm sm:text-base leading-relaxed">
            {{ $c['info.header_subjudul'] ?? '' }}
        </p>
    </div>
</section>

<!-- Biaya Pendidikan (nominal dari settings — sumber tunggal) -->
<section class="py-16 sm:py-24 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center space-y-3">
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Rincian Biaya Pendidikan</h2>
            <p class="text-slate-500 text-sm">Semua nominal biaya bersifat transparan tanpa biaya tambahan tersembunyi.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Biaya Pendaftaran -->
            <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="space-y-4">
                    <span class="text-xs font-bold text-sky-600 tracking-wider uppercase block">Fase Awal</span>
                    <h3 class="text-xl font-bold text-slate-900">Biaya Pendaftaran</h3>
                    <p class="text-xs text-slate-400 leading-normal">{{ $c['info.biaya_pendaftaran_desc'] ?? '' }}</p>
                    <div class="pt-4 border-t border-slate-200">
                        <span class="text-3xl font-black text-slate-900">Rp {{ number_format($biayaPendaftaran, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Biaya Daftar Ulang -->
            <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden">
                <div class="space-y-4">
                    <span class="text-xs font-bold text-sky-600 tracking-wider uppercase block">Fase Seleksi Lulus</span>
                    <h3 class="text-xl font-bold text-slate-900">Biaya Daftar Ulang</h3>
                    <p class="text-xs text-slate-400 leading-normal">{{ $c['info.biaya_daftar_ulang_desc'] ?? '' }}</p>
                    <div class="pt-4 border-t border-slate-200">
                        <span class="text-3xl font-black text-slate-900">Rp {{ number_format($biayaDaftarUlang, 0, ',', '.') }}</span>
                        <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-4xs font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wider">Bisa Dicicil</span>
                    </div>
                </div>
            </div>

            <!-- SPP Bulanan -->
            <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden">
                <div class="space-y-4">
                    <span class="text-xs font-bold text-sky-600 tracking-wider uppercase block">Murid Aktif</span>
                    <h3 class="text-xl font-bold text-slate-900">SPP Bulanan</h3>
                    <p class="text-xs text-slate-400 leading-normal">{{ $c['info.biaya_spp_desc'] ?? '' }}</p>
                    <div class="pt-4 border-t border-slate-200">
                        <span class="text-3xl font-black text-slate-900">Rp {{ number_format($biayaSpp, 0, ',', '.') }}</span>
                        <span class="text-slate-400 text-xs">/ bulan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Persyaratan & Alur (dinamis dari site_features) -->
<section class="py-16 sm:py-24 bg-slate-50 border-t border-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-start">

            <!-- Persyaratan -->
            <div class="space-y-6">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight border-b-2 border-sky-500 pb-2 inline-block">Persyaratan Dokumen</h3>
                <p class="text-slate-500 text-sm">Beberapa berkas digital yang wajib diunggah dalam format gambar (JPG) atau dokumen (PDF):</p>
                <ul class="space-y-3.5 text-sm text-slate-600">
                    @foreach ($persyaratan as $syarat)
                        <li class="flex items-center space-x-3">
                            <span class="w-6 h-6 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                            <span><strong>{{ $syarat->judul }}</strong>@if ($syarat->deskripsi) ({{ $syarat->deskripsi }})@endif</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Alur Pendaftaran -->
            <div class="space-y-6">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight border-b-2 border-sky-500 pb-2 inline-block">Alur Pendaftaran Online</h3>
                <div class="space-y-4 relative pl-6 border-l border-sky-200">
                    @foreach ($alur as $i => $langkah)
                        <div class="relative space-y-1">
                            <span class="absolute -left-9 top-0 w-6 h-6 bg-sky-500 text-white rounded-full flex items-center justify-center text-xs font-bold ring-4 ring-slate-50">{{ $i + 1 }}</span>
                            <h4 class="font-bold text-slate-900 text-sm">{{ $langkah->judul }}</h4>
                            <p class="text-slate-500 text-xs">{{ $langkah->deskripsi }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <div class="mt-16 text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-10 py-4 font-bold text-white bg-sky-600 hover:bg-sky-500 rounded-2xl shadow-xl shadow-sky-100 transition-all hover:-translate-y-0.5">
                Daftar Sekarang Secara Online
            </a>
        </div>
    </div>
</section>
@endsection
