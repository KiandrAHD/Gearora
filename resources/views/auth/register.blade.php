<x-layout title="Daftar">
    <section class="page-block auth-block">
        <div class="auth-card">
            <p class="eyebrow">DAFTAR</p>
            <h1 class="page-title">Buat akun GEARORA.</h1>
            <p class="page-lead">Akun pelanggan untuk menyewa gear dan melacak pesananmu.</p>

            @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif

            <form class="stack-form" method="post" action="{{ route('register.store') }}">
                @csrf
                <label><span>Nama lengkap</span><input name="name" type="text" value="{{ old('name') }}" required autocomplete="name"></label>
                <label><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
                <label><span>Nomor telepon</span><input name="phone" type="text" value="{{ old('phone') }}" autocomplete="tel"></label>
                <label><span>Alamat</span><textarea name="address" rows="2" autocomplete="street-address">{{ old('address') }}</textarea></label>
                <label><span>Password</span><input name="password" type="password" required autocomplete="new-password"></label>
                <label><span>Konfirmasi password</span><input name="password_confirmation" type="password" required autocomplete="new-password"></label>
                <button class="btn-primary" type="submit">Daftar</button>
            </form>
            <p class="field-hint">Sudah punya akun? <a class="text-link" href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>
</x-layout>
