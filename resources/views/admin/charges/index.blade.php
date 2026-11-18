<x-layouts.admin title="Biaya Tambahan" subtitle="Biaya tambahan dipotong dari deposit lebih dulu; sisa menjadi tagihan pelanggan.">
    <div class="panel">
        <div class="panel-head"><h2>Daftar biaya tambahan</h2></div>
        <form method="get" action="{{ route('admin.charges.index') }}" class="inline-filter inline-filter-wide">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Kode biaya / order">
            <select name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            <button class="text-link" type="submit">Terapkan</button>
        </form>
        <table class="data-table"><thead><tr><th>Kode</th><th>Order</th><th>Pelanggan</th><th>Tipe</th><th>Nominal</th><th>Potong deposit</th><th>Sisa tagihan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($charges as $charge)
                <tr>
                    <td>{{ $charge->charge_code }}</td>
                    <td><a class="text-link" href="{{ route('admin.rentals.show', $charge->order) }}">{{ $charge->order?->order_code }}</a></td>
                    <td>{{ $charge->order?->customer?->name }}</td>
                    <td>{{ $charge->type->label() }}</td>
                    <td>{{ rupiah($charge->amount) }}</td>
                    <td>{{ rupiah($charge->deposit_offset_amount) }}</td>
                    <td><strong>{{ rupiah($charge->outstanding_amount) }}</strong></td>
                    <td><x-partials.status-badge :status="$charge->status" /></td>
                    <td class="text-right">
                        @if($charge->status->value === 'pending_approval')
                            <form class="inline-form" method="post" action="{{ route('admin.charges.approve', $charge) }}">@csrf<button class="text-link" type="submit">Setujui &amp; potong deposit</button></form>
                            <form class="inline-form" method="post" action="{{ route('admin.charges.reject', $charge) }}">@csrf<input name="note" type="text" placeholder="Alasan" aria-label="Alasan penolakan" required><button class="text-link text-danger" type="submit">Tolak</button></form>
                        @elseif(in_array($charge->status->value, ['approved', 'partially_settled'], true) && $charge->outstanding_amount > 0)
                            <form class="inline-form" method="post" action="{{ route('admin.charges.pay', $charge) }}">@csrf<input name="amount" type="number" min="1" max="{{ $charge->outstanding_amount }}" placeholder="Nominal" aria-label="Nominal pembayaran" required><button class="text-link" type="submit">Catat bayar</button></form>
                        @else
                            <span class="field-hint">{{ $charge->approved_at?->translatedFormat('d M Y') ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-cell">Belum ada biaya tambahan.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $charges->links() }}</div>
    </div>
</x-layouts.admin>
