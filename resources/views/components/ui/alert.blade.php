@props(['variant' => 'info', 'title' => null, 'dismissible' => false])
@php
    $variants = [
        'info' => ['wrapper' => 'border-blue-200 bg-blue-50 text-blue-900', 'icon' => 'info'],
        'success' => ['wrapper' => 'border-emerald-200 bg-emerald-50 text-emerald-900', 'icon' => 'success'],
        'warning' => ['wrapper' => 'border-amber-200 bg-amber-50 text-amber-900', 'icon' => 'warning'],
        'danger' => ['wrapper' => 'border-red-200 bg-red-50 text-red-900', 'icon' => 'danger'],
    ];
    $style = $variants[$variant] ?? $variants['info'];
@endphp
<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif
    role="{{ $variant === 'danger' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$style['wrapper']]) }}
>
    <span class="mt-0.5 shrink-0" aria-hidden="true">
        @switch($style['icon'])
            @case('success')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                @break
            @case('warning')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 2.4 18a1.8 1.8 0 0 0 1.6 2.7h16a1.8 1.8 0 0 0 1.6-2.7L13.7 3.9a1.8 1.8 0 0 0-3.4 0Z" /></svg>
                @break
            @case('danger')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 8v5m0 3h.01" /></svg>
                @break
            @default
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 11v5m0-8h.01" /></svg>
        @endswitch
    </span>

    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-1' => $title])>{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button type="button" class="shrink-0 rounded p-1 hover:bg-black/5" @click="show = false" aria-label="Tutup notifikasi">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    @endif
</div>