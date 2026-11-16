@props(['number', 'title', 'description'])
<article {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-white p-6']) }}>
    <p class="font-heading text-3xl font-medium text-sage">{{ $number }}</p>
    <h3 class="mt-5 font-heading text-lg font-semibold text-navy">{{ $title }}</h3>
    <p class="mt-3 text-sm leading-7 text-[#526267]">{{ $description }}</p>
</article>
