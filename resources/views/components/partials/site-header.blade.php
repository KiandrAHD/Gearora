@php
    $homeUrl = route('home');
    $catalogUrl = \Illuminate\Support\Facades\Route::has('catalog.index') ? route('catalog.index') : $homeUrl;
    $guidesUrl = \Illuminate\Support\Facades\Route::has('guides.index') ? route('guides.index') : $homeUrl;
    $accountUrl = \Illuminate\Support\Facades\Route::has('customer.dashboard') ? route('customer.dashboard') : $homeUrl;
@endphp
<header class="border-b border-line bg-cloud" x-data="{ open: false }">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-5 lg:px-10">
        <a href="{{ $homeUrl }}" class="flex min-h-11 items-center gap-3" aria-label="GEARORA, kembali ke beranda">
            <span class="grid size-10 place-items-center rounded-full bg-forest text-lg font-bold text-white" aria-hidden="true">G</span>
            <span>
                <span class="block font-heading text-lg font-bold tracking-[0.05em] text-navy">GEARORA</span>
                <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-forest">Outdoor Supply</span>
            </span>
        </a>

        <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex" aria-label="Navigasi utama">
            <a class="transition-colors hover:text-forest" href="{{ $homeUrl }}">Beranda</a>
            <a class="transition-colors hover:text-forest" href="{{ $catalogUrl }}">Katalog</a>
            <a class="transition-colors hover:text-forest" href="{{ $guidesUrl }}">Panduan</a>
            <a class="transition-colors hover:text-forest" href="{{ $homeUrl }}#tentang">Tentang</a>
            <a class="rounded-md border border-line px-4 py-2.5 text-navy transition hover:border-forest hover:text-forest" href="{{ $accountUrl }}">Akun saya</a>
        </nav>

        <button
            type="button"
            class="inline-flex size-11 items-center justify-center rounded-md border border-line text-navy lg:hidden"
            @click="open = ! open"
            :aria-expanded="open.toString()"
            aria-controls="mobile-navigation"
            aria-label="Buka atau tutup menu navigasi"
        >
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path x-show="! open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>
    <nav id="mobile-navigation" class="border-t border-line px-6 py-4 lg:hidden" x-show="open" x-cloak x-transition aria-label="Navigasi seluler">
        <ul class="flex flex-col gap-1 text-sm font-semibold text-slate-600">
            <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="{{ $homeUrl }}" @click="open = false">Beranda</a></li>
            <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="{{ $catalogUrl }}" @click="open = false">Katalog</a></li>
            <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="{{ $guidesUrl }}" @click="open = false">Panduan</a></li>
            <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="{{ $homeUrl }}#tentang" @click="open = false">Tentang</a></li>
            <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="{{ $accountUrl }}" @click="open = false">Akun saya</a></li>
        </ul>
    </nav>
</header>