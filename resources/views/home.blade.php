<x-layout title="Gear Up. Explore More.">
    {{-- Full-bleed Photographic Hero (Clean Adventure) --}}
    <section class="hero-cinema" id="tentang">
        <div class="hero-cinema-bg" style="background-image: url('{{ asset('images/editorial/hero-mountain.jpg') }}')" aria-hidden="true"></div>
        <div class="hero-cinema-scrim" aria-hidden="true"></div>

        <div class="page-container hero-cinema-inner">
            <div class="hero-cinema-copy">
                <div class="mb-5">
                    <x-partials.images-badge
                        label="Gear terinspeksi · Siap untuk setiap medan"
                        :href="route('catalog.index', ['mode' => 'sewa'])"
                        :images="[
                            ['src' => asset('images/editorial/story-trail.jpg'), 'alt' => 'Jalur pendakian'],
                            ['src' => asset('images/editorial/story-lake.jpg'), 'alt' => 'Danau pegunungan'],
                            ['src' => asset('images/editorial/story-forest.jpg'), 'alt' => 'Hutan pinus'],
                            ['src' => asset('images/editorial/story-group.jpg'), 'alt' => 'Petualangan tim'],
                            ['src' => asset('images/editorial/story-chill.jpg'), 'alt' => 'Istirahat di puncak'],
                            ['src' => asset('images/editorial/trip-banner.jpg'), 'alt' => 'Camping alpine'],
                        ]"
                    />
                </div>

                <h1>Lebih dekat dengan alam.<br>Lebih siap bertualang.</h1>

                <p class="hero-cinema-desc">
                    Sewa atau beli perlengkapan outdoor terkurasi. Bersih, terawat, dan siap menemani cerita perjalananmu dari lembah hingga puncak.
                </p>

                <div class="hero-cinema-actions">
                    <a class="btn-hero-white" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">
                        Mulai Sewa Gear <span aria-hidden="true">→</span>
                    </a>
                    <a class="btn-glass" href="{{ route('trip-planner.index') }}">
                        Bantu Pilihkan Gear <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Floating Integrated Search Bar --}}
        <div class="page-container hero-search-wrap">
            <form class="hero-search-bar" action="{{ route('catalog.index') }}" method="get">
                <label class="search-field">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m16 16 4.5 4.5"/></svg>
                    <span>
                        <small>Mau cari gear apa?</small>
                        <input name="q" type="search" placeholder="Tenda, carrier, jaket, kompor...">
                    </span>
                </label>

                <label class="search-field date-field">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                    <span>
                        <small>Tanggal mulai petualangan</small>
                        <input type="date" name="start_date" aria-label="Tanggal mulai">
                    </span>
                </label>

                <button class="search-submit" type="submit">Cari Gear</button>
            </form>
        </div>
    </section>

    {{-- Value Propositions Strip --}}
    <section class="page-container value-strip" aria-label="Keunggulan GEARORA">
        <div class="value-item">
            <span class="value-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5c0 5 3.2 8.3 7.5 10 4.3-1.7 7.5-5 7.5-10V6L12 3Z"/><path d="m8.5 12 2.3 2.3 4.8-5"/></svg>
            </span>
            <div>
                <strong>Gear Terinspeksi</strong>
                <small>Dicek fungsi &amp; kebersihan sebelum serah terima.</small>
            </div>
        </div>
        <div class="value-item">
            <span class="value-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
            </span>
            <div>
                <strong>Cek Stok Real-Time</strong>
                <small>Ketersediaan unit akurat sesuai tanggal trip.</small>
            </div>
        </div>
        <div class="value-item">
            <span class="value-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 21s-6.5-4.8-6.5-10.5a6.5 6.5 0 1 1 13 0C18.5 16.2 12 21 12 21Z"/><circle cx="12" cy="10.5" r="2.2"/></svg>
            </span>
            <div>
                <strong>Self Pickup Praktis</strong>
                <small>Ambil &amp; kembalikan gear langsung di satu titik.</small>
            </div>
        </div>
        <div class="value-item">
            <span class="value-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>
            </span>
            <div>
                <strong>Panduan &amp; Trip Planner</strong>
                <small>Rekomendasi paket gear otomatis sesuai medan.</small>
            </div>
        </div>
    </section>

    {{-- Categories (Interactive Glass Folder Cards) --}}
    <section class="page-container section-block categories-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">EXPLORE THE GEAR</p>
                <h2>Mulai dari kebutuhanmu.</h2>
                <p class="folder-hint">Klik folder kategori untuk mengintip isi alat, atau tekan tombol panah untuk melihat seluruh koleksi.</p>
            </div>
            <a class="text-link" href="{{ route('catalog.index') }}">Lihat semua kategori <span aria-hidden="true">→</span></a>
        </div>
        <div class="folder-grid">
            @forelse($categories as $category)
                <x-partials.glass-folder :category="$category" :index="$loop->index" />
            @empty
                <p class="empty-note">Kategori gear akan tampil di sini.</p>
            @endforelse
        </div>
    </section>

    {{-- Featured Products (Glass Folder Cards) --}}
    <section class="featured-section">
        <div class="page-container section-block">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">MOST LOVED</p>
                    <h2>Gear favorit petualang.</h2>
                    <p class="folder-hint">Klik kartu untuk melihat opsi sewa &amp; beli, atau tekan panah untuk membuka detail produk.</p>
                </div>
                <a class="text-link" href="{{ route('catalog.index') }}">Jelajahi katalog <span aria-hidden="true">→</span></a>
            </div>
            <div class="folder-grid">
                @forelse($featuredProducts as $product)
                    <x-partials.glass-folder-product :product="$product" :index="$loop->index" />
                @empty
                    <p class="empty-note">Produk unggulan akan tampil setelah katalog tersedia.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Field Stories Bento & 3-Step Rental Flow --}}
    <section class="page-container adventure-showcase">
        <div class="showcase-left">
            <p class="eyebrow">RENT WITH CONFIDENCE</p>
            <h2>Petualangan hebat dimulai dari persiapan yang tenang.</h2>
            <p class="showcase-lead">Tidak perlu beli semua perlengkapan mahal hanya untuk satu kali pendakian. Sewa yang kamu butuh, atau miliki gear andalanmu selamanya.</p>

            <div class="step-list">
                <article class="step-item">
                    <span class="step-num">01</span>
                    <div>
                        <h3>Pilih gear sesuai medan</h3>
                        <p>Filter berdasarkan kategori atau gunakan Trip Planner kami.</p>
                    </div>
                </article>
                <article class="step-item">
                    <span class="step-num">02</span>
                    <div>
                        <h3>Tentukan tanggal perjalanan</h3>
                        <p>Sistem mengecek ketersediaan unit secara otomatis tanpa bentrok.</p>
                    </div>
                </article>
                <article class="step-item">
                    <span class="step-num">03</span>
                    <div>
                        <h3>Ambil &amp; jelajahi alam</h3>
                        <p>Gear siap pakai di lokasi pickup, kembalikan setelah trip selesai.</p>
                    </div>
                </article>
            </div>

            <div class="showcase-actions">
                <a class="btn-primary" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Jelajahi Katalog Sewa <span aria-hidden="true">→</span></a>
                <a class="text-link" href="{{ route('guides.index') }}">Baca panduan outdoor <span aria-hidden="true">↗</span></a>
            </div>
        </div>

        <div class="showcase-bento" aria-label="Galeri petualangan GEARORA">
            <figure class="bento-card bento-tall">
                <img src="{{ asset('images/editorial/story-trail.jpg') }}" alt="Pendaki berjalan di jalur pegunungan menuju puncak" loading="lazy">
                <figcaption>Alpine Trail · Siap mendaki</figcaption>
            </figure>
            <figure class="bento-card">
                <img src="{{ asset('images/editorial/story-lake.jpg') }}" alt="Petualang memandang danau pegunungan" loading="lazy">
                <figcaption>Camp &amp; Views</figcaption>
            </figure>
            <figure class="bento-card">
                <img src="{{ asset('images/editorial/story-forest.jpg') }}" alt="Menjelajah hutan pinus berkabut" loading="lazy">
                <figcaption>Forest Mist</figcaption>
            </figure>
        </div>
    </section>

    {{-- Photographic Trip Planner Banner --}}
    <section class="page-container trip-cinema-wrap">
        <div class="trip-cinema-banner">
            <div class="trip-cinema-bg" style="background-image: url('{{ asset('images/editorial/trip-banner.jpg') }}')" aria-hidden="true"></div>
            <div class="trip-cinema-scrim" aria-hidden="true"></div>
            <div class="trip-cinema-copy">
                <p class="eyebrow">GEARORA TRIP PLANNER</p>
                <h2>Bingung harus bawa apa untuk trip berikutnya?</h2>
                <p>Ceritakan destinasi, durasi, dan tingkat pengalamanmu. Dapatkan daftar rekomendasi perlengkapan yang pas dan langsung bisa dipesan.</p>
                <a class="btn-trail" href="{{ route('trip-planner.index') }}">Buat Rekomendasi Trip <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>
</x-layout>