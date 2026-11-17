<x-layouts.admin title="Inventaris">
    <div class="tab-row">
        <a class="tab {{ $tab === 'assets' ? 'tab-active' : '' }}" href="{{ route('admin.inventory.index', ['tab' => 'assets']) }}">Unit rental</a>
        <a class="tab {{ $tab === 'stock' ? 'tab-active' : '' }}" href="{{ route('admin.inventory.index', ['tab' => 'stock']) }}">Stok jual</a>
        <a class="tab {{ $tab === 'movements' ? 'tab-active' : '' }}" href="{{ route('admin.inventory.index', ['tab' => 'movements']) }}">Pergerakan stok</a>
    </div>

    @if($tab === 'assets')
        <div class="panel">
            <div class="panel-head"><h2>Unit rental</h2><a class="btn-primary" href="{{ route('admin.inventory.assets.create') }}">Tambah unit</a></div>
            <form method="get" action="{{ route('admin.inventory.index') }}" class="inline-filter inline-filter-wide">
                <input type="hidden" name="tab" value="assets">
                <input name="q" type="search" value="{{ request('q') }}" placeholder="Kode unit, varian, atau SKU">
                <select name="status"><option value="">Semua status</option>@foreach($assetStatuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
                <button class="text-link" type="submit">Terapkan</button>
            </form>
            <table class="data-table"><thead><tr><th>Kode unit</th><th>Produk / Varian</th><th>Status</th><th>Kondisi</th><th class="text-right">Aksi</th></tr></thead><tbody>
                @forelse($assets as $asset)
                    <tr><td>{{ $asset->asset_code }}</td><td>{{ $asset->variant?->product?->name }} · {{ $asset->variant?->name }}</td><td><x-partials.status-badge :status="$asset->status" /></td><td><x-partials.status-badge :status="$asset->condition" /></td><td class="text-right"><a class="text-link" href="{{ route('admin.inventory.assets.edit', $asset) }}">Ubah</a></td></tr>
                @empty
                    <tr><td colspan="5" class="empty-cell">Belum ada unit rental.</td></tr>
                @endforelse
            </tbody></table>
            <div class="pagination-wrap">{{ $assets->links() }}</div>
        </div>
    @elseif($tab === 'stock')
        <div class="panel">
            <h2>Stok jual</h2>
            <table class="data-table"><thead><tr><th>SKU</th><th>Produk / Varian</th><th>Stok</th><th>Penyesuaian</th></tr></thead><tbody>
                @forelse($saleVariants as $variant)
                    <tr>
                        <td>{{ $variant->sku }}</td><td>{{ $variant->product?->name }} · {{ $variant->name }}</td><td>{{ $variant->stock_quantity }}</td>
                        <td><form class="inline-form" method="post" action="{{ route('admin.inventory.stock.adjust', $variant) }}">@csrf
                            <input name="quantity" type="number" min="0" value="{{ $variant->stock_quantity }}" aria-label="Stok baru">
                            <select name="type" aria-label="Jenis penyesuaian">@foreach($movementTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>
                            <input name="reason" type="text" placeholder="Alasan" aria-label="Alasan">
                            <button class="text-link" type="submit">Simpan</button>
                        </form></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-cell">Belum ada varian jual.</td></tr>
                @endforelse
            </tbody></table>
            <div class="pagination-wrap">{{ $saleVariants->links() }}</div>
        </div>
    @else
        <div class="panel">
            <h2>Pergerakan stok</h2>
            <table class="data-table"><thead><tr><th>Waktu</th><th>Jenis</th><th>Varian / Unit</th><th>Perubahan</th><th>Alasan</th><th>Oleh</th></tr></thead><tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td>{{ $movement->type->label() }}</td>
                        <td>{{ $movement->variant?->sku }} {{ $movement->rentalAsset?->asset_code ? '· '.$movement->rentalAsset->asset_code : '' }}</td>
                        <td>@if($movement->quantity_before !== null || $movement->quantity_after !== null){{ $movement->quantity_before }} → {{ $movement->quantity_after }}@else {{ $movement->status_before }} → {{ $movement->status_after }} @endif</td>
                        <td>{{ $movement->reason }}</td>
                        <td>{{ $movement->user?->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-cell">Belum ada pergerakan stok.</td></tr>
                @endforelse
            </tbody></table>
            <div class="pagination-wrap">{{ $movements->links() }}</div>
        </div>
    @endif
</x-layouts.admin>
