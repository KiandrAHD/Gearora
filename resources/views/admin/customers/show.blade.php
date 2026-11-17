<x-layouts.admin :title="'Pelanggan '.$customer->name">
    <div class="admin-grid-2">
        <section class="panel">
            <h2>Profil</h2>
            <dl class="summary-list">
                <div><dt>Nama</dt><dd>{{ $customer->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $customer->email }}</dd></div>
                <div><dt>Telepon</dt><dd>{{ $customer->phone ?? '—' }}</dd></div>
                <div><dt>Alamat</dt><dd>{{ $customer->address ?? '—' }}</dd></div>
                <div><dt>Status</dt><dd>{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                <div><dt>Login terakhir</dt><dd>{{ $customer->last_login_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd></div>
            </dl>
        </section>
        <section class="panel">
            <h2>Aktivitas</h2>
            <ul class="plain-list">
                @forelse($activities as $activity)
                    <li><span>{{ $activity->description }}</span><span>{{ $activity->created_at?->translatedFormat('d M H:i') }}</span></li>
                @empty
                    <li class="empty-cell">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="panel">
        <h2>Pesanan pelanggan</h2>
        <table class="data-table"><thead><tr><th>Kode</th><th>Periode</th><th>Status</th><th class="text-right">Total</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr><td>{{ $order->order_code }}</td><td>{{ $order->start_date->translatedFormat('d M') }} → {{ $order->end_date->translatedFormat('d M Y') }}</td><td><x-partials.status-badge :status="$order->order_status" /></td><td class="text-right">{{ rupiah($order->total) }}</td><td class="text-right"><a class="text-link" href="{{ route('admin.rentals.show', $order) }}">Detail</a></td></tr>
            @empty
                <tr><td colspan="5" class="empty-cell">Belum ada pesanan.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    </section>
</x-layouts.admin>
