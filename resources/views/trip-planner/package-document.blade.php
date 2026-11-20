<x-layout :title="'Dokumen Paket '.$package->code">
    <section class="page-block">
        <div class="page-container prose-container">
            <div class="document-toolbar">
                <a class="text-link" href="{{ route('customer.packages.show', $package) }}">← Kembali ke paket</a>
                <a class="button button-primary" href="{{ route('customer.packages.pdf', $package) }}">Unduh PDF</a>
                <button class="button" type="button" onclick="window.print()">Cetak</button>
            </div>

            <article class="package-document">
                <header class="package-document-head">
                    <p class="eyebrow">GEARORA</p>
                    <h1>Paket Trip {{ $package->code }}</h1>
                    <p class="field-hint">
                        {{ $package->destination }}@if($package->location) · {{ $package->location }}@endif ·
                        {{ $package->duration_days }} hari · {{ $package->participants }} peserta ·
                        {{ $package->status->label() }}
                    </p>
                </header>

                <dl class="trip-summary">
                    <div><dt>Pemesan</dt><dd>{{ $package->customer?->name ?? 'Pelanggan GEARORA' }}</dd></div>
                    <div><dt>Aktivitas</dt><dd>{{ \App\Enums\TripActivity::tryFrom($package->activity)?->label() ?? $package->activity }}</dd></div>
                    <div><dt>Level</dt><dd>{{ $package->experience_level->label() }}</dd></div>
                    <div><dt>Mode</dt><dd>{{ $package->mode->label() }}</dd></div>
                    @if($package->start_date && $package->end_date)
                        <div><dt>Tanggal</dt><dd>{{ $package->start_date->translatedFormat('d M Y') }} → {{ $package->end_date->translatedFormat('d M Y') }}</dd></div>
                    @endif
                </dl>

                @if($package->rentalOrder)
                    <section class="document-section">
                        <h2>Pesanan sewa · {{ $package->rentalOrder->order_code }}</h2>
                        <table class="table-plain">
                            <thead><tr><th>Gear</th><th>Qty</th><th>Hari</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                @foreach($package->rentalOrder->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name_snapshot }} — {{ $item->variant_name_snapshot }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->duration_days }}</td>
                                        <td>{{ rupiah($item->line_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="document-total">Total sewa: <strong>{{ rupiah($package->rentalOrder->total) }}</strong></p>
                    </section>
                @endif

                @if($package->salesOrder)
                    <section class="document-section">
                        <h2>Pesanan beli · {{ $package->salesOrder->order_code }}</h2>
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
                        <p class="document-total">Total beli: <strong>{{ rupiah($package->salesOrder->total) }}</strong></p>
                    </section>
                @endif

                <p class="field-hint">Dibuat {{ $package->created_at?->translatedFormat('d F Y H:i') }}. Dokumen ini adalah ringkasan paket, bukan bukti pembayaran.</p>
            </article>
        </div>
    </section>
</x-layout>
