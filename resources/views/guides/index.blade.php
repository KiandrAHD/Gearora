<x-layout title="Panduan">
    <section class="page-block">
        <div class="page-container">
            <p class="eyebrow">PANDUAN GEARORA</p>
            <h1 class="page-title">Panduan sebelum bertualang.</h1>
            <p class="page-lead">Catatan praktis untuk memilih, menyiapkan, dan merawat perlengkapan outdoor-mu.</p>

            <form method="GET" action="{{ route('guides.index') }}" class="filter-bar">
                <input type="search" name="q" value="{{ $keyword }}" placeholder="Cari panduan..." aria-label="Cari panduan">
                <select name="kategori" aria-label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <button class="button button-secondary" type="submit">Filter</button>
            </form>

            <div class="card-grid">
                @forelse($guides as $guide)
                    <article class="info-card">
                        @if($guide->cover_image_path)
                            <img class="card-image" src="{{ media_url($guide->cover_image_path) }}" alt="" loading="lazy">
                        @endif
                        <p class="product-category">{{ $guide->category?->name ?? 'Panduan' }}</p>
                        <h2><a class="text-link" href="{{ route('guides.show', $guide) }}">{{ $guide->title }}</a></h2>
                        <p>{{ \Illuminate\Support\Str::limit($guide->excerpt ?: \App\Support\GuideContentRenderer::plain($guide->content), 160) }}</p>
                    </article>
                @empty
                    <p class="field-hint">Belum ada panduan dipublikasikan.</p>
                @endforelse
            </div>

            {{ $guides->links() }}
        </div>
    </section>
</x-layout>
