<x-layouts.admin title="Kategori panduan">
    <div class="panel">
        <div class="panel-head"><h2>Daftar kategori panduan</h2><a class="btn-primary" href="{{ route('admin.guide-categories.create') }}">Tambah kategori</a></div>
        <table class="data-table"><thead><tr><th>Nama</th><th>Slug</th><th>Panduan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
            @forelse($categories as $category)
                <tr><td>{{ $category->name }}</td><td>{{ $category->slug }}</td><td>{{ $category->guides_count }}</td><td>{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="text-right"><a class="text-link" href="{{ route('admin.guide-categories.edit', $category) }}">Ubah</a></td></tr>
            @empty<tr><td colspan="5" class="empty-cell">Belum ada kategori.</td></tr>@endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $categories->links() }}</div>
    </div>
</x-layouts.admin>
