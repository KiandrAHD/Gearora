<x-layout title="Pembelian saya">
    <section class="page-block"><div class="page-container"><h1 class="page-title">Pembelian saya</h1><div class="card-grid">
        @forelse($orders as $order)<article class="info-card"><p class="product-category">{{ $order->order_code }}</p><h2>{{ $order->order_status->label() }}</h2><p>{{ $order->items->sum('quantity') }} item · Rp {{ number_format($order->total, 0, ',', '.') }}</p><a class="text-link" href="{{ route('customer.sales.show', $order) }}">Detail pesanan</a></article>@empty<p>Belum ada pembelian.</p>@endforelse
    </div>{{ $orders->links() }}</div></section>
</x-layout>
