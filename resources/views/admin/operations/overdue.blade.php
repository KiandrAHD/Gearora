<x-layouts.admin title="Overdue Rental" subtitle="Perhitungan denda memakai waktu Asia/Jakarta, grace period, dan snapshot harga order.">
    <section class="panel"><div class="table-responsive"><table class="data-table"><thead><tr><th>Order</th><th>Pelanggan</th><th>Jatuh tempo</th><th>Hari terlambat</th><th>Estimasi denda</th><th>Status</th></tr></thead><tbody>
        @forelse($orders as $order)
            @php($result = app(\App\Services\RentalOverdueService::class)->forOrder($order))
            <tr><td><a class="text-link" href="{{ route('admin.rentals.show', $order) }}">{{ $order->order_code }}</a></td><td>{{ $order->customer?->name }}</td><td>{{ $result->deadline?->translatedFormat('d M Y H:i') }} WIB</td><td>{{ $result->daysLate }}</td><td>{{ $result->isLost ? 'Proses barang hilang' : rupiah($result->lateFee) }}</td><td><x-partials.status-badge :status="$result->isLost ? 'lost' : 'overdue'" /></td></tr>
        @empty
            <tr><td colspan="6" class="empty-cell">Tidak ada rental overdue.</td></tr>
        @endforelse
    </tbody></table></div>{{ $orders->links() }}</section>
</x-layouts.admin>
