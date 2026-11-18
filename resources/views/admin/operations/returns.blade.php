<x-layouts.admin title="Pengembalian Rental" subtitle="Daftar order aktif yang menunggu unit kembali.">
    <section class="panel">
        <div class="table-responsive"><table class="data-table"><thead><tr><th>Order</th><th>Pelanggan</th><th>Batas kembali</th><th>Unit rental</th><th>Aksi</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr>
                    <td><a class="text-link" href="{{ route('admin.rentals.show', $order) }}">{{ $order->order_code }}</a></td>
                    <td>{{ $order->customer?->name }}</td>
                    <td>{{ $order->end_date->translatedFormat('d M Y') }} pukul 20.00 WIB</td>
                    <td>{{ $order->assignments->whereNotNull('handed_over_at')->whereNull('returned_at')->map(fn($a) => $a->asset?->asset_code)->filter()->join(', ') }}</td>
                    <td><a class="btn-primary" href="{{ route('admin.operations.returns.show', $order) }}">Terima unit</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">Tidak ada pengembalian tertunda.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $orders->links() }}
    </section>
</x-layouts.admin>
