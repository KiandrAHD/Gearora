@props(['product', 'startDate' => null, 'endDate' => null])
@php
    $variant = $product->variants->first();
    $query = array_filter(['start_date' => $startDate, 'end_date' => $endDate]);
    $url = route('catalog.show', array_merge(['product' => $product], $query));
@endphp
<article class="product-card">
    <a class="product-photo" href="{{ $url }}" @if($product->displayImagePath()) style="background-image:url('{{ media_url($product->displayImagePath()) }}')" @endif aria-label="Lihat {{ $product->name }}">
        <span class="product-tag">{{ $product->brand ?: 'GEARORA' }}</span>
    </a>
    <div class="product-body">
        <p class="product-category">{{ $product->category?->name ?? 'Outdoor' }} @if($product->brand) · {{ $product->brand }} @endif</p>
        <h3><a href="{{ $url }}">{{ $product->name }}</a></h3>
        <div class="product-modes">@if($product->supportsRental())<span>SEWA</span>@endif @if($product->supportsSale())<span>BELI</span>@endif</div>
        <p class="product-price">
            @if($product->supportsRental() && $variant?->effectiveRentalPrice() > 0)
                {{ rupiah($variant->effectiveRentalPrice()) }} <small>/ hari</small>
            @elseif($product->supportsSale() && $variant?->effectiveSalePrice() > 0)
                {{ rupiah($variant->effectiveSalePrice()) }} <small>/ unit</small>
            @else
                Hubungi kami
            @endif
        </p>
        <a class="product-arrow" href="{{ $url }}" aria-label="Lihat detail {{ $product->name }}">→</a>
    </div>
</article>
