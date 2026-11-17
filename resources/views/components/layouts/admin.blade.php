@props(['title' => 'Dashboard', 'subtitle' => null])
@php
    $user = auth()->user();
    $can = fn (string $permission) => $user !== null && ($user->isOwner() || $user->hasPermission($permission));
    $adminNav = array_values(array_filter([
        $can('dashboard.view') ? ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'] : null,
        $can('categories.view') ? ['label' => 'Kategori', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*'] : null,
        $can('products.view') ? ['label' => 'Produk & varian', 'route' => 'admin.products.index', 'match' => 'admin.products.*'] : null,
        $can('inventory.view') ? ['label' => 'Inventaris', 'route' => 'admin.inventory.index', 'match' => 'admin.inventory.*'] : null,
        $can('rentals.view') ? ['label' => 'Pesanan rental', 'route' => 'admin.rentals.index', 'match' => 'admin.rentals.*'] : null,
        $can('payments.view') ? ['label' => 'Pembayaran', 'route' => 'admin.payments.index', 'match' => 'admin.payments.*'] : null,
        $can('customers.view') ? ['label' => 'Pelanggan', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*'] : null,
        $can('dashboard.view') ? ['label' => 'Log aktivitas', 'route' => 'admin.activity.index', 'match' => 'admin.activity.*'] : null,
    ]));
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <title>{{ $title }} — Admin GEARORA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body" x-data="{ sidebarOpen: false }">
    <a href="#admin-content" class="skip-link">Lewati ke konten utama</a>
    <div class="admin-shell">
        <div class="admin-overlay" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" aria-hidden="true"></div>
        <aside class="admin-sidebar" :class="sidebarOpen ? 'open' : ''" aria-label="Navigasi admin">
            <div class="admin-brand">
                <a href="{{ route('admin.dashboard') }}" class="brand brand-light"><img src="{{ asset('images/gearora-logo.png') }}" width="34" height="34" alt=""><span class="brand-copy"><strong>GEARORA</strong><small>ADMIN CONSOLE</small></span></a>
                <button type="button" class="admin-close" @click="sidebarOpen = false" aria-label="Tutup menu admin">×</button>
            </div>
            <nav class="admin-nav">
                @foreach($adminNav as $item)
                    <a href="{{ route($item['route']) }}" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <div class="admin-foot">
                <p>{{ $user?->name }} · {{ $user?->primaryStaffRoleLabel() ?? 'Staf' }}</p>
                <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="text-link" type="submit">Keluar</button></form>
            </div>
        </aside>
        <div class="admin-main">
            <header class="admin-header">
                <button type="button" class="admin-toggle" @click="sidebarOpen = true" aria-label="Buka menu admin" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()">☰</button>
                <div><p class="admin-kicker">Admin Console</p><h1>{{ $title }}</h1></div>
                <a class="text-link" href="{{ route('home') }}">Lihat situs publik</a>
            </header>
            <main id="admin-content" class="admin-content">
                @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
                @if(session('error'))<div class="notice notice-warning" role="alert">{{ session('error') }}</div>@endif
                @if($subtitle)<p class="page-lead">{{ $subtitle }}</p>@endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
