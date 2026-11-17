@props(['title' => 'Akun', 'active' => 'dashboard'])
@php
    $accountNav = [
        'dashboard' => ['label' => 'Ringkasan akun', 'url' => route('customer.dashboard')],
        'orders' => ['label' => 'Pesanan saya', 'url' => route('customer.dashboard').'#pesanan'],
        'profile' => ['label' => 'Profil', 'url' => route('customer.dashboard').'#profil'],
    ];
@endphp
<x-layouts.public :title="$title">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-10 lg:py-14">
        <div class="max-w-2xl">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-forest">Akun pelanggan</p>
            <h1 class="mt-3 text-3xl font-semibold text-navy sm:text-4xl">{{ $title }}</h1>
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-[240px_minmax(0,1fr)]">
            <aside>
                <nav class="rounded-xl border border-line bg-white p-2" aria-label="Navigasi akun">
                    <ul class="space-y-1 text-sm font-semibold">
                        @foreach ($accountNav as $key => $item)
                            <li>
                                <a
                                    href="{{ $item['url'] }}"
                                    @if ($active === $key) aria-current="page" @endif
                                    class="block rounded-md px-3 py-3 transition {{ $active === $key ? 'bg-forest text-white' : 'text-slate-600 hover:bg-cloud hover:text-forest' }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <p class="mt-4 px-2 text-xs leading-6 text-slate-500">Autentikasi pelanggan aktif pada Phase 2. Halaman ini masih skeleton.</p>
            </aside>

            <section aria-label="Konten akun">{{ $slot }}</section>
        </div>
    </div>
</x-layouts.public>