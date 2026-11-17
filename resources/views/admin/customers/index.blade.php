<x-layouts.admin title="Pelanggan">
    <div class="panel">
        <div class="panel-head"><h2>Daftar pelanggan</h2></div>
        <form method="get" action="{{ route('admin.customers.index') }}" class="inline-filter"><input name="q" type="search" value="{{ request('q') }}" placeholder="Nama, email, atau telepon"><button class="text-link" type="submit">Cari</button></form>
        <table class="data-table"><thead><tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Pesanan</th><th>Terdaftar</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>{{ $customer->rental_orders_count }}</td>
                    <td>{{ $customer->created_at?->translatedFormat('d M Y') }}</td>
                    <td class="text-right"><a class="text-link" href="{{ route('admin.customers.show', $customer) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">Belum ada pelanggan.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $customers->links() }}</div>
    </div>
</x-layouts.admin>
