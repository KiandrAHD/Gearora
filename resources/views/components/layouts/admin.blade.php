@props(['title' => 'Dashboard', 'subtitle' => null])
@php
    $adminNav = [
        ['label' => 'Dashboard', 'key' => 'dashboard', 'ready' => true],
        ['label' => 'Kategori', 'key' => 'categories', 'ready' => false],
        ['label' => 'Produk & varian', 'key' => 'products', 'ready' => false],
        ['label' => 'Inventaris', 'key' => 'inventory', 'ready' => false],
        ['label' => 'Pesanan rental', 'key' => 'rentals', 'ready' => false],
        ['label' => 'Pembayaran', 'key' => 'payments', 'ready' => false],
        ['label' => 'Pengembalian', 'key' => 'returns', 'ready' => false],
        ['label' => 'Laporan', 'key' => 'reports', 'ready' => false],
    ];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} — Admin GEARORA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cloud text-navy" x-data="{ sidebarOpen: false }">
    <a href="#admin-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-4 focus:py-3 focus:text-navy">
        Lewati ke konten utama
    </a>

    <div class="min-h-screen lg:grid lg:grid-cols-[260px_minmax(0,1fr)]">
        <div class="fixed inset-0 z-30 bg-navy/60 lg:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" aria-hidden="true"></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-[260px] flex-col bg-navy text-white transition-transform lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            aria-label="Navigasi admin"
        >
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="GEARORA, kembali ke situs publik">
                    <span class="grid size-9 place-items-center rounded-full bg-forest text-base font-bold text-white" aria-hidden="true">G</span>
                    <span>
                        <span class="block font-heading text-sm font-bold tracking-[0.05em]">GEARORA</span>
                        <span class="block text-[0.6rem] font-semibold uppercase tracking-[0.18em] text-sage">Admin panel</span>
                    </span>
                </a>
                <button type="button" class="inline-flex size-9 items-center justify-center rounded-md border border-white/20 lg:hidden" @click="sidebarOpen = false" aria-label="Tutup menu admin">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-400">Operasional</p>
                <ul class="space-y-1 text-sm font-semibold">
                    @foreach ($adminNav as $item)
                        <li>
                            @if ($item['ready'])
                                <span class="block rounded-md bg-white/10 px-3 py-2.5 text-white" aria-current="page">{{ $item['label'] }}</span>
                            @else
                                <span class="flex items-center justify-between gap-2 rounded-md px-3 py-2.5 text-slate-400" aria-disabled="true">
                                    {{ $item['label'] }}
                                    <span class="rounded-full border border-white/15 px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide">Segera</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="border-t border-white/10 p-4">
                <a href="{{ route('customer.dashboard') }}" class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-white/10 hover:text-white">Lihat situs pelanggan</a>
                <p class="mt-2 px-3 text-xs text-slate-400">RBAC aktif pada Phase 2.</p>
            </div>
        </aside>

        <div class="flex min-w-0 flex-col">
            <header class="sticky top-0 z-20 border-b border-line bg-cloud/95 backdrop-blur">
                <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex size-10 items-center justify-center rounded-md border border-line text-navy lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu admin" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-forest">Admin Dashboard</p>
                            <h1 class="font-heading text-lg font-semibold leading-tight text-navy sm:text-xl">{{ $title }}</h1>
                        </div>
                    </div>
                    <span class="hidden items-center gap-2 rounded-full border border-line bg-white px-3 py-2 text-xs font-semibold text-slate-600 sm:inline-flex">
                        <span class="size-2 rounded-full bg-sage" aria-hidden="true"></span>
                        Skeleton Phase 1
                    </span>
                </div>
            </header>

            <main id="admin-content" class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if ($subtitle)
                    <p class="mb-6 max-w-3xl text-sm leading-7 text-slate-600">{{ $subtitle }}</p>
                @endif
                {{ $slot }}
            </main>

            <footer class="border-t border-line px-4 py-4 text-xs text-slate-500 sm:px-6 lg:px-8">
                GEARORA Admin · Layout skeleton. Modul admin dihubungkan pada phase berikutnya.
            </footer>
        </div>
    </div>
</body>
</html>