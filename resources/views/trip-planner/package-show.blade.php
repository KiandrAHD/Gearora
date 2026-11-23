<x-layout :title="'Paket '.$package->code">
    <section class="page-block">
        <div class="page-container">
            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif

            <p class="eyebrow">PAKET TRIP</p>
            <h1 class="page-title">{{ $package->code }}</h1>
            <p class="page-lead">
                {{ $package->destination }} · {{ $package->duration_days }} hari · {{ $package->participants }} peserta ·
                <strong>{{ $package->status->label() }}</strong>
            </p>

            <div class="package-actions">
                <a class="button" href="{{ route('customer.packages.document', $package) }}" target="_blank" rel="noopener">Lihat dokumen paket</a>
                <a class="button button-primary" href="{{ route('customer.packages.pdf', $package) }}">Unduh PDF paket</a>
                <a class="text-link" href="{{ route('customer.orders.index') }}">Semua pesanan</a>
            </div>

            <section class="panel">
                <h2>Rencana</h2>
                <dl class="trip-summary">
                    <div><dt>Destinasi</dt><dd>{{ $package->destination }}</dd></div>
                    @if($package->location)
                        <div><dt>Basecamp</dt><dd>{{ $package->location }}</dd></div>
                    @endif
                    <div><dt>Aktivitas</dt><dd>{{ \App\Enums\TripActivity::tryFrom($package->activity)?->label() ?? $package->activity }}</dd></div>
                    <div><dt>Level</dt><dd>{{ $package->experience_level->label() }}</dd></div>
                    <div><dt>Mode</dt><dd>{{ $package->mode->label() }}</dd></div>
                    <div><dt>Durasi</dt><dd>{{ $package->duration_days }} hari</dd></div>
                    <div><dt>Peserta</dt><dd>{{ $package->participants }} orang</dd></div>
                    @if($package->start_date && $package->end_date)
                        <div><dt>Tanggal</dt><dd>{{ $package->start_date->translatedFormat('d M Y') }} → {{ $package->end_date->translatedFormat('d M Y') }}</dd></div>
                    @endif
                    @if($package->departure_time)
                        <div><dt>Waktu berkumpul</dt><dd>{{ $package->departure_time }}</dd></div>
                    @endif
                    @php($needs = $package->specialNeeds())
                    @if($needs !== [])
                        <div class="trip-summary-wide"><dt>Kebutuhan khusus</dt><dd>{{ collect($needs)->map(fn ($need) => \App\Enums\TripSpecialNeed::tryFrom($need)?->label() ?? $need)->implode(', ') }}</dd></div>
                    @endif
                    @if($package->planDescription())
                        <div class="trip-summary-wide"><dt>Catatan</dt><dd>{{ $package->planDescription() }}</dd></div>
                    @endif
                </dl>
            </section>

            @if($package->rentalOrder)
                <section class="panel">
                    <div class="panel-head">
                        <h2>Pesanan sewa</h2>
                        <div class="panel-head-badges">
                            <x-partials.status-badge :status="$package->rentalOrder->order_status" />
                            <x-partials.payment-badge :status="$package->rentalPaymentStatus()" />
                        </div>
                    </div>
                    <p class="field-hint">{{ $package->rentalOrder->order_code }} · Total {{ rupiah($package->rentalOrder->total) }}</p>
                    <table class="table-plain">
                        <thead><tr><th>Gear</th><th>Qty</th><th>Durasi</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            @foreach($package->rentalOrder->items as $item)
                                <tr>
                                    <td>{{ $item->product_name_snapshot }} — {{ $item->variant_name_snapshot }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->duration_days }} hari</td>
                                    <td>{{ rupiah($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <a class="text-link" href="{{ route('customer.orders.show', $package->rentalOrder) }}">Kelola &amp; bayar pesanan sewa <span aria-hidden="true">→</span></a>
                </section>
            @endif

            @if($package->salesOrder)
                <section class="panel">
                    <div class="panel-head">
                        <h2>Pesanan beli</h2>
                        <div class="panel-head-badges">
                            <x-partials.status-badge :status="$package->salesOrder->order_status" />
                            <x-partials.payment-badge :status="$package->salesPaymentStatus()" />
                        </div>
                    </div>
                    <p class="field-hint">{{ $package->salesOrder->order_code }} · Total {{ rupiah($package->salesOrder->total) }}</p>
                    <table class="table-plain">
                        <thead><tr><th>Gear</th><th>Qty</th><th>Harga/unit</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            @foreach($package->salesOrder->items as $item)
                                <tr>
                                    <td>{{ $item->product_name_snapshot }} — {{ $item->variant_name_snapshot }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ rupiah($item->unit_price_snapshot) }}</td>
                                    <td>{{ rupiah($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <a class="text-link" href="{{ route('customer.sales.show', $package->salesOrder) }}">Kelola &amp; bayar pesanan beli <span aria-hidden="true">→</span></a>
                </section>
            @endif

            @if(! $package->rentalOrder && ! $package->salesOrder)
                <div class="notice notice-warning" role="status">Paket ini belum memiliki pesanan terkait.</div>
            @endif

            <p class="field-hint">Pembayaran diproses per pesanan (sewa dan beli) melalui metode masing-masing. Status paket mengikuti gabungan status kedua pesanan.</p>
        </div>
    </section>
</x-layout>
