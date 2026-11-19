<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24mm 16mm 20mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #08213F; }
        header { position: fixed; top: -18mm; left: 0; right: 0; height: 14mm; border-bottom: 2px solid #183D32; }
        .brand { font-size: 13px; font-weight: bold; color: #183D32; letter-spacing: 1px; }
        .tagline { font-size: 8px; color: #526267; }
        footer { position: fixed; bottom: -13mm; left: 0; right: 0; border-top: 1px solid #DBE0D7; padding-top: 5px; color: #526267; font-size: 8px; }
        .page-number:after { content: counter(page); }
        h1 { margin: 6px 0; font-size: 16px; color: #08213F; }
        .meta { margin-bottom: 14px; color: #526267; }
        .summary { width: 100%; border-collapse: collapse; margin: 8px 0 16px; }
        .summary td { padding: 5px 7px; border: 1px solid #DBE0D7; }
        .summary td:first-child { width: 34%; background: #F2F4EF; }
        table.data { width: 100%; border-collapse: collapse; table-layout: auto; }
        .data thead { display: table-header-group; }
        .data th { background: #183D32; color: #fff; text-align: left; padding: 6px 5px; font-size: 8px; }
        .data td { padding: 5px; border-bottom: 1px solid #DBE0D7; word-wrap: break-word; overflow-wrap: anywhere; }
        .empty { padding: 18px; text-align: center; color: #526267; background: #F2F4EF; }
        .money { text-align: right; white-space: nowrap; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
<header><div class="brand">GEARORA — OUTDOOR SUPPLY</div><div class="tagline">Gear Up. Explore More.</div></header>
<footer>Laporan internal · Dibuat {{ now(config('app.timezone'))->format('d M Y H:i') }} WIB · Halaman <span class="page-number"></span></footer>
<main>
    <h1>{{ $title }}</h1>
    <div class="meta">Periode: {{ \Carbon\CarbonImmutable::parse($filters['start_date'])->locale('id')->translatedFormat('d F Y') }} — {{ \Carbon\CarbonImmutable::parse($filters['end_date'])->locale('id')->translatedFormat('d F Y') }}<br>
    Diekspor: {{ now(config('app.timezone'))->locale('id')->translatedFormat('d F Y H:i') }} WIB · Disiapkan oleh: {{ $preparedBy }}</div>

    @php
        $renderMetric = function ($key, $value) use ($moneyMetrics) {
            if (is_array($value) || is_bool($value) || $value === null) {
                return null;
            }
            if (in_array($key, $moneyMetrics, true) && is_numeric($value)) {
                return rupiah((int) $value);
            }
            if (is_numeric($value)) {
                return is_float($value + 0) ? number_format($value, 1, ',', '.') : number_format((int) $value, 0, ',', '.');
            }
            return (string) $value;
        };
    @endphp
    @if(!empty($report['metrics']))
        <table class="summary"><tbody>
            @foreach($report['metrics'] as $key => $value)
                @if(is_array($value))
                    @foreach($value as $subKey => $subValue)
                        @php $rendered = $renderMetric($subKey, $subValue); @endphp
                        @if($rendered !== null)
                            <tr><td>{{ str_replace('_', ' ', ucfirst($key)) }} — {{ str_replace('_', ' ', $subKey) }}</td><td>{{ $rendered }}</td></tr>
                        @endif
                    @endforeach
                @else
                    @php $rendered = $renderMetric($key, $value); @endphp
                    @if($rendered !== null)
                        <tr><td>{{ str_replace('_', ' ', ucfirst($key)) }}</td><td>{{ $rendered }}</td></tr>
                    @endif
                @endif
            @endforeach
        </tbody></table>
    @endif

    @if(count($report['table']) === 0)
        <div class="empty">Belum ada data pada periode yang dipilih.</div>
    @else
        @php $keys = array_keys($report['table'][0]); @endphp
        <table class="data">
            <thead><tr>@foreach($keys as $key)<th>{{ str_replace('_', ' ', ucfirst($key)) }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach($report['table'] as $row)
                    <tr>
                        @foreach($keys as $key)
                            @php $value = $row[$key] ?? null; @endphp
                            <td @if(in_array($key, $currencyKeys, true)) class="money" @endif>{{ in_array($key, $currencyKeys, true) ? rupiah((int) $value) : (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) ($value ?? '—')) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</main>
</body>
</html>
