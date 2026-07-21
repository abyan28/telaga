@extends('layouts.dashboard')

@section('title', 'Data Akun — TELAGA AL KAUTSAR')
@section('header_title', 'Data Akun')

@section('content')
@php
    // ponytail: badge live-cek username (reuse di 2 modal)
    $uBadge = 'x-text="({ok:\'✓ Tersedia\',taken:\'✕ Sudah dipakai\',cek:\'⋯\',invalid:\'Min 3: huruf/angka/._\'})[uStatus]||\'\'" '
        .':class="({ok:\'text-emerald-600\',taken:\'text-rose-600\',cek:\'text-amber-500\',invalid:\'text-rose-500\'})[uStatus]||\'\'" '
        .'class="text-3xs font-bold ml-1"';
@endphp
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    addOpen: false, editOpen: false,
    editId: 0, editUsername: '', editEmail: '', editNoHp: '',
    uStatus: '', uTimer: null,
    openAdd() { this.uStatus = ''; this.addOpen = true; },
    openEdit(id, u, e, hp) { this.editId = id; this.editUsername = u; this.editEmail = e ?? ''; this.editNoHp = hp ?? ''; this.uStatus = ''; this.editOpen = true; },
    checkU(username, ignore = 0) {
        clearTimeout(this.uTimer);
        this.uStatus = 'cek';
        this.uTimer = setTimeout(async () => {
            if (!/^[a-zA-Z0-9_.]{3,30}$/.test(username)) { this.uStatus = 'invalid'; return; }
            const r = await fetch(`{{ route('register.check-username') }}?username=${encodeURIComponent(username)}&ignore=${ignore}`);
            this.uStatus = (await r.json()).available ? 'ok' : 'taken';
        }, 400);
    }
}">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Filter bar --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-4 md:p-6 shadow-xs flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.accounts') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="relative grow min-w-[200px]">
                <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari username/email/HP…" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <select name="role" class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-sky-500" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                <option value="admin" @selected($roleFilter === 'admin')>Admin</option>
                <option value="guru" @selected($roleFilter === 'guru')>Guru</option>
                <option value="ortu" @selected($roleFilter === 'ortu')>Orang Tua</option>
            </select>
            <select name="status" class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-sky-500" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
            @if ($cari || $status || $roleFilter)
                <a href="{{ route('admin.accounts') }}" class="text-3xs font-semibold text-slate-400 hover:text-rose-500">✕ Reset</a>
            @endif
            <button type="button" @click="openAdd()" class="ml-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">+ Tambah Admin</button>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-3 pl-4 pr-2">User</th>
                        <th class="py-3 px-2">Role</th>
                        <th class="py-3 px-2">Email / No. HP</th>
                        <th class="py-3 px-2">Profil</th>
                        <th class="py-3 pr-4 pl-2 text-right">Status & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($users as $u)
                        @php
                            $roleBadge = ['admin'=>'bg-purple-100 text-purple-700', 'guru'=>'bg-sky-100 text-sky-700', 'ortu'=>'bg-emerald-100 text-emerald-700'][$u->role] ?? 'bg-slate-100 text-slate-600';
                            $profil = $u->role === 'admin' ? $u->username : ($u->ortu?->namaWali() ?? $u->teacher?->nama ?? '-');
                            $email = $u->email ?? '-';
                            $noHp = $u->no_hp ?? ($u->teacher?->no_hp ?? $u->ortu?->noHpWali() ?? '-');
                        @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors {{ ! $u->is_aktif ? 'opacity-60' : '' }}">
                            <td class="py-3 pl-4 pr-2 font-bold text-slate-900">{{ $u->username }}</td>
                            <td class="py-3 px-2"><span class="inline-block px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $roleBadge }}">{{ $u->role }}</span></td>
                            <td class="py-3 px-2 text-slate-500">
                                <span class="block">{{ $email }}</span>
                                <span class="text-4xs text-slate-400">{{ $noHp }}</span>
                            </td>
                            <td class="py-3 px-2 text-slate-500 max-w-[140px] truncate">{{ $profil }}</td>
                            <td class="py-3 pr-4 pl-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit({{ $u->id_users }}, '{{ addslashes($u->username) }}', '{{ addslashes($u->email ?? '') }}', '{{ addslashes($u->no_hp ?? '') }}')" class="px-2.5 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-lg text-4xs">Edit</button>

                                    @php $isSelf = $u->id_users === auth()->id(); @endphp

                                    @unless ($isSelf)
                                        <form action="{{ route('admin.accounts.toggle-status', $u) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 border rounded-lg font-bold text-4xs {{ $u->is_aktif ? 'border-red-200 hover:bg-red-50 text-red-600' : 'border-emerald-200 hover:bg-emerald-50 text-emerald-600' }}">{{ $u->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    @endunless

                                    @if ($isSelf)
                                        <span class="text-4xs text-slate-400 font-bold">Akun Anda</span>
                                    @elseif ($u->role === 'admin')
                                        <form action="{{ route('admin.accounts.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun admin ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1.5 border border-rose-200 hover:bg-rose-50 text-rose-600 font-bold rounded-lg text-4xs">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($users->isEmpty())
            <div class="p-8 text-center text-xs text-slate-400">Tidak ada akun ditemukan.</div>
        @endif
        <div class="px-4 py-4 border-t border-slate-100">
            <x-paginate :paginator="$users" />
        </div>
    </div>

    {{-- Modal Tambah Admin --}}
    <div x-show="addOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
        <div class="bg-white rounded-[2.5rem] p-6 max-w-md w-full border border-slate-100 shadow-2xl space-y-4" @click.away="addOpen = false">
            <div class="flex justify-between"><h3 class="text-sm font-bold text-slate-950">Tambah Admin</h3>
                <button @click="addOpen = false" class="text-slate-400 hover:text-slate-600 p-1">✕</button>
            </div>
            <form action="{{ route('admin.accounts.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="text-2xs font-bold text-slate-500 uppercase">Username <span {!! $uBadge !!}></span></label>
                    <input type="text" name="name" required @input="checkU($event.target.value)" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
                </div>
                <div><label class="text-2xs font-bold text-slate-500 uppercase">Email</label><input type="email" name="email" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500"></div>
                <div><label class="text-2xs font-bold text-slate-500 uppercase">Password (min 8)</label><input type="password" name="password" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500"></div>
                <button type="submit" :disabled="uStatus === 'taken' || uStatus === 'invalid'" class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs">Simpan Admin</button>
            </form>
        </div>
    </div>

    {{-- Modal Edit Akun --}}
    <div x-show="editOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
        <div class="bg-white rounded-[2.5rem] p-6 max-w-md w-full border border-slate-100 shadow-2xl space-y-4" @click.away="editOpen = false">
            <div class="flex justify-between"><h3 class="text-sm font-bold text-slate-950">Edit Akun</h3>
                <button @click="editOpen = false" class="text-slate-400 hover:text-slate-600 p-1">✕</button>
            </div>
            <form :action="'/portal/admin/accounts/'+editId" method="POST" class="space-y-3">
                @csrf @method('PUT')
                <div>
                    <label class="text-2xs font-bold text-slate-500 uppercase">Username <span {!! $uBadge !!}></span></label>
                    <input type="text" name="username" x-model="editUsername" required @input="checkU($event.target.value, editId)" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                </div>
                <div><label class="text-2xs font-bold text-slate-500 uppercase">Email</label><input type="email" name="email" x-model="editEmail" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500"></div>
                <div><label class="text-2xs font-bold text-slate-500 uppercase">No. HP</label><input type="text" name="no_hp" inputmode="numeric" pattern="[0-9]{9,14}" x-model="editNoHp" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500"></div>
                <div><label class="text-2xs font-bold text-slate-500 uppercase">Password (kosongi jika tidak diganti)</label><input type="password" name="password" minlength="8" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500"></div>
                <button type="submit" :disabled="uStatus === 'taken' || uStatus === 'invalid'" class="w-full py-3 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>{{-- /x-data --}}
@endsection
