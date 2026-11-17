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
    x-data="modalDialog('{{ $id }}')"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') show()"
    x-on:close-modal.window="if (! $event.detail || $event.detail === '{{ $id }}') close()"
    x-on:keydown.escape.window="close()"
    x-on:keydown.tab="trap($event)"
    x-show="open"
    x-transition.opacity
    x-cloak
    class="fixed inset-0 z-[80] flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $id }}-title" @else aria-label="Dialog" @endif
>
    <div class="fixed inset-0 bg-navy/60" @click="close()" aria-hidden="true"></div>
    <div x-ref="panel" tabindex="-1" class="relative flex max-h-[calc(100dvh-2rem)] w-full {{ $widthClass }} flex-col overflow-hidden rounded-2xl bg-white shadow-2xl outline-none">
        @if ($title)
            <header class="flex shrink-0 items-start justify-between gap-4 border-b border-line px-6 py-4">
                <h2 id="{{ $id }}-title" class="font-heading text-lg font-semibold text-navy">{{ $title }}</h2>
                <button type="button" class="inline-flex size-9 shrink-0 items-center justify-center rounded-md text-slate-500 hover:bg-cloud hover:text-navy" @click="close()" aria-label="Tutup dialog">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </header>
        @endif

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">{{ $slot }}</div>

        @if (isset($footer))
            <footer class="flex shrink-0 flex-wrap justify-end gap-3 border-t border-line bg-cloud px-6 py-4">{{ $footer }}</footer>
        @endif
    </div>
</div>