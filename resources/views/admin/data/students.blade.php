@extends('layouts.dashboard')

@section('title', 'Data Murid — RA Al Kautsar')
@section('header_title', 'Data Murid')

@section('content')
<div class="space-y-8" x-data="studentCrud()">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h3 class="text-base font-bold text-slate-950">Data Murid</h3>
            <div class="flex gap-2">
                <a href="{{ route('admin.students.import') }}" class="px-4 py-2.5 border border-indigo-200 text-indigo-700 hover:bg-indigo-50 font-bold rounded-xl text-xs transition-colors">Import CSV</a>
                <button @click="openCreate()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">+ Tambah Murid</button>
            </div>
        </div>

        {{-- Filter + pencarian (T5.2) — GET, native form --}}
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / NIK / NISN…" class="sm:col-span-2 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
            <select name="id_class" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                <option value="">Semua Kelas</option>
                <option value="none" @selected(request('id_class') === 'none')>— Belum Dapat Kelas —</option>
                @foreach ($classes as $kelas)
                    <option value="{{ $kelas->id_classes }}" @selected(request('id_class') == $kelas->id_classes)>{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                <option value="semua" @selected($status === 'semua')>Semua Status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="lulus" @selected($status === 'lulus')>Alumni (Lulus)</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Drop Out (Nonaktif)</option>
            </select>
            <div class="sm:col-span-4 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs">Cari</button>
                <a href="{{ route('admin.data.students') }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">Nama Murid</th>
                        <th class="py-4">NISN</th>
                        <th class="py-4">Wali Kelas</th>
                        <th class="py-4">Kelas</th>
                        <th class="py-4">Orang Tua</th>
                        <th class="py-4">No. HP Ortu</th>
                        <th class="py-4">Angkatan</th>
                        <th class="py-4">Status</th>
                        <th class="py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($students as $student)
                        @php
                            $stClass = ['aktif' => 'bg-emerald-100 text-emerald-700', 'lulus' => 'bg-sky-100 text-sky-700'][$student->status] ?? 'bg-slate-100 text-slate-500';
                        @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors">
                            <td class="py-4 font-bold text-slate-900">{{ $student->nama_lengkap }}</td>
                            <td class="py-4 font-semibold text-slate-500">{{ $student->nisn ?? '—' }}</td>
                            <td class="py-4 text-slate-500">{{ $student->schoolClass?->homeroomTeacher?->nama ?? '-' }}</td>
                            <td class="py-4 font-semibold text-slate-700">{{ $student->schoolClass?->nama_kelas ?? 'Belum Terbagi' }}</td>
                            <td class="py-4 text-slate-500">{{ $student->ortu?->namaWali() ?? '-' }}</td>
                            <td class="py-4 font-semibold text-slate-500">{{ $student->ortu?->noHpWali() ?? '-' }}</td>
                            <td class="py-4 text-slate-500">{{ $student->angkatan ?? '—' }}</td>
                            <td class="py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-4xs font-bold uppercase tracking-wider {{ $stClass }}">{{ $student->status ?? '-' }}</span>
                            </td>
                            <td class="py-4 text-right whitespace-nowrap">
                                @php
                                    $row = [
                                        'id' => $student->id_students, 'nama_lengkap' => $student->nama_lengkap,
                                        'nama_panggilan' => $student->nama_panggilan, 'nik' => $student->nik,
                                        'nisn' => $student->nisn, 'nis' => $student->nis, 'angkatan' => $student->angkatan,
                                        'jenis_kelamin' => $student->jenis_kelamin, 'tempat_lahir' => $student->tempat_lahir,
                                        'tanggal_lahir' => optional($student->tanggal_lahir)->format('Y-m-d'),
                                        'id_class' => $student->id_class, 'status' => $student->status,
                                                            'agama' => $student->agama, 'anak_ke' => $student->anak_ke,
                                                            'jumlah_saudara' => $student->jumlah_saudara,
                                                            'warga_negara' => $student->warga_negara,
                                                            'bahasa_keseharian' => $student->bahasa_keseharian,
                                                            'kondisi_kesehatan' => $student->kondisi_kesehatan,
                                                            'sudah_mengaji' => $student->sudah_mengaji,
                                                            'ngaji_dimana' => $student->ngaji_dimana,
                                                            'ngaji_metode' => $student->ngaji_metode,
                                                            'ngaji_jilid' => $student->ngaji_jilid,
                                                            'pernah_belajar' => $student->pernah_belajar,
                                                            'belajar_keterangan' => $student->belajar_keterangan,
                                                            'ukuran_baju' => $student->ukuran_baju,
                                                            'foto_path' => $student->foto_path,
                                    ];
                                @endphp
                                <a href="{{ route('admin.students.show', $student) }}" class="px-2.5 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-lg">Profil</a>
                                @unless ($student->ortu?->user)
                                    <button @click="openWali({{ $student->id_students }})" class="px-2.5 py-1.5 border border-emerald-200 hover:bg-emerald-50 text-emerald-600 font-bold rounded-lg">+ Akun Orang Tua</button>
                                @endunless
                                <button @click="openEdit({{ \Illuminate\Support\Js::from($row) }})" class="px-2.5 py-1.5 border border-indigo-200 hover:bg-indigo-50 text-indigo-600 font-bold rounded-lg">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-6 text-center text-slate-400">Belum ada data murid.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginate :paginator="$students" />
    </div>

    {{-- Modal tambah/edit murid (satu form dipakai dua mode) --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-lg w-full border border-slate-100 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto" @click.away="open = false">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold text-slate-950" x-text="mode === 'edit' ? 'Edit Data Murid' : 'Tambah Murid'"></h3>
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
                        <input type="text" name="nama_lengkap" x-model="f.nama_lengkap" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Panggilan</label>
                        <input type="text" name="nama_panggilan" x-model="f.nama_panggilan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NIK (16 digit)</label>
                        <input type="text" name="nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" x-model="f.nik"  required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NISN (10 digit, opsional)</label>
                        <input type="text" name="nisn" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" x-model="f.nisn"  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">NIS (15-18 digit, opsional)</label>
                        <input type="text" name="nis" inputmode="numeric" pattern="[0-9]{15,18}" maxlength="18" x-model="f.nis" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Angkatan (4 digit tahun)</label>
                        <input type="number" name="angkatan" min="2000" max="2099" x-model="f.angkatan" placeholder="Auto dari NIS bila diisi" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jenis Kelamin</label>
                        <select name="jenis_kelamin" x-model="f.jenis_kelamin" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Status</label>
                        <select name="status" x-model="f.status" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="aktif">Aktif</option>
                            <option value="lulus">Alumni (Lulus)</option>
                            <option value="nonaktif">Drop Out (Nonaktif)</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" x-model="f.tempat_lahir" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" x-model="f.tanggal_lahir" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    {{-- L1.2: field profil murid (alamat kini di profil orang tua, tak di sini). --}}
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Agama</label>
                        <select name="agama" x-model="f.agama" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            @foreach (['ISLAM','KRISTEN','KATOLIK','HINDU','BUDDHA','KONGHUCU'] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Ukuran Baju</label>
                        <select name="ukuran_baju" x-model="f.ukuran_baju" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="">-</option>
                            @foreach (['S','M','L','XL','XXL','Jumbo'] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Anak ke-</label>
                        <input type="number" name="anak_ke" x-model="f.anak_ke" min="1" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jumlah Saudara</label>
                        <input type="number" name="jumlah_saudara" x-model="f.jumlah_saudara" min="0" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Warga Negara</label>
                        <input type="text" name="warga_negara" x-model="f.warga_negara" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Bahasa Keseharian</label>
                        <input type="text" name="bahasa_keseharian" x-model="f.bahasa_keseharian" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Kondisi Kesehatan Khusus</label>
                        <input type="text" name="kondisi_kesehatan" x-model="f.kondisi_kesehatan" placeholder="Tulis 'TIDAK ADA' bila tak ada" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div>
                    {{-- Cabang: mengaji --}}
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Sudah Mengaji?</label>
                        <select name="sudah_mengaji" x-model="f.sudah_mengaji" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="">-</option><option value="Sudah">Sudah</option><option value="Belum">Belum</option>
                        </select>
                    </div>
                    <template x-if="f.sudah_mengaji === 'Sudah'"><div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Ngaji Di Mana</label>
                        <input type="text" name="ngaji_dimana" x-model="f.ngaji_dimana" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div></template>
                    <template x-if="f.sudah_mengaji === 'Sudah'"><div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Metode Ngaji</label>
                        <input type="text" name="ngaji_metode" x-model="f.ngaji_metode" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div></template>
                    <template x-if="f.sudah_mengaji === 'Sudah'"><div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jilid</label>
                        <input type="text" name="ngaji_jilid" x-model="f.ngaji_jilid" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div></template>
                    {{-- Cabang: pernah belajar --}}
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Pernah Belajar?</label>
                        <select name="pernah_belajar" x-model="f.pernah_belajar" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="">-</option><option value="PAUD">PAUD</option><option value="Les">Les</option><option value="Belum">Belum</option>
                        </select>
                    </div>
                    <template x-if="f.pernah_belajar === 'PAUD' || f.pernah_belajar === 'Les'"><div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Keterangan (nama PAUD/Les)</label>
                        <input type="text" name="belajar_keterangan" x-model="f.belajar_keterangan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                    </div></template>
                    <div class="space-y-1 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Kelas</label>
                        <select name="id_class" x-model="f.id_class" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                            <option value="">Belum Terbagi</option>
                            @foreach ($classes as $kelas)
                                <option value="{{ $kelas->id_classes }}">{{ $kelas->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- L8.1: no HP ortu (mode tambah). Isi → auto-buat akun ortu (username=NIK, password=NIK anak, paksa ganti). Kosong → akun via T9.1 nanti. --}}
                    <template x-if="mode === 'create'"><div class="space-y-1 col-span-2">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">No. HP Orang Tua (opsional — buat akun login otomatis)</label>
                        <input type="text" name="no_hp_ortu" inputmode="numeric" pattern="[0-9]{9,14}" x-model="f.no_hp_ortu" placeholder="Kosongkan bila belum ada" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                        <p class="text-4xs text-slate-400">Diisi → akun orang tua dibuat (login &amp; sandi awal = NIK anak, wajib ganti saat login). No. HP tersimpan sebagai kontak Ibu.</p>
                    </div></template>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="open = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Buatkan Akun Orang Tua (T9.1): admin isi no_hp+nama; backend firstOrCreate by no_hp. --}}
    <div x-show="waliOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-sm w-full border border-slate-100 shadow-2xl space-y-6" @click.away="waliOpen = false">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold text-slate-950">Buatkan Akun Orang Tua</h3>
                <button type="button" @click="waliOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <p class="text-xs text-slate-400">Login & password awal = No. HP orang tua. Jika No. HP sudah terdaftar (kakak/adik), murid ini otomatis ditautkan ke akun orang tua yang sama.</p>
            <form :action="waliAction" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Orang Tua</label>
                    <input type="text" name="nama" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">No. HP Orang Tua</label>
                    <input type="text" name="no_hp" inputmode="numeric" pattern="[0-9]{9,14}" required  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="waliOpen = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-md text-xs">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // State modal CRUD murid: satu form dipakai untuk tambah & edit (T5.2).
    function studentCrud() {
        const blank = { nama_lengkap: '', nama_panggilan: '', nik: '', nisn: '', nis: '', angkatan: '', jenis_kelamin: 'L', tempat_lahir: '', tanggal_lahir: '', id_class: '', status: 'aktif',
            agama: 'ISLAM', anak_ke: '', jumlah_saudara: '', warga_negara: 'INDONESIA', bahasa_keseharian: '', kondisi_kesehatan: '',
            sudah_mengaji: '', ngaji_dimana: '', ngaji_metode: '', ngaji_jilid: '',
            pernah_belajar: '', belajar_keterangan: '', ukuran_baju: '', foto_path: '', no_hp_ortu: '' };
        return {
            open: false, mode: 'create', action: '{{ route('admin.students.store') }}', f: { ...blank },
            openCreate() {
                this.mode = 'create';
                this.action = '{{ route('admin.students.store') }}';
                this.f = { ...blank };
                this.open = true;
            },
            openEdit(s) {
                this.mode = 'edit';
                this.action = '/portal/admin/data/students/' + s.id;
                this.f = { ...blank, ...Object.fromEntries(Object.keys(blank).map(k => [k, s[k] ?? blank[k]])) };
                this.open = true;
            },
            // T9.1: buka modal buatkan akun orang tua untuk murid lama (action per-murid).
            waliOpen: false, waliAction: '',
            openWali(id) {
                this.waliAction = '/portal/admin/data/students/' + id + '/ortu-account';
                this.waliOpen = true;
            },
        };
    }
</script>
@endsection
