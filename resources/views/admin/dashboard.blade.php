<x-layouts.admin title="Dashboard" subtitle="Ringkasan operasional GEARORA. Semua angka diambil langsung dari database.">
    <div class="stat-grid">
        @foreach($stats as $stat)
            <div class="stat-card"><p>{{ $stat['label'] }}</p><strong>{{ number_format((int) $stat['value'], 0, ',', '.') }}</strong><small>{{ $stat['hint'] }}</small></div>
        @endforeach
    </div>

    <div class="admin-grid-2">
        <section class="panel">
            <div class="panel-head"><h2>Pesanan terbaru</h2><a class="text-link" href="{{ route('admin.rentals.index') }}">Semua pesanan</a></div>
            <table class="data-table"><thead><tr><th>Kode</th><th>Pelanggan</th><th>Status</th><th class="text-right">Total</th></tr></thead><tbody>
                @forelse($recentOrders as $order)
                    <tr><td><a class="text-link" href="{{ route('admin.rentals.show', $order) }}">{{ $order->order_code }}</a></td><td>{{ $order->customer?->name }}</td><td><x-partials.status-badge :status="$order->order_status" /></td><td class="text-right">{{ rupiah($order->total) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty-cell">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody></table>
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Pembayaran menunggu verifikasi</h2><a class="text-link" href="{{ route('admin.payments.index', ['status' => 'waiting_verification']) }}">Kelola</a></div>
            <ul class="plain-list">
                @forelse($pendingPayments as $payment)
                    <li><a class="text-link" href="{{ route('admin.rentals.show', $payment->order) }}">{{ $payment->order?->order_code }}</a><span>{{ rupiah($payment->amount) }} · {{ $payment->method->label() }}</span></li>
                @empty
                    <li class="empty-cell">Tidak ada pembayaran menunggu.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="admin-grid-2">
        <section class="panel">
            <div class="panel-head"><h2>Stok jual menipis</h2><a class="text-link" href="{{ route('admin.inventory.index', ['tab' => 'stock']) }}">Inventaris</a></div>
            <ul class="plain-list">
                @forelse($lowStock as $product)
                    <li><span>{{ $product->name }}</span><span>{{ $product->variants->sum('stock_quantity') }} unit</span></li>
                @empty
                    <li class="empty-cell">Tidak ada varian jual dengan stok menipis.</li>
                @endforelse
            </ul>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Ringkasan data</h2></div>
            <dl class="summary-list">
                <div><dt>Kategori</dt><dd>{{ $totals['categories'] }}</dd></div>
                <div><dt>Produk</dt><dd>{{ $totals['products'] }}</dd></div>
                <div><dt>Unit rental</dt><dd>{{ $totals['assets'] }}</dd></div>
                <div><dt>Pelanggan</dt><dd>{{ $totals['customers'] }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="panel">
        <div class="panel-head"><h2>Aktivitas terbaru</h2><a class="text-link" href="{{ route('admin.activity.index') }}">Log lengkap</a></div>
        <ul class="plain-list">
            @forelse($activities as $activity)
                <li><span>{{ $activity->description }}</span><span>{{ $activity->created_at?->translatedFormat('d M Y H:i') }}</span></li>
            @empty
                <li class="empty-cell">Belum ada aktivitas.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.admin>
