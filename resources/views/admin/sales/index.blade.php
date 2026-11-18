<x-layouts.admin title="Pesanan penjualan retail">
    <div class="panel">
        <div class="panel-head"><h2>Daftar sales order</h2><form class="inline-filter"><input name="q" value="{{ request('q') }}" placeholder="Kode order"><select name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select><button class="text-link">Filter</button></form></div>
        <table class="data-table"><thead><tr><th>Kode</th><th>Pelanggan</th><th>Item</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse($orders as $order)<tr><td>{{ $order->order_code }}</td><td>{{ $order->customer?->name }}</td><td>{{ $order->items->sum('quantity') }}</td><td>Rp {{ number_format($order->total, 0, ',', '.') }}</td><td>{{ $order->payment_status->label() }}</td><td>{{ $order->order_status->label() }}</td><td><a class="text-link" href="{{ route('admin.sales.show', $order) }}">Buka</a></td></tr>@empty<tr><td colspan="7" class="empty-cell">Belum ada order.</td></tr>@endforelse
        </tbody></table><div class="pagination-wrap">{{ $orders->links() }}</div>
    </div>
</x-layouts.admin>
