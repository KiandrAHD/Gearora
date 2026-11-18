<x-layouts.admin title="Siap Pickup" subtitle="Order siap diserahkan setelah pembayaran dan deposit diverifikasi.">
    <section class="panel">
        <h2>Daftar pickup</h2>
        <div class="table-responsive"><table class="data-table"><thead><tr><th>Order</th><th>Pelanggan</th><th>Periode</th><th>Pembayaran</th><th>Deposit</th><th>Unit</th><th>Aksi</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr>
                    <td><a class="text-link" href="{{ route('admin.rentals.show', $order) }}">{{ $order->order_code }}</a></td>
                    <td>{{ $order->customer?->name }}</td>
                    <td>{{ $order->start_date->translatedFormat('d M Y') }} — {{ $order->end_date->translatedFormat('d M Y') }}</td>
                    <td>{{ $order->payments->contains(fn ($p) => $p->isVerified()) ? 'Terverifikasi' : 'Belum' }}</td>
                    <td>{{ rupiah($order->deposit?->required_amount ?? 0) }} (terkumpul {{ rupiah($order->deposit?->collected_amount ?? 0) }})</td>
                    <td>{{ $order->assignments->map(fn($a) => $a->asset?->asset_code)->filter()->join(', ') }}</td>
                    <td><a class="btn-primary" href="{{ route('admin.operations.pickups.show', $order) }}">Proses handover</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-cell">Tidak ada order siap pickup.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $orders->links() }}
    </section>
</x-layouts.admin>
