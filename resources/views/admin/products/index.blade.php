<x-layouts.admin title="Produk & Varian">
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
        <table class="data-table"><thead><tr><th>Produk</th><th>Kategori</th><th>Tipe</th><th>Status</th><th>Varian</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($products as $product)
                <tr>
                    <td><strong>{{ $product->name }}</strong><br><small>{{ $product->brand ?: '—' }}</small></td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td>{{ $product->type->label() }}</td>
                    <td><x-partials.status-badge :status="$product->status" /></td>
                    <td>{{ $product->variants->count() }}</td>
                    <td class="text-right"><a class="text-link" href="{{ route('admin.products.edit', $product) }}">Kelola</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">Belum ada produk.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $products->links() }}</div>
    </div>
</x-layouts.admin>
