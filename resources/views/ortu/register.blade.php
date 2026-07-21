@extends('layouts.dashboard')

@section('title', 'Pendaftaran Murid Baru — TELAGA AL KAUTSAR')
@section('header_title', 'Form Pendaftaran Murid Baru')

@section('content')
@php
    // Mode edit (K2.5): $form terisi → prefill data murid & ubah target submit.
    $form = $form ?? null;
    $s = $form?->student;
    $isEdit = (bool) $form;
    $val = fn ($key, $default = null) => old($key, $default);
    // Cut-off usia dari TA PPDB (dipakai di x-data validateStep & input tanggal_lahir).
    $_taThn = \App\Models\AcademicYear::taPpdb()->tahun;
    $_tA = $_taThn ? (int) explode('/', $_taThn)[0] : now()->year;
    $_min = ($_tA - 6).'-07-01';
    $_max = ($_tA - 4).'-07-01';
@endphp
<form action="{{ $isEdit ? route('ortu.register.update', $form) : route('ortu.register.store') }}" method="POST" enctype="multipart/form-data"
    class="max-w-3xl mx-auto bg-white border border-slate-100 rounded-[2rem] shadow-xl p-8" x-data="{
    step: 1,
    isEdit: {{ $isEdit ? 'true' : 'false' }},
    errors: {},
    nik: '{{ old('nik', $s?->nik) }}',
    kkFile: null,
    aktaFile: null,
    fotoFile: null,
    validateStep(stepNum) {
        this.errors = {};
        if (stepNum === 1) {
            // cek semua [required] di step 1 — generic, nol hardcode per-field
            $refs.step1.querySelectorAll('[required]').forEach(el => {
                if (!el.value.trim()) {
                    const key = el.name || el.getAttribute('x-ref') || 'field';
                    this.errors[key] = (el.labels?.[0]?.textContent.trim() || key) + ' wajib diisi';
                }
            });
            // NIK 16 digit tetap spesifik karena formatnya unik
            if (!/^\d{16}$/.test(this.nik)) this.errors.nik = 'NIK harus 16 digit angka';
            // tanggal lahir range
            const tgl = $refs.tanggal_lahir.value;
            if (tgl && (tgl < '{{ $_min }}' || tgl > '{{ $_max }}'))
                this.errors.tanggal_lahir = 'Usia tidak dalam rentang 4–6 tahun per 1 Juli {{ $_tA }}';
        }
        if (stepNum === 2 && !this.isEdit) {
            if (!this.kkFile) this.errors.kkFile = 'Kartu Keluarga wajib diunggah';
            else if (this.kkFile.size > 2 * 1024 * 1024) this.errors.kkFile = 'File KK maksimal 2 MB';
            else if (!['image/jpeg', 'application/pdf'].includes(this.kkFile.type)) this.errors.kkFile = 'Format harus JPG atau PDF';
            if (!this.aktaFile) this.errors.aktaFile = 'Akta Kelahiran wajib diunggah';
            else if (this.aktaFile.size > 2 * 1024 * 1024) this.errors.aktaFile = 'File Akta maksimal 2 MB';
            else if (!['image/jpeg', 'application/pdf'].includes(this.aktaFile.type)) this.errors.aktaFile = 'Format harus JPG atau PDF';
            if (!this.fotoFile) this.errors.fotoFile = 'Foto anak wajib diunggah';
            else if (this.fotoFile.size > 2 * 1024 * 1024) this.errors.fotoFile = 'File Foto maksimal 2 MB';
            else if (!['image/jpeg'].includes(this.fotoFile.type)) this.errors.fotoFile = 'Format harus JPG';
        }
        return Object.keys(this.errors).length === 0;
    },
    goToStep(n) {
        if (this.validateStep(this.step)) { this.step = n; }
    }
}">
    @csrf

    {{-- Tampilkan error validasi dari server (mis. NIK sudah terdaftar) --}}
    @if ($errors->any())
        <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs text-rose-600 font-semibold">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Progress Steps Header -->
    <div class="flex items-center justify-between mb-12">
        <div class="flex flex-col items-center space-y-2 flex-1">
            <div :class="step >= 1 ? 'bg-sky-500 text-white shadow-md shadow-sky-100' : 'bg-slate-100 text-slate-400'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300">1</div>
            <span class="text-3xs font-extrabold uppercase tracking-wider" :class="step >= 1 ? 'text-sky-600' : 'text-slate-400'">Data Anak</span>
        </div>
        <div :class="step >= 2 ? 'bg-sky-500' : 'bg-slate-100'" class="h-1 flex-1 -mt-5 transition-colors duration-300"></div>
        <div class="flex flex-col items-center space-y-2 flex-1">
            <div :class="step >= 2 ? 'bg-sky-500 text-white shadow-md shadow-sky-100' : 'bg-slate-100 text-slate-400'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300">2</div>
            <span class="text-3xs font-extrabold uppercase tracking-wider" :class="step >= 2 ? 'text-sky-600' : 'text-slate-400'">Upload Berkas</span>
        </div>
    </div>

    <!-- Step 1: Data Anak -->
    <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6" x-ref="step1">
        <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Langkah 1: Profil & NIK Anak</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Nama Lengkap Anak</label>
                <input type="text" name="nama_anak" x-ref="nama_anak" required value="{{ old('nama_anak', $s?->nama_lengkap) }}" :class="errors.namaAnak ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50'" class="w-full px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all border">
                <template x-if="errors.namaAnak"><span class="text-4xs text-rose-600 font-semibold" x-text="errors.namaAnak"></span></template>
            </div>
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Nama Panggilan</label>
                <input type="text" name="nama_panggilan" required value="{{ old('nama_panggilan', $s?->nama_panggilan) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Nomor Induk Kependudukan (NIK)</label>
                <input type="text" name="nik" x-model="nik" required maxlength="16" :class="errors.nik ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50'" class="w-full px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all border" placeholder="16 digit NIK wajib">
                <template x-if="errors.nik"><span class="text-4xs text-rose-600 font-semibold" x-text="errors.nik"></span></template>
            </div>
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Jenis Kelamin</label>
                <select name="jenis_kelamin" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all">
                    <option value="L" @selected(old('jenis_kelamin', $s?->jenis_kelamin) === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin', $s?->jenis_kelamin) === 'P')>Perempuan</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" x-ref="tempat_lahir" required value="{{ old('tempat_lahir', $s?->tempat_lahir) }}" :class="errors.tempatLahir ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50'" class="w-full px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all border">
                <template x-if="errors.tempatLahir"><span class="text-4xs text-rose-600 font-semibold" x-text="errors.tempatLahir"></span></template>
            </div>
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" x-ref="tanggal_lahir" required
                       min="{{ $_min }}" max="{{ $_max }}"
                       @change="
                           errors.tanggalLahir = '';
                           if (!$el.value) errors.tanggalLahir = 'Tanggal lahir wajib diisi';
                           else if ($el.value < '{{ $_min }}') errors.tanggalLahir = 'Usia calon murid maksimal 6 tahun per 1 Juli {{ $_tA }}';
                           else if ($el.value > '{{ $_max }}') errors.tanggalLahir = 'Usia calon murid minimal 4 tahun per 1 Juli {{ $_tA }}';
                       "
                       value="{{ old('tanggal_lahir', $s?->tanggal_lahir?->format('Y-m-d')) }}"
                       :class="errors.tanggalLahir ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50'"
                       class="w-full px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all border">
                <template x-if="errors.tanggalLahir"><span class="text-4xs text-rose-600 font-semibold" x-text="errors.tanggalLahir"></span></template>
            </div>
        </div>

        {{-- L1.2: field profil murid tambahan. Alamat kini di profil orang tua (tak di sini). --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Agama</label>
                <select name="agama" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                    @foreach (['ISLAM','KRISTEN','KATOLIK','HINDU','BUDDHA','KONGHUCU'] as $o)
                        <option value="{{ $o }}" @selected(old('agama', $s?->agama ?? 'ISLAM') === $o)>{{ $o }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Anak ke-</label>
                <input type="number" name="anak_ke" required min="1" value="{{ old('anak_ke', $s?->anak_ke) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
            </div>
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Jumlah Saudara</label>
                <input type="number" name="jumlah_saudara" required min="0" value="{{ old('jumlah_saudara', $s?->jumlah_saudara) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Warga Negara</label>
                <input type="text" name="warga_negara" required value="{{ old('warga_negara', $s?->warga_negara ?? 'INDONESIA') }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
            </div>
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Bahasa Keseharian</label>
                <input type="text" name="bahasa_keseharian" required value="{{ old('bahasa_keseharian', $s?->bahasa_keseharian) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Kondisi Kesehatan Khusus</label>
                <input type="text" name="kondisi_kesehatan" required value="{{ old('kondisi_kesehatan', $s?->kondisi_kesehatan) }}" placeholder="Tulis 'TIDAK ADA' bila tak ada" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
            </div>
            <div class="space-y-1">
                <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Ukuran Baju</label>
                <select name="ukuran_baju" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                    <option value="">-</option>
                    @foreach (['S','M','L','XL','XXL','Jumbo'] as $o)
                        <option value="{{ $o }}" @selected(old('ukuran_baju', $s?->ukuran_baju) === $o)>{{ $o }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Cabang: sudah mengaji --}}
        <div class="space-y-4" x-data="{ mengaji: '{{ old('sudah_mengaji', $s?->sudah_mengaji) }}' }">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Sudah Mengaji?</label>
                    <select name="sudah_mengaji" required x-model="mengaji" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                        <option value="">-</option>
                        <option value="Sudah">Sudah</option>
                        <option value="Belum">Belum</option>
                    </select>
                </div>
            </div>
            <div x-show="mengaji === 'Sudah'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Ngaji Di Mana</label>
                    <input type="text" name="ngaji_dimana" value="{{ old('ngaji_dimana', $s?->ngaji_dimana) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                </div>
                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Metode</label>
                    <input type="text" name="ngaji_metode" value="{{ old('ngaji_metode', $s?->ngaji_metode) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                </div>
                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Jilid</label>
                    <input type="text" name="ngaji_jilid" value="{{ old('ngaji_jilid', $s?->ngaji_jilid) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                </div>
            </div>
        </div>

        {{-- Cabang: pernah belajar --}}
        <div class="space-y-4" x-data="{ belajar: '{{ old('pernah_belajar', $s?->pernah_belajar) }}' }">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Pernah Belajar Sebelumnya?</label>
                    <select name="pernah_belajar" required x-model="belajar" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                        <option value="">-</option>
                        <option value="PAUD">PAUD</option>
                        <option value="Les">Les</option>
                        <option value="Belum">Belum</option>
                    </select>
                </div>
                <div class="space-y-1" x-show="belajar === 'PAUD' || belajar === 'Les'" x-cloak>
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Keterangan (nama PAUD / Les)</label>
                    <input type="text" name="belajar_keterangan" value="{{ old('belajar_keterangan', $s?->belajar_keterangan) }}" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                </div>
            </div>
        </div>

        <div class="flex flex-col items-end gap-2 pt-4">
            <template x-if="Object.keys(errors).length">
                <span class="text-xs text-rose-600 font-semibold">Lengkapi semua field wajib sebelum lanjut.</span>
            </template>
            <button type="button" @click="goToStep(2)" class="px-6 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-lg shadow-sky-100 hover:shadow-sky-200 transition-all flex items-center space-x-2">
                <span>Langkah Selanjutnya</span><span>&rarr;</span>
            </button>
        </div>
    </div>

    <!-- Step 2: Upload Dokumen -->
    <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Langkah 2: Unggah Dokumen Wajib</h3>
        @if ($isEdit)
            <div class="bg-sky-50 border border-sky-100 text-sky-800 text-xs rounded-xl p-4">
                Mode edit: kosongkan berkas bila tidak ingin menggantinya. Berkas yang diunggah ulang akan diverifikasi ulang oleh Admin.
            </div>
        @endif
        <div class="bg-amber-50 border border-amber-100 text-amber-800 text-xs rounded-xl p-4 space-y-1">
            <h4 class="font-bold flex items-center space-x-1">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.072 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <span>Ketentuan Dokumen:</span>
            </h4>
            <ul class="list-disc list-inside space-y-0.5 text-2xs text-amber-700/90 pl-1">
                <li>Kartu Keluarga & Akta Kelahiran berformat <strong>JPG / PDF</strong>.</li>
                <li>Foto Anak berformat <strong>JPG</strong>.</li>
                <li>Masing-masing file berukuran maksimal <strong>2 MB</strong>.</li>
            </ul>
        </div>
        @php
            // Berkas lama per-jenis (mode edit) → ditampilkan agar orang tua tak bingung (poin 2/3).
            $docByJenis = $isEdit ? $form->documents->keyBy('jenis') : collect();
            $uploads = [
                ['kk',   'kk',   'Kartu Keluarga (KK) *', 'Format: JPG, PDF (Maksimal 2 MB)', '.jpg,.jpeg,.pdf', 'kkFile'],
                ['akta', 'akta', 'Akta Kelahiran *',      'Format: JPG, PDF (Maksimal 2 MB)', '.jpg,.jpeg,.pdf', 'aktaFile'],
                ['foto', 'foto', 'Foto Anak *',           'Format: JPG (Maksimal 2 MB)',      '.jpg,.jpeg',      'fotoFile'],
            ];
        @endphp
        <div class="space-y-5">
            @foreach ($uploads as [$name, $jenis, $label, $hint, $accept, $fileVar])
                @php
                    $doc = $docByJenis[$jenis] ?? null;
                    $docUrl = $doc?->path ? asset('storage/'.$doc->path) : null;
                    $docGambar = $doc?->path && preg_match('/\.(jpe?g|png|gif|webp)$/i', $doc->path);
                @endphp
                <div x-data="{ preview: null, previewName: '' }">
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50 flex items-center justify-between gap-4"
                         :class="errors.{{ $fileVar }} ? 'border-rose-300' : ''">
                        <div class="space-y-1 min-w-0">
                            <span class="text-xs font-bold text-slate-800 block">{{ $label }}</span>
                            <span class="text-3xs text-slate-400 block">{{ $hint }}</span>
                            {{-- Berkas lama (mode edit): thumbnail klik-untuk-lihat --}}
                            @if ($docUrl)
                                <button type="button" onclick="openBerkas('{{ $docUrl }}')" x-show="!preview"
                                        class="mt-1 inline-flex items-center gap-1.5 text-3xs font-bold text-sky-600 hover:underline">
                                    <span class="w-8 h-8 rounded border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center bg-white">
                                        @if ($docGambar)
                                            <img src="{{ $docUrl }}" class="w-full h-full object-cover" alt="Berkas saat ini">
                                        @else
                                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        @endif
                                    </span>
                                    <span>Berkas saat ini</span>
                                </button>
                            @endif
                            {{-- Preview file yang baru dipilih (poin 1): gambar tampil thumbnail, PDF ikon+nama --}}
                            <div x-show="preview" x-cloak class="mt-1 flex items-center gap-1.5">
                                <template x-if="preview !== 'pdf'">
                                    <img :src="preview" class="w-8 h-8 rounded border border-slate-200 object-cover" alt="Pratinjau">
                                </template>
                                <template x-if="preview === 'pdf'">
                                    <span class="w-8 h-8 rounded border border-slate-200 flex items-center justify-center bg-white text-slate-400 text-3xs font-bold">PDF</span>
                                </template>
                                <span class="text-3xs text-slate-500 truncate max-w-[10rem]" x-text="previewName"></span>
                            </div>
                        </div>
                        <input type="file" name="{{ $name }}" accept="{{ $accept }}"
                               @change="{{ $fileVar }} = $event.target.files[0]; errors.{{ $fileVar }} = null;
                                        const f = {{ $fileVar }};
                                        preview = f ? (f.type === 'application/pdf' ? 'pdf' : URL.createObjectURL(f)) : null;
                                        previewName = f?.name || '';"
                               class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer shrink-0">
                    </div>
                    <template x-if="errors.{{ $fileVar }}"><span class="text-4xs text-rose-600 font-semibold mt-1 block" x-text="errors.{{ $fileVar }}"></span></template>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between pt-4">
            <button type="button" @click="step = 1" class="px-6 py-3 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-xl transition-all">&larr; Kembali</button>
            <button type="submit" @click="if(!validateStep(2)) $event.preventDefault()" class="px-8 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-lg shadow-sky-100 hover:shadow-sky-200 transition-all flex items-center space-x-2">
                <span>{{ $isEdit ? 'Simpan Perubahan' : 'Kirim Pendaftaran' }}</span><span>&rarr;</span>
            </button>
        </div>
    </div>

</form>
@endsection
