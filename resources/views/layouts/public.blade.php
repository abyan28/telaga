<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RA Al Kautsar — Sistem Informasi Sekolah')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-100 shadow-sm" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    @if (!empty($siteLogo))
                        <img src="{{ asset('storage/'.$siteLogo) }}" alt="Logo" class="w-12 h-12 rounded-2xl object-cover shadow-md shadow-sky-200">
                    @else
                        <div class="w-12 h-12 bg-sky-500 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-md shadow-sky-200">
                            AK
                        </div>
                    @endif
                    <div>
                        <span class="text-lg font-bold tracking-tight text-slate-900 block leading-tight">RA AL KAUTSAR</span>
                        <span class="text-xs text-sky-600 font-medium tracking-wider uppercase block">Islamic Kids School</span>
                    </div>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('home') }}" class="text-sm font-semibold {{ request()->routeIs('home') ? 'text-sky-600' : 'text-slate-600 hover:text-sky-600' }} transition-colors">Beranda</a>
                    <a href="{{ route('profile') }}" class="text-sm font-semibold {{ request()->routeIs('profile') ? 'text-sky-600' : 'text-slate-600 hover:text-sky-600' }} transition-colors">Profil Sekolah</a>
                    <a href="{{ route('info') }}" class="text-sm font-semibold {{ request()->routeIs('info') ? 'text-sky-600' : 'text-slate-600 hover:text-sky-600' }} transition-colors">Info Pendaftaran</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-500 rounded-xl shadow-lg shadow-sky-100 transition-all hover:-translate-y-0.5">
                        Portal / Login
                    </a>
                </nav>

                <!-- Mobile Menu Button -->
                <div class="flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-500 hover:text-sky-600 focus:outline-none p-2 rounded-lg">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-b border-slate-100 bg-white" x-cloak>
            <div class="px-2 pt-2 pb-4 space-y-1 sm:px-3 shadow-inner">
                <a href="{{ route('home') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('home') ? 'bg-sky-50 text-sky-600' : 'text-slate-600 hover:bg-slate-50' }} transition-colors">Beranda</a>
                <a href="{{ route('profile') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('profile') ? 'bg-sky-50 text-sky-600' : 'text-slate-600 hover:bg-slate-50' }} transition-colors">Profil Sekolah</a>
                <a href="{{ route('info') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('info') ? 'bg-sky-50 text-sky-600' : 'text-slate-600 hover:bg-slate-50' }} transition-colors">Info Pendaftaran</a>
                <a href="{{ route('login') }}" class="block w-full text-center px-4 py-3 mt-2 rounded-xl text-base font-semibold text-white bg-sky-600 hover:bg-sky-500 shadow-md">
                    Portal / Login
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <!-- About / Identity -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        @if (!empty($siteLogo))
                            <img src="{{ asset('storage/'.$siteLogo) }}" alt="Logo" class="w-10 h-10 rounded-xl object-cover">
                        @else
                            <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                AK
                            </div>
                        @endif
                        <span class="text-lg font-bold tracking-tight text-white">RA AL KAUTSAR</span>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Pendidikan anak usia dini berbasis nilai-nilai keislaman yang membentuk akhlak mulia, kemandirian, dan kecerdasan anak sejak usia dini.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-4">Navigasi Cepat</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('home') }}" class="hover:text-sky-400 transition-colors">Beranda</a></li>
                        <li><a href="{{ route('profile') }}" class="hover:text-sky-400 transition-colors">Profil Sekolah</a></li>
                        <li><a href="{{ route('info') }}" class="hover:text-sky-400 transition-colors">Info Pendaftaran</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-sky-400 transition-colors">Portal Dashboard</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="space-y-3 text-sm">
                    <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-4">Kontak Kami</h3>
                    <p class="flex items-start space-x-2.5">
                        <svg class="w-5 h-5 text-sky-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Jl. Pembangunan No. 12, Kec. Sukajadi, Kota Bandung, Jawa Barat</span>
                    </p>
                    <p class="flex items-center space-x-2.5">
                        <svg class="w-5 h-5 text-sky-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>(022) 1234-5678</span>
                    </p>
                    <p class="flex items-center space-x-2.5">
                        <svg class="w-5 h-5 text-sky-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>info@raalkautsar.sch.id</span>
                    </p>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-slate-800 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} RA Al Kautsar. Hak cipta dilindungi undang-undang.
            </div>
        </div>
    </footer>

</body>
</html>
