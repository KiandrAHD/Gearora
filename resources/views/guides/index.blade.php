<x-layout title="Panduan">
    <section class="guide-library-hero">
        <div class="page-container guide-library-hero-inner">
            <p class="eyebrow">FIELD NOTES · GEARORA</p>
            <h1 class="page-title">Bekal pengetahuan<br>sebelum melangkah.</h1>
            <p class="page-lead">Panduan memilih, menyiapkan, dan merawat perlengkapan outdoor. Ditulis ringkas, bersumber, dan siap dibawa ke jalur.</p>
        </div>
    </section>

    <section class="page-block guide-library-content">
        <div class="page-container">
            <form method="GET" action="{{ route('guides.index') }}" class="guide-library-filter">
                <input type="search" name="q" value="{{ $keyword }}" placeholder="Cari panduan..." aria-label="Cari panduan">
                <select name="kategori" aria-label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <button class="button button-secondary" type="submit">Filter</button>
            </form>

            @if($featured->isNotEmpty() && $keyword === '' && $activeCategory === '')
                <section class="guide-featured" aria-label="Panduan unggulan">
                    @foreach($featured as $index => $item)
                        <article class="guide-featured-card {{ $index === 0 ? 'is-lead' : '' }}">
                            @if($item->cover_image_path)
                                <img class="guide-featured-cover" src="{{ media_url($item->cover_image_path) }}" alt="" loading="lazy" width="640" height="400">
                            @endif
                            <div class="guide-featured-body">
                                <p class="product-category">{{ $item->category?->name ?? 'Panduan' }} · {{ $item->readingMinutes() }} menit baca</p>
                                <h2><a href="{{ route('guides.show', $item) }}">{{ $item->title }}</a></h2>
                                <p>{{ \Illuminate\Support\Str::limit($item->excerpt ?: \App\Support\GuideContentRenderer::plain($item->content), 180) }}</p>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif

            <div class="guide-library-heading">
                <div><h2>Semua panduan</h2><p>Pilih satu catatan, simpan ilmunya.</p></div>
                @if($guides->total() > 0)<p>{{ $guides->total() }} artikel</p>@endif
            </div>

            <div class="guide-grid">
                @forelse($guides as $guide)
                    <article class="guide-card">
                        <a class="guide-card-photo" href="{{ route('guides.show', $guide) }}" aria-label="Baca {{ $guide->title }}">
                            @if($guide->cover_image_path)
                                <img src="{{ media_url($guide->cover_image_path) }}" alt="" loading="lazy" decoding="async" width="400" height="260">
                            @else
                                <span class="guide-card-photo-fallback" aria-hidden="true">Panduan</span>
                            @endif
                        </a>
                        <div class="guide-card-body">
                            <p class="product-category">{{ $guide->category?->name ?? 'Panduan' }}</p>
                            <h3><a href="{{ route('guides.show', $guide) }}">{{ $guide->title }}</a></h3>
                            <p>{{ \Illuminate\Support\Str::limit($guide->excerpt ?: \App\Support\GuideContentRenderer::plain($guide->content), 140) }}</p>
                            <p class="field-hint">{{ $guide->readingMinutes() }} menit baca @if($guide->published_at) · {{ $guide->published_at->translatedFormat('d M Y') }} @endif</p>
                        </div>
                    </article>
                @empty
                    <p class="field-hint">Belum ada panduan dipublikasikan.</p>
                @endforelse
            </div>

            <div class="pagination-wrap">{{ $guides->links() }}</div>
        </div>
    </section>
</x-layout>
