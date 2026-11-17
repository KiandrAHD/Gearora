@php
    $guides = [
        ['title' => 'Menyiapkan tenda untuk hujan tropis', 'topic' => 'Camping', 'summary' => 'Cara memiliki dan merawat tenda yang tahan terhadap hujan dan angin.'],
        ['title' => 'Memilih carrier sesuai durasi perjalanan', 'topic' => 'Trekking', 'summary' => 'Kapasitas, bobot, dan kenyamanan yang harus kamu perhatikan.'],
        ['title' => 'Merawat gear setelah digunakan', 'topic' => 'Perawatan', 'summary' => 'Kebiasaan sederhana agar gear awet dan siap dipakai kembali.'],
    ];
@endphp
<x-layout title="Panduan">
    <section class="page-block">
        <div class="page-container">
            <p class="eyebrow">PANDUAN GEARORA</p>
            <h1 class="page-title">Panduan sebelum bertualang.</h1>
            <p class="page-lead">Catatan praktis untuk memilih, menyiapkan, dan merawat perlengkapan outdoor-mu. Naskah panduan lengkap sedang disusun bersama tim.</p>
            <div class="card-grid">
                @foreach($guides as $guide)
                    <article class="info-card"><p class="product-category">{{ $guide['topic'] }}</p><h2>{{ $guide['title'] }}</h2><p>{{ $guide['summary'] }}</p><span class="badge-inline">Segera hadir</span></article>
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
