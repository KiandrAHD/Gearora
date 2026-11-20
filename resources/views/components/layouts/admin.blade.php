@props(['title' => 'Dashboard', 'subtitle' => null])
@php
    use App\Support\Navigation\AdminNavigation;
    use App\Support\Navigation\PendingIndicators;

    $user = auth()->user();
    $navGroups = AdminNavigation::forUser($user);
    $pendingBadges = PendingIndicators::forUser($user);
    $activeGroups = [];

    foreach ($navGroups as $group) {
        if (! empty($group['standalone'])) {
            continue;
        }

        $activeGroups[$group['id']] = AdminNavigation::isGroupActive($group);
    }
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <title>{{ $title }} — Admin GEARORA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body"
      x-data="{ sidebarOpen: false, openGroups: @js($activeGroups), openSidebar() { this.sidebarOpen = true; this.$nextTick(() => this.$refs.sidebarClose?.focus()); }, closeSidebar() { this.sidebarOpen = false; this.$nextTick(() => this.$refs.sidebarToggle?.focus()); } }"
      x-effect="document.body.classList.toggle('nav-open', sidebarOpen)"
      @keydown.escape.window="sidebarOpen = false">
    <a href="#admin-content" class="skip-link">Lewati ke konten utama</a>
    <div class="admin-shell">
        <div class="admin-overlay" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" aria-hidden="true"></div>
        <aside id="admin-sidebar" class="admin-sidebar" :class="sidebarOpen ? 'open' : ''" aria-label="Navigasi admin" x-ref="sidebar">
            <div class="admin-brand">
                <a href="{{ route('admin.dashboard') }}" class="brand brand-light"><img src="{{ asset('images/gearora-logo.png') }}" width="34" height="34" alt=""><span class="brand-copy"><strong>GEARORA</strong><small>ADMIN CONSOLE</small></span></a>
                <button type="button" class="admin-close" x-ref="sidebarClose" @click="closeSidebar()" aria-label="Tutup menu admin">×</button>
            </div>
            <nav class="admin-nav" aria-label="Menu utama">
                @foreach($navGroups as $group)
                    @if(! empty($group['standalone']))
                        <a class="admin-nav-standalone" href="{{ route($group['route']) }}" @if(AdminNavigation::isActive($group)) aria-current="page" @endif>
                            <x-admin.nav-icon :name="$group['icon']" />
                            <span>{{ $group['label'] }}</span>
                        </a>
                        @continue
                    @endif
                    @php
                        $groupId = $group['id'];
                        $panelId = 'admin-nav-'.$groupId;
                    @endphp
                    <div class="admin-nav-group" :class="openGroups['{{ $groupId }}'] ? 'is-open' : ''">
                        <button type="button"
                                class="admin-nav-group-toggle"
                                @click="openGroups['{{ $groupId }}'] = !openGroups['{{ $groupId }}']"
                                :aria-expanded="openGroups['{{ $groupId }}'].toString()"
                                aria-controls="{{ $panelId }}"
                                @if(AdminNavigation::isGroupActive($group)) data-group-active="true" @endif>
                            <x-admin.nav-icon :name="$group['icon']" />
                            <span class="admin-nav-label">{{ $group['label'] }}</span>
                            <svg class="admin-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div id="{{ $panelId }}" class="admin-nav-sub" x-show="openGroups['{{ $groupId }}']" x-cloak>
                            @foreach($group['children'] as $child)
                                <a href="{{ route($child['route'], array_filter(['report' => $child['report'] ?? null])) }}"
                                   @if(AdminNavigation::isActive($child)) aria-current="page" @endif>
                                    <span>{{ $child['label'] }}</span>
                                    @if(! empty($child['badge']) && ($pendingBadges[$child['badge']] ?? 0) > 0)
                                        <span class="admin-nav-badge" aria-label="{{ $pendingBadges[$child['badge']] }} item menunggu">{{ $pendingBadges[$child['badge']] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
            <div class="admin-foot">
                <p>{{ $user?->name }} · {{ $user?->primaryStaffRoleLabel() ?? 'Staf' }}</p>
                <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="text-link" type="submit">Keluar</button></form>
            </div>
        </aside>
        <div class="admin-main">
            <header class="admin-header">
                <button type="button" class="admin-toggle" x-ref="sidebarToggle" @click="openSidebar()" aria-label="Buka menu admin" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()">☰</button>
                <div><p class="admin-kicker">Admin Console</p><h1>{{ $title }}</h1></div>
                <a class="text-link" href="{{ route('home') }}">Lihat situs publik</a>
            </header>
            <main id="admin-content" class="admin-content" tabindex="-1">
                @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
                @if(session('error'))<div class="notice notice-warning" role="alert">{{ session('error') }}</div>@endif
                @if($subtitle)<p class="page-lead">{{ $subtitle }}</p>@endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
