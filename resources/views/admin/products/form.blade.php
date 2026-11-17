@php
    $editing = $product->exists;
    $specs = old('specifications', collect($product->specifications ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->all());
    if (empty($specs)) { $specs = [['key' => '', 'value' => '']]; }
@endphp
<x-layouts.admin :title="$editing ? 'Kelola produk' : 'Produk baru'">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif

    <div class="panel">
        <h2>Data produk</h2>
        <form class="stack-form" method="post" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="form-row">
                <label><span>Nama</span><input name="name" type="text" value="{{ old('name', $product->name) }}" required></label>
                <label><span>Slug</span><input name="slug" type="text" value="{{ old('slug', $product->slug) }}" placeholder="otomatis dari nama"></label>
            </div>
            <div class="form-row">
                <label><span>Kategori</span><select name="category_id" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label><span>Merek</span><input name="brand" type="text" value="{{ old('brand', $product->brand) }}"></label>
            </div>
            <div class="form-row">
                <label><span>Tipe</span><select name="type">@foreach($types as $type)<option value="{{ $type->value }}" @selected(old('type', $product->type?->value) === $type->value)>{{ $type->label() }}</option>@endforeach</select></label>
                <label><span>Status</span><select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $product->status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
            </div>
            <label><span>Deskripsi</span><textarea name="description" rows="3">{{ old('description', $product->description) }}</textarea></label>
            <div class="form-row">
                <label><span>Harga sewa / hari</span><input name="rental_price_per_day" type="number" step="0.01" min="0" value="{{ old('rental_price_per_day', $product->rental_price_per_day) }}"></label>
                <label><span>Harga jual / unit</span><input name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price) }}"></label>
            </div>
            <div class="form-row">
                <label class="check-row"><input name="requires_deposit" type="checkbox" value="1" @checked(old('requires_deposit', $product->requires_deposit))> <span>Wajib deposit</span></label>
                <label><span>Nominal deposit</span><input name="deposit_amount" type="number" step="0.01" min="0" value="{{ old('deposit_amount', $product->deposit_amount) }}"></label>
                <label class="check-row"><input name="is_featured" type="checkbox" value="1" @checked(old('is_featured', $product->is_featured))> <span>Produk unggulan</span></label>
            </div>
            <fieldset class="spec-fieldset">
                <legend>Spesifikasi</legend>
                @foreach($specs as $index => $spec)
                    <div class="form-row"><input name="specifications[{{ $index }}][key]" type="text" value="{{ $spec['key'] ?? '' }}" placeholder="Nama spesifikasi"><input name="specifications[{{ $index }}][value]" type="text" value="{{ $spec['value'] ?? '' }}" placeholder="Nilai"></div>
                @endforeach
                <div class="form-row"><input name="specifications[{{ count($specs) }}][key]" type="text" placeholder="Nama spesifikasi"><input name="specifications[{{ count($specs) }}][value]" type="text" placeholder="Nilai"></div>
            </fieldset>
            <label><span>Foto produk</span><input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
            <label class="check-row"><input name="image_primary" type="checkbox" value="1"> <span>Jadikan foto utama</span></label>
            <div class="form-actions"><button class="btn-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Buat produk' }}</button><a class="text-link" href="{{ route('admin.products.index') }}">Kembali</a></div>
        </form>
        @if($editing)
            <form class="danger-zone" method="post" action="{{ route('admin.products.archive', $product) }}" onsubmit="return confirm('Arsipkan produk ini?')">@csrf<button class="btn-danger" type="submit">Arsipkan produk</button></form>
        @endif
    </div>

    @if($editing)
        <div class="panel">
            <h2>Varian</h2>
            <table class="data-table"><thead><tr><th>Nama</th><th>SKU</th><th>Sewa/hari</th><th>Jual</th><th>Stok</th><th>Aktif</th><th class="text-right">Aksi</th></tr></thead><tbody>
                @forelse($product->variants as $variant)
                    <tr>
                        <td>{{ $variant->name }}</td><td>{{ $variant->sku }}</td>
                        <td>{{ $variant->rental_price_per_day ? rupiah($variant->rental_price_per_day) : '—' }}</td>
                        <td>{{ $variant->sale_price ? rupiah($variant->sale_price) : '—' }}</td>
                        <td>{{ $variant->stock_quantity }}</td>
                        <td><span class="status-badge status-{{ $variant->is_active ? 'ok' : 'neutral' }}">{{ $variant->is_active ? 'Ya' : 'Tidak' }}</span></td>
                        <td class="text-right"><form method="post" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" onsubmit="return confirm('Hapus varian ini?')">@csrf @method('DELETE')<button class="text-link" type="submit">Hapus</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell">Belum ada varian.</td></tr>
                @endforelse
            </tbody></table>
            <h3>Varian baru</h3>
            <form class="stack-form" method="post" action="{{ route('admin.products.variants.store', $product) }}">
                @csrf
                <div class="form-row">
                    <label><span>Nama</span><input name="name" type="text" required></label>
                    <label><span>SKU</span><input name="sku" type="text" required></label>
                </div>
                <div class="form-row">
                    <label><span>Harga sewa / hari</span><input name="rental_price_per_day" type="number" step="0.01" min="0"></label>
                    <label><span>Harga jual</span><input name="sale_price" type="number" step="0.01" min="0"></label>
                    <label><span>Stok</span><input name="stock_quantity" type="number" min="0" value="0"></label>
                </div>
                <label class="check-row"><input name="is_active" type="checkbox" value="1" checked> <span>Aktif</span></label>
                <button class="btn-primary" type="submit">Tambah varian</button>
            </form>
        </div>

        <div class="panel">
            <h2>Foto produk</h2>
            <div class="thumb-grid">
                @forelse($product->images as $image)
                    <figure class="thumb"><img src="{{ media_url($image->path) }}" alt="{{ $image->alt }}"><figcaption>
                        @if($image->is_primary)<span class="status-badge status-ok">Utama</span>@else<form method="post" action="{{ route('admin.products.images.primary', [$product, $image]) }}">@csrf<button class="text-link" type="submit">Jadikan utama</button></form>@endif
                        <form method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="text-link" type="submit">Hapus</button></form>
                    </figcaption></figure>
                @empty
                    <p class="empty-cell">Belum ada foto.</p>
                @endforelse
            </div>
            <form class="stack-form" method="post" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data">
                @csrf
                <label><span>Tambah foto</span><input name="image" type="file" accept="image/jpeg,image/png,image/webp" required></label>
                <button class="btn-primary" type="submit">Unggah foto</button>
            </form>
        </div>
    @endif
</x-layouts.admin>
