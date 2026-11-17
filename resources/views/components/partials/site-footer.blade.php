<footer class="bg-navy px-6 py-10 text-white lg:px-10">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-heading font-semibold">GEARORA</p>
            <p class="mt-1 text-sm text-slate-300">Gear Up. Explore More.</p>
        </div>
        <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-300" aria-label="Navigasi footer">
            <a class="hover:text-white" href="{{ route('home') }}">Beranda</a>
            <a class="hover:text-white" href="{{ route('catalog.index') }}">Katalog</a>
            <a class="hover:text-white" href="{{ route('guides.index') }}">Panduan</a>
            <a class="hover:text-white" href="{{ route('home') }}#tentang">Tentang</a>
        </nav>
    </div>
</footer>