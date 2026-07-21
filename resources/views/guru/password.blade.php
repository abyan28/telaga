@extends('layouts.dashboard')

@section('title', 'Akun & Profil — RA Al Kautsar')
@section('header_title', 'Akun & Profil')

@section('content')
<div class="max-w-md mx-auto space-y-6" x-data="hashTabs('profil')">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Sub-tab (K9.4): pisah 3 card (Profil/Password/Akun) agar tak scroll panjang. --}}
    <div class="flex gap-2 border-b border-slate-100">
        <button @click="setTab('profil')" :class="tab === 'profil' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="px-4 py-3 border-b-2 font-bold text-xs transition-colors">Profil</button>
        <button @click="setTab('password')" :class="tab === 'password' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="px-4 py-3 border-b-2 font-bold text-xs transition-colors">Ganti Password</button>
        <button @click="setTab('akun')" :class="tab === 'akun' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="px-4 py-3 border-b-2 font-bold text-xs transition-colors">Akun</button>
    </div>

    <div x-show="tab === 'profil'" x-cloak x-data="{ editing: {{ $errors->any() && old('nama') !== null ? 'true' : 'false' }} }" class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Profil Saya</h3>
            <p class="text-xs text-slate-400 mt-1">NUPTK dikelola admin. Perbarui data pribadi Anda di sini.</p>
        </div>

        {{-- Mode lihat (default): profil read-only --}}
        <div x-show="!editing">
            <div class="flex items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <x-avatar :path="$teacher?->foto_path" :name="$teacher?->nama ?? ''" size="w-20 h-20" />
                    <div>
                        <p class="text-base font-bold text-slate-900">{{ $teacher?->nama ?? '-' }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $teacher?->jabatan ?: 'Guru' }}</p>
                    </div>
                </div>
                <button type="button" @click="editing = true" class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl text-xs transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </button>
            </div>
            <dl class="grid grid-cols-2 gap-4 text-xs">
                @foreach ([
                    'NUPTK' => $teacher?->nuptk ?: '-',
                    'Jabatan' => $teacher?->jabatan ?: '-',
                    'Jenis Kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'][$teacher?->jenis_kelamin] ?? '-',
                    'Tempat, Tgl Lahir' => trim(($teacher?->tempat_lahir ?: '').', '.optional($teacher?->tanggal_lahir)->translatedFormat('d F Y'), ', ') ?: '-',
                    'Mulai Mengajar' => optional($teacher?->tanggal_mulai_mengajar)->translatedFormat('d F Y') ?: '-',
                    'No. HP' => $teacher?->no_hp ?: '-',
                    'Email' => auth()->user()->email ?: '-',
                    'Alamat' => trim(($teacher?->alamat ?: '').' '.collect([$teacher?->kelurahan_nama, $teacher?->kecamatan_nama, $teacher?->kota_nama, $teacher?->provinsi_nama])->filter()->implode(', ')) ?: '-',
                    'Riwayat Pendidikan' => $teacher?->riwayat_pendidikan ?: '-',
                ] as $label => $val)
                    <div class="{{ $label === 'Riwayat Pendidikan' ? 'col-span-2' : '' }}">
                        <dt class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">{{ $label }}</dt>
                        <dd class="font-bold text-slate-800 mt-0.5 whitespace-pre-line">{{ $val }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Mode edit: form --}}
        <div x-show="editing" x-cloak>

        <form action="{{ route('guru.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pas Foto (opsional, JPG/PNG maks 2 MB)</label>
                @if ($teacher?->foto_path)
                    <img src="{{ asset('storage/'.$teacher->foto_path) }}" alt="Foto saat ini" class="w-20 h-20 rounded-xl object-cover border border-slate-200 mb-1">
                @endif
                <input type="file" name="foto" accept="image/*" class="w-full text-xs text-slate-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-teal-50 file:text-teal-600 file:font-bold file:text-xs">
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Lengkap</label>
                <input type="text" name="nama" required value="{{ old('nama', $teacher?->nama) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NUPTK</label>
                <input type="text" value="{{ $teacher?->nuptk }}" readonly class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-xs text-slate-500 cursor-not-allowed">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                        <option value="">-</option>
                        <option value="L" @selected(old('jenis_kelamin', $teacher?->jenis_kelamin) === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin', $teacher?->jenis_kelamin) === 'P')>Perempuan</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Mulai Mengajar</label>
                    <input type="date" name="tanggal_mulai_mengajar" value="{{ old('tanggal_mulai_mengajar', optional($teacher?->tanggal_mulai_mengajar)->format('Y-m-d')) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $teacher?->tempat_lahir) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                </div>
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($teacher?->tanggal_lahir)->format('Y-m-d')) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Riwayat Pendidikan</label>
                <textarea name="riwayat_pendidikan" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">{{ old('riwayat_pendidikan', $teacher?->riwayat_pendidikan) }}</textarea>
            </div>
            @php $isWaliKelas = $teacher?->homeroomClass->isNotEmpty(); @endphp
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jabatan</label>
                <input type="text" name="jabatan" value="{{ old('jabatan', $teacher?->jabatan) }}" @readonly($isWaliKelas)
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500 {{ $isWaliKelas ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50' }}"
                       placeholder="mis. Guru Kelas">
                @if ($isWaliKelas)
                    <span class="text-4xs text-slate-400 block">Terisi otomatis "WALI KELAS" — dikelola admin.</span>
                @endif
            </div>

            {{-- Alamat wilayah berjenjang (T2.1): 4 searchable dropdown dari DB wilayah. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                 x-data="{
                    prov: { id: '{{ old('provinsi_id', $teacher?->provinsi_id) }}', nama: '{{ old('provinsi_nama', $teacher?->provinsi_nama) }}' },
                    kota: { id: '{{ old('kota_id', $teacher?->kota_id) }}', nama: '{{ old('kota_nama', $teacher?->kota_nama) }}' },
                    kec:  { id: '{{ old('kecamatan_id', $teacher?->kecamatan_id) }}', nama: '{{ old('kecamatan_nama', $teacher?->kecamatan_nama) }}' },
                    kel:  { id: '{{ old('kelurahan_id', $teacher?->kelurahan_id) }}', nama: '{{ old('kelurahan_nama', $teacher?->kelurahan_nama) }}' },
                 }">
                @php
                    $pickers = [
                        'provinsi'  => ['Provinsi', 'prov', null],
                        'kota'      => ['Kota/Kabupaten', 'kota', 'prov'],
                        'kecamatan' => ['Kecamatan', 'kec', 'kota'],
                        'kelurahan' => ['Desa/Kelurahan', 'kel', 'kec'],
                    ];
                @endphp
                @foreach ($pickers as $level => [$label, $var, $parent])
                    <div class="space-y-1"
                         x-data="{
                            open: false, q: '', items: [], loading: false,
                            async load() {
                                @if ($parent) if (!{{ $parent }}.id) { this.items = []; return; } @endif
                                this.loading = true;
                                const url = new URL('{{ route('wilayah', ['level' => $level]) }}', location.origin);
                                @if ($parent) url.searchParams.set('parent', {{ $parent }}.id); @endif
                                if (this.q) url.searchParams.set('q', this.q);
                                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                                this.items = r.ok ? await r.json() : [];
                                this.loading = false;
                            },
                            pick(it) {
                                {{ $var }}.id = it.id; {{ $var }}.nama = it.nama;
                                this.q = ''; this.open = false;
                                @foreach (array_keys($pickers) as $lvl)
                                    @php [$l2, $v2, $p2] = $pickers[$lvl]; @endphp
                                    @if ($p2 === $var){{ $v2 }}.id = ''; {{ $v2 }}.nama = '';@endif
                                @endforeach
                            }
                         }"
                         @click.away="open = false">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">{{ $label }}</label>
                        <input type="hidden" name="{{ $level }}_id" :value="{{ $var }}.id">
                        <input type="hidden" name="{{ $level }}_nama" :value="{{ $var }}.nama">
                        <div class="relative">
                            <input type="text" autocomplete="off"
                                   :placeholder="{{ $var }}.nama || 'Pilih {{ $label }}…'"
                                   :class="{{ $var }}.nama ? 'text-slate-900' : 'text-slate-400'"
                                   x-model="q"
                                   @focus="open = true; load()"
                                   @input.debounce.300ms="open = true; load()"
                                   @if ($parent) :disabled="!{{ $parent }}.id" @endif
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500 disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full max-h-52 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg">
                                <template x-if="loading"><div class="px-4 py-2 text-xs text-slate-400">Memuat…</div></template>
                                <template x-for="it in items" :key="it.id">
                                    <button type="button" @click="pick(it)" class="block w-full text-left px-4 py-2 text-xs hover:bg-teal-50" x-text="it.nama"></button>
                                </template>
                                <template x-if="!loading && items.length === 0"><div class="px-4 py-2 text-xs text-slate-400">Tidak ada hasil.</div></template>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Alamat Lengkap (Jalan, RT/RW, No. Rumah)</label>
                <textarea name="alamat" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">{{ old('alamat', $teacher?->alamat) }}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="editing = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-xl text-xs transition-colors">Batal</button>
                <button type="submit" class="flex-1 py-3 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Profil</button>
            </div>
        </form>
        </div>
    </div>

    <div x-show="tab === 'password'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Ganti Password</h3>
            <p class="text-xs text-slate-400 mt-1">Password awal Anda adalah NUPTK. Wajib diganti saat login pertama sekaligus menetapkan username.</p>
        </div>

        <form action="{{ route('guru.password.update') }}" method="POST" class="space-y-4"
              x-data="{
                username: @js(old('username', auth()->user()->username)),
                status: '',
                check() {
                    this.status = '';
                    const u = this.username;
                    if (!/^[a-zA-Z0-9_.]{6,30}$/.test(u)) { if (u) this.status = 'invalid'; return; }
                    this.status = 'checking';
                    clearTimeout(this._t);
                    this._t = setTimeout(async () => {
                        const r = await fetch(`{{ route('register.check-username') }}?min=6&username=${encodeURIComponent(u)}`);
                        this.status = (await r.json()).available ? 'available' : 'taken';
                    }, 400);
                }
              }">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Username (min 6)</label>
                <input type="text" name="username" x-model="username" @input="check()" autocomplete="off" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                <p class="text-3xs font-bold pt-0.5" x-show="status" x-cloak
                   :class="{ 'text-slate-400': status==='checking', 'text-green-600': status==='available', 'text-red-500': status==='taken' || status==='invalid' }">
                    <span x-show="status==='checking'">Memeriksa…</span>
                    <span x-show="status==='available'">✓ Username tersedia</span>
                    <span x-show="status==='taken'">✗ Username sudah dipakai</span>
                    <span x-show="status==='invalid'">✗ Min 6 karakter, hanya huruf/angka/titik/underscore</span>
                </p>
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Password Saat Ini</label>
                <input type="password" name="current_password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Password Baru (min 8)</label>
                <input type="password" name="password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
            </div>
            <button type="submit" class="w-full py-3 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Password Baru</button>
        </form>
    </div>

    <div x-show="tab === 'akun'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-950">Pengaturan Akun</h3>
            <p class="text-xs text-slate-400 mt-1">Perbarui email & nomor HP. Keduanya bisa dipakai untuk login.</p>
        </div>

        <form action="{{ route('guru.account.update') }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Email (opsional)</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
            </div>
            <div class="space-y-1">
                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nomor HP</label>
                <input type="text" name="no_hp" required value="{{ old('no_hp', auth()->user()->no_hp) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
            </div>
            <button type="submit" class="w-full py-3 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Perubahan</button>
        </form>
    </div>

</div>
@endsection
