<x-layouts.admin title="Deposit" subtitle="Deposit adalah liabilitas terpisah dari pendapatan sewa dan wajib dikembalikan atau dipotong sesuai inspeksi.">
    <div class="panel">
        <div class="panel-head"><h2>Daftar deposit</h2></div>
        <form method="get" action="{{ route('admin.deposits.index') }}" class="inline-filter inline-filter-wide">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Kode order / pelanggan">
            <select name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            <button class="text-link" type="submit">Terapkan</button>
        </form>
        <table class="data-table"><thead><tr><th>Order</th><th>Pelanggan</th><th>Wajib</th><th>Terkumpul</th><th>Terpakai</th><th>Dikembalikan</th><th>Saldo</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($deposits as $deposit)
                <tr>
                    <td><a class="text-link" href="{{ route('admin.rentals.show', $deposit->order) }}">{{ $deposit->order?->order_code }}</a></td>
                    <td>{{ $deposit->order?->customer?->name }}</td>
                    <td>{{ rupiah($deposit->required_amount) }}</td>
                    <td>{{ rupiah($deposit->collected_amount) }}</td>
                    <td>{{ rupiah($deposit->deducted_amount) }}</td>
                    <td>{{ rupiah($deposit->refunded_amount) }}</td>
                    <td><strong>{{ rupiah($deposit->balance()) }}</strong></td>
                    <td><x-partials.status-badge :status="$deposit->status" /></td>
                    <td class="text-right"><a class="text-link" href="{{ route('admin.deposits.show', $deposit) }}">Kelola</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-cell">Belum ada deposit.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $deposits->links() }}</div>
    </div>
</x-layouts.admin>
