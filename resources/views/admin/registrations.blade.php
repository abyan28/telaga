@extends('layouts.dashboard')

@section('title', 'Kelola Pendaftaran Murid — RA Al Kautsar')
@section('header_title', 'Kelola Pendaftaran Murid Baru')

@section('content')
@php
    // Metadata status pendaftaran untuk badge (label + warna) — kelas statis penuh (Tailwind JIT).
    $statusMap = [
        'submitted' => ['Sudah Submit', 'bg-sky-100 text-sky-700'],
        'menunggu_bukti' => ['Bukti Ditolak – Upload Ulang', 'bg-rose-100 text-rose-700'],
        'menunggu_verifikasi' => ['Menunggu Verifikasi Pembayaran', 'bg-amber-100 text-amber-700'],
        'pembayaran_diverifikasi' => ['Proses Verifikasi Berkas', 'bg-sky-100 text-sky-700'],
        'diproses_seleksi' => ['Proses Seleksi', 'bg-indigo-100 text-indigo-700'],
        'lulus' => ['Lulus Seleksi', 'bg-emerald-100 text-emerald-700'],
        'gagal' => ['Belum Lulus', 'bg-rose-100 text-rose-700'],
    ];
@endphp

<div class="space-y-6" x-data="{ searchQuery: '' }">

    {{-- Flash / error --}}
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Kontrol PPDB (T5.6a): buka/tutup pendaftaran + tahun ajaran baru + kuota --}}
    <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-2xs flex flex-col md:flex-row gap-4 md:items-center justify-between" x-data="{ kuotaOpen: false }">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-2xs font-bold uppercase tracking-wider {{ $ppdbDibuka ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                PPDB {{ $ppdbDibuka ? 'Dibuka' : 'Ditutup' }}
            </span>
            <span class="text-xs text-slate-500">TA Aktif: <strong>{{ $tahunAktif ?? '-' }}</strong></span>
            <span class="text-xs text-slate-400">Tahun Ajaran PPDB: <strong>{{ $taPpdb?->tahun ?? $tahunAktif }}</strong></span>
        </div>
        <div class="flex flex-wrap gap-2">
            {{-- D2: kontrol < > pindah ke Set Pembayaran; hanya toggle + kuota di sini. --}}
            <form action="{{ route('admin.ppdb.toggle') }}" method="POST">
                @csrf
                <input type="hidden" name="dibuka" value="{{ $ppdbDibuka ? 0 : 1 }}">
                <button type="submit" class="px-4 py-2 {{ $ppdbDibuka ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500' }} text-white font-bold rounded-xl text-xs">
                    {{ $ppdbDibuka ? 'Tutup Pendaftaran' : 'Buka Pendaftaran' }}
                </button>
            </form>
            <button type="button" @click="kuotaOpen = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs">
                Kuota ({{ $kuota > 0 ? $kuota : 'belum diatur' }})
            </button>
        </div>

        {{-- Modal Kuota --}}
        <div x-show="kuotaOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
            <div class="bg-white rounded-[2.5rem] p-8 max-w-md w-full border border-slate-100 shadow-2xl space-y-6" @click.away="kuotaOpen = false">
                <div class="flex justify-between items-start">
                    <h3 class="text-lg font-bold text-slate-950">Kuota Pendaftaran (Target Diterima)</h3>
                    <button type="button" @click="kuotaOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Jumlah Target Diterima</label>
                        <input type="number" name="kuota_pendaftaran" value="{{ $kuota }}" min="0" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="kuotaOpen = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs">Simpan Kuota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter Bar (TA + status server-side GET; search Alpine) -->
    <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-2xs flex flex-col md:flex-row gap-4 items-center justify-between">
        <div class="relative w-full md:max-w-xs">
            <svg class="absolute left-3 top-3.5 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="searchQuery" placeholder="Cari nama murid, NIK atau orang tua..." class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
        </div>
        <form method="GET" class="flex gap-2 shrink-0 w-full md:w-auto" id="filter-form">
            <select name="tahun" onchange="this.form.submit()" class="w-full md:w-auto px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none cursor-pointer">
                @foreach ($tahunOpsi as $ta)
                    <option value="{{ $ta->id_academic_years }}" @selected((string) $tahun === (string) $ta->id_academic_years)>T.A. {{ $ta->tahun }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="w-full md:w-auto px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none cursor-pointer">
                <option value="">Semua Status</option>
                <option value="submitted" @selected($status === 'submitted')>Sudah Submit</option>
                <option value="menunggu_bukti" @selected($status === 'menunggu_bukti')>Bukti Ditolak</option>
                <option value="menunggu_verifikasi" @selected($status === 'menunggu_verifikasi')>Menunggu Verifikasi Pembayaran</option>
                <option value="pembayaran_diverifikasi" @selected($status === 'pembayaran_diverifikasi')>Proses Verifikasi Berkas</option>
                <option value="diproses_seleksi" @selected($status === 'diproses_seleksi')>Proses Seleksi</option>
                <option value="lulus" @selected($status === 'lulus')>Lulus</option>
                <option value="gagal" @selected($status === 'gagal')>Gagal</option>
            </select>
        </form>
    </div>

    <!-- Student Registrations Table -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">No. Pendaftaran</th>
                        <th class="py-4">Nama Murid</th>
                        <th class="py-4">NIK (Wajib)</th>
                        <th class="py-4">Nama Orang Tua</th>
                        <th class="py-4">Tanggal Daftar</th>
                        <th class="py-4">Status Seleksi</th>
                        <th class="py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($forms as $form)
                        @php
                            [$statusLabel, $statusClass] = $statusMap[$form->status] ?? ['-', 'bg-slate-100 text-slate-600'];
                            $namaSiswa = $form->student?->nama_lengkap ?? '-';
                            $nik = $form->student?->nik ?? '-';
                            $namaWali = $form->user?->ortu?->namaWali() ?? '-';
                            $emailWali = $form->user?->email ?? '-';
                        @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors"
                            x-show="(!searchQuery
                                    || '{{ \Illuminate\Support\Str::lower(addslashes($namaSiswa)) }}'.includes(searchQuery.toLowerCase())
                                    || '{{ addslashes($nik) }}'.includes(searchQuery)
                                    || '{{ \Illuminate\Support\Str::lower(addslashes($namaWali)) }}'.includes(searchQuery.toLowerCase()))">
                            <td class="py-4 font-bold text-slate-800">REG-{{ str_pad($form->id_registration_forms, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-4 font-bold text-slate-900">{{ $namaSiswa }}</td>
                            <td class="py-4 font-semibold text-slate-500">{{ $nik }}</td>
                            <td class="py-4">
                                <span class="font-semibold text-slate-700 block">{{ $namaWali }}</span>
                                <span class="text-4xs text-slate-400 block mt-0.5">{{ $emailWali }}</span>
                            </td>
                            <td class="py-4 text-slate-400">{{ $form->created_at?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-4xs font-bold uppercase tracking-wider {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="py-4 text-right">
                                <a href="{{ route('admin.registrations.show', $form) }}" class="inline-block px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-xl transition-all">
                                    Detail / Seleksi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-400">Belum ada pendaftaran masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginate :paginator="$forms" />
    </div>

</div>
@endsection
