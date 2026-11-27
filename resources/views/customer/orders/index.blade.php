<x-layouts.customer title="Sewa saya" active="orders">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Riwayat pesanan sewa</h2>
                    <p class="panel-sub">Semua pesanan gear sewa beserta status pembayarannya.</p>
                </div>
                <a class="btn-secondary" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Sewa lagi</a>
            </div>

            @if($orders->isEmpty())
                <div class="account-empty" role="status">
                    <h3>Belum ada pesanan sewa</h3>
                    <p>Pilih gear, tentukan tanggal sewa, lalu selesaikan pembayaran dari halaman pesanan.</p>
                    <a class="btn-primary" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Jelajahi katalog</a>
                </div>
            @else
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Kode</th><th>Item</th><th>Periode</th><th>Status</th><th>Pembayaran</th><th class="text-right">Total</th><th class="text-right">Aksi</th></tr></thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td><a class="text-link" href="{{ route('customer.orders.show', $order) }}">{{ $order->order_code }}</a></td>
                                    <td>{{ \Illuminate\Support\Str::limit($order->items->pluck('product_name_snapshot')->filter()->join(', '), 40) ?: '—' }}</td>
                                    <td>{{ $order->start_date->translatedFormat('d M') }} → {{ $order->end_date->translatedFormat('d M Y') }}</td>
                                    <td><x-partials.status-badge :status="$order->order_status" /></td>
                                    <td>@if($order->latestPayment)<x-partials.status-badge :status="$order->latestPayment->status" />@else — @endif</td>
                                    <td class="text-right">{{ rupiah($order->total) }}</td>
                                    <td class="text-right"><a class="text-link" href="{{ route('customer.orders.show', $order) }}">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.customer>