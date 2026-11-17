@props(['variant' => 'neutral'])
@php
    $variants = [
        'neutral' => 'border-slate-200 bg-slate-100 text-slate-700',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-red-200 bg-red-50 text-red-800',
        'info' => 'border-blue-200 bg-blue-50 text-blue-800',
        'forest' => 'border-forest/20 bg-forest/10 text-forest',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold '.($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>