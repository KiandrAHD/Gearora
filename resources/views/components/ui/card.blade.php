@props(['title' => null, 'description' => null, 'padding' => 'p-6'])
<article {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-line bg-white']) }}>
    @if ($title || isset($header))
        <header class="border-b border-line px-6 py-4">
            @if ($title)
                <h2 class="font-heading text-lg font-semibold text-navy">{{ $title }}</h2>
            @endif
            @if ($description)
                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $description }}</p>
            @endif
            {{ $header ?? '' }}
        </header>
    @endif

    <div class="{{ $padding }}">{{ $slot }}</div>

    @if (isset($footer))
        <footer class="border-t border-line bg-cloud px-6 py-4">{{ $footer }}</footer>
    @endif
</article>