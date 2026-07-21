@extends('layouts.dashboard')

@section('title', 'Kelola Konten Web — TELAGA AL KAUTSAR')
@section('header_title', 'Kelola Konten Website')

@section('content')
@php
    $grupKonten = ['home' => 'Beranda', 'profile' => 'Profil', 'info' => 'Info Pendaftaran', 'kontak' => 'Kontak/Footer'];
    $grupFitur = ['statistik' => 'Statistik Beranda', 'program' => 'Program/Fasilitas', 'kurikulum' => 'Curriculum Highlight (Slider Foto)', 'misi' => 'Misi', 'persyaratan' => 'Persyaratan Dokumen', 'alur' => 'Alur Pendaftaran'];
    // Fitur mana muncul di tab halaman mana.
    $fiturDiTab = ['statistik' => 'home', 'program' => 'home', 'kurikulum' => 'home', 'misi' => 'profile', 'persyaratan' => 'info', 'alur' => 'info'];

    // Susun sub-tab per grup halaman: [id, label]. 'teks' bila grup punya konten key-value,
    // lalu tiap fitur milik grup, dan (khusus home) sub-tab 'media' untuk upload logo/gambar.
    $subtabs = [];
    foreach ($grupKonten as $gk => $gl) {
        $items = [];
        if (isset($contents[$gk]) && $contents[$gk]->isNotEmpty()) {
            $items[] = ['teks', 'Teks Halaman'];
        }
        foreach ($grupFitur as $fk => $fl) {
            if (($fiturDiTab[$fk] ?? null) === $gk) {
                $items[] = [$fk, $fl];
            }
        }
        if ($gk === 'home') {
            $items[] = ['media', 'Logo / Gambar'];
        }
        $subtabs[$gk] = $items;
    }
@endphp

