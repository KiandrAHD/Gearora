@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'block' => false,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-md font-bold transition focus-visible:outline-3 focus-visible:outline-offset-3 disabled:cursor-not-allowed disabled:opacity-60';
    $variants = [
        'primary' => 'bg-forest text-white shadow-lg shadow-forest/15 hover:bg-navy',
        'secondary' => 'bg-navy text-white hover:bg-forest',
        'outline' => 'border border-forest bg-transparent text-forest hover:bg-white',
        'ghost' => 'bg-transparent text-navy hover:bg-white',
        'danger' => 'bg-red-700 text-white hover:bg-red-800',
    ];
    $sizes = [
        'sm' => 'min-h-10 px-4 text-sm',
        'md' => 'min-h-12 px-6 text-sm',
        'lg' => 'min-h-14 px-8 text-base',
    ];
    $classes = trim(
        $base.' '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md'])
        .($block ? ' w-full' : '')
    );
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif