@props(['title' => ''])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GEARORA — Gear Up. Explore More. Rental dan perlengkapan outdoor.">
    <title>{{ $title ? $title.' — ' : '' }}GEARORA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-4 focus:py-3 focus:text-navy">
        Lewati ke konten utama
    </a>

    <x-partials.site-header />

    <main id="main-content" class="flex-1">{{ $slot }}</main>

    <x-partials.site-footer />
</body>
</html>