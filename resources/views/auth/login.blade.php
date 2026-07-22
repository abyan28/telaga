<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Sign Up — RA Al Kautsar</title>

    @php $__favicon = \App\Models\SiteContent::logoUrl(); @endphp
    @if ($__favicon)
        <link rel="icon" href="{{ $__favicon }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-slate-800 to-sky-950 text-slate-800 min-h-screen flex flex-col justify-between" x-data="{ tab: '{{ old('name') ? 'signup' : 'login' }}' }">

    <!-- Header / Brand -->
    <header class="py-6 px-8 flex justify-between items-center w-full">
        <a href="{{ route('home') }}" class="flex items-center space-x-2.5 text-white">
            @if ($__favicon)
                <img src="{{ $__favicon }}" alt="Logo" class="w-10 h-10 rounded-xl object-cover">
            @else
                <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center font-bold text-lg">
                    AK
                </div>
            @endif
            <span class="text-base font-bold tracking-tight">RA AL KAUTSAR</span>
        </a>
        <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            &larr; Kembali ke Beranda
        </a>
    </header>

    <!-- Form Container -->
    <main class="w-full max-w-md mx-auto px-4 py-8 shrink-0">

        <!-- Login Form Tab -->
        <div x-show="tab === 'login'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="bg-white/95 border border-white/25 rounded-[2.5rem] shadow-2xl p-8 backdrop-blur-lg">
                @include('auth._tabs')

                {{-- Tampilkan error validasi/kredensial --}}
                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-600 font-semibold">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.submit') }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="space-y-1">
                        <label for="login-email" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Email / No. HP / Username</label>
                        <input id="login-email" name="login" type="text" placeholder="email, no. HP, atau username" value="{{ old('login') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" required>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between items-center">
                            <label for="login-password" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Kata Sandi</label>
                            <a href="#" class="text-2xs text-sky-600 font-semibold hover:underline">Lupa Sandi?</a>
                        </div>
                        <x-password-input id="login-password" name="password" :required="true" placeholder="••••••••" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" />
                    </div>
                    <div class="flex items-center">
                        <input id="remember-me" name="remember" type="checkbox" class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-slate-300 rounded">
                        <label for="remember-me" class="ml-2 block text-xs text-slate-500 font-semibold cursor-pointer">Ingat perangkat saya</label>
                    </div>
                    <button type="submit" class="w-full py-4 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-lg shadow-sky-100 hover:shadow-sky-200 transition-all hover:-translate-y-0.5">
                        Masuk Ke Portal
                    </button>
                </form>
            </div>
        </div>

        <!-- Signup Form Tab (khusus Orang Tua — role dikunci sistem) -->
        <div x-show="tab === 'signup'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="bg-white/95 border border-white/25 rounded-[2.5rem] shadow-2xl p-8 backdrop-blur-lg">
                @include('auth._tabs')

                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-600 font-semibold">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('register.submit') }}" method="POST" class="space-y-5"
                      x-data="{
                        username: @js(old('username')),
                        status: '',
                        check() {
                            this.status = '';
                            const u = this.username;
                            if (!/^[a-zA-Z0-9_.]{6,30}$/.test(u)) { if (u) this.status = 'invalid'; return; }
                            this.status = 'checking';
                            clearTimeout(this._t);
                            this._t = setTimeout(async () => {
                                const r = await fetch(`{{ route('register.check-username') }}?min=6&username=${encodeURIComponent(u)}`);
                                const d = await r.json();
                                this.status = d.available ? 'available' : 'taken';
                            }, 400);
                        }
                      }">
                    @csrf
                    <div class="space-y-1">
                        <label for="signup-username" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Username</label>
                        <input id="signup-username" name="username" type="text" placeholder="minimal 6 karakter" x-model="username" @input="check()" autocomplete="off" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" required>
                        <p class="text-xs font-semibold pt-0.5" x-show="status" x-cloak
                           :class="{ 'text-slate-400': status==='checking', 'text-green-600': status==='available', 'text-red-500': status==='taken' || status==='invalid' }">
                            <span x-show="status==='checking'">Memeriksa…</span>
                            <span x-show="status==='available'">✓ Username tersedia</span>
                            <span x-show="status==='taken'">✗ Username sudah dipakai</span>
                            <span x-show="status==='invalid'">✗ 6–30 karakter, hanya huruf/angka/titik/underscore</span>
                        </p>
                    </div>
                    <div class="space-y-1">
                        <label for="signup-email" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Alamat Email</label>
                        <input id="signup-email" name="email" type="email" placeholder="nama@email.com" value="{{ old('email') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" required>
                    </div>
                    <div class="space-y-1">
                        <label for="signup-password" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Buat Kata Sandi</label>
                        <x-password-input id="signup-password" name="password" :required="true" placeholder="Minimal 8 karakter" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" />
                    </div>
                    <div class="space-y-1">
                        <label for="signup-password-confirm" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Ulangi Kata Sandi</label>
                        <x-password-input id="signup-password-confirm" name="password_confirmation" :required="true" placeholder="Ulangi kata sandi" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" />
                    </div>
                    <button type="submit" class="w-full py-4 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-lg shadow-sky-100 hover:shadow-sky-200 transition-all hover:-translate-y-0.5">
                        Daftar Akun Baru
                    </button>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer Copyright -->
    <footer class="py-6 text-center text-slate-500 text-2xs uppercase tracking-widest w-full">
        &copy; {{ date('Y') }} RA Al Kautsar. All Rights Reserved.
    </footer>

</body>
</html>
