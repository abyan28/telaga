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
                        <label for="login-password" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Kata Sandi</label>
                        <x-password-input id="login-password" name="password" :required="true" placeholder="••••••••" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" />
                        <div class="text-right pt-1">
                            <a href="{{ route('password.request') }}" class="text-2xs text-sky-600 font-semibold hover:underline">Lupa Sandi?</a>
                        </div>
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
                        email: @js(old('email', '')),
                        otpSent: false,
                        otpVerified: false,
                        otpCode: '',
                        otpMsg: '',
                        otpLoading: false,
                        cooldown: 0,
                        _timer: null,
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
                        },
                        async sendOtp() {
                            if (!this.email || this.cooldown > 0) return;
                            this.otpLoading = true; this.otpMsg = '';
                            try {
                                const r = await fetch('{{ route('register.send-otp') }}', {
                                    method: 'POST',
                                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                    body: JSON.stringify({email: this.email})
                                });
                                const d = await r.json();
                                if (r.status === 429) { this.otpMsg = d.error || 'Tunggu sebelum mengirim ulang.'; }
                                else if (d.sent) {
                                    this.otpSent = true; this.otpVerified = false; this.otpCode = '';
                                    this.otpMsg = 'Kode terkirim. Cek inbox/spam Anda.';
                                    this.cooldown = 60;
                                    clearInterval(this._timer);
                                    this._timer = setInterval(() => { this.cooldown--; if (this.cooldown <= 0) clearInterval(this._timer); }, 1000);
                                } else { this.otpMsg = 'Gagal mengirim kode.'; }
                            } catch { this.otpMsg = 'Terjadi kesalahan jaringan.'; }
                            this.otpLoading = false;
                        },
                        async verifyOtp() {
                            if (!this.otpCode || this.otpCode.length !== 6) return;
                            this.otpLoading = true; this.otpMsg = '';
                            try {
                                const r = await fetch('{{ route('register.verify-otp') }}', {
                                    method: 'POST',
                                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                    body: JSON.stringify({email: this.email, code: this.otpCode})
                                });
                                const d = await r.json();
                                if (d.verified) { this.otpVerified = true; this.otpMsg = ''; }
                                else { this.otpMsg = d.error || 'Verifikasi gagal.'; }
                            } catch { this.otpMsg = 'Terjadi kesalahan jaringan.'; }
                            this.otpLoading = false;
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
                        <div class="flex gap-2">
                            <input id="signup-email" name="email" type="email" placeholder="nama@email.com" x-model="email" class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" required :disabled="otpVerified">
                            <button type="button" @click="sendOtp()" :disabled="!email || cooldown > 0 || otpLoading || otpVerified"
                                    class="px-4 py-3 bg-slate-800 hover:bg-slate-700 disabled:opacity-40 text-white font-bold rounded-xl text-xs whitespace-nowrap transition-colors shrink-0">
                                <span x-show="!otpSent || cooldown <= 0">Kirim Kode</span>
                                <span x-show="otpSent && cooldown > 0" x-cloak x-text="cooldown + ' dtk'"></span>
                            </button>
                        </div>
                    </div>
                    {{-- OTP input + verify --}}
                    <div x-show="otpSent && !otpVerified" x-cloak class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Kode Verifikasi (6 digit)</label>
                        <div class="flex gap-2">
                            <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" x-model="otpCode" placeholder="Masukkan 6 digit kode"
                                   class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm tracking-[0.5em] font-mono text-center focus:outline-none focus:border-sky-500">
                            <button type="button" @click="verifyOtp()" :disabled="otpCode.length !== 6 || otpLoading"
                                    class="px-4 py-3 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white font-bold rounded-xl text-xs whitespace-nowrap transition-colors shrink-0">
                                Verifikasi
                            </button>
                        </div>
                    </div>
                    {{-- Feedback --}}
                    <p x-show="otpMsg" x-cloak x-text="otpMsg" class="text-xs font-semibold"
                       :class="otpVerified ? 'text-emerald-600' : 'text-slate-500'"></p>
                    <p x-show="otpVerified" x-cloak class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Email terverifikasi
                    </p>
                    <div class="space-y-1">
                        <label for="signup-password" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Buat Kata Sandi</label>
                        <x-password-input id="signup-password" name="password" :required="true" placeholder="Minimal 8 karakter" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" />
                    </div>
                    <div class="space-y-1">
                        <label for="signup-password-confirm" class="text-xs font-bold text-slate-500 uppercase tracking-wide">Ulangi Kata Sandi</label>
                        <x-password-input id="signup-password-confirm" name="password_confirmation" :required="true" placeholder="Ulangi kata sandi" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all" />
                    </div>
                    <button type="submit" :disabled="!otpVerified"
                            class="w-full py-4 bg-sky-600 hover:bg-sky-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg shadow-sky-100 hover:shadow-sky-200 transition-all hover:-translate-y-0.5">
                        Daftar Akun Baru
                    </button>
                    <p x-show="!otpVerified" x-cloak class="text-3xs text-center text-slate-400">Verifikasi email dulu untuk mengaktifkan tombol daftar.</p>
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
