<x-layouts.customer title="Pembelian saya" active="sales">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Riwayat pembelian gear</h2>
                    <p class="panel-sub">Transaksi beli gear beserta status pembayaran dan pengambilan.</p>
                </div>
                <a class="btn-secondary" href="{{ route('catalog.index', ['mode' => 'beli']) }}">Belanja lagi</a>
            </div>

            @if($orders->isEmpty())
                <div class="account-empty" role="status">
                    <h3>Belum ada pembelian</h3>
                    <p>Keranjang retail dan checkout akan muncul di sini setelah pesanan dibuat.</p>
                    <a class="btn-primary" href="{{ route('catalog.index', ['mode' => 'beli']) }}">Lihat katalog beli</a>
                </div>
            @else
                <div class="account-order-list">
                    @foreach($orders as $order)
                        <article class="account-order">
                            <div class="account-order-main">
                                <p class="product-category">{{ $order->order_code }}</p>
                                <h3>{{ $order->items->sum('quantity') }} item · {{ $order->items->pluck('product_name_snapshot')->filter()->take(2)->join(', ') ?: 'Pembelian gear' }}</h3>
                                <p class="field-hint">Dibuat {{ $order->created_at->translatedFormat('d M Y H:i') }}</p>
                            </div>
                            <div class="account-order-side">
                                <x-partials.status-badge :status="$order->order_status" />
                                <strong>{{ rupiah($order->total) }}</strong>
                                <a class="text-link" href="{{ route('customer.sales.show', $order) }}">Detail</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="pagination-wrap">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.customer>