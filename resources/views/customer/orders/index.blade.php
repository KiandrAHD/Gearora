<x-layouts.customer title="Pesanan saya" active="orders">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        <section class="panel">
            <table class="data-table"><thead><tr><th>Kode</th><th>Item</th><th>Periode</th><th>Status</th><th>Pembayaran</th><th class="text-right">Total</th><th class="text-right">Aksi</th></tr></thead><tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_code }}</td>
                        <td>{{ $order->items->pluck('product_name_snapshot')->join(', ') }}</td>
                        <td>{{ $order->start_date->translatedFormat('d M') }} → {{ $order->end_date->translatedFormat('d M Y') }}</td>
                        <td><x-partials.status-badge :status="$order->order_status" /></td>
                        <td>@if($order->latestPayment)<x-partials.status-badge :status="$order->latestPayment->status" />@else —@endif</td>
                        <td class="text-right">{{ rupiah($order->total) }}</td>
                        <td class="text-right"><a class="text-link" href="{{ route('customer.orders.show', $order) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody></table>
            <div class="pagination-wrap">{{ $orders->links() }}</div>
        </section>
    </div>
</x-layouts.customer>
