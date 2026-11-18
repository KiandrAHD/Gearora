<x-layouts.admin title="Pembayaran">
    <div class="panel">
        <div class="panel-head"><h2>Daftar pembayaran</h2></div>
        <form method="get" action="{{ route('admin.payments.index') }}" class="inline-filter inline-filter-wide">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Kode order atau pelanggan">
            <select name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            <button class="text-link" type="submit">Terapkan</button>
        </form>
        <table class="data-table"><thead><tr><th>Order</th><th>Pelanggan</th><th>Metode</th><th>Nominal</th><th>Status</th><th>Bukti</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($payments as $payment)
                <tr>
                    <td><a class="text-link" href="{{ route('admin.rentals.show', $payment->order) }}">{{ $payment->order?->order_code }}</a></td>
                    <td>{{ $payment->order?->customer?->name }}</td>
                    <td>{{ $payment->method->label() }}</td>
                    <td>{{ rupiah($payment->amount) }}</td>
                    <td><x-partials.status-badge :status="$payment->status" /></td>
                    <td>@if($payment->proof_path)<a class="text-link" href="{{ route('admin.payments.proof', $payment) }}">Unduh</a>@else —@endif</td>
                    <td class="text-right">
                        @if($payment->method->isGateway())
                            <span class="field-hint">Diverifikasi otomatis via gateway</span>
                        @elseif(in_array($payment->status->value, ['waiting_verification', 'unpaid'], true))
                            <form class="inline-form" method="post" action="{{ route('admin.payments.decide', $payment) }}">
                                @csrf
                                <input name="note" type="text" placeholder="Catatan" aria-label="Catatan verifikasi">
                                <button class="text-link" name="decision" value="verified" type="submit">Verifikasi</button>
                                <button class="text-link text-danger" name="decision" value="rejected" type="submit">Tolak</button>
                            </form>
                            @if($payment->method->value === 'cash')
                                <form method="post" action="{{ route('admin.payments.cash', $payment) }}">@csrf<button class="text-link" type="submit">Catat tunai diterima</button></form>
                            @endif
                        @else
                            <span class="field-hint">{{ $payment->verifier?->name ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-cell">Belum ada pembayaran.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $payments->links() }}</div>
    </div>
</x-layouts.admin>
