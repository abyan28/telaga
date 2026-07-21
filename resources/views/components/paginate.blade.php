@props(['paginator'])

{{-- Pagination ringkas (K9.3) — dipakai di semua datatable admin. Tailwind v4. --}}
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 pt-4 text-xs" role="navigation">
        <p class="text-slate-400 font-semibold">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300 font-bold cursor-not-allowed">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">‹</a>
            @endif

            <span class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-bold">{{ $paginator->currentPage() }}</span>
            <span class="text-slate-300">/ {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">›</a>
            @else
                <span class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-300 font-bold cursor-not-allowed">›</span>
            @endif
        </div>
    </nav>
@endif
