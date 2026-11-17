<x-layouts.admin title="Pesanan Rental">
    <div class="panel">
        <div class="panel-head"><h2>Daftar pesanan</h2></div>
        <form method="get" action="{{ route('admin.rentals.index') }}" class="inline-filter inline-filter-wide">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Kode atau pelanggan">
            <select name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            <input name="from" type="date" value="{{ request('from') }}" aria-label="Dari tanggal">
            <input name="to" type="date" value="{{ request('to') }}" aria-label="Sampai tanggal">
            <button class="text-link" type="submit">Terapkan</button>
        </form>
        <table class="data-table"><thead><tr><th>Kode</th><th>Pelanggan</th><th>Periode</th><th>Status</th><th>Pembayaran</th><th class="text-right">Total</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->order_code }}</td>
                    <td>{{ $order->customer?->name }}<br><small>{{ $order->customer?->email }}</small></td>
                    <td>{{ $order->start_date->translatedFormat('d M') }} → {{ $order->end_date->translatedFormat('d M Y') }}</td>
                    <td><x-partials.status-badge :status="$order->order_status" /></td>
                    <td>@if($order->latestPayment)<x-partials.status-badge :status="$order->latestPayment->status" />@else —@endif</td>
                    <td class="text-right">{{ rupiah($order->total) }}</td>
                    <td class="text-right"><a class="text-link" href="{{ route('admin.rentals.show', $order) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-cell">Belum ada pesanan.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    </div>
</x-layouts.admin>
