<x-layouts.admin :title="'Handover '.$order->order_code" subtitle="Verifikasi identitas, kondisi unit, dan persetujuan pelanggan sebelum menyerahkan barang.">
    <section class="panel">
        <h2>{{ $order->customer?->name }} · {{ $order->customer?->email }}</h2>
        <p>Periode {{ $order->start_date->translatedFormat('d M Y') }} — {{ $order->end_date->translatedFormat('d M Y') }}. Total {{ rupiah($order->total) }}.</p>
        <h3>Unit dialokasikan</h3>
        <ul>@foreach($order->assignments as $assignment)<li>{{ $assignment->asset?->asset_code }} — {{ $assignment->asset?->variant?->name }} ({{ $assignment->asset?->status?->label() }})</li>@endforeach</ul>
        <form class="stack-form" method="post" action="{{ route('admin.operations.pickups.handover', $order) }}">
            @csrf
            <label><span>Jenis identitas (KTP/SIM)</span><select name="identity_type"><option value="ktp">KTP</option><option value="sim">SIM</option></select></label>
            <label><input type="checkbox" name="identity_checked" value="1" required> Identitas pelanggan sudah diperiksa</label>
            <label><input type="checkbox" name="customer_acknowledged" value="1" required> Pelanggan menyetujui kondisi awal dan kelengkapan unit</label>
            <label><span>Catatan kondisi / kelengkapan</span><textarea name="notes" rows="4" maxlength="500"></textarea></label>
            <button class="btn-primary" type="submit">Konfirmasi handover</button>
        </form>
    </section>
</x-layouts.admin>
