<x-layouts.admin :title="'Paket '.$package->code">
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>{{ $package->code }}</h2>
                <p class="field-hint">
                    {{ $package->destination }} · {{ $package->participants }} peserta ·
                    {{ $package->experience_level->label() }} · {{ $package->mode->label() }}
                </p>
            </div>
            <x-partials.status-badge :status="$package->status" />
        </div>

        <dl class="trip-summary">
            <div><dt>Pelanggan</dt><dd>{{ $package->customer?->name }} ({{ $package->customer?->email }})</dd></div>
            <div><dt>Aktivitas</dt><dd>{{ \App\Enums\TripActivity::tryFrom($package->activity)?->label() ?? $package->activity }}</dd></div>
            <div><dt>Durasi</dt><dd>{{ $package->duration_days }} hari</dd></div>
            @if($package->start_date && $package->end_date)
                <div><dt>Tanggal</dt><dd>{{ $package->start_date->translatedFormat('d M Y') }} → {{ $package->end_date->translatedFormat('d M Y') }}</dd></div>
            @endif
            <div><dt>Dibuat</dt><dd>{{ $package->created_at?->translatedFormat('d M Y H:i') }}</dd></div>
        </dl>
    </div>

    @if($package->rentalOrder)
        <div class="panel">
            <div class="panel-head">
                <h2>Pesanan rental</h2>
                <x-partials.status-badge :status="$package->rentalOrder->order_status" />
            </div>
            <p class="field-hint">
                {{ $package->rentalOrder->order_code }} · Total {{ rupiah($package->rentalOrder->total) }}
                · Pembayaran:
                @if($package->rentalOrder->latestPayment)
                    <x-partials.status-badge :status="$package->rentalOrder->latestPayment->status" />
                @else
                    belum ada
                @endif
            </p>
            <table class="data-table">
                <thead><tr><th>Gear</th><th>Qty</th><th>Durasi</th><th class="text-right">Subtotal</th></tr></thead>
                <tbody>
                    @foreach($package->rentalOrder->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }} — {{ $item->variant_name_snapshot }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->duration_days }} hari</td>
                            <td class="text-right">{{ rupiah($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <a class="text-link" href="{{ route('admin.rentals.show', $package->rentalOrder) }}">Buka pesanan rental →</a>
        </div>
    @endif

    @if($package->salesOrder)
        <div class="panel">
            <div class="panel-head">
                <h2>Pesanan retail</h2>
                <x-partials.status-badge :status="$package->salesOrder->order_status" />
            </div>
            <p class="field-hint">
                {{ $package->salesOrder->order_code }} · Total {{ rupiah($package->salesOrder->total) }}
                · Pembayaran:
                @if($package->salesOrder->latestPayment)
                    <x-partials.status-badge :status="$package->salesOrder->latestPayment->status" />
                @else
                    belum ada
                @endif
            </p>
            <table class="data-table">
                <thead><tr><th>Gear</th><th>Qty</th><th class="text-right">Harga/unit</th><th class="text-right">Subtotal</th></tr></thead>
                <tbody>
                    @foreach($package->salesOrder->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }} — {{ $item->variant_name_snapshot }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-right">{{ rupiah($item->unit_price_snapshot) }}</td>
                            <td class="text-right">{{ rupiah($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <a class="text-link" href="{{ route('admin.sales.show', $package->salesOrder) }}">Buka pesanan retail →</a>
        </div>
    @endif

    <div class="panel">
        <h2>Total paket</h2>
        <p class="package-total-grand">
            {{ rupiah((int) ($package->rentalOrder?->total ?? 0) + (int) ($package->salesOrder?->total ?? 0)) }}
        </p>
        <p class="field-hint">Nominal mengikuti total pesanan child. Tidak ada penagihan gabungan gateway.</p>
    </div>
</x-layouts.admin>