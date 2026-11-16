@props(['title' => ''])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GEARORA — Gear Up. Explore More. Rental dan perlengkapan outdoor.">
    <title>{{ $title ? $title.' — ' : '' }}GEARORA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-4 focus:py-3 focus:text-navy">
        Lewati ke konten utama
    </a>

    <header class="border-b border-line bg-cloud" x-data="{ open: false }">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-10">
            <a href="{{ route('home') }}" class="flex min-h-11 items-center gap-3" aria-label="GEARORA, kembali ke beranda">
                <span class="grid size-10 place-items-center rounded-full bg-forest text-lg font-bold text-white" aria-hidden="true">G</span>
                <span>
                    <span class="block font-heading text-lg font-bold tracking-[0.05em] text-navy">GEARORA</span>
                    <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-forest">Outdoor Supply</span>
                </span>
            </a>
            <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-600 md:flex" aria-label="Navigasi utama">
                <a class="transition-colors hover:text-forest" href="#tentang">Tentang</a>
                <a class="transition-colors hover:text-forest" href="#fondasi">Fondasi</a>
                <a class="rounded-md bg-forest px-5 py-3 text-white shadow-lg shadow-forest/15 transition hover:bg-navy" href="mailto:hello@gearora.test">Hubungi kami</a>
            </nav>
            <button
                type="button"
                class="inline-flex size-11 items-center justify-center rounded-md border border-line text-navy md:hidden"
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
        <nav id="mobile-navigation" class="border-t border-line px-6 py-4 md:hidden" x-show="open" x-cloak x-transition aria-label="Navigasi seluler">
            <ul class="flex flex-col gap-1 text-sm font-semibold text-slate-600">
                <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="#tentang" @click="open = false">Tentang</a></li>
                <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="#fondasi" @click="open = false">Fondasi</a></li>
                <li><a class="block rounded-md px-3 py-3 hover:bg-white" href="mailto:hello@gearora.test" @click="open = false">Hubungi kami</a></li>
            </ul>
        </nav>
    </header>

    <main id="main-content">{{ $slot }}</main>

    <footer class="bg-navy px-6 py-10 text-white lg:px-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="font-heading font-semibold">GEARORA</p>
            <p class="text-sm text-slate-300">Gear Up. Explore More.</p>
        </div>
    </footer>
</body>
</html>
