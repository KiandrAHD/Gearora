<x-layout title="Rekomendasi Trip">
    <section class="page-block">
        <div class="page-container">
            <p class="eyebrow">GEARORA TRIP PLANNER</p>
            <h1 class="page-title">Bingung harus bawa apa?</h1>
            <p class="page-lead">Isi rencana perjalanan. Sistem memberi rekomendasi gear berbasis aturan katalog.</p>

            @if ($errors->any())
                <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
            @endif

            @php($plan = $plan ?? null)
            <form method="POST" action="{{ route('trip-planner.recommend') }}" class="form-card">
                @csrf
                <div class="form-grid">
                    <label class="field"><span>Aktivitas</span><select name="activity" required>@foreach(\App\Enums\TripActivity::cases() as $activity)<option value="{{ $activity->value }}" @selected(old('activity', $plan?->activity ?? '') === $activity->value)>{{ $activity->label() }}</option>@endforeach</select></label>
                    <label class="field"><span>Destinasi</span><input name="destination" value="{{ old('destination', $plan?->destination ?? '') }}" required maxlength="120"></label>
                    <label class="field"><span>Durasi (hari)</span><input type="number" name="duration_days" min="1" max="60" value="{{ old('duration_days', $plan?->durationDays ?? 1) }}" required></label>
                    <label class="field"><span>Peserta</span><input type="number" name="participants" min="1" max="100" value="{{ old('participants', $plan?->participants ?? 1) }}" required></label>
                    <label class="field"><span>Mode</span><select name="mode" required><option value="sewa" @selected(old('mode', $plan?->mode->value ?? '') === 'sewa')>Sewa</option><option value="beli" @selected(old('mode', $plan?->mode->value ?? '') === 'beli')>Beli</option></select></label>
                    <label class="field"><span>Mulai (opsional, cek rental)</span><input type="date" name="start_date" value="{{ old('start_date', $plan?->startDate ?? '') }}"></label>
                    <label class="field"><span>Selesai (opsional)</span><input type="date" name="end_date" value="{{ old('end_date', $plan?->endDate ?? '') }}"></label>
                </div>
                <button class="button button-primary" type="submit">Buat rekomendasi</button>
            </form>

            @isset($results)
                <div class="section-heading"><h2>Rekomendasi gear</h2><p>{{ count($results) }} item cocok.</p></div>
                <div class="card-grid">
                    @forelse($results as $result)
                        <article class="info-card">
                            <p class="product-category">{{ $result['product']->category?->name }}</p>
                            <h2>{{ $result['product']->name }} — {{ $result['variant']->name }}</h2>
                            <p>{{ $result['reason'] }}</p>
                            <p class="field-hint">Jumlah saran: {{ $result['quantity'] }}. @if($result['availability_checked']) {{ $result['available'] ? 'Tersedia pada periode dipilih.' : 'Tidak tersedia.' }} @else Ketersediaan belum dicek; tanggal rental dapat diisi di atas. @endif</p>
                            <a class="text-link" href="{{ route('catalog.show', $result['product']) }}">Lihat detail <span aria-hidden="true">→</span></a>
                        </article>
                    @empty
                        <p class="field-hint">Belum ada aturan cocok. Coba kombinasi lain.</p>
                    @endforelse
                </div>
            @endisset

            <p class="field-hint">Catatan keselamatan: rekomendasi hanya panduan awal. Cek cuaca, kondisi jalur, kemampuan peserta, dan panduan keselamatan setempat sebelum berangkat.</p>
        </div>
    </section>
</x-layout>