<div class="max-w-4xl mx-auto space-y-6" x-data="contentCms({{ \Illuminate\Support\Js::from(collect($subtabs)->map(fn ($i) => $i[0][0] ?? null)) }})">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    <div class="bg-sky-50 border border-sky-100 rounded-2xl px-5 py-3 text-xs text-sky-800">
        Kelola teks & item yang tampil di halaman publik. Nominal biaya di halaman Info diambil otomatis dari menu Pengaturan Keuangan (tidak diedit di sini).
    </div>

    {{-- Tab utama (navbar halaman) --}}
    <div class="flex flex-wrap gap-1.5 border-b border-slate-100 pb-3">
        @foreach ($grupKonten as $key => $label)
            <button @click="setTab('{{ $key }}')" :class="tab === '{{ $key }}' ? 'bg-sky-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'" class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Panel per grup konten --}}
    @foreach ($grupKonten as $grupKey => $grupLabel)
        <div x-show="tab === '{{ $grupKey }}'" x-cloak class="space-y-5">

            {{-- Sub-tab (slidebar dalam halaman) — hindari scroll panjang --}}
            <div class="flex flex-wrap gap-1.5">
                @foreach ($subtabs[$grupKey] as [$sid, $slabel])
                    <button @click="sub = '{{ $sid }}'; syncUrl()" :class="sub === '{{ $sid }}' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3 py-1.5 text-2xs font-bold rounded-lg transition-all">{{ $slabel }}</button>
                @endforeach
            </div>

            {{-- Sub-panel: Teks halaman (key-value) --}}
            @if (isset($contents[$grupKey]) && $contents[$grupKey]->isNotEmpty())
                <div x-show="sub === 'teks'" x-cloak class="bg-white border border-slate-100 rounded-3xl p-6 md:p-8 shadow-xs space-y-4">
                    <div class="flex flex-wrap justify-between items-center gap-2">
                        <h3 class="text-base font-bold text-slate-950">Teks Halaman {{ $grupLabel }}</h3>
                        <button type="button"
                            @click="openText('{{ $grupLabel }}', {{ \Illuminate\Support\Js::from($contents[$grupKey]->map(fn ($i) => ['key' => $i->key, 'label' => $i->label ?? $i->key, 'value' => $i->value, 'tipe' => $i->tipe])->values()) }})"
                            class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg text-xs shrink-0">Edit Teks</button>
                    </div>
                    <dl class="grid sm:grid-cols-2 gap-3">
                        @foreach ($contents[$grupKey] as $item)
                            <div class="border border-slate-100 rounded-xl p-3">
                                <dt class="text-2xs font-bold uppercase tracking-wider text-slate-400">{{ $item->label ?? $item->key }}</dt>
                                <dd class="text-xs text-slate-700 mt-1 line-clamp-2">
                                    @if ($item->tipe === 'image')
                                        @if ($item->value)
                                            <img src="{{ asset('storage/'.$item->value) }}" class="w-16 h-16 rounded-lg object-cover border border-slate-200">
                                        @else
                                            <span class="text-slate-400">Belum ada gambar.</span>
                                        @endif
                                    @else
                                        {{ $item->value ?: '—' }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Sub-panel: tiap fitur milik grup ini --}}
            @foreach ($grupFitur as $fgKey => $fgLabel)
                @if (($fiturDiTab[$fgKey] ?? null) === $grupKey)
                    <div x-show="sub === '{{ $fgKey }}'" x-cloak class="bg-white border border-slate-100 rounded-3xl p-6 md:p-8 shadow-xs space-y-4">
                        <div class="flex flex-wrap justify-between items-center gap-2">
                            <h3 class="text-base font-bold text-slate-950">{{ $fgLabel }}</h3>
                            <button type="button" @click="openFeature('create', '{{ $fgKey }}', '{{ $fgLabel }}', {{ $fgKey === 'kurikulum' ? 'true' : 'false' }})" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs shrink-0">+ Tambah</button>
                        </div>

                        <div class="space-y-2">
                            @forelse ($features[$fgKey] ?? [] as $f)
                                <div class="flex items-center gap-3 border border-slate-100 rounded-xl p-3">
                                    @if ($fgKey === 'kurikulum' && $f->foto_path)
                                        <img src="{{ asset('storage/'.$f->foto_path) }}" alt="" class="w-12 h-12 object-cover rounded-lg shrink-0">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-800 truncate">{{ $f->judul }}</p>
                                        @if ($f->deskripsi)
                                            <p class="text-2xs text-slate-500 truncate">{{ $f->deskripsi }}</p>
                                        @endif
                                    </div>
                                    <span class="text-2xs text-slate-400 shrink-0">#{{ $f->urutan }}</span>
                                    <span class="inline-flex px-2 py-0.5 rounded text-4xs font-bold uppercase shrink-0 {{ $f->aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $f->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                    <button type="button"
                                        @click="openFeature('edit', '{{ $fgKey }}', '{{ $fgLabel }}', {{ $fgKey === 'kurikulum' ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from(['id' => $f->id_site_features, 'judul' => $f->judul, 'deskripsi' => $f->deskripsi, 'urutan' => $f->urutan, 'aktif' => (bool) $f->aktif, 'foto_path' => $f->foto_path]) }})"
                                        class="px-3 py-1.5 bg-sky-50 text-sky-700 font-bold rounded-lg text-xs hover:bg-sky-100 shrink-0">Edit</button>
                                    <form action="{{ route('admin.content.features.destroy', $f->id_site_features) }}" method="POST" onsubmit="return confirm('Hapus item ini?')" class="shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 bg-rose-50 text-rose-600 font-bold rounded-lg text-xs hover:bg-rose-100">Hapus</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400">Belum ada item.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Sub-panel Media (khusus Beranda): upload logo/gambar untuk slot bertipe image --}}
            @if ($grupKey === 'home')
                <div x-show="sub === 'media'" x-cloak class="bg-white border border-slate-100 rounded-3xl p-6 md:p-8 shadow-xs space-y-4">
                    <h3 class="text-base font-bold text-slate-950">Logo / Gambar Website</h3>
                    <p class="text-xs text-slate-500">Pilih slot gambar yang ingin diganti (mis. Logo header & footer), lalu unggah file baru.</p>
                    @php $imageSlots = $contents->flatten()->where('tipe', 'image'); @endphp
                    @if ($imageSlots->isNotEmpty())
                        {{-- Pratinjau slot gambar saat ini --}}
                        <div class="flex flex-wrap gap-4">
                            @foreach ($imageSlots as $img)
                                <div class="text-center">
                                    @if ($img->value)
                                        <img src="{{ asset('storage/'.$img->value) }}" class="w-16 h-16 rounded-xl object-cover border border-slate-200 mx-auto">
                                    @else
                                        <div class="w-16 h-16 rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-3xs text-slate-400 mx-auto">Kosong</div>
                                    @endif
                                    <p class="text-4xs text-slate-500 mt-1">{{ $img->label ?? $img->key }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <form action="{{ route('admin.content.media') }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <select name="key" required class="px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-sky-400">
                            <option value="">— pilih target gambar —</option>
                            @foreach ($imageSlots as $img)
                                <option value="{{ $img->key }}">{{ $img->label ?? $img->key }}</option>
                            @endforeach
                        </select>
                        <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" required class="flex-1 text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
                        <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg text-xs">Unggah</button>
                    </form>
                    <p class="text-4xs text-slate-400">Format JPG/PNG/WEBP, maksimal 2 MB. Logo tampil di header & footer semua halaman publik.</p>
                </div>
            @endif

        </div>
    @endforeach

    {{-- ===== Modal Edit Teks Halaman (key-value) ===== --}}
    <div x-show="textOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-2xl w-full border border-slate-100 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto" @click.away="textOpen = false">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold text-slate-950">Edit Teks Halaman <span x-text="textLabel"></span></h3>
                <button type="button" @click="textOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.content.update') }}" method="POST" class="space-y-5">
                @csrf
                <template x-for="item in textItems" :key="item.key">
                    <div class="space-y-1">
                        <label class="text-2xs font-bold uppercase tracking-wider text-slate-500" x-text="item.label"></label>
                        <template x-if="item.tipe === 'textarea'">
                            <textarea :name="'contents[' + item.key + ']'" rows="3" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-sky-200 focus:border-sky-400 outline-none" x-text="item.value"></textarea>
                        </template>
                        <template x-if="item.tipe === 'image'">
                            <p class="text-xs text-slate-400">Gunakan sub-tab "Logo / Gambar" untuk mengganti.</p>
                        </template>
                        <template x-if="item.tipe !== 'textarea' && item.tipe !== 'image'">
                            <input type="text" :name="'contents[' + item.key + ']'" :value="item.value" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-sky-200 focus:border-sky-400 outline-none">
                        </template>
                    </div>
                </template>
                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="textOpen = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md text-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modal Tambah/Edit Item Fitur ===== --}}
    <div x-show="featOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-lg w-full border border-slate-100 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto" @click.away="featOpen = false">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold text-slate-950"><span x-text="featMode === 'edit' ? 'Edit' : 'Tambah'"></span> <span x-text="featLabel"></span></h3>
                <button type="button" @click="featOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="featAction" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <template x-if="featMode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>
                <template x-if="featMode === 'create'"><input type="hidden" name="grup" :value="featGrup"></template>

                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Judul</label>
                    <input type="text" name="judul" x-model="f.judul" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                </div>
                <div class="space-y-1">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Deskripsi (opsional)</label>
                    <textarea name="deskripsi" x-model="f.deskripsi" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500"></textarea>
                </div>

                <div class="space-y-1" x-show="featFoto">
                    <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Foto Slide (opsional, maks 2 MB)</label>
                    <template x-if="f.foto_path">
                        <img :src="'{{ asset('storage') }}/' + f.foto_path" alt="" class="w-20 h-20 object-cover rounded-lg mb-2">
                    </template>
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700">
                </div>

                <div class="grid grid-cols-2 gap-4" x-show="featMode === 'edit'">
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Urutan</label>
                        <input type="number" name="urutan" x-model="f.urutan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-sky-500">
                    </div>
                    <div class="space-y-1">
                        <label class="text-3xs font-extrabold uppercase tracking-widest text-slate-400">Status</label>
                        <label class="flex items-center gap-2 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <input type="checkbox" name="aktif" value="1" x-model="f.aktif"> Aktif
                        </label>
                    </div>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="featOpen = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-md text-xs">Simpan</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    // State CMS konten web. tab = navbar halaman; sub = sub-tab (slidebar dalam halaman).
    // subFirst = map grup→id sub-tab pertama, dipakai reset sub saat ganti tab utama.
    function contentCms(subFirst) {
        const featStoreUrl = '{{ route('admin.content.features.store') }}';
        // Posisi tab/sub disimpan di QUERY STRING (?tab=&sub=), bukan hash: query ikut ke
        // Referer saat form POST → controller back() redirect balik dgn query → posisi tetap.
        const p = new URLSearchParams(location.search);
        const validTab = p.get('tab') in subFirst ? p.get('tab') : 'home';
        return {
            tab: validTab,
            sub: p.get('sub') || subFirst[validTab] || 'teks',
            init() { this.syncUrl(); },
            syncUrl() {
                const u = new URL(location);
                u.searchParams.set('tab', this.tab);
                u.searchParams.set('sub', this.sub);
                history.replaceState(null, '', u);
            },
            setTab(t) {
                this.tab = t;
                this.sub = subFirst[t] ?? 'teks';
                this.syncUrl();
            },

            // --- Modal teks halaman (key-value) ---
            textOpen: false, textLabel: '', textItems: [],
            openText(label, items) {
                this.textLabel = label;
                this.textItems = items;
                this.textOpen = true;
            },

            // --- Modal item fitur (tambah/edit) ---
            featOpen: false, featMode: 'create', featGrup: '', featLabel: '', featFoto: false, featAction: featStoreUrl,
            f: { judul: '', deskripsi: '', urutan: 0, aktif: true, foto_path: null },
            openFeature(mode, grup, label, foto, item = null) {
                this.featMode = mode;
                this.featGrup = grup;
                this.featLabel = label;
                this.featFoto = foto;
                if (mode === 'edit' && item) {
                    this.f = { judul: item.judul ?? '', deskripsi: item.deskripsi ?? '', urutan: item.urutan ?? 0, aktif: !!item.aktif, foto_path: item.foto_path ?? null };
                    this.featAction = '/portal/admin/content/features/' + item.id;
                } else {
                    this.f = { judul: '', deskripsi: '', urutan: 0, aktif: true, foto_path: null };
                    this.featAction = featStoreUrl;
                }
                this.featOpen = true;
            },
        };
    }
</script>
@endsection
