@extends('layouts.public')

@section('title', (\App\Models\SiteContent::get('kontak.nama_sekolah', 'RA Al Kautsar')))

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-sky-50 via-white to-blue-50/50 py-20 lg:py-32 overflow-hidden">
    <div class="absolute top-1/4 left-10 w-72 h-72 bg-sky-300 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-blob"></div>
    <div class="absolute bottom-1/4 right-10 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-blob" style="animation-delay: 2s;"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">

            <!-- Left Info Column -->
            <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 tracking-wide uppercase">
                    {{ $c['home.badge'] ?? 'Penerimaan Murid Baru Online' }}
                </span>
                @php
                    $heroTitle = $c['home.hero_title'] ?? 'Membentuk Generasi Islami & Mandiri Sejak Dini';
                    $highlight = $c['home.hero_highlight'] ?? '';
                @endphp
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    @if ($highlight && str_contains($heroTitle, $highlight))
                        {!! str_replace(e($highlight), '<span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-600 to-blue-600">'.e($highlight).'</span>', e($heroTitle)) !!}
                    @else
                        {{ $heroTitle }}
                    @endif
                </h1>
                <p class="text-base sm:text-lg text-slate-600 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    {{ $c['home.hero_subtitle'] ?? '' }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start space-y-3 sm:space-y-0 sm:space-x-4 pt-4">
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-sky-600 hover:bg-sky-500 rounded-2xl shadow-xl shadow-sky-200 hover:shadow-sky-300 transition-all hover:-translate-y-0.5">
                        Daftar Murid Baru
                    </a>
                    <a href="{{ route('info') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl shadow-xs transition-all hover:-translate-y-0.5">
                        Informasi Biaya
                    </a>
                </div>
            </div>

            <!-- Right Visual Column: Curriculum Highlight slider (T7.1, dinamis dari site_features grup 'kurikulum') -->
            <div class="lg:col-span-5 relative flex justify-center">
                <div class="relative w-full max-w-md aspect-square bg-gradient-to-tr from-sky-400 to-blue-500 rounded-[3rem] shadow-2xl overflow-hidden rotate-2 transform hover:rotate-0 transition-transform duration-500"
                     @if ($kurikulum->count() > 1)
                     x-data="{ i: 0, n: {{ $kurikulum->count() }} }"
                     x-init="setInterval(() => i = (i + 1) % n, 4000)"
                     @else x-data="{ i: 0 }" @endif>

                    @forelse ($kurikulum as $idx => $slide)
                        {{-- Tiap slide: foto (bila ada) + overlay caption; hanya slide aktif yang tampil (fade). --}}
                        <div x-show="i === {{ $idx }}" x-transition.opacity.duration.700ms class="absolute inset-0">
                            @if ($slide->foto_path)
                                <img src="{{ asset('storage/'.$slide->foto_path) }}" alt="{{ $slide->judul }}" class="absolute inset-0 w-full h-full object-cover">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-900/80 via-blue-900/20 to-transparent"></div>
                            <div class="absolute inset-0 flex flex-col justify-between p-8 text-white">
                                <span class="self-start text-3xs uppercase font-extrabold tracking-widest px-3 py-1 bg-white/20 backdrop-blur-md rounded-full">
                                    Curriculum Highlight
                                </span>
                                <div>
                                    <h3 class="text-2xl font-extrabold mb-1 leading-tight">{{ $slide->judul }}</h3>
                                    @if ($slide->deskripsi)
                                        <span class="text-3xs text-slate-100/90 block font-medium">{{ $slide->deskripsi }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        {{-- Fallback bila admin belum mengisi slide (jaga tampilan tetap penuh). --}}
                        <div class="absolute inset-0 flex flex-col justify-between p-8 text-white">
                            <span class="self-start text-3xs uppercase font-extrabold tracking-widest px-3 py-1 bg-white/20 backdrop-blur-md rounded-full">
                                Curriculum Highlight
                            </span>
                            <div>
                                <span class="text-slate-100/80 text-xs block mb-1">Materi Unggulan</span>
                                <h3 class="text-2xl font-extrabold leading-tight">{{ $c['home.kurikulum_judul'] ?? 'Tahfidz Quran & Doa Harian Anak' }}</h3>
                            </div>
                        </div>
                    @endforelse

                    {{-- Dot indicator (hanya bila >1 slide). --}}
                    @if ($kurikulum->count() > 1)
                        <div class="absolute bottom-4 right-6 flex gap-1.5 z-10">
                            @foreach ($kurikulum as $idx => $slide)
                                <button @click="i = {{ $idx }}" :class="i === {{ $idx }} ? 'bg-white w-4' : 'bg-white/50 w-1.5'" class="h-1.5 rounded-full transition-all"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Stats Grid (dinamis dari site_features grup 'statistik') -->
<section class="bg-white border-y border-slate-100 py-12 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            @foreach ($statistik as $stat)
                <div class="space-y-1">
                    <span class="text-3xl sm:text-4xl font-extrabold text-sky-600 block">{{ $stat->judul }}</span>
                    <span class="text-xs font-bold tracking-wider text-slate-400 uppercase">{{ $stat->deskripsi }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Program & Fasilitas (dinamis dari site_features grup 'program') -->
<section class="py-20 lg:py-28 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center space-y-4 max-w-2xl mx-auto mb-16">
            <h2 class="text-xs font-bold tracking-widest text-sky-600 uppercase">Unggulan & Fasilitas</h2>
            <p class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">{{ $c['home.mengapa_judul'] ?? 'Mengapa Memilih Kami?' }}</p>
            <p class="text-slate-500 text-sm sm:text-base">{{ $c['home.mengapa_subjudul'] ?? '' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach ($program as $p)
                <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-xs hover:shadow-lg transition-all duration-300 group hover:-translate-y-1">
                    <div class="w-14 h-14 bg-sky-50 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-sky-500 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">{{ $p->judul }}</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">{{ $p->deskripsi }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="bg-slate-900 py-20 lg:py-28 relative overflow-hidden">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-sky-500 rounded-full mix-blend-screen filter blur-3xl opacity-15"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8 relative">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
            {{ $c['home.cta_judul'] ?? 'Siap Bergabung?' }}
        </h2>
        <p class="text-slate-300 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
            {{ $c['home.cta_teks'] ?? '' }}
        </p>
        <div class="pt-4 flex flex-col sm:flex-row justify-center items-center space-y-3 sm:space-y-0 sm:space-x-4">
            <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-bold text-slate-900 bg-sky-400 hover:bg-sky-300 rounded-2xl shadow-xl transition-all hover:-translate-y-0.5">
                Daftar Sekarang
            </a>
            <a href="{{ route('profile') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white border border-slate-700 hover:border-slate-500 rounded-2xl transition-all hover:-translate-y-0.5">
                Pelajari Profil RA
            </a>
        </div>
    </div>
</section>
@endsection
