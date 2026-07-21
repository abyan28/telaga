@extends('layouts.dashboard')

@section('title', 'Data Kelas — RA Al Kautsar')
@section('header_title', 'Data Kelas')

@section('content')
<div class="space-y-8" x-data="classCrud()">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950">Data Kelas</h3>
            <button @click="openCreate()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">
                + Tambah Kelas
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($classes as $kelas)
                <div class="border border-slate-100 rounded-2xl p-5 space-y-2">
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <span class="text-sm font-bold text-slate-900 block">{{ $kelas->nama_kelas }}</span>
                            <span class="text-4xs text-slate-400 uppercase tracking-wide">T.A. {{ $kelas->academicYear?->tahun ?? '-' }}</span>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <button @click="openEdit({{ $kelas->id_classes }}, @js($kelas->nama_kelas), {{ $kelas->id_homeroom_teacher ?? 'null' }})" class="px-2 py-1 border border-indigo-200 hover:bg-indigo-50 text-indigo-600 font-bold rounded-lg text-4xs">Edit</button>
                            <form action="{{ route('admin.classes.destroy', $kelas->id_classes) }}" method="POST" onsubmit="return confirm('Hapus kelas ini? Murid & guru akan terlepas dari kelas.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 border border-rose-200 hover:bg-rose-50 text-rose-600 font-bold rounded-lg text-4xs">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <p class="text-4xs text-slate-500">Wali Kelas: <span class="font-bold text-slate-700">{{ $kelas->homeroomTeacher?->nama ?? '—' }}</span></p>
                    <div class="flex justify-between text-4xs text-slate-500 pt-2 border-t border-slate-50">
                        <span>{{ $kelas->teachers->count() }} guru</span>
                        <span>{{ $kelas->students->count() }} murid</span>
                    </div>
                </div>
            @empty
                <p class="col-span-full text-xs text-slate-400 text-center py-4">Belum ada kelas.</p>
            @endforelse
        </div>
    </div>

    {{-- Modal tambah/edit kelas (satu form dua mode) --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-sm w-full border border-slate-100 shadow-2xl space-y-6" @click.away="open = false">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold text-slate-950" x-text="mode === 'edit' ? 'Edit Kelas' : 'Tambah Kelas Baru'"></h3>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="action" method="POST" class="space-y-4">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Nama Kelas</label>
                    <input type="text" name="nama_kelas" x-model="nama" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500" placeholder="Kelas A - Rahmat">
                    <span class="text-4xs text-slate-400 block" x-show="mode === 'create'">* Kelas dibuat pada tahun ajaran aktif.</span>
                </div>
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Wali Kelas (opsional)</label>
                    <select name="id_homeroom_teacher" x-model="homeroom" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                        <option value="">— Tanpa Wali Kelas —</option>
                        @foreach ($teachers as $guru)
                            <option value="{{ $guru->id_teachers }}">{{ $guru->nama }}</option>
                        @endforeach
                    </select>
                    <span class="text-4xs text-slate-400 block">Satu guru hanya bisa jadi wali kelas di satu kelas.</span>
                </div>
                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="open = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    // State modal CRUD kelas: satu form tambah & edit (T5.5).
    function classCrud() {
        return {
            open: false, mode: 'create', action: '{{ route('admin.classes.store') }}', nama: '', homeroom: '',
            openCreate() { this.mode = 'create'; this.action = '{{ route('admin.classes.store') }}'; this.nama = ''; this.homeroom = ''; this.open = true; },
            openEdit(id, nama, homeroom) { this.mode = 'edit'; this.action = '/portal/admin/classes/' + id; this.nama = nama; this.homeroom = homeroom == null ? '' : String(homeroom); this.open = true; },
        };
    }
</script>
@endsection
