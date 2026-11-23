@props(['category', 'index' => 0])
@php
    $previewProducts = $category->products->take(4)->values();
    $coverImage = $previewProducts->first()?->displayImagePath();
    $coverUrl = $coverImage ? media_url($coverImage) : asset('images/editorial/story-forest.jpg');
    $categoryUrl = route('catalog.index', ['kategori' => $category->slug]);
    $count = (int) $category->products_count;
    $uid = 'gf-' . $category->id;

    $backPath = 'M0 56A22 22 0 0 1 22 34L146 34C170 34 172 0 198 0L318 0A22 22 0 0 1 340 22L340 250A22 22 0 0 1 318 272L22 272A22 22 0 0 1 0 250Z';
    $frontPath = 'M0 22A22 22 0 0 1 22 0L118 0C144 0 146 32 172 32L318 32A22 22 0 0 1 340 54L340 176A22 22 0 0 1 318 198L22 198A22 22 0 0 1 0 176Z';

    $palettes = [
        'linear-gradient(135deg, #143B2E 0%, #265E4A 55%, #8DB095 100%)',
        'linear-gradient(135deg, #0F1E26 0%, #1B4B6B 55%, #5C95B8 100%)',
        'linear-gradient(135deg, #3D281D 0%, #8C4A27 55%, #E08D58 100%)',
        'linear-gradient(135deg, #1F2E28 0%, #435D4E 55%, #A5B8A1 100%)',
    ];
    $fallbackBg = $palettes[$index % count($palettes)];
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
            <div class="gf-back" style="clip-path: path('{{ $backPath }}'); background: {{ $fallbackBg }};" aria-hidden="true">
                <img class="gf-art" src="{{ $coverUrl }}" alt="" width="364" height="296" loading="lazy" draggable="false">
                <div class="gf-back-sheen"></div>
            </div>
            <svg class="gf-rim gf-back-rim" viewBox="0 0 340 272" aria-hidden="true">
                <path d="{{ $backPath }}" />
            </svg>

            {{-- Product Sheets Inside Folder --}}
            <div class="gf-files" role="list" aria-label="Produk {{ $category->name }}" :inert="!open">
                @foreach($previewProducts as $v => $product)
                    @php
                        $totalVisible = $previewProducts->count();
                        $slot = $totalVisible <= 1 ? 0 : ($v / ($totalVisible - 1)) * 2 - 1;
                        $depth = $totalVisible - 1 - $v;
                        $jx = (($v % 2 === 0 ? 1 : -1) * (2.5 + $v)) . 'px';
                        $jr = (($v - 1.5) * 1.4) . 'deg';
                        $imgPath = $product->displayImagePath();
                        $variant = $product->variants->first();
                        $price = $variant?->effectiveRentalPrice() ?? $product->rental_price_per_day;
                    @endphp
                    <div role="listitem" class="gf-sheet-slot">
                        <a
                            href="{{ route('catalog.show', $product) }}"
                            class="gf-sheet gf-sheet--art"
                            style="--gf-slot: {{ $slot }}; --gf-depth: {{ $depth }}; --gf-jx: {{ $jx }}; --gf-jr: {{ $jr }}; z-index: {{ $v + 1 }};"
                        >
                            @if($imgPath)
                                <img class="gf-sheet-art" src="{{ media_url($imgPath) }}" alt="{{ $product->name }}" width="272" height="184" loading="lazy" draggable="false">
                            @endif
                            <span class="gf-sheet-head">
                                <span class="gf-sheet-name">{{ $product->name }}</span>
                                @if($price)
                                    <span class="gf-sheet-meta">{{ rupiah($price) }}/hr</span>
                                @endif
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
                    aria-label="Buka folder {{ $category->name }}, {{ $count }} produk"
                    @click="open = !open"
                ></button>

                <div class="gf-label">
                    <span class="gf-title">{{ $category->name }}</span>
                    <span class="gf-meta">{{ $count }} produk</span>
                </div>
            </div>

            {{-- Action Key: Open Category Page --}}
            <a
                href="{{ $categoryUrl }}"
                class="gf-key gf-key--add"
                title="Lihat semua {{ $count }} produk {{ $category->name }}"
                aria-label="Lihat semua {{ $count }} produk {{ $category->name }}"
            >
                <svg class="gf-glyph gf-glyph--arrow" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </a>
        </div>
    </div>
</div>
