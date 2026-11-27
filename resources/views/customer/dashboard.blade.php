<x-layouts.customer title="Ringkasan akun" active="dashboard">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif

        @php
            $tiles = [
                ['label' => 'Menunggu pembayaran', 'value' => $stats['waiting_payment'], 'hint' => 'Selesaikan agar pesanan diproses', 'tone' => 'warn'],
                ['label' => 'Sedang berjalan', 'value' => $stats['active'], 'hint' => 'Dikonfirmasi hingga menunggu pengembalian', 'tone' => 'ok'],
                ['label' => 'Selesai', 'value' => $stats['completed'], 'hint' => 'Pesanan sewa yang sudah tuntas', 'tone' => 'neutral'],
                ['label' => 'Pembelian retail', 'value' => $stats['sales_total'], 'hint' => 'Total transaksi beli gear', 'tone' => 'neutral'],
            ];
        @endphp

        <div class="account-stat-grid">
            @foreach($tiles as $tile)
                <article class="account-stat account-stat--{{ $tile['tone'] }}">
                    <p>{{ $tile['label'] }}</p>
                    <strong>{{ number_format((int) $tile['value'], 0, ',', '.') }}</strong>
                    <small>{{ $tile['hint'] }}</small>
                </article>
            @endforeach
        </div>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Pesanan sewa terbaru</h2>
                    <p class="panel-sub">Diambil langsung dari riwayat pesanan akunmu.</p>
                </div>
                <a class="text-link" href="{{ route('customer.orders.index') }}">Semua pesanan</a>
            </div>

            @if($orders->isEmpty())
                <div class="account-empty" role="status">
                    <h3>Belum ada pesanan sewa</h3>
                    <p>Mulai pilih gear di katalog, tentukan tanggal, lalu selesaikan pembayaran.</p>
                    <a class="btn-primary" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Jelajahi katalog sewa</a>
                </div>
            @else
                <div class="account-order-list">
                    @foreach($orders as $order)
                        <article class="account-order">
                            <div class="account-order-main">
                                <p class="product-category">{{ $order->order_code }}</p>
                                <h3>{{ $order->items->pluck('product_name_snapshot')->filter()->take(2)->join(', ') ?: 'Pesanan sewa' }}</h3>
                                <p class="field-hint">{{ $order->start_date->translatedFormat('d M') }} → {{ $order->end_date->translatedFormat('d M Y') }}</p>
                            </div>
                            <div class="account-order-side">
                                <x-partials.status-badge :status="$order->order_status" />
                                @if($order->latestPayment)
                                    <x-partials.status-badge :status="$order->latestPayment->status" />
                                @endif
                                <strong>{{ rupiah($order->total) }}</strong>
                                <a class="text-link" href="{{ route('customer.orders.show', $order) }}">Detail</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="account-quick-actions">
            <a class="btn-primary" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Sewa gear baru</a>
            <a class="btn-secondary" href="{{ route('catalog.index', ['mode' => 'beli']) }}">Beli gear</a>
            <a class="btn-secondary" href="{{ route('trip-planner.index') }}">Susun rekomendasi trip</a>
        </div>
    </div>
</x-layouts.customer>