@props([
    'headers' => [],
    'caption' => null,
    'empty' => null,
])
@php
    $columnCount = max(count($headers), 1);
@endphp
<div class="overflow-x-auto rounded-xl border border-line bg-white">
    <table class="min-w-full divide-y divide-line text-left text-sm">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        @if (! empty($headers))
            <thead class="bg-cloud text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-line">
            @if ($empty)
                <tr>
                    <td colspan="{{ $columnCount }}" class="px-4 py-10 text-center text-slate-600">{{ $empty }}</td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>