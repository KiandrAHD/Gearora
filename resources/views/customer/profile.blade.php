<x-layouts.customer title="Profil" active="profile">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
        <section class="panel">
            <h2>Data akun</h2>
            <form class="stack-form" method="post" action="{{ route('customer.profile.update') }}">
                @csrf @method('PUT')
                <label><span>Nama lengkap</span><input name="name" type="text" value="{{ old('name', $user->name) }}" required></label>
                <label><span>Email</span><input type="email" value="{{ $user->email }}" disabled></label>
                <label><span>Telepon</span><input name="phone" type="text" value="{{ old('phone', $user->phone) }}"></label>
                <label><span>Alamat</span><textarea name="address" rows="3">{{ old('address', $user->address) }}</textarea></label>
                <fieldset class="form-fieldset"><legend>Ubah password (opsional)</legend>
                    <label><span>Password saat ini</span><input name="current_password" type="password" autocomplete="current-password"></label>
                    <div class="form-row"><label><span>Password baru</span><input name="password" type="password" autocomplete="new-password"></label><label><span>Konfirmasi</span><input name="password_confirmation" type="password" autocomplete="new-password"></label></div>
                </fieldset>
                <button class="btn-primary" type="submit">Simpan profil</button>
            </form>
        </section>
    </div>
</x-layouts.customer>
