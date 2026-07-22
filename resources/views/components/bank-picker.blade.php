@props(['banks', 'name' => 'bank_asal', 'value' => ''])

{{--
    Searchable dropdown bank (Alpine). Input teks + list terfilter saat mengetik;
    klik memilih. Nilai bebas juga diterima (bank tak terdaftar tetap bisa diketik).
--}}
<div x-data="bankPicker(@js($banks), @js(old($name, $value)))" class="relative" @click.outside="open = false">
    <input
        type="text"
        name="{{ $name }}"
        x-model="query"
        @focus="open = true"
        @input="open = true"
        autocomplete="off"
        placeholder="Ketik untuk mencari bank/e-wallet…"
        {{ $attributes }}>

    <ul x-show="open && filtered.length" x-cloak
        class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg text-sm">
        <template x-for="b in filtered" :key="b">
            <li @click="query = b; open = false"
                class="px-4 py-2 cursor-pointer hover:bg-sky-50"
                x-text="b"></li>
        </template>
    </ul>
</div>

@once
<script>
function bankPicker(banks, initial) {
    return {
        banks, query: initial || '', open: false,
        get filtered() {
            const q = this.query.toLowerCase().trim();
            if (!q) return this.banks.slice(0, 50);
            return this.banks.filter(b => b.toLowerCase().includes(q)).slice(0, 50);
        },
    };
}
</script>
@endonce
