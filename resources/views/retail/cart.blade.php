<x-layout title="Keranjang Belanja">
    <section class="page-block"><div class="page-container"><h1 class="page-title">Keranjang belanja</h1>
        @if(session('error'))<div class="notice notice-warning">{{ session('error') }}</div>@endif
        @forelse($lines as $line)
            <article class="info-card"><h2>{{ $line['product']->name }} — {{ $line['variant']->name }}</h2><p>Rp {{ number_format($line['unit_price'], 0, ',', '.') }} × {{ $line['quantity'] }}</p><p>Stok tersedia: {{ $line['available'] }}</p>
                <form method="post" action="{{ route('retail.cart.update', $line['variant']) }}">@csrf @method('PUT')<input type="number" name="quantity" min="0" max="99" value="{{ $line['quantity'] }}"><button class="text-link" type="submit">Perbarui</button></form>
            </article>
        @empty <p>Keranjang kosong.</p> @endforelse
        @if($lines->isNotEmpty())
            <div class="panel"><h2>Total: Rp {{ number_format($subtotal, 0, ',', '.') }}</h2>
                @auth<form method="post" action="{{ route('customer.sales.checkout') }}">@csrf<select name="payment_method"><option value="transfer">Transfer bank</option><option value="cash">Tunai di lokasi</option><option value="midtrans">Midtrans</option></select><button class="btn-primary" type="submit">Buat pesanan</button></form>@else<a class="btn-primary" href="{{ route('login') }}">Masuk untuk checkout</a>@endauth
            </div>
        @endif
    </div></section>
</x-layout>
