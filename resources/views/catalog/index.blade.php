<x-layout title="Katalog">
    <section class="page-block">
        <div class="page-container">
            <p class="eyebrow">KATALOG GEARORA</p>
            <h1 class="page-title">Temukan gear untuk perjalananmu.</h1>
            <p class="page-lead">Saring berdasarkan kategori, kata kunci, dan tanggal untuk melihat unit rental yang benar-benar tersedia.</p>
            <form class="catalog-filter" method="get" action="{{ route('catalog.index') }}">
                <label><span>Kata kunci</span><input name="q" type="search" value="{{ $keyword }}" placeholder="Tenda, carrier, kompor"></label>
                <label><span>Kategori</span><select name="kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected($categorySlug === $category->slug)>{{ $category->name }}</option>@endforeach</select></label>
                <label><span>Mode</span><select name="mode"><option value="sewa" @selected($mode === 'sewa')>Sewa</option><option value="beli" @selected($mode === 'beli')>Beli</option><option value="semua" @selected($mode === 'semua')>Sewa &amp; Beli</option></select></label>
                <label><span>Mulai</span><input name="start_date" type="date" value="{{ $startDate }}"></label>
                <label><span>Kembali</span><input name="end_date" type="date" value="{{ $endDate }}"></label>
                <button class="btn-primary" type="submit">Cari gear</button>
            </form>
        </div>
    </section>

    <section class="page-block section-block">
        <div class="page-container">
            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if($dateNotice)<div class="notice notice-warning" role="alert">{{ $dateNotice }}</div>@endif
            <div class="section-heading"><div><p class="eyebrow">HASIL PENCARIAN</p><h2>{{ $products->total() }} gear ditemukan</h2></div>@if($period)<span class="badge-inline">Filter tanggal aktif</span>@endif</div>
            <div class="product-grid">
                @forelse($products as $product)
                    <x-partials.product-card :product="$product" :start-date="$startDate ?: null" :end-date="$endDate ?: null" />
                @empty
                    <p class="empty-note">Belum ada gear yang cocok dengan filter ini. Coba ubah kata kunci atau tanggal.</p>
                @endforelse
            </div>
            <div class="pagination-wrap">{{ $products->links() }}</div>
        </div>
    </section>
</x-layout>
