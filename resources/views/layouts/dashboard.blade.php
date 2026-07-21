<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal RA Al Kautsar')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800" x-data="{ sidebarOpen: false }">

    @php
        // Ambil data dari user yang sedang login (auth asli, bukan mock lagi).
        $authUser = auth()->user();
        $activeRole = $authUser->role;              // 'orang tua' | 'admin' | 'guru'
        $userName = $authUser->displayName();
        $userEmail = $authUser->email ?? $authUser->no_hp;

        // Metadata tampilan berdasarkan role
        $roleName = match ($activeRole) {
            'admin' => 'Administrator',
            'guru' => 'Guru Kelas',
            default => 'Orang Tua',
        };
        $themeColor = match ($activeRole) {
            'admin' => 'indigo',
            'guru' => 'teal',
            default => 'sky',
        };
    @endphp

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar for Desktop -->
        <aside class="hidden md:flex flex-col w-72 bg-slate-900 text-slate-300 shrink-0 border-r border-slate-800">
            <!-- Sidebar Header -->
            <div class="flex items-center space-x-3 px-6 h-20 border-b border-slate-800">
                <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-sky-900/50">
                    AK
                </div>
                <div>
                    <span class="text-sm font-bold tracking-tight text-white block leading-tight">RA AL KAUTSAR</span>
                    <span class="text-xs text-sky-400 font-semibold tracking-wider uppercase block">Portal Sistem</span>
                </div>
            </div>

            <!-- Profile Info in Sidebar -->
            <div class="p-6 border-b border-slate-800 bg-slate-950/40">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 flex items-center justify-center text-sky-700 font-semibold text-lg uppercase shadow-inner">
                        {{ substr($userName, 0, 2) }}
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-sm font-bold text-white truncate leading-snug">{{ $userName }}</h4>
                        <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-2xs font-semibold bg-sky-400/10 text-sky-400 uppercase tracking-wider">
                            {{ $roleName }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <!-- Orang Tua Menu -->
                @if ($activeRole === 'orang tua')
                    <a href="{{ route('ortu.dashboard', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.dashboard') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('ortu.register', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.register') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Daftar Murid Baru</span>
                    </a>
                    <a href="{{ route('ortu.status', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.status') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Status Pendaftaran</span>
                    </a>
                    <a href="{{ route('ortu.payments', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.payments') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Pembayaran & SPP</span>
                    </a>
                    <a href="{{ route('ortu.children', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.children') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <span>Data Anak</span>
                    </a>
                    <a href="{{ route('ortu.profile', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.profile') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <span>Profil Orang Tua</span>
                    </a>
                    <a href="{{ route('ortu.account', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.account') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span>Pengaturan Akun</span>
                    </a>
                    <a href="{{ route('ortu.password', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.password') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/10' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        <span>Ganti Password</span>
                    </a>
                @endif

                <!-- Admin Menu -->
                @if ($activeRole === 'admin')
                    @php
                        $aOn = 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/15';
                        $aOff = 'hover:bg-slate-800 text-slate-400 hover:text-white';
                        $ppdbActive = request()->routeIs('admin.registrations*') || request()->routeIs('admin.daftar-ulang');
                        $bayarActive = request()->routeIs('admin.settings*') || request()->routeIs('admin.spp');
                        // K6.1: total badge untuk header dropdown (child dijumlah di header).
                        $badgePpdb = ($sidebarBadge['pendaftaran'] ?? 0) + ($sidebarBadge['daftar_ulang'] ?? 0);
                        // Badge kecil rose (dipakai berulang). Kosong bila 0.
                        $badge = fn ($n) => $n > 0
                            ? '<span class="ml-auto inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold">'.$n.'</span>'
                            : '';
                    @endphp

                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? $aOn : $aOff }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"/></svg>
                        <span>Dashboard</span>
                    </a>

                    {{-- PPDB (dropdown) --}}
                    <div x-data="{ open: {{ $ppdbActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold {{ $ppdbActive ? $aOn : $aOff }} transition-all">
                            <span class="flex items-center space-x-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span>PPDB</span>
                            </span>
                            <span class="flex items-center gap-2">
                                {!! $badge($badgePpdb) !!}
                                <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 ml-4 pl-3 border-l border-slate-800 space-y-1">
                            <a href="{{ route('admin.registrations') }}" class="flex items-center px-4 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.registrations*') ? 'text-white' : 'text-slate-400 hover:text-white' }}"><span>Pendaftaran Murid</span>{!! $badge($sidebarBadge['pendaftaran'] ?? 0) !!}</a>
                            <a href="{{ route('admin.daftar-ulang') }}" class="flex items-center px-4 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.daftar-ulang') ? 'text-white' : 'text-slate-400 hover:text-white' }}"><span>Daftar Ulang</span>{!! $badge($sidebarBadge['daftar_ulang'] ?? 0) !!}</a>
                        </div>
                    </div>

                    <a href="{{ route('admin.data.teachers') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.data.teachers') ? $aOn : $aOff }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Data Guru</span>
                    </a>
                    <a href="{{ route('admin.data.students') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.data.students') || request()->routeIs('admin.users') ? $aOn : $aOff }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span>Data Murid</span>
                    </a>
                    <a href="{{ route('admin.data.classes') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.data.classes') ? $aOn : $aOff }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Data Kelas</span>
                    </a>
                    {{-- Pembayaran (dropdown) --}}
                    <div x-data="{ open: {{ $bayarActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold {{ $bayarActive ? $aOn : $aOff }} transition-all">
                            <span class="flex items-center space-x-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 2h3m2 3H9m12-5a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Pembayaran</span>
                            </span>
                            <span class="flex items-center gap-2">
                                {!! $badge($sidebarBadge['spp'] ?? 0) !!}
                                <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 ml-4 pl-3 border-l border-slate-800 space-y-1">
                            <a href="{{ route('admin.settings') }}" class="block px-4 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.settings') ? 'text-white' : 'text-slate-400 hover:text-white' }}">Set Pembayaran</a>
                            <a href="{{ route('admin.spp') }}" class="flex items-center px-4 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.spp') ? 'text-white' : 'text-slate-400 hover:text-white' }}"><span>Kelola Pembayaran</span>{!! $badge($sidebarBadge['spp'] ?? 0) !!}</a>
                        </div>
                    </div>

                    <a href="{{ route('admin.content') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.content') ? $aOn : $aOff }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                        <span>Konten Web</span>
                    </a>

                    <a href="{{ route('admin.accounts') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.accounts') ? $aOn : $aOff }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Data Akun</span>
                    </a>
                    <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.reports') ? $aOn : $aOff }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Laporan & Audit Log</span>
                    </a>
                @endif

                <!-- Guru Menu -->
                @if ($activeRole === 'guru')
                    <a href="{{ route('guru.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('guru.dashboard') ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/15' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Murid Kelas Saya</span>
                    </a>
                    <a href="{{ route('guru.password') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('guru.password') ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/15' : 'hover:bg-slate-800 text-slate-400 hover:text-white' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Akun & Profil</span>
                    </a>
                @endif
            </nav>

            <!-- Bottom Brand Info -->
            <div class="p-6 border-t border-slate-800 text-2xs text-slate-500">
                <span>Versi Proyek: 1.0 (Front-End Only)</span>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Topbar Header -->
            <header class="h-20 bg-white border-b border-slate-100 flex items-center justify-between px-6 shrink-0 shadow-sm">
                <!-- Mobile Open Menu Button -->
                <button @click="sidebarOpen = true" class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-50 focus:outline-none shrink-0">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page title -->
                <h2 class="hidden sm:block text-lg font-bold text-slate-900 tracking-tight">
                    @yield('header_title', 'Portal RA Al Kautsar')
                </h2>

                <!-- Right items (User details & Logout) -->
                <div class="flex items-center space-x-6 ml-auto">
                    <!-- User Actions Profile -->
                    <div class="flex items-center space-x-3">
                        <span class="hidden lg:block text-right">
                            <span class="text-sm font-semibold text-slate-800 block leading-tight">{{ $userName }}</span>
                            <span class="text-xs text-slate-400 block mt-0.5">{{ $userEmail }}</span>
                        </span>
                        <a href="{{ route('home') }}" class="p-2.5 rounded-xl border border-slate-200 text-slate-500 hover:text-sky-600 hover:bg-slate-50 transition-colors shadow-2xs" title="Buka Halaman Utama">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </a>
                        <!-- Logout (auth asli) -->
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="p-2.5 rounded-xl border border-slate-200 text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors shadow-2xs" title="Keluar / Logout">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content Container -->
            <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
                @yield('content')
            </main>

            {{-- Lightbox global: gambar <img>, PDF <iframe> inline (klik luar/ESC tutup). --}}
            <div x-data="{ src: null, pdf: false }"
                 @open-lightbox.window="src = $event.detail.url; pdf = $event.detail.pdf" x-show="src" x-cloak
                 @click="src = null" @keydown.escape.window="src = null"
                 class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-6 cursor-zoom-out">
                <img x-show="!pdf" :src="src" alt="Pratinjau berkas" class="max-w-full max-h-full rounded-xl shadow-2xl object-contain">
                <div x-show="pdf" @click.stop class="w-full max-w-4xl h-[85vh] rounded-xl shadow-2xl bg-white overflow-hidden cursor-default">
                    <object :data="src" type="application/pdf" class="w-full h-full">
                        <div class="w-full h-full flex flex-col items-center justify-center gap-2 text-slate-500">
                            <p class="text-sm">Pratinjau PDF tidak dapat dimuat di sini.</p>
                            <a :href="src" target="_blank" class="text-sm font-bold text-indigo-600 hover:underline">Buka di tab baru</a>
                        </div>
                    </object>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Sidebar Menu -->
    <div x-show="sidebarOpen" class="fixed inset-0 z-50 flex md:hidden" role="dialog" aria-modal="true" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="sidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative flex flex-col w-full max-w-xs bg-slate-900" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <!-- Mobile Sidebar Header -->
            <div class="flex items-center justify-between px-6 h-20 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                        AK
                    </div>
                    <div>
                        <span class="text-sm font-bold tracking-tight text-white block leading-tight">RA AL KAUTSAR</span>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="p-2 rounded-lg text-slate-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Profile Info in Mobile Sidebar -->
            <div class="p-6 border-b border-slate-800 bg-slate-950/40">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 flex items-center justify-center text-sky-700 font-semibold text-lg uppercase shadow-inner">
                        {{ substr($userName, 0, 2) }}
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white leading-tight">{{ $userName }}</h4>
                        <span class="inline-block text-3xs font-semibold text-sky-400 bg-sky-400/10 px-2 py-0.5 rounded mt-1 uppercase tracking-wider">
                            {{ $roleName }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                @if ($activeRole === 'orang tua')
                    <a href="{{ route('ortu.dashboard', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.dashboard') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('ortu.register', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.register') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Daftar Murid Baru</span>
                    </a>
                    <a href="{{ route('ortu.status', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.status') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Status Pendaftaran</span>
                    </a>
                    <a href="{{ route('ortu.payments', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.payments') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Pembayaran & SPP</span>
                    </a>
                    <a href="{{ route('ortu.children', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.children') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Data Anak</span>
                    </a>
                    <a href="{{ route('ortu.profile', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.profile') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Profil Orang Tua</span>
                    </a>
                    <a href="{{ route('ortu.account', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.account') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Pengaturan Akun</span>
                    </a>
                    <a href="{{ route('ortu.password', ['role' => 'orang tua']) }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('ortu.password') ? 'bg-sky-500 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Ganti Password</span>
                    </a>
                @elseif ($activeRole === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.registrations') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.registrations*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>PPDB — Pendaftaran Murid</span>{!! $badge($sidebarBadge['pendaftaran'] ?? 0) !!}
                    </a>
                    <a href="{{ route('admin.daftar-ulang') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.daftar-ulang') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>PPDB — Daftar Ulang</span>{!! $badge($sidebarBadge['daftar_ulang'] ?? 0) !!}
                    </a>
                    <a href="{{ route('admin.data.teachers') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.data.teachers') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Data Guru</span>
                    </a>
                    <a href="{{ route('admin.data.students') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.data.students') || request()->routeIs('admin.users') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Data Murid</span>
                    </a>
                    <a href="{{ route('admin.data.classes') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.data.classes') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Data Kelas</span>
                    </a>
                    <a href="{{ route('admin.settings') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.settings') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Set Pembayaran</span>
                    </a>
                    <a href="{{ route('admin.spp') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.spp') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Kelola Pembayaran</span>{!! $badge($sidebarBadge['spp'] ?? 0) !!}
                    </a>
                    <a href="{{ route('admin.content') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.content') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Konten Web</span>
                    </a>
                    <a href="{{ route('admin.accounts') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.accounts') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Data Akun</span>
                    </a>
                    <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.reports') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Laporan & Audit Log</span>
                    </a>
                @elseif ($activeRole === 'guru')
                    <a href="{{ route('guru.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('guru.dashboard') ? 'bg-teal-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Murid Kelas Saya</span>
                    </a>
                    <a href="{{ route('guru.password') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('guru.password') ? 'bg-teal-600 text-white' : 'text-slate-400 hover:bg-slate-800' }}">
                        <span>Akun & Profil</span>
                    </a>
                @endif
            </nav>
        </div>
    </div>

<script>
    // Buka berkas di lightbox: gambar sebagai <img>, PDF sebagai <iframe> inline.
    function openBerkas(url) {
        const pdf = !/\.(jpe?g|png|gif|webp)$/i.test(url);
        window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { url, pdf } }));
    }

    // Tab yang posisinya tetap setelah reload akibat aksi/submit. Posisi disimpan di
    // QUERY STRING (?tab=) — query ikut ke Referer saat POST → back() redirect balik dgn
    // query → tab tak reset. (Hash tak dipakai: fragment hilang saat form submit.)
    // Pakai: x-data="hashTabs('defaultTab')", tombol pakai setTab('x').
    function hashTabs(fallback) {
        const write = (t) => {
            const u = new URL(location);
            u.searchParams.set('tab', t);
            history.replaceState(null, '', u);
        };
        return {
            tab: new URLSearchParams(location.search).get('tab') || fallback,
            init() { write(this.tab); },
            setTab(t) { this.tab = t; write(t); },
        };
    }
</script>
</body>
</html>
