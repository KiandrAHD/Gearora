<x-layouts.admin title="Sales order {{ $order->order_code }}">
    <div class="panel"><h2>{{ $order->order_code }}</h2><p>{{ $order->customer?->name }} · {{ $order->order_status->label() }} · {{ $order->payment_status->label() }}</p>
        <table class="data-table"><thead><tr><th>Produk</th><th>SKU</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->product_name_snapshot }} — {{ $item->variant_name_snapshot }}</td><td>{{ $item->sku_snapshot }}</td><td>{{ $item->quantity }}</td><td>Rp {{ number_format($item->unit_price_snapshot, 0, ',', '.') }}</td><td>Rp {{ number_format($item->line_total, 0, ',', '.') }}</td></tr>@endforeach</tbody></table>
        <h3>Total Rp {{ number_format($order->total, 0, ',', '.') }}</h3>
    </div>
    @foreach($order->payments as $payment)
        <div class="panel"><h2>Pembayaran {{ $payment->method->label() }}</h2><p>Rp {{ number_format($payment->amount, 0, ',', '.') }} · {{ $payment->status->label() }}</p>
            @if($payment->proof_path)<a class="text-link" href="{{ route('admin.sales.payments.proof', $payment) }}">Lihat bukti transfer</a>@endif
            @if($payment->status->value !== 'verified')
                @if($payment->method->value === 'cash')<form method="post" action="{{ route('admin.sales.payments.cash', $payment) }}">@csrf<button class="btn-primary">Catat pembayaran tunai</button></form>@elseif($payment->method->value === 'transfer' && $payment->proof_path)<form method="post" action="{{ route('admin.sales.payments.verify', $payment) }}">@csrf<button class="btn-primary">Verifikasi pembayaran</button></form><form method="post" action="{{ route('admin.sales.payments.reject', $payment) }}">@csrf<input name="note" required placeholder="Alasan penolakan"><button class="btn-danger">Tolak</button></form>@endif
            @endif
        </div>
    @endforeach
    <div class="panel"><h2>Operasional pickup</h2>
        @if(in_array($order->order_status->value, ['confirmed', 'ready_for_pickup'], true))
            @if($order->order_status->value === 'confirmed')<form method="post" action="{{ route('admin.sales.transition', $order) }}">@csrf<input type="hidden" name="status" value="ready_for_pickup"><button class="btn-primary">Tandai siap diambil</button></form>@else<form method="post" action="{{ route('admin.sales.transition', $order) }}">@csrf<input type="hidden" name="status" value="completed"><button class="btn-primary">Selesaikan pickup</button></form>@endif
        @endif
        @if(!in_array($order->order_status->value, ['completed', 'cancelled', 'expired'], true))<form method="post" action="{{ route('admin.sales.transition', $order) }}">@csrf<input type="hidden" name="status" value="cancelled"><input name="reason" required placeholder="Alasan pembatalan"><button class="btn-danger">Batalkan</button></form>@endif
    </div>
</x-layouts.admin>
