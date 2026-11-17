@props(['title' => 'Akun', 'active' => 'dashboard'])
@php
    $accountNav = [
        'dashboard' => ['label' => 'Ringkasan akun', 'url' => route('customer.dashboard')],
        'orders' => ['label' => 'Pesanan saya', 'url' => route('customer.orders.index')],
        'profile' => ['label' => 'Profil', 'url' => route('customer.profile.edit')],
    ];
@endphp
<x-layouts.public :title="$title">
    <div class="page-block">
        <div class="page-container">
            <p class="eyebrow">AKUN PELANGGAN</p>
            <h1 class="page-title">{{ $title }}</h1>
            <div class="account-grid">
                <aside>
                    <nav class="account-nav" aria-label="Navigasi akun">
                        @foreach ($accountNav as $key => $item)
                            <a href="{{ $item['url'] }}" @if ($active === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                    <form method="post" action="{{ route('logout') }}" class="account-logout">@csrf<button class="text-link" type="submit">Keluar</button></form>
                </aside>
                <section aria-label="Konten akun">{{ $slot }}</section>
            </div>
        </div>
    </div>
</x-layouts.public>
