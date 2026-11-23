@props(['product', 'index' => 0])
@php
    $variant = $product->variants->first();
    $rentalPrice = $variant?->effectiveRentalPrice() ?? $product->rental_price_per_day;
    $salePrice = $variant?->effectiveSalePrice() ?? $product->sale_price;
    $imagePath = $product->displayImagePath();
    $imageUrl = $imagePath ? media_url($imagePath) : asset('images/editorial/story-forest.jpg');
    $productUrl = route('catalog.show', $product);
    $uid = 'gfp-' . $product->id;

    $backPath = 'M0 56A22 22 0 0 1 22 34L146 34C170 34 172 0 198 0L318 0A22 22 0 0 1 340 22L340 250A22 22 0 0 1 318 272L22 272A22 22 0 0 1 0 250Z';
    $frontPath = 'M0 22A22 22 0 0 1 22 0L118 0C144 0 146 32 172 32L318 32A22 22 0 0 1 340 54L340 176A22 22 0 0 1 318 198L22 198A22 22 0 0 1 0 176Z';

    $metaLine = $rentalPrice
        ? rupiah($rentalPrice) . ' / hari'
        : ($salePrice ? rupiah($salePrice) . ' / unit' : 'Lihat detail');

    $sheets = [
        [
            'name' => $product->category?->name ?? 'Outdoor',
            'meta' => 'Terinspeksi',
            'image' => null,
        ],
    ];
    if ($product->supportsSale() && $salePrice) {
        $sheets[] = [
            'name' => 'Beli Unit Baru',
            'meta' => rupiah($salePrice),
            'image' => $imageUrl,
        ];
    }
    if ($product->supportsRental() && $rentalPrice) {
        $sheets[] = [
            'name' => 'Sewa Harian',
            'meta' => rupiah($rentalPrice) . '/hr',
            'image' => $imageUrl,
        ];
    }
@endphp

<div
    class="gf-root"
    data-tone="light"
    data-place="bottom-left"
    data-entered="true"
    x-data="{
        open: false,
        raf: 0,
        px: 0,
        py: 0,
        onMove(e) {
            if (e.pointerType === 'touch') return;
            const r = this.$el.getBoundingClientRect();
            this.px = ((e.clientX - r.left) / r.width) * 2 - 1;
            this.py = ((e.clientY - r.top) / r.height) * 2 - 1;
            if (!this.raf) {
                this.raf = requestAnimationFrame(() => {
                    this.raf = 0;
                    this.$el.style.setProperty('--gf-px', this.px.toFixed(3));
                    this.$el.style.setProperty('--gf-py', this.py.toFixed(3));
                });
            }
        },
        onLeave() {
            this.px = 0;
            this.py = 0;
            this.$el.style.setProperty('--gf-px', '0');
            this.$el.style.setProperty('--gf-py', '0');
        }
    }"
    :data-open="open ? 'true' : null"
    @pointermove="onMove($event)"
    @pointerleave="onLeave()"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <div class="gf-stage">
        <div class="gf-tilt">
            <div class="gf-shadow" aria-hidden="true"></div>

            {{-- Back Panel --}}
            <div class="gf-back" style="clip-path: path('{{ $backPath }}');" aria-hidden="true">
                <img class="gf-art" src="{{ $imageUrl }}" alt="" width="364" height="296" loading="lazy" draggable="false">
                <div class="gf-back-sheen"></div>
            </div>
            <svg class="gf-rim gf-back-rim" viewBox="0 0 340 272" aria-hidden="true">
                <path d="{{ $backPath }}" />
            </svg>

            {{-- Product Option Sheets Inside Folder --}}
            <div class="gf-files" role="list" aria-label="Opsi {{ $product->name }}" :inert="!open">
                @foreach($sheets as $v => $sheet)
                    @php
                        $totalVisible = count($sheets);
                        $slot = $totalVisible <= 1 ? 0 : ($v / ($totalVisible - 1)) * 2 - 1;
                        $depth = $totalVisible - 1 - $v;
                        $jx = (($v % 2 === 0 ? 1 : -1) * (2.5 + $v)) . 'px';
                        $jr = (($v - 1) * 1.6) . 'deg';
                    @endphp
                    <div role="listitem" class="gf-sheet-slot">
                        <a
                            href="{{ $productUrl }}"
                            class="gf-sheet {{ $sheet['image'] ? 'gf-sheet--art' : 'gf-sheet--paper' }}"
                            style="--gf-slot: {{ $slot }}; --gf-depth: {{ $depth }}; --gf-jx: {{ $jx }}; --gf-jr: {{ $jr }}; z-index: {{ $v + 1 }};"
                        >
                            @if($sheet['image'])
                                <img class="gf-sheet-art" src="{{ $sheet['image'] }}" alt="{{ $product->name }}" width="272" height="184" loading="lazy" draggable="false">
                            @else
                                <span class="gf-lines" aria-hidden="true">
                                    <i style="width: 86%"></i>
                                    <i style="width: 92%"></i>
                                    <i style="width: 64%"></i>
                                    <i style="width: 88%"></i>
                                    <i style="width: 42%"></i>
                                </span>
                            @endif
                            <span class="gf-sheet-head">
                                <span class="gf-sheet-name">{{ $sheet['name'] }}</span>
                                <span class="gf-sheet-meta">{{ $sheet['meta'] }}</span>
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Frosted Glass Front Panel --}}
            <div class="gf-front" style="clip-path: path('{{ $frontPath }}')">
                <div class="gf-tint" aria-hidden="true"></div>
                <div class="gf-spec" aria-hidden="true"></div>
                <svg class="gf-rim gf-front-rim" viewBox="0 0 340 198" aria-hidden="true">
                    <defs>
                        <linearGradient id="{{ $uid }}-rim" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#fff" stop-opacity="0.95" />
                            <stop offset="0.35" stop-color="#fff" stop-opacity="0.35" />
                            <stop offset="1" stop-color="#fff" stop-opacity="0.12" />
                        </linearGradient>
                    </defs>
                    <path d="{{ $frontPath }}" stroke="url(#{{ $uid }}-rim)" />
                </svg>

                <button
                    type="button"
                    class="gf-hit"
                    :aria-expanded="open.toString()"
                    aria-label="Buka kartu {{ $product->name }}, {{ $metaLine }}"
                    @click="open = !open"
                ></button>

                <div class="gf-label">
                    <span class="gf-title">{{ $product->name }}</span>
                    <span class="gf-meta">{{ $metaLine }}</span>
                </div>
            </div>

            {{-- Action Key: Go to Product Detail --}}
            <a
                href="{{ $productUrl }}"
                class="gf-key gf-key--add"
                title="Lihat detail {{ $product->name }}"
                aria-label="Lihat detail {{ $product->name }}"
            >
                <svg class="gf-glyph gf-glyph--arrow" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </a>
        </div>
    </div>
</div>
