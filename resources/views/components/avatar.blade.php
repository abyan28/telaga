@props(['path' => null, 'name' => '', 'size' => 'w-24 h-24'])

{{-- Avatar reusable: foto bila ada, else inisial nama. Dipakai profil murid/guru. --}}
@if ($path)
    <img src="{{ asset('storage/'.$path) }}" alt="{{ $name }}" {{ $attributes->merge(['class' => $size.' rounded-2xl object-cover border border-slate-100']) }}>
@else
    <div {{ $attributes->merge(['class' => $size.' rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center text-2xl font-black uppercase']) }}>
        {{ mb_substr(trim($name) ?: '?', 0, 1) }}
    </div>
@endif
