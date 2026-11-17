@props(['id' => 'modal', 'title' => null, 'maxWidth' => 'lg'])
@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
    $widthClass = $widths[$maxWidth] ?? $widths['lg'];
@endphp
<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    x-on:close-modal.window="if (! $event.detail || $event.detail === '{{ $id }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $id }}-title" @endif
>
    <div class="fixed inset-0 bg-navy/60" @click="open = false" aria-hidden="true"></div>
    <div class="relative w-full {{ $widthClass }} rounded-2xl bg-white shadow-2xl" x-show="open" x-transition>
        @if ($title)
            <header class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
                <h2 id="{{ $id }}-title" class="font-heading text-lg font-semibold text-navy">{{ $title }}</h2>
                <button type="button" class="inline-flex size-9 items-center justify-center rounded-md text-slate-500 hover:bg-cloud hover:text-navy" @click="open = false" aria-label="Tutup dialog">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </header>
        @endif

        <div class="px-6 py-5">{{ $slot }}</div>

        @if (isset($footer))
            <footer class="flex flex-wrap justify-end gap-3 border-t border-line bg-cloud px-6 py-4">{{ $footer }}</footer>
        @endif
    </div>
</div>