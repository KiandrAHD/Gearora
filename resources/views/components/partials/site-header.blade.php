@php
    $homeUrl = route('home');
    $catalogUrl = route('catalog.index');
    $tripUrl = route('trip-planner.index');
    $guidesUrl = route('guides.index');
    $accountUrl = auth()->check() ? route('customer.dashboard') : route('login');
    $isCatalogIndex = request()->routeIs('catalog.index');
    $activeMode = request()->query('mode', 'sewa');
    $isRentalActive = $isCatalogIndex && $activeMode === 'sewa';
    $isSaleActive = $isCatalogIndex && $activeMode === 'beli';
    $cartCount = app(\App\Services\SalesCartService::class)->count();
    $cartUrl = $cartCount > 0 ? route('retail.cart.index') : route('catalog.index', ['mode' => 'beli']);
@endphp
<header class="site-header" x-data="mobileNav" x-effect="document.body.classList.toggle('nav-open', open)" @keydown.escape.window="close()" @resize.window="if (window.innerWidth > 900) open = false">
    <div class="site-header-inner">
        <a href="{{ $homeUrl }}" class="brand" aria-label="GEARORA, kembali ke beranda">
            <img src="{{ asset('images/gearora-logo.png') }}" width="37" height="37" alt="" class="brand-mark">
            <span class="brand-copy"><strong>GEARORA</strong><small>OUTDOOR SUPPLY</small></span>
        </a>
        <nav class="primary-nav" aria-label="Navigasi utama">
            <a href="{{ route('catalog.index', ['mode' => 'sewa']) }}" @if($isRentalActive) aria-current="page" @endif>Sewa</a>
            <a href="{{ route('catalog.index', ['mode' => 'beli']) }}" @if($isSaleActive) aria-current="page" @endif>Beli</a>
            <a href="{{ $tripUrl }}" @if(request()->routeIs('trip-planner.*')) aria-current="page" @endif>Rekomendasi Trip</a>
            <a href="{{ $guidesUrl }}" @if(request()->routeIs('guides.*')) aria-current="page" @endif>Panduan</a>
        </nav>
        <div class="header-actions">
            <a class="header-icon search-action" href="{{ $catalogUrl }}" aria-label="Cari gear"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m16 16 4.5 4.5"/></svg></a>
            <a class="header-icon cart-action" href="{{ $cartUrl }}" aria-label="Keranjang, {{ $cartCount }} item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 1.9-1.4L21 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>@if($cartCount > 0)<span class="cart-count">{{ $cartCount }}</span>@endif</a>
            <a class="account-action" href="{{ $accountUrl }}"><span class="user-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.7-3.4 2.9-5.2 6.5-5.2s5.8 1.8 6.5 5.2"/></svg></span>{{ auth()->check() ? 'Akun saya' : 'Masuk' }}</a>
            <button type="button" class="mobile-menu" @click="toggle()" :aria-expanded="open.toString()" aria-controls="mobile-navigation" aria-label="Buka atau tutup menu"><svg viewBox="0 0 24 24" aria-hidden="true"><path x-show="!open" d="M4 7h16M4 12h16M4 17h16"/><path x-show="open" x-cloak d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
    </div>
    <div class="mobile-nav-backdrop" x-show="open" x-cloak x-transition.opacity @click="close()" aria-hidden="true"></div>
    <nav id="mobile-navigation" class="mobile-nav" x-ref="drawer" x-show="open" x-cloak x-transition @keydown.tab="trap($event)" aria-label="Navigasi seluler">
        <button type="button" class="mobile-nav-close" x-ref="drawerClose" @click="close()" aria-label="Tutup menu navigasi">Tutup menu</button>
        <a href="{{ route('catalog.index', ['mode' => 'sewa']) }}" @click="close()" class="{{ $isRentalActive ? 'is-active' : '' }}" @if($isRentalActive) aria-current="page" @endif>Sewa</a>
        <a href="{{ route('catalog.index', ['mode' => 'beli']) }}" @click="close()" class="{{ $isSaleActive ? 'is-active' : '' }}" @if($isSaleActive) aria-current="page" @endif>Beli</a>
        <a href="{{ $tripUrl }}" @click="close()" @if(request()->routeIs('trip-planner.*')) aria-current="page" @endif>Rekomendasi Trip</a>
        <a href="{{ $guidesUrl }}" @click="close()" @if(request()->routeIs('guides.*')) aria-current="page" @endif>Panduan</a>
        <a href="{{ $accountUrl }}" @click="close()">{{ auth()->check() ? 'Akun Saya' : 'Masuk' }}</a>
        @if(auth()->check() && auth()->user()->isStaff())<a href="{{ route('admin.dashboard') }}" @click="close()">Admin Console</a>@endif
    </nav>
</header>
