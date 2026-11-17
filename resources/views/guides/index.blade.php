<x-layout title="Panduan Perlengkapan">
    <section class="border-b border-line bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-10 lg:py-16">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-forest">Panduan</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-semibold text-navy sm:text-5xl">Panduan perlengkapan sebelum berangkat.</h1>
            <p class="mt-4 max-w-2xl text-slate-600">Artikel panduan dan rekomendasi gear dihubungkan pada Phase 6. Struktur halaman dan empty state sudah tersedia.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-10 lg:px-10 lg:py-14">
        <x-ui.empty-state
            icon="inbox"
            title="Belum ada panduan yang dipublikasikan"
            description="Panduan yang sudah berstatus published akan tampil di sini. Konten draft tidak akan ditampilkan ke publik."
        >
            <x-ui.button href="{{ route('catalog.index') }}" variant="outline" size="sm">Lihat katalog</x-ui.button>
        </x-ui.empty-state>
    </section>
</x-layout>