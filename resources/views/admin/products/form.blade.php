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
            <div class="danger-zone">
                <p class="field-hint">Arsipkan produk agar tidak tampil di katalog publik tetapi riwayat transaksi tetap tersimpan.</p>
                <button class="btn-danger" type="button" x-on:click="$dispatch('open-modal', 'product-archive-{{ $product->id }}')">Arsipkan produk</button>
            </div>
        @endif
    </div>

    @if($editing)
        <div class="panel">
            <h2>Varian</h2>
            <div class="overflow-x-auto">
                <table class="data-table"><thead><tr><th>Nama</th><th>SKU</th><th>Sewa/hari</th><th>Jual</th><th>Stok</th><th>Unit</th><th>Aktif</th><th class="text-right">Aksi</th></tr></thead><tbody>
                    @forelse($product->variants as $variant)
                        @php
                            $variantDeps = $variantDependencies[$variant->id] ?? ['rental_assets' => 0, 'stock_movements' => 0, 'order_items' => 0, 'assignments' => 0];
                            $variantHasHistory = array_sum($variantDeps) > 0;
                        @endphp
                        <tr>
                            <td>{{ $variant->name }}</td><td>{{ $variant->sku }}</td>
                            <td>{{ $variant->rental_price_per_day ? rupiah($variant->rental_price_per_day) : '—' }}</td>
                            <td>{{ $variant->sale_price ? rupiah($variant->sale_price) : '—' }}</td>
                            <td>{{ $variant->stock_quantity }}</td>
                            <td>{{ $variant->rental_assets_count }}</td>
                            <td><span class="status-badge status-{{ $variant->is_active ? 'ok' : 'neutral' }}">{{ $variant->is_active ? 'Ya' : 'Tidak' }}</span></td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <button type="button" class="text-link" x-on:click="$dispatch('open-modal', 'variant-edit-{{ $variant->id }}')">Edit</button>
                                    @if($variantHasHistory && ! $variant->is_active)
                                        <span class="badge-inline">Nonaktif</span>
                                    @else
                                        <button type="button" class="text-link text-danger" x-on:click="$dispatch('open-modal', 'variant-action-{{ $variant->id }}')">{{ $variantHasHistory ? 'Nonaktifkan' : 'Hapus permanen' }}</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-cell">Belum ada varian.</td></tr>
                    @endforelse
                </tbody></table>
            </div>
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
                        <button type="button" class="text-link text-danger" x-on:click="$dispatch('open-modal', 'image-delete-{{ $image->id }}')">Hapus</button>
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

        @foreach($product->variants as $variant)
            @php
                $variantDeps = $variantDependencies[$variant->id] ?? ['rental_assets' => 0, 'stock_movements' => 0, 'order_items' => 0, 'assignments' => 0];
                $variantHasHistory = array_sum($variantDeps) > 0;
            @endphp

            <x-ui.modal id="variant-edit-{{ $variant->id }}" title="Edit varian" maxWidth="lg">
                <form id="variant-edit-form-{{ $variant->id }}" class="stack-form" method="post" action="{{ route('admin.products.variants.update', [$product, $variant]) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-row">
                        <label><span>Nama</span><input name="name" type="text" value="{{ $variant->name }}" required></label>
                        <label><span>SKU</span><input name="sku" type="text" value="{{ $variant->sku }}" required></label>
                    </div>
                    <div class="form-row">
                        <label><span>Harga sewa / hari</span><input name="rental_price_per_day" type="number" step="0.01" min="0" value="{{ $variant->rental_price_per_day }}"></label>
                        <label><span>Harga jual</span><input name="sale_price" type="number" step="0.01" min="0" value="{{ $variant->sale_price }}"></label>
                    </div>
                    <div class="form-row">
                        <label><span>Stok jual</span><input name="stock_quantity" type="number" min="0" value="{{ $variant->stock_quantity }}"></label>
                        <label><span>Urutan</span><input name="sort_order" type="number" min="0" value="{{ $variant->sort_order }}"></label>
                    </div>
                    <label class="check-row"><input name="is_active" type="checkbox" value="1" @checked($variant->is_active)> <span>Aktif</span></label>
                    <p class="field-hint">Perubahan stok jual dicatat sebagai stock movement.</p>
                </form>
                <x-slot name="footer">
                    <button type="button" class="text-link" @click="close()">Batal</button>
                    <button type="submit" form="variant-edit-form-{{ $variant->id }}" class="btn-primary">Simpan varian</button>
                </x-slot>
            </x-ui.modal>

            @if(! ($variantHasHistory && ! $variant->is_active))
                <x-ui.modal id="variant-action-{{ $variant->id }}" :title="$variantHasHistory ? 'Nonaktifkan varian?' : 'Hapus varian permanen?'" maxWidth="md">
                    <div class="space-y-3 text-sm text-slate-600">
                        <p>Nama: <strong class="text-navy">{{ $variant->name }}</strong></p>
                        <p>SKU: {{ $variant->sku }}</p>
                        @if($variantHasHistory)
                            <p>Varian masih mempunyai:</p>
                            <ul class="list-disc pl-5">
                                @if($variantDeps['rental_assets'] > 0)<li>{{ $variantDeps['rental_assets'] }} unit rental.</li>@endif
                                @if($variantDeps['stock_movements'] > 0)<li>{{ $variantDeps['stock_movements'] }} riwayat stok.</li>@endif
                                @if($variantDeps['order_items'] > 0)<li>{{ $variantDeps['order_items'] }} riwayat pesanan.</li>@endif
                                @if($variantDeps['assignments'] > 0)<li>{{ $variantDeps['assignments'] }} riwayat pemakaian unit.</li>@endif
                            </ul>
                            <p>Data historis akan dipertahankan. Varian tidak lagi dapat digunakan untuk pemesanan baru.</p>
                        @else
                            <p class="text-danger">Tindakan ini akan menghapus data secara permanen. Tindakan tidak dapat dibatalkan.</p>
                        @endif
                    </div>
                    <x-slot name="footer">
                        <button type="button" class="text-link" @click="close()">Batal</button>
                        <form method="post" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn-danger" type="submit">{{ $variantHasHistory ? 'Nonaktifkan' : 'Hapus permanen' }}</button>
                        </form>
                    </x-slot>
                </x-ui.modal>
            @endif
        @endforeach

        @foreach($product->images as $image)
            <x-ui.modal id="image-delete-{{ $image->id }}" title="Hapus foto produk?" maxWidth="md">
                <div class="space-y-3 text-sm text-slate-600">
                    <img src="{{ media_url($image->path) }}" alt="{{ $image->alt }}" class="max-h-48 w-full rounded-lg border border-line object-cover">
                    <p class="text-danger">Foto akan dihapus permanen dari produk dan penyimpanan. Tindakan tidak dapat dibatalkan.</p>
                </div>
                <x-slot name="footer">
                    <button type="button" class="text-link" @click="close()">Batal</button>
                    <form method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}">@csrf @method('DELETE')<button class="btn-danger" type="submit">Hapus foto</button></form>
                </x-slot>
            </x-ui.modal>
        @endforeach

        <x-ui.modal id="product-archive-{{ $product->id }}" title="Arsipkan produk?" maxWidth="md">
            <div class="space-y-3 text-sm text-slate-600">
                <p><strong class="text-navy">{{ $product->name }}</strong></p>
                <p>Produk yang diarsipkan:</p>
                <ul class="list-disc pl-5">
                    <li>Tidak muncul di katalog publik.</li>
                    <li>Tidak dapat digunakan untuk booking baru.</li>
                    <li>Tetap tersimpan untuk menjaga riwayat transaksi.</li>
                </ul>
            </div>
            <x-slot name="footer">
                <button type="button" class="text-link" @click="close()">Batal</button>
                <form method="post" action="{{ route('admin.products.archive', $product) }}">@csrf<button class="btn-danger" type="submit">Arsipkan</button></form>
            </x-slot>
        </x-ui.modal>
    @endif
</x-layouts.admin>