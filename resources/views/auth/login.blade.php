<x-layout title="Masuk">
    <section class="page-block auth-block">
        <div class="auth-card">
            <p class="eyebrow">MASUK</p>
            <h1 class="page-title">Selamat datang kembali.</h1>
            <p class="page-lead">Masuk untuk melanjutkan pesanan rental dan melihat riwayat perjalananmu.</p>

            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif

            <form class="stack-form" method="post" action="{{ route('login.store') }}">
                @csrf
                <label><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
                <label><span>Password</span><input name="password" type="password" required autocomplete="current-password"></label>
                <label class="check-row"><input name="remember" type="checkbox" value="1"> <span>Ingat saya</span></label>
                <button class="btn-primary" type="submit">Masuk</button>
            </form>
            <p class="field-hint">Belum punya akun? <a class="text-link" href="{{ route('register') }}">Daftar sekarang</a></p>
            <p class="field-hint">Staf GEARORA masuk melalui <a class="text-link" href="{{ route('admin.login') }}">Admin Console</a>.</p>
        </div>
    </section>
</x-layout>
