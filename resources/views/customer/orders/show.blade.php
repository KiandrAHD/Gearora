<x-layouts.customer :title="'Pesanan '.$order->order_code" active="orders">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="notice notice-warning" role="alert">{{ session('error') }}</div>@endif

        <section class="panel">
            <div class="panel-head"><h2>Status pesanan</h2><x-partials.status-badge :status="$order->order_status" /></div>
            <dl class="summary-list">
                <div><dt>Periode</dt><dd>{{ $order->start_date->translatedFormat('d M Y') }} → {{ $order->end_date->translatedFormat('d M Y') }} ({{ $order->duration_days }} hari)</dd></div>
                <div><dt>Subtotal</dt><dd>{{ rupiah($order->subtotal) }}</dd></div>
                <div><dt>Deposit</dt><dd>{{ rupiah($order->deposit_total) }}</dd></div>
                <div><dt>Total</dt><dd><strong>{{ rupiah($order->total) }}</strong></dd></div>
                @if($order->notes)<div><dt>Catatan</dt><dd>{{ $order->notes }}</dd></div>@endif
            </dl>
            <table class="data-table"><thead><tr><th>Produk</th><th>Varian</th><th>Harga/hari</th><th>Jumlah</th><th class="text-right">Total</th></tr></thead><tbody>
                @foreach($order->items as $item)
                    <tr><td>{{ $item->product_name_snapshot }}</td><td>{{ $item->variant_name_snapshot }}</td><td>{{ rupiah($item->rental_price_snapshot) }}</td><td>{{ $item->quantity }} × {{ $item->duration_days }} hari</td><td class="text-right">{{ rupiah($item->line_total) }}</td></tr>
                @endforeach
            </tbody></table>
        </section>

        <section class="panel">
            <h2>Pembayaran</h2>
            @forelse($order->payments as $payment)
                <div class="payment-row">
                    <div><p>{{ $payment->method->label() }} · {{ rupiah($payment->amount) }} <x-partials.status-badge :status="$payment->status" /></p>
                        @if($payment->verification_note)<p class="field-hint">Catatan admin: {{ $payment->verification_note }}</p>@endif
                    </div>
                    @if($payment->method->value === 'transfer' && ! in_array($payment->status->value, ['verified', 'refunded'], true))
                        <form class="stack-form" method="post" action="{{ route('customer.orders.payment-proof.store', $order) }}" enctype="multipart/form-data">
                            @csrf
                            <label><span>Bukti transfer (JPG/PNG/PDF)</span><input name="proof" type="file" accept="image/jpeg,image/png,application/pdf" required></label>
                            <div class="form-row"><input name="reference" type="text" placeholder="Nomor referensi"><input name="note" type="text" placeholder="Catatan (opsional)"></div>
                            <button class="btn-primary" type="submit">Kirim bukti pembayaran</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="empty-cell">Belum ada data pembayaran.</p>
            @endforelse

            @if($gatewayAvailable && ! in_array($order->order_status->value, ['cancelled', 'completed'], true))
                <div class="payment-instructions">
                    <h3>Bayar otomatis</h3>
                    <p class="field-hint">Kartu kredit/debit, VA bank, e-wallet, dan QRIS diproses melalui Midtrans. Status terverifikasi otomatis setelah pembayaran berhasil.</p>
                    <form method="post" action="{{ route('customer.orders.midtrans.start', $order) }}">
                        @csrf
                        <button class="btn-primary" type="submit">Bayar sekarang (Midtrans)</button>
                    </form>
                </div>
            @endif

            @if($hasTransferInstruction)
                <div class="payment-instructions">
                    <h3>Instruksi transfer</h3>
                    <p>{{ $instructions['bank_name'] }}</p>
                    <p>No. rekening: <strong>{{ $instructions['bank_account_number'] }}</strong></p>
                    <p>Atas nama: {{ $instructions['bank_account_name'] }}</p>
                </div>
            @endif
            @if(filled($instructions['cash_location']))
                <p class="field-hint">Pembayaran tunai di: {{ $instructions['cash_location'] }}</p>
            @endif
        </section>

        @if(in_array($order->order_status->value, ['pending_payment', 'payment_review'], true))
            <form method="post" action="{{ route('customer.orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini?')">
                @csrf
                <button class="btn-danger" type="submit">Batalkan pesanan</button>
            </form>
        @endif
    </div>
</x-layouts.customer>
