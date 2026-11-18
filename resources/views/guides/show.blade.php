<x-layout :title="$guide->seo_title ?: $guide->title">
    <article class="page-block">
        <div class="page-container prose-container">
            <p class="eyebrow">{{ $guide->category?->name ?? 'Panduan' }}</p>
            <h1 class="page-title">{{ $guide->title }}</h1>
            <p class="field-hint">
                @if($guide->author) Oleh {{ $guide->author->name }} · @endif
                @if($guide->published_at) {{ $guide->published_at->translatedFormat('d F Y') }} @endif
            </p>

            @if($guide->cover_image_path)
                <img class="guide-cover" src="{{ media_url($guide->cover_image_path) }}" alt="">
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
                    <div class="card-grid">
                        @foreach($related as $item)
                            <article class="info-card"><h3><a class="text-link" href="{{ route('guides.show', $item) }}">{{ $item->title }}</a></h3></article>
                        @endforeach
                    </div>
                </section>
            @endif

            <p class="field-hint">Panduan bersifat informasi umum, bukan pengganti pelatihan atau instruksi keselamatan resmi.</p>
        </div>
    </article>
</x-layout>
