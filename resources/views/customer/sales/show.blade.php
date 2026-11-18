<x-layout title="Pembelian {{ $order->order_code }}">
    <section class="page-block"><div class="page-container"><h1 class="page-title">{{ $order->order_code }}</h1><p class="page-lead">{{ $order->order_status->label() }} · {{ $order->payment_status->label() }}</p>
        <div class="panel">@foreach($order->items as $item)<p>{{ $item->product_name_snapshot }} — {{ $item->variant_name_snapshot }} × {{ $item->quantity }} <strong>Rp {{ number_format($item->line_total, 0, ',', '.') }}</strong></p>@endforeach<h2>Total Rp {{ number_format($order->total, 0, ',', '.') }}</h2></div>
        @foreach($order->payments as $payment)<div class="panel"><h2>{{ $payment->method->label() }}</h2><p>Status: {{ $payment->status->label() }}</p>
            @if($payment->status->value !== 'verified' && $payment->method->value === 'transfer')<form method="post" enctype="multipart/form-data" action="{{ route('customer.sales.proof.store', [$order, $payment]) }}">@csrf<input type="file" name="proof" required accept="image/jpeg,image/png,application/pdf"><input name="reference" placeholder="Referensi transfer"><textarea name="note" placeholder="Catatan"></textarea><button class="btn-primary">Kirim bukti</button></form>@endif
            @if($payment->status->value !== 'verified' && $payment->method->value === 'midtrans' && $gatewayAvailable)<form method="post" action="{{ route('customer.sales.midtrans.start', $order) }}">@csrf<button class="btn-primary">Bayar dengan Midtrans</button></form>@endif
        </div>@endforeach
        @if(!in_array($order->order_status->value, ['completed', 'cancelled', 'expired'], true))<form method="post" action="{{ route('customer.sales.cancel', $order) }}">@csrf<button class="btn-danger">Batalkan pesanan</button></form>@endif
        <p class="field-hint">Pengambilan dilakukan di lokasi GEARORA. Bawa identitas dan bukti pesanan.</p>
    </div></section>
</x-layout>
