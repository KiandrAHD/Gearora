<x-layouts.admin :title="'Terima Return '.$order->order_code" subtitle="Pilih unit yang benar-benar kembali. Unit masuk inspeksi sebelum tersedia lagi.">
    <section class="panel">
        <form class="stack-form" method="post" action="{{ route('admin.operations.returns.receive', $order) }}">
            @csrf
            <fieldset><legend>Unit yang dikembalikan</legend>
                @forelse($assignments as $assignment)
                    <label><input type="checkbox" name="assignment_ids[]" value="{{ $assignment->id }}"> {{ $assignment->asset?->asset_code }} — {{ $assignment->asset?->variant?->name }}</label>
                @empty
                    <p class="field-hint">Semua unit sudah dicatat kembali.</p>
                @endforelse
            </fieldset>
            <label><input type="checkbox" name="customer_acknowledged" value="1"> Pelanggan mengakui unit telah diterima petugas</label>
            <label><span>Catatan umum</span><textarea name="notes" rows="4" maxlength="500"></textarea></label>
            <button class="btn-primary" type="submit">Catat pengembalian</button>
        </form>
    </section>
</x-layouts.admin>
