<x-layouts.admin title="Paket Trip">
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Paket Trip Planner</h2>
                <p class="field-hint">Status dan total mengikuti pesanan rental/retail yang tertaut.</p>
            </div>
        </div>

        <form method="get" action="{{ route('admin.trip-packages.index') }}" class="inline-filter inline-filter-wide">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Kode paket, pelanggan, atau destinasi">
            <select name="status">
                <option value="">Semua status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="text-link" type="submit">Terapkan</button>
            <a class="text-link" href="{{ route('admin.trip-packages.index') }}">Reset</a>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pelanggan</th>
                    <th>Jadwal</th>
                    <th>Peserta</th>
                    <th>Pesanan</th>
                    <th>Status paket</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $package)
                    <tr>
                        <td>{{ $package->code }}</td>
                        <td>{{ $package->customer?->name }}<br><small>{{ $package->customer?->email }}</small></td>
                        <td>
                            @if($package->start_date && $package->end_date)
                                {{ $package->start_date->translatedFormat('d M Y') }} &rarr; {{ $package->end_date->translatedFormat('d M Y') }}
                            @else
                                {{ $package->duration_days }} hari
                            @endif
                        </td>
                        <td>{{ $package->participants }} orang</td>
                        <td>
                            @if($package->rentalOrder)
                                <div><small>Rental:</small> {{ $package->rentalOrder->order_code }}</div>
                            @endif
                            @if($package->salesOrder)
                                <div><small>Retail:</small> {{ $package->salesOrder->order_code }}</div>
                            @endif
                            @if(! $package->rentalOrder && ! $package->salesOrder)
                                <span class="field-hint">Belum ada</span>
                            @endif
                        </td>
                        <td><x-partials.status-badge :status="$package->status" /></td>
                        <td class="text-right">
                            {{ rupiah((int) ($package->rentalOrder?->total ?? 0) + (int) ($package->salesOrder?->total ?? 0)) }}
                        </td>
                        <td class="text-right"><a class="text-link" href="{{ route('admin.trip-packages.show', $package) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-cell">Belum ada paket trip.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">{{ $packages->links() }}</div>
    </div>
</x-layouts.admin>