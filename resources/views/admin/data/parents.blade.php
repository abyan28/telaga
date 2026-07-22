@extends('layouts.dashboard')

@section('title', 'Data Orang Tua — RA Al Kautsar')
@section('header_title', 'Data Orang Tua')

@section('content')
<div class="space-y-6">

@if (session('success'))
    <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
@endif

    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <h3 class="text-base font-bold text-slate-950">Data Orang Tua / Wali Murid</h3>

        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama ayah, ibu, atau murid…" class="flex-1 min-w-[180px] px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
            <select name="kelas" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none">
                <option value="">Semua Kelas</option>
                @foreach ($classes as $c)
                    <option value="{{ $c->id_classes }}" @selected((string) $kelas === (string) $c->id_classes)>{{ $c->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none">
                <option value="">Semua Status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
                <option value="lulus" @selected($status === 'lulus')>Lulus</option>
                <option value="dropout" @selected($status === 'dropout')>Drop Out</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs">Cari</button>
            <a href="{{ route('admin.data.parents') }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4 pr-3">Ayah</th>
                        <th class="py-4 px-3">Ibu</th>
                        <th class="py-4 px-3">Nama Anak</th>
                        <th class="py-4 px-3">No. HP (Ibu)</th>
                        <th class="py-4 px-3">Pekerjaan (Ayah)</th>
                        <th class="py-4 px-3">Wali Kelas</th>
                        <th class="py-4 px-3">Kelas</th>
                        <th class="py-4 px-3">Status</th>
                        <th class="py-4 pl-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($parents as $ortu)
                        @php $children = $ortu->students; $n = max($children->count(), 1); @endphp
                        @forelse ($children as $idx => $student)
                            @php
                                $wk = $student->schoolClass?->homeroomTeacher?->nama ?? '-';
                                $kelasNama = $student->schoolClass?->nama_kelas ?? '-';
                                $statusClass = match ($student->status) {
                                    'aktif' => 'text-emerald-700 bg-emerald-100',
                                    'nonaktif' => 'text-slate-500 bg-slate-100',
                                    'lulus' => 'text-indigo-700 bg-indigo-100',
                                    'dropout' => 'text-rose-700 bg-rose-100',
                                    default => 'text-slate-500 bg-slate-100',
                                };
                            @endphp
                            <tr class="text-slate-600 hover:bg-slate-50 transition-colors {{ $idx > 0 ? 'border-t-0' : '' }}">
                                @if ($idx === 0)
                                    <td rowspan="{{ $n }}" class="py-4 pr-3 font-bold text-slate-900 align-top">{{ $ortu->ayah_nama ?? '-' }}</td>
                                    <td rowspan="{{ $n }}" class="py-4 px-3 font-bold text-slate-900 align-top">{{ $ortu->ibu_nama ?? '-' }}</td>
                                @endif
                                <td class="py-4 px-3 font-semibold text-slate-700">{{ $student->nama_lengkap }}</td>
                                @if ($idx === 0)
                                    <td rowspan="{{ $n }}" class="py-4 px-3 text-slate-500 align-top">{{ $ortu->ibu_no_hp ?? '-' }}</td>
                                    <td rowspan="{{ $n }}" class="py-4 px-3 text-slate-500 align-top">{{ $ortu->ayah_pekerjaan ?? '-' }}</td>
                                @endif
                                <td class="py-4 px-3">{{ $wk }}</td>
                                <td class="py-4 px-3">{{ $kelasNama }}</td>
                                <td class="py-4 px-3"><span class="inline-block px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $statusClass }}">{{ $student->status }}</span></td>
                                @if ($idx === 0)
                                    <td rowspan="{{ $n }}" class="py-4 pl-3 text-right align-top whitespace-nowrap">
                                        <div class="flex gap-1 justify-end">
                                            <a href="{{ route('admin.students.show', $student) }}" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-4xs">Profil</a>
                                            <a href="{{ route('admin.data.students') }}?cari={{ urlencode($ortu->namaWali() ?? '') }}" class="px-3 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-lg text-4xs">Edit</a>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                        @endforelse
                    @empty
                        <tr><td colspan="9" class="py-8 text-center text-slate-400">Tidak ada data orang tua.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginate :paginator="$parents" />
    </div>

</div>
@endsection
