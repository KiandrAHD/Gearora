@props([
    'images' => [],
    'label' => '',
    'maxVisible' => 3,
    'revealCount' => 2,
    'href' => null,
])
@php
    $allImages = array_values($images);
    $rendered = array_slice($allImages, 0, $maxVisible + $revealCount);
    $overflow = max(0, count($allImages) - $maxVisible - $revealCount);
    $slots = $overflow > 0 ? [...$rendered, null] : $rendered;
    $total = count($slots);

    $px = 34;
    $gap = 8;
    $peekPx = (int) round($px * 0.34);
    $collapsedW = $px + (max(0, min($maxVisible, $total) - 1) * $peekPx);
    $spreadW = ($total * $px) + (max(0, $total - 1) * $gap);

    $restRot = [-14, -7, -2, 5, 11, -9, 3, -5];
    $hoverRot = [-8, -4, -1, 2, 6, -6, 2, -3];
@endphp

<{{ $href ? 'a' : 'div' }}
    @if($href) href="{{ $href }}" @endif
    class="ib-pill"
    x-data="{ hovered: false }"
    @mouseenter="hovered = true"
    @mouseleave="hovered = false"
    @focusin="hovered = true"
    @focusout="hovered = false"
    :class="{ 'is-hovered': hovered }"
>
    <span
        class="ib-strip"
        style="--ib-collapsed-w: {{ $collapsedW }}px; --ib-spread-w: {{ $spreadW }}px; height: {{ $px + 6 }}px;"
    >
        @foreach($slots as $i => $slot)
            @php
                $isHidden = $i >= $maxVisible && $slot !== null;
                $isOverflow = $slot === null;

                $collapsedX = $i >= $maxVisible ? (($maxVisible - 1) * $peekPx) : ($i * $peekPx);
                $spreadX = $i * ($px + $gap);

                $mid = ($total - 1) / 2;
                $t = $total > 1 ? (($i - $mid) / $mid) : 0;
                $arcY = round($t * $t * ($px * 0.2), 1);

                $rRot = $restRot[$i] ?? 0;
                $hRot = $hoverRot[$i] ?? 0;
                $zRest = $total - $i;
                $zHover = $i + 1;
                $delay = $isHidden ? (($i - $maxVisible) * 60) : 0;
            @endphp
            <span
                class="ib-card {{ $isOverflow ? 'ib-card--overflow' : '' }} {{ $isHidden ? 'ib-card--hidden' : '' }}"
                style="
                    width: {{ $px }}px;
                    height: {{ $px }}px;
                    --ib-cx: {{ $collapsedX }}px;
                    --ib-sx: {{ $spreadX }}px;
                    --ib-sy: {{ $arcY }}px;
                    --ib-cr: {{ $rRot }}deg;
                    --ib-hr: {{ $hRot }}deg;
                    --ib-zr: {{ $zRest }};
                    --ib-zh: {{ $zHover }};
                    --ib-delay: {{ $delay }}ms;
                "
            >
                @if($isOverflow)
                    <span class="ib-overflow-count">+{{ $overflow }}</span>
                @else
                    <img src="{{ $slot['src'] }}" alt="{{ $slot['alt'] ?? '' }}" width="{{ $px }}" height="{{ $px }}" draggable="false">
                @endif
            </span>
        @endforeach
    </span>

    @if($label)
        <span class="ib-label">{{ $label }}</span>
    @endif
</{{ $href ? 'a' : 'div' }}>
