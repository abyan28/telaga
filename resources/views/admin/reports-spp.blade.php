@extends('layouts.dashboard')

@section('title', 'Laporan SPP — RA Al Kautsar')
@section('header_title', 'Laporan SPP Bulanan')

@section('content')
<div class="space-y-8">
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h3 class="text-base font-bold text-slate-950">Tagihan SPP Bulanan</h3>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.spp.csv', request()->query()) }}" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl border border-slate-200 text-xs transition-colors">Export CSV</a>
                <a href="{{ route('admin.reports.spp.pdf', request()->query()) }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">Export PDF</a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <select name="id_student" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="">Semua Siswa</option>
                @foreach ($allStudents as $s)
                    <option value="{{ $s->id_students }}" @selected(request('id_student') == $s->id_students)>{{ $s->nama_lengkap }}</option>
                @endforeach
            </select>
            <select name="id_class" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="">Semua Kelas</option>
                @foreach ($classes as $k)
                    <option value="{{ $k->id_classes }}" @selected(request('id_class') == $k->id_classes)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="id_academic_year" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="">Semua TA</option>
                @foreach ($academicYears as $ta)
                    <option value="{{ $ta->id_academic_years }}" @selected(request('id_academic_year') == $ta->id_academic_years)>{{ $ta->tahun }}</option>
                @endforeach
            </select>
            <input type="month" name="bulan" value="{{ request('bulan') }}" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
            <input type="month" name="dari" value="{{ request('dari') }}" placeholder="Dari bulan" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
            <input type="month" name="sampai" value="{{ request('sampai') }}" placeholder="Sampai bulan" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
            <select name="status_bayar" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="">Semua Status</option>
                <option value="lunas" @selected(request('status_bayar') === 'lunas')>Lunas</option>
                <option value="belum" @selected(request('status_bayar') === 'belum')>Belum Lunas / Kurang</option>
            </select>
            <div class="flex gap-2 items-end">
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs">Terapkan</button>
                <a href="{{ route('admin.reports.spp') }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">Murid</th><th class="py-4">Ortu (Ibu)</th><th class="py-4">Kelas</th>
                        <th class="py-4">TA</th><th class="py-4">Bulan</th>
                        <th class="py-4">Nominal</th><th class="py-4">Terbayar</th>
                        <th class="py-4">Sisa</th>
                        <th class="py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($bills as $b)
                        <tr>
                            <td class="py-3 font-semibold text-slate-800">{{ $b->student?->nama_lengkap ?? '-' }}</td>
                            <td class="py-3">{{ $b->student?->ortu?->ibu_nama ?? $b->student?->ortu?->ayah_nama ?? '-' }}</td>
                            <td class="py-3">{{ $b->student?->schoolClass?->nama_kelas ?? '-' }}</td>
                            <td class="py-3">{{ $b->academicYear?->tahun ?? '-' }}</td>
                            <td class="py-3">{{ $b->bulan }}</td>
                            <td class="py-3">{{ number_format((float) $b->nominal, 0, ',', '.') }}</td>
                            <td class="py-3">{{ number_format((float) $b->jumlah_terbayar, 0, ',', '.') }}</td>
                            <td class="py-3">{{ number_format((float) $b->sisa(), 0, ',', '.') }}</td>
                            <td class="py-3 text-center">{{ ucfirst(str_replace('_', ' ', $b->status)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-6 text-center text-slate-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-paginate :paginator="$bills" />
    </div>
</div>
@endsection
