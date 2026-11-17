<x-layout title="Gear Up. Explore More.">
    <section class="hero-section" id="tentang">
        <div class="page-container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow"><span></span> OUTDOOR MADE SIMPLE</p>
                <h1>Lebih dekat dengan<br><em class="editorial-accent">alam.</em> Lebih siap<br>bertualang.</h1>
                <p class="hero-description">Sewa atau beli perlengkapan outdoor terkurasi. Berkualitas, aman, dan siap menemani cerita perjalananmu.</p>
                <div class="hero-ctas"><a class="btn-primary" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Mulai Sewa <span aria-hidden="true">→</span></a><a class="text-link" href="{{ route('trip-planner.index') }}">Bantu pilihkan gear <span aria-hidden="true">↗</span></a></div>
                <div class="trust-row"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5c0 5 3.2 8.3 7.5 10 4.3-1.7 7.5-5 7.5-10V6L12 3Z"/><path d="m8.5 12 2.3 2.3 4.8-5"/></svg>Gear terinspeksi</span><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>Rating dan ulasan pelanggan</span></div>
            </div>
            <div class="hero-visual">
                <div class="adventure-stamp">GEAR UP<br>· EXPLORE<br>MORE ·</div>
                <svg class="hero-art" viewBox="0 0 660 520" role="img" aria-label="Ilustrasi petualang berkemah di pegunungan" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="480" cy="90" r="54" fill="#E9DDC9"/>
                    <path d="M0 390 96 265l59-85 83 103 92-153 126 168 67-89 137 181Z" fill="#A8B99A"/>
                    <path d="m126 222 29-42 34 42-22-8-12 16-13-17Zm159-42 45-50 44 54-32-18-15 18-15-16Z" fill="#F7F8F3"/>
                    <path d="M0 390q100-44 196-9t195 2q139-13 269 20v117H0Z" fill="#183D32"/>
                    <path d="M0 443q96-57 195-23t198 1q139-6 267 22v77H0Z" fill="#08213F"/>
                    <path d="m28 389 28-66 28 66Zm40 5 35-84 35 84Zm464-6 35-69 35 69Zm43 11 27-57 27 57Z" fill="#183D32"/>
                    <path d="m291 442 88-132 91 132Z" fill="#E9DDC9" stroke="#08213F" stroke-width="8" stroke-linejoin="round"/>
                    <path d="m379 310 91 132h-91Z" fill="#80664E"/>
                    <path d="m332 442 47-93 50 93Z" fill="#F7F8F3" stroke="#08213F" stroke-width="7" stroke-linejoin="round"/>
                    <g transform="translate(178 290)">
                        <circle cx="42" cy="24" r="18" fill="#B98562"/><path d="M23 23q3-22 22-18 13 3 17 17l-7 2-6-8-11 8Z" fill="#08213F"/>
                        <path d="m31 46 26-1 15 55-39 4-8-34Z" fill="#2377C9"/><path d="m35 55-18 23m37-23 14 22" fill="none" stroke="#B98562" stroke-width="10" stroke-linecap="round"/>
                        <path d="m42 101-7 42m27-40 13 37" fill="none" stroke="#08213F" stroke-width="11" stroke-linecap="round"/>
                        <path d="m21 56-13 6 7 53 16-5Z" fill="#E9DDC9" stroke="#08213F" stroke-width="6"/>
                    </g>
                    <path d="M246 470q39-15 88 0" fill="none" stroke="#A8B99A" stroke-width="8" stroke-linecap="round"/><circle cx="545" cy="238" r="5" fill="#E9DDC9"/><circle cx="520" cy="260" r="3" fill="#E9DDC9"/><path d="m217 202 3 8 8 3-8 3-3 8-3-8-8-3 8-3Z" fill="#E9DDC9"/>
                </svg>
                <div class="hero-info"><span class="info-icon" aria-hidden="true">⌂</span><span><small>Gear terdata</small><strong>{{ number_format($featuredProducts->count(), 0, ',', '.') }} pilihan gear</strong></span></div>
            </div>
        </div>
        <form class="page-container hero-search" action="{{ route('catalog.index') }}" method="get">
            <label class="search-field"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m16 16 4.5 4.5"/></svg><span><small>Mau cari apa?</small><input name="q" type="search" placeholder="Tenda, carrier, kompor..."></span></label>
            <label class="search-field date-field"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg><span><small>Tanggal petualangan</small><input type="date" name="start_date" aria-label="Tanggal mulai"></span></label>
            <button class="search-submit" type="submit">Cari Gear</button>
        </form>
    </section>

    <section class="page-container section-block categories-section">
        <div class="section-heading"><div><p class="eyebrow">EXPLORE THE GEAR</p><h2>Mulai dari kebutuhanmu.</h2></div><a class="text-link" href="{{ route('catalog.index') }}">Lihat semua kategori <span aria-hidden="true">→</span></a></div>
        <div class="category-grid">
            @forelse($categories as $category)
                <a class="category-card" href="{{ route('catalog.index', ['kategori' => $category->slug]) }}"><span class="category-icon">{{ $category->icon ? '⌁' : '⌂' }}</span><span><strong>{{ $category->name }}</strong><small>{{ $category->products_count }} produk aktif</small></span><span class="category-arrow" aria-hidden="true">›</span></a>
            @empty
                <p class="empty-note">Kategori gear akan tampil di sini.</p>
            @endforelse
        </div>
    </section>

    <section class="featured-section">
        <div class="page-container section-block">
            <div class="section-heading"><div><p class="eyebrow">MOST LOVED</p><h2>Gear favorit petualang.</h2></div><a class="text-link" href="{{ route('catalog.index') }}">Jelajahi katalog <span aria-hidden="true">→</span></a></div>
            <div class="product-grid">
                @forelse($featuredProducts as $product)
                    @php($variant = $product->variants->first())
                    <article class="product-card">
                        <a class="product-photo" href="{{ route('catalog.show', $product) }}" @if($product->displayImagePath()) style="background-image:url('{{ media_url($product->displayImagePath()) }}')" @endif aria-label="Lihat {{ $product->name }}">
                            <span class="product-tag">{{ $product->is_featured ? 'PILIHAN GEARORA' : 'SIAP BERTUALANG' }}</span>
                        </a>
                        <div class="product-body"><p class="product-category">{{ $product->category?->name ?? 'Outdoor' }}</p><h3><a href="{{ route('catalog.show', $product) }}">{{ $product->name }}</a></h3>
                            <div class="product-modes">@if($product->supportsRental())<span>SEWA</span>@endif @if($product->supportsSale())<span>BELI</span>@endif</div>
                            <p class="product-price">@if($product->supportsRental() && $variant?->effectiveRentalPrice()){{ rupiah($variant->effectiveRentalPrice()) }} <small>/ hari</small>@elseif($variant?->effectiveSalePrice()){{ rupiah($variant->effectiveSalePrice()) }} <small>/ unit</small>@else Harga hubungi kami @endif</p>
                            <a class="product-arrow" href="{{ route('catalog.show', $product) }}" aria-label="Lihat detail {{ $product->name }}">→</a>
                        </div>
                    </article>
                @empty
                    <p class="empty-note">Produk unggulan akan tampil setelah katalog tersedia.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="page-container process-panel">
        <div class="process-copy"><p class="eyebrow">RENT WITH CONFIDENCE</p><h2>Petualangan hebat dimulai dari gear yang tepat.</h2><p>Pilih perlengkapan yang sesuai, tentukan tanggal, lalu berangkat dengan lebih tenang.</p><a class="btn-sand" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Coba sekarang <span aria-hidden="true">→</span></a></div>
        <div class="process-steps"><article><b>01</b><div><h3>Pilih gear</h3><p>Jelajahi perlengkapan terkurasi sesuai kebutuhan perjalananmu.</p></div></article><article><b>02</b><div><h3>Tentukan tanggal</h3><p>Cek ketersediaan dan biaya rental sebelum mengirim pesanan.</p></div></article><article><b>03</b><div><h3>Ambil dan bertualang</h3><p>Ambil gear di lokasi, nikmati perjalanan, lalu kembalikan tepat waktu.</p></div></article></div>
    </section>

    <section class="page-container trip-banner"><div class="trip-copy"><p class="eyebrow">GEARORA TRIP PLANNER</p><h2>Bingung harus bawa apa?</h2><p>Ceritakan rencana perjalananmu. Temukan inspirasi gear dan panduan yang membantu persiapan.</p><a class="btn-primary" href="{{ route('trip-planner.index') }}">Buat rekomendasi <span aria-hidden="true">→</span></a></div><div class="trip-photo" role="img" aria-label="Pegunungan untuk inspirasi perjalanan"></div></section>
</x-layout>
