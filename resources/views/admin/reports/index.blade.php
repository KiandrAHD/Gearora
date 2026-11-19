<x-layouts.admin title="Laporan" subtitle="Laporan operasional dan keuangan GEARORA. Seluruh angka diambil langsung dari transaksi pada database.">
    @php
        $groupLabels = [
            'rental' => 'Rental', 'sales' => 'Penjualan retail', 'payments' => 'Pembayaran',
            'deposits' => 'Deposit', 'charges' => 'Biaya tambahan', 'inventory' => 'Inventaris', 'operations' => 'Operasional',
        ];
        $currencyColumns = [
            'rental' => ['subtotal', 'deposit_total', 'total', 'rental_paid'],
            'sales' => ['subtotal', 'discount_total', 'total'],
            'payments' => ['amount'],
            'deposits' => ['required', 'collected', 'deducted', 'refunded', 'forfeited', 'balance'],
            'charges' => ['amount', 'outstanding'],
            'inventory' => [],
            'operations' => [],
        ][$active] ?? [];
        $query = request()->query();
    @endphp

    <div class="panel">
        <nav class="report-tabs" aria-label="Jenis laporan">
            @foreach($types as $type)
                <a class="{{ $active === $type ? 'is-active' : '' }}" @if($active === $type) aria-current="page" @endif href="{{ route('admin.reports.index', array_merge($query, ['report' => $type])) }}">{{ $typeLabels[$type] ?? ucfirst($type) }}</a>
            @endforeach
        </nav>

        <form method="get" action="{{ route('admin.reports.index') }}">
            <input type="hidden" name="report" value="{{ $active }}">
            <div class="filter-grid">
                <label>Tanggal Mulai<input type="date" name="start_date" value="{{ $filters['start_date'] }}"></label>
                <label>Tanggal Akhir<input type="date" name="end_date" value="{{ $filters['end_date'] }}"></label>

                @if(!empty($statuses))
                    <label>Status
                        <select name="status"><option value="">Semua status</option>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                @if($active === 'payments')
                    <label>Metode Pembayaran
                        <select name="method"><option value="">Semua metode</option>
                            @foreach($methods as $method)
                                <option value="{{ $method->value }}" @selected($filters['method'] === $method->value)>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Jenis Transaksi
                        <select name="transaction_type"><option value="">Semua transaksi</option>
                            <option value="rental" @selected($filters['transaction_type'] === 'rental')>Rental</option>
                            <option value="sales" @selected($filters['transaction_type'] === 'sales')>Penjualan retail</option>
                        </select>
                    </label>
                @endif

                @if($active === 'inventory')
                    <label>Produk
                        <select name="product_id"><option value="">Semua produk</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((int) $filters['product_id'] === $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Kategori
                        <select name="category_id"><option value="">Semua kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) $filters['category_id'] === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Terapkan Filter</button>
                <a class="btn-secondary" href="{{ route('admin.reports.index', ['report' => $active]) }}">Reset</a>
            </div>
        </form>
    </div>

    @foreach($dashboard as $group => $metrics)
        <div class="panel">
            <div class="panel-head"><h2>{{ $groupLabels[$group] ?? ucfirst($group) }}</h2></div>
            <dl class="kpi-list">
                @foreach($metrics as $key => $value)
                    @continue(is_array($value) || is_bool($value) || $value === null)
                    <div>
                        <dt>{{ str_replace('_', ' ', $key) }}</dt>
                        <dd>{{ is_float($value) ? number_format($value, 1, ',', '.') : number_format((int) $value, 0, ',', '.') }}</dd>
                    </div>
                @endforeach
            </dl>
            @if($group === 'sales' && ($metrics['refunded_sales_available'] ?? true) === false)
                <p class="qa-note">Refund penjualan retail belum dihitung sebagai angka final karena alur refund retail belum tersedia pada sistem.</p>
            @endif
        </div>
    @endforeach

    <div class="panel">
        <div class="panel-head">
            <h2>Data {{ ucfirst($active) }}</h2>
            @if($canExport)
                <div class="export-menu" x-data="{ open: false }" @keydown.escape.window="open = false">
                    <button type="button" class="btn-primary" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="menu">Ekspor Laporan</button>
                    <div class="export-menu-panel" x-show="open" x-cloak @click.outside="open = false" role="menu">
                        <a role="menuitem" href="{{ route('admin.reports.export', array_merge($query, ['report' => $active, 'format' => 'pdf'])) }}">PDF (.pdf)</a>
                        <a role="menuitem" href="{{ route('admin.reports.export', array_merge($query, ['report' => $active, 'format' => 'xlsx'])) }}">Excel (.xlsx)</a>
                        <a role="menuitem" href="{{ route('admin.reports.export', array_merge($query, ['report' => $active, 'format' => 'csv'])) }}">CSV (.csv)</a>
                    </div>
                </div>
            @endif
        </div>
        <div class="report-table-wrap">
            <table class="data-table">
                <thead><tr>@foreach(array_keys($current['table'][0] ?? ['Tidak ada data' => '']) as $heading)<th>{{ str_replace('_', ' ', ucfirst($heading)) }}</th>@endforeach</tr></thead>
                <tbody>
                @forelse($current['table'] as $row)
                    <tr>
                        @foreach($row as $column => $value)
                            <td>
                                @if(in_array($column, $currencyColumns, true))
                                    {{ rupiah((int) $value) }}
                                @elseif(is_array($value))
                                    {{ json_encode($value) }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td class="empty-cell" colspan="{{ max(1, count(array_keys($current['table'][0] ?? ['x' => 1]))) }}">Belum ada data pada rentang ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
