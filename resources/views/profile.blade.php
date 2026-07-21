@extends('layouts.public')

@section('title', 'Profil Sekolah — ' . (\App\Models\SiteContent::get('kontak.nama_sekolah', 'RA Al Kautsar')))

@section('content')
<!-- Header Page -->
<section class="bg-gradient-to-br from-sky-500 to-blue-600 text-white py-16 lg:py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-slate-900/10"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center space-y-4">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">Profil Sekolah</h1>
        <p class="text-sky-100 max-w-xl mx-auto text-sm sm:text-base leading-relaxed">
            {{ $c['profile.header_subjudul'] ?? '' }}
        </p>
    </div>
</section>

<!-- Sejarah Section -->
<section class="py-16 sm:py-24 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
            <div class="md:col-span-4">
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight border-b-4 border-sky-500 pb-3 inline-block">
                    Sejarah RA
                </h2>
            </div>
            <div class="md:col-span-8 space-y-4 text-slate-600 text-sm sm:text-base leading-relaxed">
                @forelse (preg_split('/\n\n+/', (string) ($c['profile.sejarah'] ?? '')) as $paragraf)
                    @if (trim($paragraf) !== '')
                        <p>{{ trim($paragraf) }}</p>
                    @endif
                @empty
                @endforelse
            </div>
        </div>
    </div>
</section>

<!-- Visi & Misi Section -->
<section class="py-16 sm:py-24 bg-slate-50 border-y border-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <!-- Visi -->
            <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-xs space-y-4">
                <div class="w-12 h-12 bg-sky-100 text-sky-600 rounded-xl flex items-center justify-center font-bold text-xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">Visi Kami</h3>
                <p class="text-slate-500 text-sm leading-relaxed">
                    "{{ $c['profile.visi'] ?? '' }}"
                </p>
            </div>

            <!-- Misi (dinamis dari site_features grup 'misi') -->
            <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-xs space-y-4">
                <div class="w-12 h-12 bg-sky-100 text-sky-600 rounded-xl flex items-center justify-center font-bold text-xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">Misi Kami</h3>
                <ul class="text-slate-500 text-sm space-y-2.5 list-disc list-inside">
                    @foreach ($misi as $m)
                        <li>{{ $m->judul }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Tenaga Pendidik (dari tabel teachers, tampil_di_web = true) -->
<section class="py-16 sm:py-24 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center space-y-3">
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tenaga Pendidik Kami</h2>
            <p class="text-slate-500 text-sm">Dipimpin oleh Ustadz dan Ustadzah pilihan yang berdedikasi tinggi.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            @forelse ($guru as $g)
                @php
                    $nama = $g->nama ?? '-';
                    $inisial = \Illuminate\Support\Str::of($nama)->explode(' ')->take(2)->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->implode('');
                @endphp
                <div class="bg-slate-50 rounded-2xl p-6 text-center border border-slate-100 space-y-3">
                    @if ($g->foto_path)
                        <img src="{{ asset('storage/'.$g->foto_path) }}" alt="{{ $nama }}" class="w-20 h-20 rounded-full object-cover mx-auto">
                    @else
                        <div class="w-20 h-20 bg-sky-100 text-sky-700 rounded-full flex items-center justify-center text-xl font-bold mx-auto uppercase">
                            {{ $inisial }}
                        </div>
                    @endif
                    <div>
                        <h4 class="font-bold text-slate-900">{{ $nama }}</h4>
                        <span class="text-xs text-sky-600 font-medium block mt-0.5">{{ $g->jabatan ?? 'Tenaga Pendidik' }}</span>
                    </div>
                </div>
            @empty
                <p class="col-span-3 text-center text-slate-400 text-sm">Data tenaga pendidik belum tersedia.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
