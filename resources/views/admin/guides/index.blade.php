<x-layouts.admin title="Panduan">
    <div class="panel">
        <div class="panel-head">
            <h2>Artikel panduan</h2>
            <div class="panel-actions">
                <form method="get" action="{{ route('admin.guides.index') }}" class="inline-filter">
                    <input name="q" type="search" value="{{ request('q') }}" placeholder="Cari judul">
                    <select name="status">
                        <option value="">Semua status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <button class="text-link" type="submit">Filter</button>
                </form>
                <a class="btn-primary" href="{{ route('admin.guides.create') }}">Tambah panduan</a>
            </div>
        </div>
        <table class="data-table">
            <thead><tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Terbit</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($guides as $guide)
                    <tr>
                        <td>{{ $guide->title }} @if($guide->is_featured)<span class="status-badge status-warn">Unggulan</span>@endif</td>
                        <td>{{ $guide->category?->name }}</td>
                        <td><span class="status-badge status-{{ $guide->status->value === 'published' ? 'ok' : ($guide->status->value === 'archived' ? 'neutral' : 'warn') }}">{{ $guide->status->label() }}</span></td>
                        <td>{{ $guide->published_at?->translatedFormat('d M Y') }}</td>
                        <td class="text-right"><a class="text-link" href="{{ route('admin.guides.edit', $guide) }}">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-cell">Belum ada panduan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $guides->links() }}</div>
    </div>
</x-layouts.admin>
