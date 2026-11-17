<x-layouts.admin title="Log Aktivitas">
    <div class="panel">
        <div class="panel-head"><h2>Audit trail</h2></div>
        <form method="get" action="{{ route('admin.activity.index') }}" class="inline-filter"><input name="q" type="search" value="{{ request('q') }}" placeholder="Cari aksi"><button class="text-link" type="submit">Cari</button></form>
        <table class="data-table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th><th>Oleh</th><th>IP</th></tr></thead><tbody>
            @forelse($logs as $log)
                <tr><td>{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td><td>{{ $log->action }}</td><td>{{ $log->description }}</td><td>{{ $log->user?->name ?? 'Sistem' }}</td><td>{{ $log->ip_address ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="5" class="empty-cell">Belum ada log aktivitas.</td></tr>
            @endforelse
        </tbody></table>
        <div class="pagination-wrap">{{ $logs->links() }}</div>
    </div>
</x-layouts.admin>
