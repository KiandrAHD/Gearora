<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paket {{ $package->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px 28px; color: #08213F; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11.5px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #DBE0D7; padding-bottom: 4px; }
        .muted { color: #526267; }
        .head { border-bottom: 2px solid #183D32; padding-bottom: 10px; margin-bottom: 14px; }
        .head .code { font-size: 15px; font-weight: bold; color: #183D32; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #DBE0D7; padding: 5px 7px; text-align: left; }
        th { background: #F7F8F3; font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; }
        td.num, th.num { text-align: right; }
        .summary { width: 100%; margin-top: 12px; }
        .summary td { border: 0; padding: 3px 0; }
        .summary td.label { color: #526267; }
        .summary td.value { text-align: right; font-weight: bold; }
        .note { margin-top: 16px; font-size: 10px; color: #526267; }
    </style>
</head>
<body>
    <div class="head">
        <div class="code">GEARORA · Paket Trip {{ $package->code }}</div>
        <div class="muted">{{ $package->destination }}@if($package->location) · {{ $package->location }}@endif · {{ $package->duration_days }} hari · {{ $package->participants }} peserta</div>
    </div>

    <p><strong>Pemesan:</strong> {{ $package->customer?->name ?? 'Pelanggan GEARORA' }}<br>
        <span class="muted">Status paket: {{ $package->status->label() }} · Aktivitas: {{ \App\Enums\TripActivity::tryFrom($package->activity)?->label() ?? $package->activity }} · Level: {{ $package->experience_level->label() }}</span>
    </p>

    <table class="summary">
        <tr><td class="label">Destinasi</td><td class="value">{{ $package->destination }}@if($package->location) · {{ $package->location }}@endif</td></tr>
        <tr><td class="label">Mode</td><td class="value">{{ $package->mode->label() }}</td></tr>
        <tr><td class="label">Durasi</td><td class="value">{{ $package->duration_days }} hari · {{ $package->participants }} peserta</td></tr>
        @if($package->start_date && $package->end_date)
            <tr><td class="label">Tanggal</td><td class="value">{{ $package->start_date->translatedFormat('d M Y') }} → {{ $package->end_date->translatedFormat('d M Y') }}</td></tr>
        @endif
        @if($package->departure_time)
            <tr><td class="label">Waktu berkumpul</td><td class="value">{{ $package->departure_time }}</td></tr>
        @endif
        @php($needs = $package->specialNeeds())
        @if($needs !== [])
            <tr><td class="label">Kebutuhan khusus</td><td class="value">{{ collect($needs)->map(fn ($need) => \App\Enums\TripSpecialNeed::tryFrom($need)?->label() ?? $need)->implode(', ') }}</td></tr>
        @endif
        @if($package->planDescription())
            <tr><td class="label">Catatan</td><td class="value">{{ $package->planDescription() }}</td></tr>
        @endif
    </table>

    @if($package->rentalOrder)
        <h2>Pesanan sewa · {{ $package->rentalOrder->order_code }}</h2>
        <p class="muted">Status pembayaran: <strong>{{ $package->rentalPaymentStatus()?->documentLabel() ?? 'Belum Dibayar' }}</strong></p>
        <table>
            <thead><tr><th>Gear</th><th>Varian</th><th class="num">Qty</th><th class="num">Hari</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
                @foreach($package->rentalOrder->items as $item)
                    <tr>
                        <td>{{ $item->product_name_snapshot }}</td>
                        <td>{{ $item->variant_name_snapshot }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ $item->duration_days }}</td>
                        <td class="num">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="summary">
            <tr><td class="label">Subtotal sewa</td><td class="value">Rp {{ number_format((float) $package->rentalOrder->subtotal, 0, ',', '.') }}</td></tr>
            <tr><td class="label">Deposit</td><td class="value">Rp {{ number_format((float) $package->rentalOrder->deposit_total, 0, ',', '.') }}</td></tr>
            <tr><td class="label">Total sewa</td><td class="value">Rp {{ number_format((float) $package->rentalOrder->total, 0, ',', '.') }}</td></tr>
        </table>
    @endif

    @if($package->salesOrder)
        <h2>Pesanan beli · {{ $package->salesOrder->order_code }}</h2>
        <p class="muted">Status pembayaran: <strong>{{ $package->salesPaymentStatus()?->documentLabel() ?? 'Belum Dibayar' }}</strong></p>
        <table>
            <thead><tr><th>Gear</th><th>Varian</th><th class="num">Qty</th><th class="num">Harga/unit</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
                @foreach($package->salesOrder->items as $item)
                    <tr>
                        <td>{{ $item->product_name_snapshot }}</td>
                        <td>{{ $item->variant_name_snapshot }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">Rp {{ number_format((float) $item->unit_price_snapshot, 0, ',', '.') }}</td>
                        <td class="num">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="summary">
            <tr><td class="label">Total beli</td><td class="value">Rp {{ number_format((float) $package->salesOrder->total, 0, ',', '.') }}</td></tr>
        </table>
    @endif

    <p class="note">
        Dibuat {{ $package->created_at?->translatedFormat('d F Y H:i') }}. Dokumen ini adalah ringkasan paket, bukan bukti pembayaran.
        Pembayaran diproses melalui pesanan sewa/beli terkait. Harga final dihitung server-side.
    </p>
</body>
</html>
