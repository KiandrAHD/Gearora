<x-layouts.admin title="Kategori">
    <div class="panel">
        <div class="panel-head">
            <h2>Daftar kategori</h2>
            <div class="panel-actions">
                <form method="get" action="{{ route('admin.categories.index') }}" class="inline-filter"><input name="q" type="search" value="{{ request('q') }}" placeholder="Cari kategori"><button class="text-link" type="submit">Cari</button></form>
                <a class="btn-primary" href="{{ route('admin.categories.create') }}">Tambah kategori</a>
            </div>
        </div>
        <table class="data-table"><thead><tr><th>Nama</th><th>Slug</th><th>Produk</th><th>Status</th><th>Urutan</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ $category->products_count }}</td>
                    <td><span class="status-badge status-{{ $category->is_active ? 'ok' : 'neutral' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>{{ $category->sort_order }}</td>
                    <td class="text-right"><a class="text-link" href="{{ route('admin.categories.edit', $category) }}">Ubah</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">Belum ada kategori.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $categories->links() }}</div>
    </div>
</x-layouts.admin>
