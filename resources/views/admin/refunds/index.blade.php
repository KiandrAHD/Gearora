<x-layouts.admin title="Refund" subtitle="Refund diproses sebagai transaksi tersendiri dan tidak mengubah riwayat pembayaran.">
    <div class="panel">
        <div class="panel-head"><h2>Daftar refund</h2></div>
        <form method="get" action="{{ route('admin.refunds.index') }}" class="inline-filter inline-filter-wide">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Kode refund / order / pelanggan">
            <select name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            <button class="text-link" type="submit">Terapkan</button>
        </form>
        <table class="data-table"><thead><tr><th>Refund</th><th>Order</th><th>Pelanggan</th><th>Sewa</th><th>Deposit</th><th>Total</th><th>Metode</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($refunds as $refund)
                <tr>
                    <td>{{ $refund->refund_code }}</td>
                    <td><a class="text-link" href="{{ route('admin.rentals.show', $refund->order) }}">{{ $refund->order?->order_code }}</a></td>
                    <td>{{ $refund->order?->customer?->name }}</td>
                    <td>{{ rupiah($refund->rental_amount) }}</td>
                    <td>{{ rupiah($refund->deposit_amount) }}</td>
                    <td><strong>{{ rupiah($refund->refund_amount) }}</strong></td>
                    <td>{{ $refund->refund_method->label() }}</td>
                    <td><x-partials.status-badge :status="$refund->status" /></td>
                    <td class="text-right">
                        @if($refund->status->value === 'pending_approval')
                            <form class="inline-form" method="post" action="{{ route('admin.refunds.approve', $refund) }}">@csrf<button class="text-link" type="submit">Setujui</button></form>
                            <form class="inline-form" method="post" action="{{ route('admin.refunds.reject', $refund) }}">@csrf<input name="note" type="text" placeholder="Alasan" aria-label="Alasan penolakan" required><button class="text-link text-danger" type="submit">Tolak</button></form>
                        @elseif(in_array($refund->status->value, ['approved', 'manual_required', 'failed'], true))
                            <form class="inline-form" method="post" action="{{ route('admin.refunds.process', $refund) }}">@csrf<button class="text-link" type="submit">Proses</button></form>
                            <form class="inline-form" method="post" action="{{ route('admin.refunds.complete', $refund) }}" enctype="multipart/form-data">@csrf<input name="evidence" type="file" accept="image/*,application/pdf" aria-label="Bukti pengembalian dana" required><button class="text-link" type="submit">Selesai manual</button></form>
                        @else
                            <span class="field-hint">{{ $refund->processed_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-cell">Belum ada refund.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $refunds->links() }}</div>
    </div>
</x-layouts.admin>
