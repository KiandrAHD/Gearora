<x-layouts.admin title="Produk & Varian">
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif

    <div class="panel">
        <div class="panel-head">
            <h2>Daftar produk</h2>
            <div class="panel-actions"><a class="btn-primary" href="{{ route('admin.products.create') }}">Tambah produk</a></div>
        </div>
        <form method="get" action="{{ route('admin.products.index') }}" class="inline-filter inline-filter-wide">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Cari nama atau merek">
            <select name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            <select name="type"><option value="">Semua tipe</option>@foreach($types as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>@endforeach</select>
            <select name="category_id"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
            <button class="text-link" type="submit">Terapkan</button>
        </form>
        <div class="overflow-x-auto">
            <table class="data-table"><thead><tr><th>Produk</th><th>Kategori</th><th>Tipe</th><th>Status</th><th>Varian</th><th class="text-right">Aksi</th></tr></thead><tbody>
                @forelse($products as $product)
                    @php
                        $dependencies = $productDependencies[$product->id] ?? ['rental_assets' => 0, 'stock_movements' => 0, 'order_items' => 0, 'assignments' => 0];
                        $hasHistory = array_sum($dependencies) > 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $product->name }}</strong><br><small>{{ $product->brand ?: '—' }}</small></td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td>{{ $product->type->label() }}</td>
                        <td><x-partials.status-badge :status="$product->status" /></td>
                        <td>{{ $product->variants->count() }}</td>
                        <td>
                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <button type="button" class="text-link" x-on:click="$dispatch('open-modal', 'product-view-{{ $product->id }}')">Lihat</button>
                                <button type="button" class="text-link" x-on:click="$dispatch('open-modal', 'product-edit-{{ $product->id }}')">Edit</button>
                                <a class="text-link" href="{{ route('admin.products.edit', $product) }}">Kelola</a>
                                @if($product->status->value !== 'archived')
                                    <button type="button" class="text-link" x-on:click="$dispatch('open-modal', 'product-archive-{{ $product->id }}')">Arsipkan</button>
                                @endif
                                @if(! $hasHistory)
                                    <button type="button" class="text-link text-danger" x-on:click="$dispatch('open-modal', 'product-delete-{{ $product->id }}')">Hapus permanen</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-cell">Belum ada produk.</td></tr>
                @endforelse
            </tbody></table>
        </div>
        <div class="pagination-wrap">{{ $products->links() }}</div>
    </div>

    @foreach($products as $product)
        @php
            $dependencies = $productDependencies[$product->id] ?? ['rental_assets' => 0, 'stock_movements' => 0, 'order_items' => 0, 'assignments' => 0];
            $hasHistory = array_sum($dependencies) > 0;
            $rentalUnits = $product->variants->sum('rental_assets_count');
            $saleStock = $product->variants->sum('stock_quantity');
            $specifications = $product->specifications ?? [];
        @endphp

        <x-ui.modal id="product-view-{{ $product->id }}" :title="$product->name" maxWidth="2xl">
            <div class="space-y-5 text-sm text-navy">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex size-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-cloud">
                        @if($product->displayImagePath())
                            <img src="{{ media_url($product->displayImagePath()) }}" alt="{{ $product->name }}" class="size-full object-cover">
                        @else
                            <span class="text-xs text-slate-500">Tanpa foto</span>
                        @endif
                    </div>
                    <dl class="summary-list min-w-0 flex-1">
                        <div><dt>Slug</dt><dd class="break-all">{{ $product->slug }}</dd></div>
                        <div><dt>Kategori</dt><dd>{{ $product->category?->name ?? '—' }}</dd></div>
                        <div><dt>Merek</dt><dd>{{ $product->brand ?: '—' }}</dd></div>
                        <div><dt>Tipe</dt><dd>{{ $product->type->label() }}</dd></div>
                        <div><dt>Status</dt><dd><x-partials.status-badge :status="$product->status" /></dd></div>
                    </dl>
                </div>

                <div>
                    <h3 class="mb-2 font-heading text-sm font-semibold">Deskripsi</h3>
                    <p class="whitespace-pre-line text-slate-600">{{ $product->description ?: 'Belum ada deskripsi.' }}</p>
                </div>

                <dl class="summary-list">
                    <div><dt>Harga sewa / hari</dt><dd>{{ $product->rental_price_per_day !== null ? rupiah($product->rental_price_per_day) : '—' }}</dd></div>
                    <div><dt>Harga jual / unit</dt><dd>{{ $product->sale_price !== null ? rupiah($product->sale_price) : '—' }}</dd></div>
                    <div><dt>Deposit</dt><dd>{{ $product->requires_deposit ? rupiah($product->deposit_amount ?? 0) : 'Tidak wajib' }}</dd></div>
                    <div><dt>Produk unggulan</dt><dd>{{ $product->is_featured ? 'Ya' : 'Tidak' }}</dd></div>
                    <div><dt>Jumlah varian</dt><dd>{{ $product->variants->count() }}</dd></div>
                    <div><dt>Unit rental fisik</dt><dd>{{ $rentalUnits }}</dd></div>
                    <div><dt>Stok jual</dt><dd>{{ $saleStock }}</dd></div>
                </dl>

                @if(! empty($specifications))
                    <div>
                        <h3 class="mb-2 font-heading text-sm font-semibold">Spesifikasi</h3>
                        <dl class="summary-list">
                            @foreach($specifications as $key => $value)
                                <div><dt>{{ $key }}</dt><dd>{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div>
                    <h3 class="mb-2 font-heading text-sm font-semibold">Varian</h3>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Nama</th><th>SKU</th><th>Sewa</th><th>Jual</th><th>Stok</th><th>Unit</th><th>Aktif</th></tr></thead>
                            <tbody>
                                @forelse($product->variants as $variant)
                                    <tr>
                                        <td>{{ $variant->name }}</td>
                                        <td>{{ $variant->sku }}</td>
                                        <td>{{ $variant->rental_price_per_day !== null ? rupiah($variant->rental_price_per_day) : '—' }}</td>
                                        <td>{{ $variant->sale_price !== null ? rupiah($variant->sale_price) : '—' }}</td>
                                        <td>{{ $variant->stock_quantity }}</td>
                                        <td>{{ $variant->rental_assets_count }}</td>
                                        <td><span class="status-badge status-{{ $variant->is_active ? 'ok' : 'neutral' }}">{{ $variant->is_active ? 'Ya' : 'Tidak' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="empty-cell">Belum ada varian.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($hasHistory)
                    <div class="notice notice-warning" role="status">Produk memiliki riwayat: {{ $dependencies['rental_assets'] }} unit rental, {{ $dependencies['stock_movements'] }} riwayat stok, {{ $dependencies['order_items'] }} item pesanan. Hapus permanen tidak tersedia.</div>
                @endif
            </div>
            <x-slot name="footer">
                <button type="button" class="btn-primary" @click="close()">Tutup</button>
            </x-slot>
        </x-ui.modal>

        <x-ui.modal id="product-edit-{{ $product->id }}" title="Edit cepat produk" maxWidth="2xl">
            <form id="product-edit-form-{{ $product->id }}" class="stack-form" method="post" action="{{ route('admin.products.update', $product) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_to" value="index">
                <div class="form-row">
                    <label><span>Nama</span><input name="name" type="text" value="{{ $product->name }}" required></label>
                    <label><span>Slug</span><input name="slug" type="text" value="{{ $product->slug }}" required></label>
                </div>
                <div class="form-row">
                    <label><span>Kategori</span><select name="category_id" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    <label><span>Merek</span><input name="brand" type="text" value="{{ $product->brand }}"></label>
                </div>
                <div class="form-row">
                    <label><span>Tipe</span><select name="type">@foreach($types as $type)<option value="{{ $type->value }}" @selected($product->type === $type)>{{ $type->label() }}</option>@endforeach</select></label>
                    <label><span>Status</span><select name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected($product->status === $status)>{{ $status->label() }}</option>@endforeach</select></label>
                </div>
                <label><span>Deskripsi</span><textarea name="description" rows="3">{{ $product->description }}</textarea></label>
                <div class="form-row">
                    <label><span>Harga sewa / hari</span><input name="rental_price_per_day" type="number" step="0.01" min="0" value="{{ $product->rental_price_per_day }}"></label>
                    <label><span>Harga jual / unit</span><input name="sale_price" type="number" step="0.01" min="0" value="{{ $product->sale_price }}"></label>
                </div>
                <div class="form-row">
                    <label class="check-row"><input name="requires_deposit" type="checkbox" value="1" @checked($product->requires_deposit)> <span>Wajib deposit</span></label>
                    <label><span>Nominal deposit</span><input name="deposit_amount" type="number" step="0.01" min="0" value="{{ $product->deposit_amount }}"></label>
                    <label class="check-row"><input name="is_featured" type="checkbox" value="1" @checked($product->is_featured)> <span>Produk unggulan</span></label>
                </div>
                @foreach($specifications as $index => $value)
                    <input type="hidden" name="specifications[{{ $index }}][key]" value="{{ $index }}">
                    <input type="hidden" name="specifications[{{ $index }}][value]" value="{{ $value }}">
                @endforeach
            </form>
            <x-slot name="footer">
                <button type="button" class="text-link" @click="close()">Batal</button>
                <button type="submit" form="product-edit-form-{{ $product->id }}" class="btn-primary">Simpan perubahan</button>
            </x-slot>
        </x-ui.modal>

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

        @if(! $hasHistory)
            <x-ui.modal id="product-delete-{{ $product->id }}" title="Hapus produk permanen?" maxWidth="md">
                <div class="space-y-3 text-sm text-slate-600">
                    <p>Nama: <strong class="text-navy">{{ $product->name }}</strong></p>
                    <p>Slug: {{ $product->slug }}</p>
                    <p class="text-danger">Tindakan ini akan menghapus produk, varian, dan foto secara permanen. Tindakan tidak dapat dibatalkan.</p>
                </div>
                <x-slot name="footer">
                    <button type="button" class="text-link" @click="close()">Batal</button>
                    <form method="post" action="{{ route('admin.products.destroy', $product) }}">@csrf @method('DELETE')<button class="btn-danger" type="submit">Hapus permanen</button></form>
                </x-slot>
            </x-ui.modal>
        @endif
    @endforeach
</x-layouts.admin>