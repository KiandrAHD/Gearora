<x-layout :title="$guide->seo_title ?: $guide->title" :description="$guide->seo_description ?: ($guide->excerpt ?: \App\Support\GuideContentRenderer::plain($guide->content))">
    <section class="guide-detail-hero">
        <div class="page-container guide-hero-inner">
            <p class="eyebrow">{{ $guide->category?->name ?? 'Panduan' }}</p>
            <h1 class="page-title">{{ $guide->title }}</h1>
            <p class="field-hint">
                @if($guide->author) Oleh {{ $guide->author->name }} · @endif
                @if($guide->published_at) {{ $guide->published_at->translatedFormat('d F Y') }} · @endif
                {{ $guide->readingMinutes() }} menit baca
            </p>
        </div>
    </section>

    <article class="page-block">
        <div class="page-container prose-container">
            @if($guide->cover_image_path)
                <img class="guide-cover" src="{{ media_url($guide->cover_image_path) }}" alt="" width="960" height="540">
            @endif

            @if($guide->excerpt)
                <p class="guide-lede">{{ $guide->excerpt }}</p>
            @endif

            <div class="guide-content">
                {!! $renderedContent !!}
            </div>

            @if($guide->sources->isNotEmpty())
                <section class="guide-sources">
                    <h2>Sumber</h2>
                    <ol>
                        @foreach($guide->sources as $source)
                            <li>
                                {{ $source->source_title }}
                                @if($source->publisher) — {{ $source->publisher }} @endif
                                @if($source->source_url)
                                    <a class="text-link" href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer nofollow">tautan</a>
                                @endif
                                @if($source->accessed_at) <span class="field-hint">(diakses {{ $source->accessed_at->translatedFormat('d F Y') }})</span> @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if($related->isNotEmpty())
                <section class="guide-related">
                    <h2>Panduan terkait</h2>
                    <div class="guide-grid">
                        @foreach($related as $item)
                            <article class="guide-card">
                                <div class="guide-card-body">
                                    <p class="product-category">{{ $item->category?->name ?? 'Panduan' }}</p>
                                    <h3><a href="{{ route('guides.show', $item) }}">{{ $item->title }}</a></h3>
                                    <p class="field-hint">{{ $item->readingMinutes() }} menit baca</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <p class="guide-safety-note">Panduan bersifat informasi umum, bukan pengganti pelatihan atau instruksi keselamatan resmi. Cek cuaca, kondisi jalur, dan arahan pengelola kawasan sebelum berangkat.</p>
        </div>
    </article>
</x-layout>
