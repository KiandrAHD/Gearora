<x-layouts.admin title="Aturan rekomendasi trip">
    <div class="panel">
        <div class="panel-head">
            <h2>Aturan Trip Planner</h2>
            <div class="panel-actions">
                <form method="get" action="{{ route('admin.trip-recommendations.index') }}" class="inline-filter">
                    <input name="q" type="search" value="{{ request('q') }}" placeholder="Cari nama aturan">
                    <select name="activity">
                        <option value="">Semua aktivitas</option>
                        @foreach($activities as $activity)
                            <option value="{{ $activity }}" @selected(request('activity') === $activity)>{{ $activity }}</option>
                        @endforeach
                    </select>
                    <button class="text-link" type="submit">Filter</button>
                </form>
                <a class="btn-primary" href="{{ route('admin.trip-recommendations.create') }}">Tambah aturan</a>
            </div>
        </div>
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Aktivitas</th><th>Mode</th><th>Prioritas</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rule->name }}</td>
                        <td>{{ $rule->activityLabel() }}</td>
                        <td>{{ $rule->service_mode->label() }}</td>
                        <td>{{ $rule->priority }}</td>
                        <td><span class="status-badge status-{{ $rule->is_active ? 'ok' : 'neutral' }}">{{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right"><a class="text-link" href="{{ route('admin.trip-recommendations.edit', $rule) }}">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-cell">Belum ada aturan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $rules->links() }}</div>
    </div>
</x-layouts.admin>
