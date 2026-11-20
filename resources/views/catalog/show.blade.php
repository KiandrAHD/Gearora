@php
    $rentalVariants = $product->variants->filter(fn ($v) => $v->effectiveRentalPrice() !== null && (float) $v->effectiveRentalPrice() > 0);
    $saleVariants = $product->variants->filter(fn ($v) => $v->effectiveSalePrice() !== null && (float) $v->effectiveSalePrice() > 0);
    $defaultVariant = $product->supportsRental() ? $rentalVariants->first() : $product->variants->first();
    $gallery = $product->images;
@endphp
<x-layout :title="$product->name">
    <section class="page-block">
        <div class="page-container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>/</span><a href="{{ route('catalog.index') }}">Katalog</a>@if($product->category)<span>/</span><a href="{{ route('catalog.index', ['kategori' => $product->category->slug]) }}">{{ $product->category->name }}</a>@endif</nav>

            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if($dateNotice)<div class="notice notice-warning" role="alert">{{ $dateNotice }}</div>@endif

            <div class="detail-grid">
                <div class="detail-media">
                    @if($product->displayImagePath())
                        <img src="{{ media_url($product->displayImagePath()) }}" alt="{{ $product->name }}" width="640" height="480" loading="eager">
                    @else
                        <div class="detail-media-empty">
                            <img src="{{ asset('images/product-placeholder.svg') }}" alt="" width="96" height="96">
                            <span>Foto belum tersedia</span>
                        </div>
                    @endif
                    @if($gallery->count() > 1)
                        <div class="detail-thumbs">@foreach($gallery as $image)<img src="{{ media_url($image->path) }}" alt="{{ $image->alt ?: $product->name }}">@endforeach</div>
                    @endif
                </div>
                <div class="detail-info">
                    <p class="product-category">{{ $product->category?->name ?? 'Outdoor' }}</p>
                    <h1 class="page-title">{{ $product->name }}</h1>
                    @if($product->brand)<p class="detail-brand">Merek: {{ $product->brand }}</p>@endif
                    <p class="detail-price">
                        @if($product->supportsRental() && $defaultVariant?->effectiveRentalPrice() > 0)
                            {{ rupiah($defaultVariant->effectiveRentalPrice()) }} <small>/ hari sewa</small>
                        @elseif($product->supportsSale() && $defaultVariant?->effectiveSalePrice() > 0)
                            {{ rupiah($defaultVariant->effectiveSalePrice()) }} <small>/ unit</small>
                        @endif
                    </p>
                    @if($product->description)<p class="page-lead">{{ $product->description }}</p>@endif

                    @if($product->supportsRental())
                        <form class="booking-form" method="post" action="{{ route('booking.store') }}">
                            @csrf
                            <input type="hidden" name="method" value="transfer">
                            <label><span>Varian</span>
                                <select name="variant_id" required>
                                    @foreach($rentalVariants as $variant)
                                        @php($result = $availability[$variant->getKey()] ?? null)
                                        <option value="{{ $variant->getKey() }}" @selected($defaultVariant?->getKey() === $variant->getKey())>
                                            {{ $variant->name }} — {{ rupiah($variant->effectiveRentalPrice()) }}/hari
                                            @if($result) (tersedia {{ $result->availableQuantity() }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="booking-row">
                                <label><span>Mulai</span><input name="start_date" type="date" value="{{ $startDate }}" required></label>
                                <label><span>Kembali</span><input name="end_date" type="date" value="{{ $endDate }}" required></label>
                                <label><span>Jumlah</span><input name="quantity" type="number" min="1" max="20" value="1" required></label>
                            </div>
                            <p class="field-hint">Durasi minimal {{ config('gearora.rental.min_days') }} hari, maksimal {{ config('gearora.rental.max_days') }} hari. Ketersediaan akhir dicek ulang saat pesanan dibuat.</p>
                            <button class="btn-primary" type="submit">Lanjutkan ke ringkasan</button>
                        </form>
                    @endif

                    @if($product->supportsSale())
                        <div class="sale-panel">
                            <h2>Beli langsung</h2>
                            @forelse($saleVariants as $variant)
                                @php($available = $saleAvailability[$variant->getKey()] ?? 0)
                                <div class="sale-row">
                                    <span>{{ $variant->name }}<br><small class="field-hint">Stok tersedia: {{ $available }}</small></span>
                                    <strong>{{ rupiah($variant->effectiveSalePrice()) }}</strong>
                                    @if($available > 0)
                                        <form class="inline-form" method="post" action="{{ route('retail.cart.store') }}">
                                            @csrf
                                            <input type="hidden" name="variant_id" value="{{ $variant->getKey() }}">
                                            <label><span class="sr-only">Jumlah {{ $variant->name }}</span><input type="number" name="quantity" min="1" max="{{ $available }}" value="1"></label>
                                            <button class="btn-primary" type="submit">+ Keranjang</button>
                                        </form>
                                    @else
                                        <span class="badge-inline">Stok habis</span>
                                    @endif
                                </div>
                            @empty
                                <p class="empty-note">Belum ada varian jual aktif.</p>
                            @endforelse
                            <span class="field-hint">Stok retail = stok fisik dikurangi reservasi aktif. Harga &amp; stok dihitung ulang server saat checkout.</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($product->specifications)
                <section class="spec-block"><h2>Spesifikasi</h2><dl>@foreach($product->specifications as $key => $value)<div><dt>{{ $key }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl></section>
            @endif

            @if($related->isNotEmpty())
                <section class="section-block"><div class="section-heading"><h2>Gear lain untukmu</h2></div><div class="product-grid">@foreach($related as $item)<x-partials.product-card :product="$item" />@endforeach</div></section>
            @endif
        </div>
    </section>
</x-layout>
