@php($editing = $rule->exists)
<x-layouts.admin :title="$editing ? 'Ubah aturan trip' : 'Aturan trip baru'">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
    <div class="panel">
        <form class="stack-form" method="post" action="{{ $editing ? route('admin.trip-recommendations.update', $rule) : route('admin.trip-recommendations.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <label><span>Nama aturan</span><input name="name" value="{{ old('name', $rule->name) }}" required maxlength="160"></label>
            <label><span>Deskripsi internal</span><textarea name="description" rows="2">{{ old('description', $rule->description) }}</textarea></label>
            <div class="form-row">
                <label><span>Aktivitas</span><select name="activity" required>@foreach($activities as $activity)<option value="{{ $activity }}" @selected(old('activity', $rule->activity) === $activity)>{{ $activity }}</option>@endforeach</select></label>
                <label><span>Mode</span><select name="service_mode" required>@foreach($modes as $mode)<option value="{{ $mode }}" @selected(old('service_mode', $rule->service_mode?->value) === $mode)>{{ $mode }}</option>@endforeach</select></label>
            </div>
            <div class="form-row">
                <label><span>Durasi min (hari)</span><input type="number" name="min_duration_days" min="1" value="{{ old('min_duration_days', $rule->min_duration_days ?? 1) }}" required></label>
                <label><span>Durasi maks</span><input type="number" name="max_duration_days" min="1" value="{{ old('max_duration_days', $rule->max_duration_days) }}"></label>
                <label><span>Peserta min</span><input type="number" name="min_participants" min="1" value="{{ old('min_participants', $rule->min_participants ?? 1) }}" required></label>
                <label><span>Peserta maks</span><input type="number" name="max_participants" min="1" value="{{ old('max_participants', $rule->max_participants) }}"></label>
            </div>
            <fieldset>
                <legend>Target gear (isi minimal satu)</legend>
                <label><span>Kategori</span><select name="product_category_id"><option value="">—</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('product_category_id', $rule->product_category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label><span>Produk</span><select name="product_id"><option value="">—</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((int) old('product_id', $rule->product_id) === $product->id)>{{ $product->name }}</option>@endforeach</select></label>
                <p class="field-hint">Varian spesifik dapat diisi lewat API/seed bila perlu; aturan kategori/produk akan memakai varian aktif.</p>
            </fieldset>
            <div class="form-row">
                <label><span>Jumlah disarankan</span><input type="number" name="recommended_quantity" min="1" max="99" value="{{ old('recommended_quantity', $rule->recommended_quantity ?? 1) }}" required></label>
                <label class="check-row"><input name="quantity_per_participant" type="checkbox" value="1" @checked(old('quantity_per_participant', $rule->quantity_per_participant))> <span>Jumlah dikali peserta</span></label>
                <label><span>Prioritas (besar dahulu)</span><input type="number" name="priority" min="0" value="{{ old('priority', $rule->priority ?? 100) }}" required></label>
            </div>
            <label><span>Template alasan</span><textarea name="reason_template" rows="2" maxlength="500">{{ old('reason_template', $rule->reason_template) }}</textarea><span class="field-hint">Placeholder: :activity :participants :duration :destination</span></label>
            <label class="check-row"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $rule->is_active ?? true))> <span>Aktif</span></label>
            <div class="form-actions"><button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Buat aturan' }}</button><a class="text-link" href="{{ route('admin.trip-recommendations.index') }}">Batal</a></div>
        </form>
        @if($editing)
            <form class="danger-zone" method="post" action="{{ route('admin.trip-recommendations.destroy', $rule) }}" onsubmit="return confirm('Hapus aturan ini?')">
                @csrf @method('DELETE')
                <button class="btn-danger" type="submit">Hapus aturan</button>
            </form>
        @endif
    </div>
</x-layouts.admin>
