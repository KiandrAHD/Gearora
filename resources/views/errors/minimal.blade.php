@php
    $code = $code ?? 500;
    $title = $title ?? 'Terjadi kesalahan';
    $message = $message ?? 'Permintaan tidak dapat diproses saat ini. Silakan coba beberapa saat lagi.';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} — GEARORA</title>
    <style>
        :root { --forest: #183D32; --navy: #08213F; --cloud: #F7F8F3; --line: #DBE0D7; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--cloud);
            color: var(--navy);
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            padding: 24px;
        }
        .card {
            max-width: 32rem;
            width: 100%;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
        }
        .code { font-size: 4rem; font-weight: 700; color: var(--forest); margin: 0; line-height: 1; }
        h1 { font-size: 1.4rem; margin: 20px 0 10px; }
        p { color: #526267; line-height: 1.7; margin: 0 0 28px; }
        a {
            display: inline-block;
            background: var(--forest);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            padding: 14px 26px;
            border-radius: 8px;
        }
        a:focus-visible { outline: 3px solid #2377C9; outline-offset: 3px; }
    </style>
</head>
<body>
    <main class="card">
        <p class="code">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">Kembali ke beranda</a>
    </main>
</body>
</html>
