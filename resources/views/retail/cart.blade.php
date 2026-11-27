<x-layout title="Keranjang Belanja">
    <section class="page-block"><div class="page-container">
        <p class="eyebrow">KERANJANG</p>
        <h1 class="page-title">Keranjang belanja</h1>
        @if(session('error'))<div class="notice notice-warning">{{ session('error') }}</div>@endif
        @forelse($lines as $line)
            <article class="info-card">
                <h2>{{ $line['product']->name }} — {{ $line['variant']->name }}</h2>
                <p>{{ rupiah($line['unit_price']) }} × {{ $line['quantity'] }}</p>
                <p class="field-hint">Stok tersedia: {{ $line['available'] }}</p>
                <form method="post" action="{{ route('retail.cart.update', $line['variant']) }}">@csrf @method('PUT')
                    <input type="number" name="quantity" min="0" max="99" value="{{ $line['quantity'] }}" aria-label="Jumlah {{ $line['variant']->name }}">
                    <button class="text-link" type="submit">Perbarui</button>
                </form>
            </article>
        @empty
            <div class="account-empty" role="status">
                <h3>Keranjang kosong</h3>
                <p>Pilih gear di katalog beli untuk mulai berbelanja.</p>
                <a class="btn-primary" href="{{ route('catalog.index', ['mode' => 'beli']) }}">Lihat katalog beli</a>
            </div>
        @endforelse
        @if($lines->isNotEmpty())
            <div class="panel">
                <h2>Total: {{ rupiah($subtotal) }}</h2>
                <p class="field-hint">Stok &amp; harga dihitung ulang server saat pesanan dibuat.</p>
                @auth
                    <form class="stack-form" method="post" action="{{ route('customer.sales.checkout') }}">
                        @csrf
                        <x-partials.payment-method-picker name="payment_method" :selected="\App\Enums\PaymentMethod::Transfer->value" />
                        <button class="btn-primary" type="submit">Buat pesanan</button>
                    </form>
                @else
                    <a class="btn-primary" href="{{ route('login') }}">Masuk untuk checkout</a>
                @endauth
            </div>
        @endif
    </div></section>
</x-layout>