@props(['title' => '', 'description' => null])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?: 'GEARORA — Gear Up. Explore More. Rental dan perlengkapan outdoor.' }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    @if($title)<meta property="og:title" content="{{ $title }}">@endif
    <title>{{ $title ? $title.' — ' : '' }}GEARORA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col {{ request()->routeIs('home') ? 'is-home' : 'has-floating-nav' }}">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-4 focus:py-3 focus:text-navy">
        Lewati ke konten utama
    </a>

    <x-partials.site-header />

    <main id="main-content" class="flex-1">{{ $slot }}</main>

    <x-partials.site-footer />
</body>
</html>