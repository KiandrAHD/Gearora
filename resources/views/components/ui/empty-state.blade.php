@props([
    'title' => 'Belum ada data',
    'description' => null,
    'icon' => 'box',
])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-xl border border-dashed border-line bg-white px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-full bg-cloud text-forest" aria-hidden="true">
        @switch($icon)
            @case('search')
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" /></svg>
                @break
            @case('inbox')
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13h4l2 3h4l2-3h4M5 5h14l2 8v6H3v-6l2-8Z" /></svg>
                @break
            @default
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Zm0 0v9m8-4.5L12 12 4 7.5" /></svg>
        @endswitch
    </span>
    <h2 class="mt-4 font-heading text-base font-semibold text-navy">{{ $title }}</h2>
    @if ($description)
        <p class="mt-2 max-w-md text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>