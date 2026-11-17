@php
    $homeUrl = route('home');
    $catalogUrl = route('catalog.index');
    $tripUrl = route('trip-planner.index');
    $guidesUrl = route('guides.index');
    $accountUrl = auth()->check() ? route('customer.dashboard') : route('login');
    $active = request()->routeIs('home') ? 'home' : (request()->routeIs('catalog.*') ? 'catalog' : '');
    $cartCount = (int) session('cart_count', 0);
@endphp
<header class="site-header" x-data="{ open: false }">
    <div class="site-header-inner">
        <a href="{{ $homeUrl }}" class="brand" aria-label="GEARORA, kembali ke beranda">
            <img src="{{ asset('images/gearora-logo.png') }}" width="37" height="37" alt="" class="brand-mark">
            <span class="brand-copy"><strong>GEARORA</strong><small>OUTDOOR SUPPLY</small></span>
        </a>
        <nav class="primary-nav" aria-label="Navigasi utama">
            <a href="{{ $catalogUrl }}?mode=sewa" @if($active === 'catalog') aria-current="page" @endif>Sewa</a>
            <a href="{{ $catalogUrl }}?mode=beli" @if($active === 'catalog') aria-current="page" @endif>Beli</a>
            <a href="{{ $tripUrl }}">Rekomendasi Trip</a>
            <a href="{{ $guidesUrl }}" @if(request()->routeIs('guides.*')) aria-current="page" @endif>Panduan</a>
        </nav>
        <div class="header-actions">
            <a class="header-icon search-action" href="{{ $catalogUrl }}" aria-label="Cari gear"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m16 16 4.5 4.5"/></svg></a>
            <a class="header-icon cart-action" href="{{ $cartCount ? route('booking.summary') : $catalogUrl }}" aria-label="Keranjang, {{ $cartCount }} item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 1.9-1.4L21 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>@if($cartCount > 0)<span class="cart-count">{{ $cartCount }}</span>@endif</a>
            <a class="account-action" href="{{ $accountUrl }}"><span class="user-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.7-3.4 2.9-5.2 6.5-5.2s5.8 1.8 6.5 5.2"/></svg></span>{{ auth()->check() ? 'Akun saya' : 'Masuk' }}</a>
            <button type="button" class="mobile-menu" @click="open = !open" :aria-expanded="open.toString()" aria-controls="mobile-navigation" aria-label="Buka atau tutup menu"><svg viewBox="0 0 24 24" aria-hidden="true"><path x-show="!open" d="M4 7h16M4 12h16M4 17h16"/><path x-show="open" x-cloak d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
    </div>
    <nav id="mobile-navigation" class="mobile-nav" x-show="open" x-cloak x-transition aria-label="Navigasi seluler">
        <a href="{{ $catalogUrl }}?mode=sewa" @click="open=false">Sewa</a><a href="{{ $catalogUrl }}?mode=beli" @click="open=false">Beli</a><a href="{{ $tripUrl }}" @click="open=false">Rekomendasi Trip</a><a href="{{ $guidesUrl }}" @click="open=false">Panduan</a><a href="{{ $accountUrl }}" @click="open=false">Akun Saya</a><a href="{{ route('admin.login') }}" @click="open=false">Admin Console</a>
    </nav>
</header>
