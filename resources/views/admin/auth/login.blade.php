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
    <title>Masuk Admin — GEARORA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main id="main-content" class="auth-card auth-card-admin">
        <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/gearora-logo.png') }}" width="37" height="37" alt=""><span class="brand-copy"><strong>GEARORA</strong><small>OUTDOOR SUPPLY</small></span></a>
        <p class="eyebrow">ADMIN CONSOLE</p>
        <h1 class="page-title">Masuk staf.</h1>
        <p class="page-lead">Area terbatas untuk Owner, Admin, dan Petugas Gudang.</p>

        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif

        <form class="stack-form" method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <label><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
            <label><span>Password</span><input name="password" type="password" required autocomplete="current-password"></label>
            <button class="btn-primary" type="submit">Masuk Admin</button>
        </form>
        <p class="field-hint">Bukan staf? <a class="text-link" href="{{ route('login') }}">Masuk sebagai pelanggan</a></p>
    </main>
</body>
</html>
