@props(['paginator'])
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect(range(1, $last))
            ->filter(fn (int $page) => $page === 1 || $page === $last || abs($page - $current) <= 1)
            ->values();
        $previousPage = $current - 1;
        $nextPage = $current + 1;
    @endphp
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            Menampilkan
            <span class="font-semibold text-navy">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-navy">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-navy">{{ $paginator->total() }}</span> data
        </p>

        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-10 cursor-not-allowed items-center rounded-md border border-line px-3 text-sm text-slate-400" aria-disabled="true">Sebelumnya</span>
            @else
                <a href="{{ $paginator->url($previousPage) }}" rel="prev" class="inline-flex min-h-10 items-center rounded-md border border-line bg-white px-3 text-sm font-semibold text-navy hover:border-forest hover:text-forest">Sebelumnya</a>
            @endif

            @foreach ($pages as $index => $page)
                @if ($index > 0 && $page - $pages[$index - 1] > 1)
                    <span class="px-1 text-sm text-slate-400" aria-hidden="true">…</span>
                @endif

                @if ($page === $current)
                    <span aria-current="page" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-md bg-forest px-3 text-sm font-bold text-white">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-md border border-line bg-white px-3 text-sm font-semibold text-navy hover:border-forest hover:text-forest">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->url($nextPage) }}" rel="next" class="inline-flex min-h-10 items-center rounded-md border border-line bg-white px-3 text-sm font-semibold text-navy hover:border-forest hover:text-forest">Berikutnya</a>
            @else
                <span class="inline-flex min-h-10 cursor-not-allowed items-center rounded-md border border-line px-3 text-sm text-slate-400" aria-disabled="true">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif