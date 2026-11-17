<x-layouts.admin :title="'Pesanan '.$order->order_code">
    <div class="admin-grid-2">
        <section class="panel">
            <div class="panel-head"><h2>Informasi pesanan</h2><x-partials.status-badge :status="$order->order_status" /></div>
            <dl class="summary-list">
                <div><dt>Pelanggan</dt><dd>{{ $order->customer?->name }} · {{ $order->customer?->email }}</dd></div>
                <div><dt>Telepon</dt><dd>{{ $order->customer?->phone ?? '—' }}</dd></div>
                <div><dt>Periode</dt><dd>{{ $order->start_date->translatedFormat('d M Y') }} → {{ $order->end_date->translatedFormat('d M Y') }} ({{ $order->duration_days }} hari)</dd></div>
                <div><dt>Subtotal</dt><dd>{{ rupiah($order->subtotal) }}</dd></div>
                <div><dt>Deposit</dt><dd>{{ rupiah($order->deposit_total) }}</dd></div>
                <div><dt>Total</dt><dd><strong>{{ rupiah($order->total) }}</strong></dd></div>
                @if($order->notes)<div><dt>Catatan pelanggan</dt><dd>{{ $order->notes }}</dd></div>@endif
                @if($order->cancellation_reason)<div><dt>Alasan batal</dt><dd>{{ $order->cancellation_reason }}</dd></div>@endif
            </dl>
        </section>

        <section class="panel">
            <h2>Ubah status</h2>
            @if(count($statuses))
                <form class="stack-form" method="post" action="{{ route('admin.rentals.transition', $order) }}">
                    @csrf
                    <label><span>Status baru</span><select name="status" required>@foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
                    <label><span>Catatan (opsional)</span><input name="reason" type="text"></label>
                    <button class="btn-primary" type="submit">Simpan status</button>
                </form>
            @else
                <p class="field-hint">Status ini bersifat final. Tidak ada transisi lanjutan.</p>
            @endif
        </section>
    </div>

    <section class="panel">
        <h2>Item rental & unit dialokasikan</h2>
        <table class="data-table"><thead><tr><th>Produk / Varian</th><th>SKU</th><th>Harga/hari</th><th>Jumlah</th><th>Total</th><th>Unit</th></tr></thead><tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name_snapshot }} · {{ $item->variant_name_snapshot }}</td>
                    <td>{{ $item->variant?->sku }}</td>
                    <td>{{ rupiah($item->rental_price_snapshot) }}</td>
                    <td>{{ $item->quantity }} × {{ $item->duration_days }} hari</td>
                    <td>{{ rupiah($item->line_total) }}</td>
                    <td>{{ $item->assignments->pluck('asset.asset_code')->filter()->join(', ') ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody></table>
    </section>

    <section class="panel">
        <h2>Pembayaran</h2>
        <table class="data-table"><thead><tr><th>Metode</th><th>Nominal</th><th>Status</th><th>Bukti</th><th>Verifikasi</th></tr></thead><tbody>
            @forelse($order->payments as $payment)
                <tr>
                    <td>{{ $payment->method->label() }}</td>
                    <td>{{ rupiah($payment->amount) }}</td>
                    <td><x-partials.status-badge :status="$payment->status" /></td>
                    <td>@if($payment->proof_path)<a class="text-link" href="{{ route('admin.payments.proof', $payment) }}">Unduh bukti</a>@else —@endif</td>
                    <td>{{ $payment->verifier?->name ?? '—' }} {{ $payment->verified_at?->translatedFormat('d M H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">Belum ada data pembayaran.</td></tr>
            @endforelse
        </tbody></table>
        <p class="field-hint">Verifikasi dan catat pembayaran melalui menu <a class="text-link" href="{{ route('admin.payments.index') }}">Pembayaran</a>.</p>
    </section>
</x-layouts.admin>
