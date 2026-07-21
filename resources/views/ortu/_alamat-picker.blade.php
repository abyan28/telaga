@php
    // $g = Guardian|Student|null — source of wilayah prefill values.
    // Supports both ortu (new home) and student (legacy) field names.
    $av = fn ($key) => old($key, $g?->{$key} ?? '');
    $pickers = [
        'provinsi'  => ['Provinsi', 'prov', null],
        'kota'      => ['Kota/Kabupaten', 'kota', 'prov'],
        'kecamatan' => ['Kecamatan', 'kec', 'kota'],
        'kelurahan' => ['Desa/Kelurahan', 'kel', 'kec'],
    ];
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"
     x-data="{
        prov: { id: '{{ $av('provinsi_id') }}', nama: '{{ $av('provinsi_nama') }}' },
        kota: { id: '{{ $av('kota_id') }}', nama: '{{ $av('kota_nama') }}' },
        kec:  { id: '{{ $av('kecamatan_id') }}', nama: '{{ $av('kecamatan_nama') }}' },
        kel:  { id: '{{ $av('kelurahan_id') }}', nama: '{{ $av('kelurahan_nama') }}' },
     }">
    @foreach ($pickers as $level => [$label, $var, $parent])
        <div class="space-y-1"
             x-data="{
                open: false, q: '', items: [], loading: false,
                async load() {
                    @if ($parent) if (!{{ $parent }}.id) { this.items = []; return; } @endif
                    this.loading = true;
                    const url = new URL('{{ route('wilayah', ['level' => $level]) }}', location.origin);
                    @if ($parent) url.searchParams.set('parent', {{ $parent }}.id); @endif
                    if (this.q) url.searchParams.set('q', this.q);
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    this.items = r.ok ? await r.json() : [];
                    this.loading = false;
                },
                pick(it) {
                    {{ $var }}.id = it.id; {{ $var }}.nama = it.nama;
                    this.q = ''; this.open = false;
                    @foreach (array_keys($pickers) as $lvl)
                        @php [$l2, $v2, $p2] = $pickers[$lvl]; @endphp
                        @if ($p2 === $var){{ $v2 }}.id = ''; {{ $v2 }}.nama = '';@endif
                    @endforeach
                }
             }"
             @click.away="open = false">
            <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">{{ $label }}</label>
            <input type="hidden" name="{{ $level }}_id" :value="{{ $var }}.id">
            <input type="hidden" name="{{ $level }}_nama" :value="{{ $var }}.nama">
            <div class="relative">
                <input type="text" autocomplete="off"
                       :placeholder="{{ $var }}.nama || 'Pilih {{ $label }}…'"
                       :class="{{ $var }}.nama ? 'text-slate-900' : 'text-slate-400'"
                       x-model="q"
                       @focus="open = true; load()"
                       @input.debounce.300ms="open = true; load()"
                       @if ($parent) :disabled="!{{ $parent }}.id" @endif
                       class="w-full px-4 py-3 rounded-xl text-sm border border-slate-200 bg-slate-50 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition-all disabled:bg-slate-100 disabled:cursor-not-allowed">
                <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full max-h-52 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg">
                    <template x-if="loading"><div class="px-4 py-2 text-xs text-slate-400">Memuat…</div></template>
                    <template x-for="it in items" :key="it.id">
                        <button type="button" @click="pick(it)" class="block w-full text-left px-4 py-2 text-sm hover:bg-sky-50" x-text="it.nama"></button>
                    </template>
                    <template x-if="!loading && items.length === 0"><div class="px-4 py-2 text-xs text-slate-400">Tidak ada hasil.</div></template>
                </div>
            </div>
        </div>
    @endforeach
</div>
