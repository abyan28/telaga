@extends('layouts.dashboard')

@section('title', 'Laporan Data Murid — RA Al Kautsar')
@section('header_title', 'Laporan Data Murid')

@section('content')
<div class="space-y-8">
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h3 class="text-base font-bold text-slate-950">Data Murid</h3>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.students.csv', request()->query()) }}" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl border border-slate-200 text-xs transition-colors">Export CSV</a>
                <a href="{{ route('admin.reports.students.pdf', request()->query()) }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">Export PDF</a>
            </div>
        </div>

        {{-- Filter: kelas / TA / status / murid baru --}}
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <select name="id_class" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="">Semua Kelas</option>
                @foreach ($classes as $k)
                    <option value="{{ $k->id_classes }}" @selected(request('id_class') == $k->id_classes)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="id_academic_year" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="">Semua Tahun Ajaran</option>
                @foreach ($academicYears as $ta)
                    <option value="{{ $ta->id_academic_years }}" @selected(request('id_academic_year') == $ta->id_academic_years)>{{ $ta->tahun }}</option>
                @endforeach
            </select>
            <select name="status" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="semua" @selected(request('status', 'semua') === 'semua')>Semua Status</option>
                <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                <option value="alumni" @selected(request('status') === 'alumni')>Alumni</option>
                <option value="nonaktif" @selected(request('status') === 'nonaktif')>Drop Out (Nonaktif)</option>
            </select>
            <select name="angkatan" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <option value="">Semua Angkatan</option>
                @foreach ($angkatanList as $th)
                    <option value="{{ $th }}" @selected(request('angkatan') == $th)>{{ $th }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs cursor-pointer">
                <input type="checkbox" name="murid_baru" value="1" @checked(request('murid_baru')) class="rounded border-slate-300">
                <span>Murid Baru (TA aktif)</span>
            </label>
            <div class="sm:col-span-4 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs">Terapkan</button>
                <a href="{{ route('admin.reports.students') }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">Nama</th><th class="py-4">NISN</th><th class="py-4">NIS</th>
                        <th class="py-4">Kelas</th><th class="py-4">Wali Kelas</th>
                        <th class="py-4">Orang Tua (Ibu)</th><th class="py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($students as $s)
                        <tr>
                            <td class="py-3 font-semibold text-slate-800">{{ $s->nama_lengkap }}</td>
                            <td class="py-3">{{ $s->nisn ?? '-' }}</td>
                            <td class="py-3">{{ $s->nis ?? '-' }}</td>
                            <td class="py-3">{{ $s->schoolClass?->nama_kelas ?? '-' }}</td>
                            <td class="py-3">{{ $s->schoolClass?->homeroomTeacher?->nama ?? '-' }}</td>
                            <td class="py-3">{{ $s->ortu?->ibu_nama ?? $s->ortu?->ayah_nama ?? '-' }}</td>
                            <td class="py-3">{{ ['calon' => 'Calon', 'aktif' => 'Aktif', 'alumni' => 'Alumni', 'nonaktif' => 'Nonaktif'][$s->status] ?? ucfirst($s->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-paginate :paginator="$students" />
    </div>
</div>
@endsection
