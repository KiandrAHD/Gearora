<x-layouts.customer title="Ringkasan akun" active="dashboard">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        <div class="stat-grid">
            <div class="stat-card"><p>Pesanan aktif</p><strong>{{ $stats['active'] }}</strong></div>
            <div class="stat-card"><p>Menunggu pembayaran</p><strong>{{ $stats['waiting_payment'] }}</strong></div>
            <div class="stat-card"><p>Selesai</p><strong>{{ $stats['completed'] }}</strong></div>
        </div>

        <section class="panel">
            <div class="panel-head"><h2>Pesanan terbaru</h2><a class="text-link" href="{{ route('customer.orders.index') }}">Semua pesanan</a></div>
            <table class="data-table"><thead><tr><th>Kode</th><th>Periode</th><th>Status</th><th class="text-right">Total</th></tr></thead><tbody>
                @forelse($orders as $order)
                    <tr><td><a class="text-link" href="{{ route('customer.orders.show', $order) }}">{{ $order->order_code }}</a></td><td>{{ $order->start_date->translatedFormat('d M') }} → {{ $order->end_date->translatedFormat('d M Y') }}</td><td><x-partials.status-badge :status="$order->order_status" /></td><td class="text-right">{{ rupiah($order->total) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty-cell">Belum ada pesanan. Mulai dari <a class="text-link" href="{{ route('catalog.index') }}">katalog</a>.</td></tr>
                @endforelse
            </tbody></table>
        </section>

        <a class="btn-primary" href="{{ route('catalog.index') }}">Sewa gear baru</a>
    </div>
</x-layouts.customer>
