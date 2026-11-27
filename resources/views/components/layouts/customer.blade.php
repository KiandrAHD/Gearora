@props(['title' => 'Akun', 'active' => 'dashboard'])
@php
    $user = auth()->user();
    $accountNav = [
        'dashboard' => ['label' => 'Ringkasan akun', 'url' => route('customer.dashboard'), 'icon' => 'grid'],
        'orders' => ['label' => 'Sewa saya', 'url' => route('customer.orders.index'), 'icon' => 'box'],
        'sales' => ['label' => 'Pembelian saya', 'url' => route('customer.sales.index'), 'icon' => 'bag'],
        'profile' => ['label' => 'Profil & keamanan', 'url' => route('customer.profile.edit'), 'icon' => 'user'],
    ];
@endphp
<x-layouts.public :title="$title">
    <div class="page-block account-page">
        <div class="page-container">
            <div class="account-hero">
                <div class="account-hero-copy">
                    <p class="eyebrow">AKUN PELANGGAN</p>
                    <h1 class="page-title">{{ $title }}</h1>
                    <p class="account-hero-lead">
                        Halo, <strong>{{ $user?->name }}</strong>. Pantau pesanan sewa, pembelian, dan data akunmu di satu tempat.
                    </p>
                </div>
                <div class="account-hero-badge" aria-hidden="true">
                    <span class="account-avatar">{{ strtoupper(mb_substr((string) $user?->name, 0, 1)) }}</span>
                    <span class="account-hero-meta">
                        <strong>{{ $user?->name }}</strong>
                        <small>{{ $user?->email }}</small>
                    </span>
                </div>
            </div>

            <div class="account-grid">
                <aside>
                    <nav class="account-nav" aria-label="Navigasi akun">
                        @foreach ($accountNav as $key => $item)
                            <a href="{{ $item['url'] }}" @if ($active === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                    <form method="post" action="{{ route('logout') }}" class="account-logout">@csrf<button class="text-link" type="submit">Keluar</button></form>
                </aside>
                <section class="account-content" aria-label="Konten akun">{{ $slot }}</section>
            </div>
        </div>
    </div>
</x-layouts.public>