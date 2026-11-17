@php
    $features = [
        ['label' => 'Pencarian', 'value' => 'Aktif Phase 3'],
        ['label' => 'Filter kategori', 'value' => 'Aktif Phase 3'],
        ['label' => 'Ketersediaan', 'value' => 'Aktif Phase 4'],
    ];
@endphp
<x-layout title="Katalog">
    <section class="border-b border-line bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-10 lg:py-16">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-forest">Katalog</p>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-2xl">
                    <h1 class="text-4xl font-semibold text-navy sm:text-5xl">Temukan gear untuk perjalananmu.</h1>
                    <p class="mt-4 text-slate-600">Halaman katalog masih skeleton. Data produk, varian, dan ketersediaan dihubungkan pada Phase 3 dan Phase 4.</p>
                </div>
                <x-ui.badge variant="forest">Foundation Phase 1</x-ui.badge>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-10 lg:px-10 lg:py-14">
        <x-ui.alert variant="info" title="Katalog belum berisi produk">
            Struktur halaman, komponen filter, dan empty state sudah siap. Modul katalog akan mengisi data nyata setelah Phase 3.
        </x-ui.alert>

        <div class="mt-8 grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
            <x-ui.card title="Filter" description="Kontrol filter disiapkan untuk katalog.">
                <form class="space-y-4" aria-label="Filter katalog" action="{{ route('catalog.index') }}" method="get">
                    <x-ui.input name="q" label="Cari gear" placeholder="Tenda, carrier, sleeping bag" hint="Pencarian dijalankan di server pada Phase 3." />
                    <div>
                        <label for="kategori" class="mb-2 block text-sm font-semibold text-navy">Kategori</label>
                        <select id="kategori" name="kategori" class="block w-full rounded-md border border-line bg-white px-3.5 py-3 text-sm text-navy shadow-sm focus:border-forest focus:outline-none focus:ring-2 focus:ring-forest/20">
                            <option value="">Semua kategori</option>
                            <option value="hiking">Hiking</option>
                            <option value="camping">Camping</option>
                            <option value="trekking">Trekking</option>
                        </select>
                    </div>
                    <x-ui.button type="submit" variant="primary" size="sm" block>Terapkan filter</x-ui.button>
                </form>
            </x-ui.card>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-600">Menampilkan 0 produk</p>
                    <x-ui.badge variant="neutral">Mode sewa &amp; beli</x-ui.badge>
                </div>

                <div class="mt-4">
                    <x-ui.empty-state
                        icon="search"
                        title="Belum ada produk di katalog"
                        description="Produk yang aktif akan muncul di sini lengkap dengan harga sewa, harga jual, spesifikasi, dan status ketersediaan."
                    >
                        <x-ui.button href="{{ route('home') }}" variant="outline" size="sm">Kembali ke beranda</x-ui.button>
                    </x-ui.empty-state>
                </div>

                <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                    @foreach ($features as $feature)
                        <div class="rounded-lg border border-line bg-white p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $feature['label'] }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-navy">{{ $feature['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>
</x-layout>