<x-layouts.admin title="Inspeksi Return" subtitle="Unit tetap tertahan sampai inspeksi selesai.">
    <section class="panel"><div class="table-responsive"><table class="data-table"><thead><tr><th>Order</th><th>Pelanggan</th><th>Unit</th><th>Aksi</th></tr></thead><tbody>
        @forelse($assignments as $assignment)
            <tr><td>{{ $assignment->order?->order_code }}</td><td>{{ $assignment->order?->customer?->name }}</td><td>{{ $assignment->asset?->asset_code }}</td><td>
                <form class="inline-form" method="post" action="{{ route('admin.operations.inspections.finalize', $assignment) }}">
                    @csrf
                    <select name="result" required><option value="pass">Lolos</option><option value="fail">Bermasalah</option></select>
                    <select name="outcome_status"><option value="maintenance">Perawatan</option><option value="damaged">Rusak</option><option value="lost">Hilang</option></select>
                    <input name="damage_description" placeholder="Catatan kerusakan" maxlength="255">
                    <button class="btn-primary" type="submit">Finalisasi</button>
                </form>
            </td></tr>
        @empty
            <tr><td colspan="4" class="empty-cell">Tidak ada unit menunggu inspeksi.</td></tr>
        @endforelse
    </tbody></table></div>{{ $assignments->links() }}</section>
</x-layouts.admin>
