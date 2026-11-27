<x-layouts.customer :title="'Pembelian '.$order->order_code" active="sales">
    <div class="stack-lg">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="notice notice-warning" role="alert">{{ session('error') }}</div>@endif

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>{{ $order->order_code }}</h2>
                    <p class="panel-sub">{{ $order->order_status->label() }} · {{ $order->payment_status->label() }}</p>
                </div>
                <a class="text-link" href="{{ route('customer.sales.index') }}">Semua pembelian</a>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Produk</th><th>Varian</th><th>Jumlah</th><th class="text-right">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name_snapshot }}</td>
                                <td>{{ $item->variant_name_snapshot }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="text-right">{{ rupiah($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <dl class="summary-list">
                <div><dt>Total</dt><dd><strong>{{ rupiah($order->total) }}</strong></dd></div>
            </dl>
        </section>

        <section class="panel">
            <h2>Pembayaran</h2>
            @php($methodIcons = ['midtrans' => 'QRIS', 'transfer' => 'BANK', 'cash' => 'TUNAI'])
            @forelse($order->payments as $payment)
                <div class="payment-row payment-row--{{ $payment->method->value }}">
                    <div class="payment-row-head">
                        <span class="payment-method-badge" aria-hidden="true">{{ $methodIcons[$payment->method->value] ?? 'BAYAR' }}</span>
                        <div>
                            <p><strong>{{ $payment->method->label() }}</strong> · {{ rupiah($payment->amount) }}</p>
                            <p class="field-hint">{{ $payment->method->description() }}</p>
                        </div>
                        <x-partials.status-badge :status="$payment->status" />
                    </div>

                    @if($payment->verification_note)<p class="field-hint">Catatan admin: {{ $payment->verification_note }}</p>@endif

                    @if($payment->status->value !== 'verified' && $payment->method->value === 'cash')
                        <div class="payment-cash-note">
                            <strong>Bayar tunai saat pengambilan</strong>
                            <p>Bayar langsung ke petugas saat gear diambil @if(filled($instructions['cash_location'])) di {{ $instructions['cash_location'] }}@endif. Bukan pembayaran COD pengiriman.</p>
                        </div>
                    @endif

                    @if($payment->status->value !== 'verified' && $payment->method->value === 'transfer')
                        <form class="stack-form" method="post" enctype="multipart/form-data" action="{{ route('customer.sales.proof.store', [$order, $payment]) }}">
                            @csrf
                            <label><span>Bukti transfer (JPG/PNG/PDF)</span><input type="file" name="proof" required accept="image/jpeg,image/png,application/pdf"></label>
                            <div class="form-row"><input name="reference" placeholder="Referensi transfer"><input name="note" placeholder="Catatan (opsional)"></div>
                            <button class="btn-primary" type="submit">Kirim bukti</button>
                        </form>
                    @endif

                    @if($payment->status->value !== 'verified' && $payment->method->value === 'midtrans' && $gatewayAvailable)
                        <p class="field-hint">Gunakan tombol "Bayar via QRIS / Midtrans" di bawah untuk melanjutkan pembayaran ini.</p>
                    @endif
                </div>
            @empty
                <p class="empty-cell">Belum ada data pembayaran.</p>
            @endforelse

            @if($gatewayAvailable && ! in_array($order->order_status->value, ['completed', 'cancelled', 'expired'], true))
                <div class="payment-instructions">
                    <h3>Bayar via QRIS / Midtrans</h3>
                    <p class="field-hint">QRIS, e-wallet, kartu, dan VA bank diproses melalui Midtrans. Status terverifikasi otomatis.</p>
                    <form method="post" action="{{ route('customer.sales.midtrans.start', $order) }}">
                        @csrf
                        <button class="btn-primary" type="submit">Bayar via QRIS / Midtrans</button>
                    </form>
                </div>
            @endif
        </section>

        @if(!in_array($order->order_status->value, ['completed', 'cancelled', 'expired'], true))
            <form method="post" action="{{ route('customer.sales.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini?')">
                @csrf
                <button class="btn-danger" type="submit">Batalkan pesanan</button>
            </form>
        @endif
        <p class="field-hint">Pengambilan dilakukan di lokasi GEARORA. Bawa identitas dan bukti pesanan.</p>
    </div>
</x-layouts.customer>