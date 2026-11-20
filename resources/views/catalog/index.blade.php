@php
    $isRetail = $mode === 'beli';
    $modeLabel = $isRetail ? 'Beli' : 'Sewa';
    $otherMode = $isRetail ? 'sewa' : 'beli';
    $hasFilters = $keyword !== '' || $categorySlug !== '' || $minPrice !== null || $maxPrice !== null
        || $stockOnly || $startDate !== '' || $endDate !== '';
    $resetUrl = route('catalog.index', ['mode' => $mode]);
@endphp
<x-layout :title="'Katalog '.$modeLabel">
    <section class="catalog-hero">
        <div class="page-container catalog-hero-inner">
            <div class="catalog-hero-copy">
                <p class="eyebrow">KATALOG {{ $isRetail ? 'BELI' : 'SEWA' }} GEARORA</p>
                <h1 class="page-title">{{ $isRetail ? 'Beli gear yang tepat untukmu.' : 'Sewa gear untuk perjalananmu.' }}</h1>
            </div>
            <p class="catalog-hero-desc">
                {{ $isRetail
                    ? 'Bandingkan harga jual per unit dan ketersediaan stok retail yang sudah dikurangi reservasi aktif.'
                    : 'Saring berdasarkan kategori, harga harian, dan tanggal untuk melihat unit rental yang benar-benar tersedia.' }}
            </p>
        </div>
    </section>

    <section class="catalog-section" x-data="{ filtersOpen: false }" x-effect="document.body.classList.toggle('modal-open', filtersOpen)">
        <div class="page-container">
            <div class="catalog-modes" role="tablist" aria-label="Mode katalog">
                <a class="catalog-mode {{ ! $isRetail ? 'is-active' : '' }}" href="{{ route('catalog.index', ['mode' => 'sewa']) }}" @if(! $isRetail) aria-current="page" @endif>Sewa</a>
                <a class="catalog-mode {{ $isRetail ? 'is-active' : '' }}" href="{{ route('catalog.index', ['mode' => 'beli']) }}" @if($isRetail) aria-current="page" @endif>Beli</a>
            </div>

            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if(session('error'))<div class="notice notice-warning" role="alert">{{ session('error') }}</div>@endif
            @if($dateNotice)<div class="notice notice-warning" role="alert">{{ $dateNotice }}</div>@endif

            <form class="catalog-form" method="get" action="{{ route('catalog.index') }}">
                <input type="hidden" name="mode" value="{{ $mode }}">

                <div class="catalog-toolbar">
                    <label class="catalog-search">
                        <span class="sr-only">Cari gear</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m16 16 4.5 4.5"/></svg>
                        <input type="search" name="q" value="{{ $keyword }}" placeholder="Cari nama, merek, atau deskripsi gear">
                    </label>

                    <div class="catalog-toolbar-actions">
                        <label class="catalog-sort">
                            <span class="sr-only">Urutkan</span>
                            <select name="sort" onchange="this.form.requestSubmit()">
                                @foreach($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value || ($sort === '' && $value === 'unggulan'))>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="catalog-filter-toggle" type="button" @click="filtersOpen = true" aria-controls="catalog-filters">
                            Filter
                            @if($hasFilters)<span class="catalog-filter-dot" aria-hidden="true"></span>@endif
                        </button>
                        <button class="btn-primary catalog-search-submit" type="submit">Cari</button>
                    </div>
                </div>

                <div class="catalog-filter-backdrop" x-show="filtersOpen" x-cloak x-transition.opacity @click="filtersOpen = false" aria-hidden="true"></div>

                <div class="catalog-layout">
                    <aside id="catalog-filters" class="catalog-sidebar" :class="{ 'is-open': filtersOpen }" aria-label="Filter katalog">
                        <div class="catalog-sidebar-inner">
                            <div class="catalog-sidebar-head">
                                <h2>Filter</h2>
                                <button type="button" class="catalog-sidebar-close" @click="filtersOpen = false" aria-label="Tutup filter">Tutup</button>
                            </div>

                            <fieldset class="filter-group">
                                <legend>Kategori</legend>
                                <label class="filter-radio"><input type="radio" name="kategori" value="" @checked($categorySlug === '')> <span>Semua kategori</span></label>
                                @foreach($categories as $category)
                                    <label class="filter-radio"><input type="radio" name="kategori" value="{{ $category->slug }}" @checked($categorySlug === $category->slug)> <span>{{ $category->name }}</span></label>
                                @endforeach
                            </fieldset>

                            <fieldset class="filter-group">
                                <legend>{{ $isRetail ? 'Harga jual / unit' : 'Harga sewa / hari' }}</legend>
                                <div class="filter-range">
                                    <label><span class="sr-only">Harga minimum</span><input type="number" inputmode="numeric" name="min_price" min="0" step="1000" value="{{ $minPrice }}" placeholder="Min"></label>
                                    <span aria-hidden="true">–</span>
                                    <label><span class="sr-only">Harga maksimum</span><input type="number" inputmode="numeric" name="max_price" min="0" step="1000" value="{{ $maxPrice }}" placeholder="Maks"></label>
                                </div>
                                <span class="field-hint">{{ $isRetail ? 'Harga per unit.' : 'Harga per hari sewa.' }}</span>
                            </fieldset>

                            @if($isRetail)
                                <fieldset class="filter-group">
                                    <legend>Ketersediaan</legend>
                                    <label class="filter-check"><input type="checkbox" name="stock" value="tersedia" @checked($stockOnly)> <span>Hanya stok tersedia</span></label>
                                    <span class="field-hint">Stok fisik dikurangi reservasi aktif.</span>
                                </fieldset>
                            @else
                                <fieldset class="filter-group">
                                    <legend>Tanggal rental</legend>
                                    <label class="filter-field"><span>Mulai</span><input type="date" name="start_date" value="{{ $startDate }}"></label>
                                    <label class="filter-field"><span>Kembali</span><input type="date" name="end_date" value="{{ $endDate }}"></label>
                                    <span class="field-hint">Ketersediaan dicek server-side via RentalAvailabilityService.</span>
                                </fieldset>
                            @endif

                            <div class="catalog-sidebar-actions">
                                <button class="btn-primary" type="submit">Terapkan filter</button>
                                <a class="text-link" href="{{ $resetUrl }}">Reset filter</a>
                            </div>
                        </div>
                    </aside>

                    <div class="catalog-results">
                        <div class="catalog-results-head">
                            <p class="catalog-count"><strong>{{ $products->total() }}</strong> gear {{ $isRetail ? 'dijual' : 'disewa' }}</p>
                            @if($hasFilters)<a class="text-link" href="{{ $resetUrl }}">Hapus semua filter</a>@endif
                        </div>

                        @if($hasFilters)
                            <ul class="catalog-chips" aria-label="Filter aktif">
                                @if($keyword !== '')<li>Kata kunci: “{{ $keyword }}”</li>@endif
                                @if($categorySlug !== '')<li>Kategori: {{ collect($categories)->firstWhere('slug', $categorySlug)?->name ?? $categorySlug }}</li>@endif
                                @if($minPrice !== null || $maxPrice !== null)<li>Harga: {{ $minPrice !== null ? rupiah($minPrice) : '0' }} – {{ $maxPrice !== null ? rupiah($maxPrice) : '∞' }}</li>@endif
                                @if($stockOnly)<li>Stok tersedia</li>@endif
                                @if($period)<li>{{ $period->startDate() }} → {{ $period->endDate() }}</li>@endif
                            </ul>
                        @endif

                        <div class="catalog-grid">
                            @forelse($products as $product)
                                <x-partials.product-card :product="$product" :mode="$mode" :start-date="$startDate ?: null" :end-date="$endDate ?: null" />
                            @empty
                                <div class="catalog-empty" role="status">
                                    <h3>Belum ada gear yang cocok</h3>
                                    <p>Coba ubah kata kunci, kategori, atau rentang harga{{ $isRetail ? '.' : ', atau pilih tanggal lain.' }}</p>
                                    <a class="btn-sand" href="{{ $resetUrl }}">Reset filter</a>
                                </div>
                            @endforelse
                        </div>

                        <div class="pagination-wrap">{{ $products->links() }}</div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</x-layout>
