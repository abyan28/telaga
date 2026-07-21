@extends('layouts.dashboard')

@section('title', 'Data Guru — RA Al Kautsar')
@section('header_title', 'Data Guru')

@section('content')
<div class="space-y-8" x-data="teacherCrud()">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h3 class="text-base font-bold text-slate-950">Data Guru / Pendidik</h3>
            <button @click="openCreate()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">+ Tambah Pendidik</button>
        </div>

        {{-- Pencarian (T5.4) + filter status (K5.4) --}}
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / email / NUPTK / HP…" class="flex-1 min-w-[180px] px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
            <select name="status" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                <option value="aktif" @selected($status === 'aktif')>Guru Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif (Keluar)</option>
                <option value="semua" @selected($status === 'semua')>Semua</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs">Cari</button>
            <a href="{{ route('admin.data.teachers') }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4 pr-6">Nama Guru</th>
                        <th class="py-4 px-3">NUPTK</th>
                        <th class="py-4 px-3">Jabatan</th>
                        <th class="py-4 px-3">Kelas</th>
                        <th class="py-4 px-3 text-center">Murid</th>
                        <th class="py-4 px-3">No. HP</th>
                        <th class="py-4 px-3">Status</th>
                        <th class="py-4 pl-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($teachers as $teacher)
                        @php
                            $row = [
                                'id' => $teacher->id_teachers, 'nama' => $teacher->nama, 'email' => $teacher->user?->email,
                                'nuptk' => $teacher->nuptk, 'no_hp' => $teacher->no_hp, 'jabatan' => $teacher->jabatan,
                                'tempat_lahir' => $teacher->tempat_lahir, 'tanggal_lahir' => optional($teacher->tanggal_lahir)->format('Y-m-d'),
                                                'jenis_kelamin' => $teacher->jenis_kelamin,
                                                'tanggal_mulai_mengajar' => optional($teacher->tanggal_mulai_mengajar)->format('Y-m-d'),
                                                'riwayat_pendidikan' => $teacher->riwayat_pendidikan,
                                'alamat' => $teacher->alamat,
                                'provinsi_id' => $teacher->provinsi_id, 'provinsi_nama' => $teacher->provinsi_nama,
                                'kota_id' => $teacher->kota_id, 'kota_nama' => $teacher->kota_nama,
                                'kecamatan_id' => $teacher->kecamatan_id, 'kecamatan_nama' => $teacher->kecamatan_nama,
                                'kelurahan_id' => $teacher->kelurahan_id, 'kelurahan_nama' => $teacher->kelurahan_nama,
                                'class_ids' => $teacher->classes->pluck('id_classes')->all(),
                                'homeroom_class_id' => (string) ($teacher->homeroomClass->first()?->id_classes ?? ''),
                                'foto_path' => $teacher->foto_path,
                            ];
                        @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors">
                            <td class="py-4 pr-6 font-bold text-slate-900">{{ $teacher->nama ?? '-' }}</td>
                            <td class="py-4 px-3 font-semibold text-slate-700">{{ $teacher->nuptk ?? '-' }}</td>
                            <td class="py-4 px-3 text-slate-500">{{ $teacher->jabatan ?? '-' }}</td>
                            <td class="py-4 px-3">
                                @php $kelasCount = $teacher->classes->count(); @endphp
                                @if ($kelasCount === 0)
                                    <span class="text-4xs text-slate-400">Belum ada kelas</span>
                                @elseif ($kelasCount === 1)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-4xs font-bold bg-sky-50 text-sky-700 border border-sky-100">{{ $teacher->classes->first()->nama_kelas }}</span>
                                @else
                                    {{-- >1 kelas: dropdown native <details> agar tabel tak penuh --}}
                                    <details class="inline-block">
                                        <summary class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-4xs font-bold bg-sky-50 text-sky-700 border border-sky-100 cursor-pointer select-none list-none">{{ $kelasCount }} kelas ▾</summary>
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach ($teacher->classes as $kelas)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-4xs font-bold bg-sky-50 text-sky-700 border border-sky-100">{{ $kelas->nama_kelas }}</span>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td class="py-4 px-3 text-center font-semibold text-slate-700">{{ $teacher->classes->sum(fn ($k) => $k->students->count()) }}</td>
                            <td class="py-4 px-3 text-slate-500">{{ $teacher->no_hp ?? '-' }}</td>
                            <td class="py-4 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-4xs font-bold uppercase tracking-wider {{ $teacher->is_aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">{{ $teacher->is_aktif ? 'Aktif' : 'Keluar' }}</span>
                            </td>
                            <td class="py-4 pl-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.teachers.show', $teacher) }}" class="px-2.5 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-lg">Profil</a>
                                <button @click="openEdit({{ \Illuminate\Support\Js::from($row) }})" class="px-2.5 py-1.5 border border-indigo-200 hover:bg-indigo-50 text-indigo-600 font-bold rounded-lg">Edit</button>
                                <form action="{{ route('admin.teachers.toggle-status', $teacher) }}" method="POST" class="inline" onsubmit="return confirm('{{ $teacher->is_aktif ? 'Nonaktifkan guru ini? Ia tak bisa login lagi.' : 'Aktifkan kembali guru ini?' }}')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 border font-bold rounded-lg {{ $teacher->is_aktif ? 'border-rose-200 hover:bg-rose-50 text-rose-600' : 'border-emerald-200 hover:bg-emerald-50 text-emerald-600' }}">{{ $teacher->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-400">Belum ada data guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginate :paginator="$teachers" />
    </div>

    {{-- Modal tambah/edit guru (satu form dua mode) --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-lg w-full border border-slate-100 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto" @click.away="open = false">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold text-slate-950" x-text="mode === 'edit' ? 'Edit Data Guru' : 'Tambah Guru Baru'"></h3>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <form :action="action" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pas Foto (opsional, JPG/PNG maks 2 MB)</label>
                    <template x-if="mode === 'edit' && f.foto_path">
                        <img :src="'/storage/' + f.foto_path" alt="Foto saat ini" class="w-20 h-20 rounded-xl object-cover border border-slate-200 mb-1">
                    </template>
                    <input type="file" name="foto" accept="image/*" class="w-full text-xs text-slate-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-600 file:font-bold file:text-xs">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Lengkap</label>
                        <input type="text" name="nama" x-model="f.nama" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Email (opsional)</label>
                        <input type="email" name="email" x-model="f.email" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                        <span class="text-4xs text-slate-400 block">Boleh kosong — guru isi sendiri setelah login.</span>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NUPTK</label>
                        <input type="text" name="nuptk" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" x-model="f.nuptk" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                        <span class="text-4xs text-slate-400 block" x-show="mode === 'create'">* Password awal = NUPTK.</span>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">No. HP</label>
                        <input type="text" name="no_hp" inputmode="numeric" pattern="[0-9]{9,14}" x-model="f.no_hp" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                        <span class="text-4xs text-slate-400 block" x-show="mode === 'create'">* Dipakai untuk login guru.</span>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jenis Kelamin</label>
                        <select name="jenis_kelamin" x-model="f.jenis_kelamin" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="">-</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Mulai Mengajar</label>
                        <input type="date" name="tanggal_mulai_mengajar" x-model="f.tanggal_mulai_mengajar" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" x-model="f.tempat_lahir" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" x-model="f.tanggal_lahir" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jabatan</label>
                        <input type="text" name="jabatan" x-model="f.jabatan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500" placeholder="mis. Guru Kelas, Kepala Sekolah">
                        <span class="text-4xs text-slate-400 block">Otomatis diisi "Wali Kelas" bila guru ditetapkan sebagai wali kelas di bawah.</span>
                    </div>
                    <div class="space-y-1 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Riwayat Pendidikan</label>
                        <textarea name="riwayat_pendidikan" x-model="f.riwayat_pendidikan" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500" placeholder="mis. S1 PGSD Univ X (2015), ..."></textarea>
                    </div>

                    {{-- Alamat wilayah berjenjang (T2.1) — 4 searchable dropdown; state di f.* (modal). --}}
                    @php
                        $pickers = [
                            'provinsi'  => ['Provinsi', 'provinsi', null],
                            'kota'      => ['Kota/Kabupaten', 'kota', 'provinsi'],
                            'kecamatan' => ['Kecamatan', 'kecamatan', 'kota'],
                            'kelurahan' => ['Desa/Kelurahan', 'kelurahan', 'kecamatan'],
                        ];
                    @endphp
                    @foreach ($pickers as $level => [$label, $key, $parent])
                        <div class="space-y-1"
                             x-data="{ open: false, q: '', items: [], loading: false,
                                async load() {
                                    @if ($parent) if (!f.{{ $parent }}_id) { this.items = []; return; } @endif
                                    this.loading = true;
                                    const u = new URL('{{ route('wilayah', ['level' => $level]) }}', location.origin);
                                    @if ($parent) u.searchParams.set('parent', f.{{ $parent }}_id); @endif
                                    if (this.q) u.searchParams.set('q', this.q);
                                    const r = await fetch(u, { headers: { 'Accept': 'application/json' } });
                                    this.items = r.ok ? await r.json() : []; this.loading = false;
                                },
                                pick(it) {
                                    f.{{ $key }}_id = it.id; f.{{ $key }}_nama = it.nama; this.q = ''; this.open = false;
                                    @foreach ($pickers as [$l2, $k2, $p2]) @if ($p2 === $key) f.{{ $k2 }}_id = ''; f.{{ $k2 }}_nama = ''; @endif @endforeach
                                } }"
                             @click.away="open = false">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">{{ $label }}</label>
                            <input type="hidden" name="{{ $key }}_id" :value="f.{{ $key }}_id">
                            <input type="hidden" name="{{ $key }}_nama" :value="f.{{ $key }}_nama">
                            <div class="relative">
                                <input type="text" autocomplete="off"
                                       :placeholder="f.{{ $key }}_nama || 'Pilih {{ $label }}…'"
                                       :class="f.{{ $key }}_nama ? 'text-slate-900' : 'text-slate-400'"
                                       x-model="q" @focus="open = true; load()" @input.debounce.300ms="open = true; load()"
                                       @if ($parent) :disabled="!f.{{ $parent }}_id" @endif
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500 disabled:bg-slate-100 disabled:cursor-not-allowed">
                                <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full max-h-52 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg">
                                    <template x-if="loading"><div class="px-4 py-2 text-xs text-slate-400">Memuat…</div></template>
                                    <template x-for="it in items" :key="it.id">
                                        <button type="button" @click="pick(it)" class="block w-full text-left px-4 py-2 text-xs hover:bg-indigo-50" x-text="it.nama"></button>
                                    </template>
                                    <template x-if="!loading && items.length === 0"><div class="px-4 py-2 text-xs text-slate-400">Tidak ada hasil.</div></template>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="space-y-1 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Alamat Lengkap (Jalan, RT/RW, No. Rumah)</label>
                        <textarea name="alamat" x-model="f.alamat" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500"></textarea>
                    </div>
                    <div class="space-y-2 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400 block">Kelas yang Diampu (Bisa Banyak)</label>
                        <div class="space-y-2 max-h-[120px] overflow-y-auto border border-slate-200 rounded-xl p-3 bg-slate-50 text-2xs">
                            @forelse ($classes as $kelas)
                                <label class="flex items-center space-x-2 cursor-pointer py-1 hover:bg-slate-100 rounded px-1">
                                    <input type="checkbox" name="class_ids[]" value="{{ $kelas->id_classes }}" x-model.number="f.class_ids" class="rounded text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                    <span class="text-slate-700">{{ $kelas->nama_kelas }}</span>
                                </label>
                            @empty
                                <span class="text-4xs text-slate-400">Belum ada kelas.</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="space-y-1 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Wali Kelas dari (opsional)</label>
                        <select name="homeroom_class_id" x-model="f.homeroom_class_id" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="">— Bukan Wali Kelas —</option>
                            @foreach ($classes as $kelas)
                                <option value="{{ $kelas->id_classes }}">{{ $kelas->nama_kelas }}</option>
                            @endforeach
                        </select>
                        <span class="text-4xs text-slate-400 block">Orang Tua kelas bisa tambah & edit data murid; 1 guru maks 1 kelas.</span>
                    </div>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="open = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // State modal CRUD guru: satu form tambah & edit (T5.4).
    function teacherCrud() {
        const blank = { nama: '', email: '', nuptk: '', no_hp: '', jabatan: '', jenis_kelamin: '', tempat_lahir: '', tanggal_lahir: '', tanggal_mulai_mengajar: '', riwayat_pendidikan: '', alamat: '',
            provinsi_id: '', provinsi_nama: '', kota_id: '', kota_nama: '', kecamatan_id: '', kecamatan_nama: '', kelurahan_id: '', kelurahan_nama: '', class_ids: [], homeroom_class_id: '', foto_path: '' };
        return {
            open: false, mode: 'create', action: '{{ route('admin.teachers.store') }}', f: { ...blank },
            openCreate() { this.mode = 'create'; this.action = '{{ route('admin.teachers.store') }}'; this.f = { ...blank }; this.open = true; },
            openEdit(t) {
                this.mode = 'edit';
                this.action = '/portal/admin/teachers/' + t.id;
                this.f = { ...blank, ...Object.fromEntries(Object.keys(blank).map(k => [k, t[k] ?? blank[k]])) };
                this.open = true;
            },
        };
    }
</script>
@endsection
