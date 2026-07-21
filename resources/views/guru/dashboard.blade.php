@extends('layouts.dashboard')

@section('title', 'Guru Portal — TELAGA AL KAUTSAR')
@section('header_title', 'Portal Guru Pengajar')

@section('content')
<div class="space-y-4" x-data="{ selectedClass: 'all', q: '' }">

{{-- Flash message --}}
@if (session('success'))
    <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">
        {{ session('success') }}
    </div>
@endif
@if ($errors->any())
    <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">
        {{ $errors->first() }}
    </div>
@endif

<!-- Welcome Card (tipis) -->
<div class="bg-white border border-slate-100 rounded-[2rem] px-6 md:px-8 py-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
    <div>
        <span class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Guru Kelas</span>
        <h3 class="text-sm font-bold text-slate-900 leading-tight mt-0.5">Selamat Datang, {{ auth()->user()->displayName() ?? 'Ustadz/Ustadzah' }}</h3>
        <p class="text-xs text-slate-500 mt-0.5">Mengampu <strong>{{ $classes->count() }} kelas</strong>.</p>
    </div>
    @if ($classes->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 md:justify-end md:max-w-md">
            @foreach ($classes as $class)
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-teal-50 border border-teal-100 text-teal-700 text-3xs font-semibold">{{ $class->nama_kelas }}</span>
            @endforeach
        </div>
    @endif
</div>

<!-- Student List Table -->
<div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6" x-data="{ addOpen: false }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-base font-bold text-slate-950">Daftar Murid</h3>
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" x-model="q" placeholder="Cari nama / NIK…" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
            <select x-model="selectedClass" class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:border-teal-500 cursor-pointer">
                <option value="all">Semua Kelas</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->nama_kelas }}">{{ $class->nama_kelas }}</option>
                @endforeach
            </select>
            @if ($homeroomIds)
                <button type="button" @click="addOpen = true" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl text-xs shadow-xs">+ Tambah Murid</button>
            @endif
        </div>
    </div>

    {{-- Modal tambah murid lama (T6.3) — hanya ke kelas yang diampu --}}
        <div x-show="addOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs text-left">
            <div class="bg-white rounded-[2.5rem] p-8 max-w-md w-full border border-slate-100 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto" @click.away="addOpen = false">
                <div class="flex justify-between items-start">
                    <h3 class="text-lg font-bold text-slate-950">Tambah Murid (Data Lama)</h3>
                    <button type="button" @click="addOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <form action="{{ route('guru.students.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1 col-span-2">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pas Foto (opsional, JPG/PNG maks 2 MB)</label>
                            <input type="file" name="foto" accept="image/*" class="w-full text-xs text-slate-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-teal-50 file:text-teal-600 file:font-bold file:text-xs">
                        </div>
                        <div class="space-y-1 col-span-2">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NIK (16 digit)</label>
                            <input type="text" name="nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16"  required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NISN (opsional)</label>
                            <input type="text" name="nisn" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NIS (15-18 digit, opsional)</label>
                            <input type="text" name="nis" inputmode="numeric" pattern="[0-9]{15,18}" maxlength="18" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jenis Kelamin</label>
                            <select name="jenis_kelamin" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                <option value="L">Laki-laki</option><option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Kelas</label>
                            <select name="id_class" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                @foreach ($classes as $class)
                                    @if (in_array($class->id_classes, $homeroomIds))
                                        <option value="{{ $class->id_classes }}">{{ $class->nama_kelas }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                        </div>
                        <div class="space-y-1">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                        </div>

                        {{-- Alamat wilayah murid (T2.1) — picker lokal per modal (bukan x-model). --}}
                        <div class="col-span-2 grid grid-cols-2 gap-3"
                             x-data="{ prov:{id:'',nama:''}, kota:{id:'',nama:''}, kec:{id:'',nama:''}, kel:{id:'',nama:''} }">
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
                                     x-data="{ open:false, q:'', items:[], loading:false,
                                        async load() {
                                            @if ($parent) if (!{{ $parent }}.id) { this.items=[]; return; } @endif
                                            this.loading=true;
                                            const u=new URL('{{ route('wilayah', ['level' => $level]) }}', location.origin);
                                            @if ($parent) u.searchParams.set('parent',{{ $parent }}.id); @endif
                                            if(this.q) u.searchParams.set('q',this.q);
                                            const r=await fetch(u,{headers:{'Accept':'application/json'}});
                                            this.items=r.ok?await r.json():[]; this.loading=false;
                                        },
                                        pick(it){ {{ $var }}.id=it.id; {{ $var }}.nama=it.nama; this.q=''; this.open=false;
                                            @foreach (array_keys($pickers) as $lvl)@php [$l2,$v2,$p2]=$pickers[$lvl];@endphp @if ($p2===$var){{ $v2 }}.id='';{{ $v2 }}.nama='';@endif @endforeach
                                        } }"
                                     @click.away="open=false">
                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">{{ $label }}</label>
                                    <input type="hidden" name="{{ $level }}_id" :value="{{ $var }}.id">
                                    <input type="hidden" name="{{ $level }}_nama" :value="{{ $var }}.nama">
                                    <div class="relative">
                                        <input type="text" autocomplete="off" :placeholder="{{ $var }}.nama || 'Pilih…'"
                                               :class="{{ $var }}.nama ? 'text-slate-900' : 'text-slate-400'"
                                               x-model="q" @focus="open=true; load()" @input.debounce.300ms="open=true; load()"
                                               @if ($parent) :disabled="!{{ $parent }}.id" @endif
                                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500 disabled:bg-slate-100 disabled:cursor-not-allowed">
                                        <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg">
                                            <template x-if="loading"><div class="px-4 py-2 text-xs text-slate-400">Memuat…</div></template>
                                            <template x-for="it in items" :key="it.id">
                                                <button type="button" @click="pick(it)" class="block w-full text-left px-4 py-2 text-xs hover:bg-teal-50" x-text="it.nama"></button>
                                            </template>
                                            <template x-if="!loading && items.length===0"><div class="px-4 py-2 text-xs text-slate-400">Tidak ada hasil.</div></template>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="space-y-1 col-span-2">
                            <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Alamat Lengkap (Jalan, RT/RW)</label>
                            <textarea name="alamat" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500"></textarea>
                        </div>
                    </div>
                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="addOpen = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-md text-xs">Simpan Murid</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">Nama Lengkap</th>
                        <th class="py-4">NISN</th>
                        <th class="py-4">Kelas</th>
                        <th class="py-4">Wali Kelas</th>
                        <th class="py-4">Status</th>
                        <th class="py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($students as $student)
                        @php
                            $kelasNama = $student->schoolClass?->nama_kelas ?? '-';
                            $badge = $student->status === 'aktif'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-rose-100 text-rose-700';
                        @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors"
                            x-data="{ editOpen: false }"
                            x-show="(selectedClass === 'all' || selectedClass === '{{ $kelasNama }}')
                                && (!q
                                    || '{{ \Illuminate\Support\Str::lower(addslashes($student->nama_lengkap)) }}'.includes(q.toLowerCase())
                                    || '{{ addslashes($student->nik ?? '') }}'.includes(q))">
                            <td class="py-4 font-bold text-slate-900">
                                <div class="flex items-center gap-3">
                                    <x-avatar :path="$student->foto_path" :name="$student->nama_lengkap" size="w-9 h-9" class="!rounded-lg !text-sm shrink-0" />
                                    <span>{{ $student->nama_lengkap }}</span>
                                </div>
                            </td>
                            <td class="py-4 font-bold text-slate-800">{{ $student->nisn ?? '-' }}</td>
                            <td class="py-4 text-slate-500">{{ $kelasNama }}</td>
                            <td class="py-4 text-slate-500">{{ $student->schoolClass?->homeroomTeacher?->nama ?? '-' }}</td>
                            <td class="py-4">
                                <span class="inline-block px-2.5 py-0.5 rounded text-4xs font-bold uppercase tracking-wide {{ $badge }}">
                                    {{ ucfirst($student->status ?? 'nonaktif') }}
                                </span>
                            </td>
                            <td class="py-4 text-right shrink-0">
                                <div class="inline-flex space-x-1">
                                    <a href="{{ route('guru.students.show', $student) }}" class="px-2.5 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg">
                                        Profil
                                    </a>
                                    @if (in_array($student->id_class, $homeroomIds))
                                        <button type="button" @click="editOpen = true" class="px-2.5 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg">
                                            Edit Profil
                                        </button>
                                    @endif
                                </div>

                                <!-- Edit Profil Modal (form asli PUT) -->
                                <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs text-left" x-cloak>
                                    <div class="bg-white rounded-[2.5rem] p-8 max-w-md w-full border border-slate-100 shadow-2xl space-y-6" @click.away="editOpen = false">
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-bold text-slate-950">Edit Profil Murid</h3>
                                            <button type="button" @click="editOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>

                                        <form action="{{ route('guru.students.update', $student) }}" method="POST" class="space-y-4">
                                            @csrf
                                            @method('PUT')

                                            <div class="space-y-1">
                                                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Lengkap</label>
                                                <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $student->nama_lengkap) }}" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                            </div>

                                            <div class="space-y-1">
                                                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Panggilan</label>
                                                <input type="text" name="nama_panggilan" value="{{ old('nama_panggilan', $student->nama_panggilan) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                            </div>

                                            <div class="space-y-1">
                                                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tempat Lahir</label>
                                                <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $student->tempat_lahir) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                            </div>

                                            <div class="space-y-1">
                                                <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Lahir</label>
                                                <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($student->tanggal_lahir)->format('Y-m-d')) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                            </div>

                                            {{-- L1.2: field profil murid (alamat kini di profil orang tua). --}}
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jenis Kelamin</label>
                                                    <select name="jenis_kelamin" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                        <option value="L" @selected($student->jenis_kelamin==='L')>Laki-laki</option>
                                                        <option value="P" @selected($student->jenis_kelamin==='P')>Perempuan</option>
                                                    </select>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Agama</label>
                                                    <select name="agama" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                        @foreach(['ISLAM','KRISTEN','KATOLIK','HINDU','BUDDHA','KONGHUCU'] as $o)<option value="{{ $o }}" @selected($student->agama===$o)>{{ $o }}</option>@endforeach
                                                    </select>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Anak ke-</label>
                                                    <input type="number" name="anak_ke" min="1" value="{{ old('anak_ke', $student->anak_ke) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jumlah Saudara</label>
                                                    <input type="number" name="jumlah_saudara" min="0" value="{{ old('jumlah_saudara', $student->jumlah_saudara) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Warga Negara</label>
                                                    <input type="text" name="warga_negara" value="{{ old('warga_negara', $student->warga_negara) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Bahasa Keseharian</label>
                                                    <input type="text" name="bahasa_keseharian" value="{{ old('bahasa_keseharian', $student->bahasa_keseharian) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                </div>
                                                <div class="space-y-1 col-span-2">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Kondisi Kesehatan Khusus</label>
                                                    <input type="text" name="kondisi_kesehatan" value="{{ old('kondisi_kesehatan', $student->kondisi_kesehatan) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Ukuran Baju</label>
                                                    <select name="ukuran_baju" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                        <option value="">-</option>
                                                        @foreach(['S','M','L','XL','XXL','Jumbo'] as $o)<option value="{{ $o }}" @selected($student->ukuran_baju===$o)>{{ $o }}</option>@endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            {{-- Cabang mengaji --}}
                                            <div x-data="{ mengaji: '{{ $student->sudah_mengaji }}' }" class="space-y-3">
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Sudah Mengaji?</label>
                                                    <select name="sudah_mengaji" x-model="mengaji" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                        <option value="">-</option><option value="Sudah">Sudah</option><option value="Belum">Belum</option>
                                                    </select>
                                                </div>
                                                <div x-show="mengaji==='Sudah'" x-cloak class="grid grid-cols-3 gap-3">
                                                    <div class="space-y-1"><label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Di Mana</label><input type="text" name="ngaji_dimana" value="{{ $student->ngaji_dimana }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500"></div>
                                                    <div class="space-y-1"><label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Metode</label><input type="text" name="ngaji_metode" value="{{ $student->ngaji_metode }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500"></div>
                                                    <div class="space-y-1"><label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jilid</label><input type="text" name="ngaji_jilid" value="{{ $student->ngaji_jilid }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500"></div>
                                                </div>
                                            </div>
                                            {{-- Cabang pernah belajar --}}
                                            <div x-data="{ belajar: '{{ $student->pernah_belajar }}' }" class="space-y-3">
                                                <div class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pernah Belajar?</label>
                                                    <select name="pernah_belajar" x-model="belajar" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                        <option value="">-</option><option value="PAUD">PAUD</option><option value="Les">Les</option><option value="Belum">Belum</option>
                                                    </select>
                                                </div>
                                                <div x-show="belajar==='PAUD'||belajar==='Les'" x-cloak class="space-y-1">
                                                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Keterangan</label>
                                                    <input type="text" name="belajar_keterangan" value="{{ $student->belajar_keterangan }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-teal-500">
                                                </div>
                                            </div>

                                            <div class="flex space-x-3 pt-2">
                                                <button type="button" @click="editOpen = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600 transition-colors">Batal</button>
                                                <button type="submit" class="flex-1 py-3 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Profil</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center">
                                <p class="text-sm font-bold text-slate-900">Belum Ada Murid</p>
                                <p class="text-xs text-slate-500 mt-1">Belum ada murid terdaftar pada kelas yang Anda ampu.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
