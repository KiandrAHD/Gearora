@props(['product', 'mode' => null, 'startDate' => null, 'endDate' => null])
@php
    $mode = in_array($mode, ['sewa', 'beli'], true) ? $mode : ($product->supportsRental() ? 'sewa' : 'beli');
    $isRetail = $mode === 'beli';
    $variant = $product->variants->first();
    $price = $isRetail ? $variant?->effectiveSalePrice() : $variant?->effectiveRentalPrice();
    $hasPrice = $price !== null && (float) $price > 0;
    $unit = $isRetail ? '/ unit' : '/ hari';
    $query = array_filter(['start_date' => $startDate, 'end_date' => $endDate]);
    $url = route('catalog.show', array_merge(['product' => $product], $query));
    $imagePath = $product->displayImagePath();

    // Status ketersediaan dihitung server-side. Mode beli memakai stok fisik
    // (reservasi aktif sudah dikurangi pada filter); mode sewa menandai unit siap.
    $stock = (int) ($variant?->stock_quantity ?? 0);
    $isAvailable = $isRetail ? $stock > 0 : $hasPrice;
@endphp
<article class="product-card">
    <a class="product-photo" href="{{ $url }}" aria-label="Lihat detail {{ $product->name }}">
        @if($imagePath)
            <img src="{{ media_url($imagePath) }}" alt="{{ $product->name }}" loading="lazy" decoding="async" width="400" height="300">
        @else
            <span class="product-photo-fallback">
                <img src="{{ asset('images/product-placeholder.svg') }}" alt="" width="96" height="96" loading="lazy" decoding="async">
                <span class="product-photo-fallback-label">Foto belum tersedia</span>
            </span>
        @endif
        <span class="product-tag">{{ $product->brand ?: 'GEARORA' }}</span>
        @if($product->is_featured)
            <span class="product-select">GEARORA SELECT</span>
        @endif
        <span class="product-availability {{ $isAvailable ? 'is-ok' : 'is-empty' }}">
            @if($isRetail)
                {{ $isAvailable ? 'Stok '.$stock : 'Stok habis' }}
            @else
                {{ $hasPrice ? 'Siap sewa' : 'Belum tersedia' }}
            @endif
        </span>
    </a>
    <div class="product-body">
        <p class="product-category">{{ $product->category?->name ?? 'Outdoor' }} @if($product->brand) · {{ $product->brand }} @endif</p>
        <h3><a href="{{ $url }}">{{ $product->name }}</a></h3>
        <div class="product-modes">
            @if($product->supportsRental())<span class="{{ ! $isRetail ? 'is-current' : '' }}">SEWA</span>@endif
            @if($product->supportsSale())<span class="{{ $isRetail ? 'is-current' : '' }}">BELI</span>@endif
        </div>
        <div class="product-foot">
            <p class="product-price">
                @if($hasPrice)
                    {{ rupiah($price) }} <small>{{ $unit }}</small>
                @else
                    <span class="product-price-empty">Harga belum tersedia</span>
                @endif
            </p>
            <a class="product-arrow" href="{{ $url }}" aria-label="Lihat detail {{ $product->name }}">→</a>
        </div>
    </div>
</article>
