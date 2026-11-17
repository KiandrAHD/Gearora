@php($editing = $asset->exists)
<x-layouts.admin :title="$editing ? 'Ubah unit rental' : 'Unit rental baru'">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
    <div class="panel">
        <form class="stack-form" method="post" action="{{ $editing ? route('admin.inventory.assets.update', $asset) : route('admin.inventory.assets.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            @unless($editing)
                <label><span>Varian rental</span><select name="product_variant_id" required><option value="">Pilih varian</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected((string) old('product_variant_id') === (string) $variant->id)>{{ $variant->product?->name }} · {{ $variant->name }} ({{ $variant->sku }})</option>@endforeach</select></label>
            @endunless
            <div class="form-row">
                <label><span>Kode unit</span><input name="asset_code" type="text" value="{{ old('asset_code', $asset->asset_code) }}" required></label>
                <label><span>Tanggal pembelian</span><input name="purchase_date" type="date" value="{{ old('purchase_date', $asset->purchase_date?->toDateString()) }}"></label>
            </div>
            <div class="form-row">
                <label><span>Status</span><select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $asset->status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
                <label><span>Kondisi</span><select name="condition">@foreach($conditions as $condition)<option value="{{ $condition->value }}" @selected(old('condition', $asset->condition?->value) === $condition->value)>{{ $condition->label() }}</option>@endforeach</select></label>
            </div>
            <label><span>Alasan perubahan status</span><input name="reason" type="text" value="{{ old('reason') }}" placeholder="Wajib bila status berubah"></label>
            <label><span>Catatan</span><textarea name="notes" rows="2">{{ old('notes', $asset->notes) }}</textarea></label>
            <div class="form-actions"><button class="btn-primary" type="submit">{{ $editing ? 'Simpan' : 'Tambah unit' }}</button><a class="text-link" href="{{ route('admin.inventory.index') }}">Batal</a></div>
        </form>
    </div>
</x-layouts.admin>
