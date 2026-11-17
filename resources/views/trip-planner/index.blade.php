@php
    $recommendations = [
        ['label' => 'Camping akhir pekan', 'items' => 'Tenda, sleeping bag, kompor', 'note' => 'Cocok untuk 2–4 orang.'],
        ['label' => 'Trekking gunung', 'items' => 'Carrier, matras, jaket', 'note' => 'Prioritaskan bobot ringan.'],
        ['label' => 'Piknik keluarga', 'items' => 'Tenda, meja lipat, lampu', 'note' => 'Setup cepat dan mudah.'],
    ];
@endphp
<x-layout title="Rekomendasi Trip">
    <section class="page-block">
        <div class="page-container">
            <p class="eyebrow">GEARORA TRIP PLANNER</p>
            <h1 class="page-title">Bingung harus bawa apa?</h1>
            <p class="page-lead">Ceritakan rencana perjalananmu dan pilih gear yang paling sesuai. Rekomendasi di bawah adalah panduan awal; tim GEARORA akan membantu penyesuaian saat pemesanan.</p>
            <div class="card-grid">
                @foreach($recommendations as $recommendation)
                    <article class="info-card"><h2>{{ $recommendation['label'] }}</h2><p>{{ $recommendation['items'] }}</p><p class="field-hint">{{ $recommendation['note'] }}</p><a class="text-link" href="{{ route('catalog.index', ['mode' => 'sewa']) }}">Lihat gear rental <span aria-hidden="true">→</span></a></article>
                @endforeach
            </div>
            <p class="field-hint">Formulir rekomendasi otomatis penuh akan hadir pada pengembangan berikutnya. Sementara ini, hubungi admin untuk bantuan pemilihan gear.</p>
        </div>
    </section>
</x-layout>
