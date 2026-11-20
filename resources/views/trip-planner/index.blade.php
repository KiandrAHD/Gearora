<x-layout title="Rekomendasi Trip">
    <section class="trip-hero">
        <div class="page-container trip-hero-inner">
            <div>
                <p class="eyebrow">GEARORA TRIP PLANNER</p>
                <h1 class="page-title">Bingung harus bawa apa?</h1>
                <p class="trip-hero-lead">Susun rencana perjalananmu dalam lima langkah. Sistem merekomendasikan gear dari katalog aktif, lengkap dengan perkiraan biaya dan ketersediaan.</p>
            </div>
            <ul class="trip-hero-points">
                <li>Rekomendasi deterministik dari aturan katalog, bukan tebakan.</li>
                <li>Harga &amp; stok dihitung ulang server saat checkout.</li>
                <li>Bisa gabung gear sewa dan gear beli dalam satu paket.</li>
            </ul>
        </div>
    </section>

    <section class="page-block">
        <div class="page-container">
            @if ($errors->any())
                <div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="notice" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="notice notice-warning" role="alert">{{ session('error') }}</div>
            @endif

            @php($plan = $plan ?? null)
            @php($initialStep = isset($results) ? 5 : 1)

            <div class="trip-wizard" x-data="tripWizard({ step: {{ $initialStep }} })">
                <ol class="trip-steps" aria-label="Langkah perencanaan">
                    <template x-for="(label, index) in steps" :key="index">
                        <li class="trip-step" :class="{ 'is-active': step === index + 1, 'is-done': step > index + 1 }">
                            <span class="trip-step-num" x-text="index + 1"></span>
                            <span class="trip-step-label" x-text="label"></span>
                        </li>
                    </template>
                </ol>

                <form method="POST" action="{{ route('trip-planner.recommend') }}" class="form-card trip-form">
                    @csrf

                    <fieldset class="trip-panel" x-show="step === 1" x-cloak>
                        <legend>Langkah 1 · Aktivitas &amp; destinasi</legend>
                        <div class="form-grid">
                            <label class="field"><span>Aktivitas</span>
                                <select name="activity" required>
                                    @foreach(\App\Enums\TripActivity::cases() as $activity)
                                        <option value="{{ $activity->value }}" @selected(old('activity', $plan?->activity ?? '') === $activity->value)>{{ $activity->label() }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="field"><span>Destinasi</span>
                                <input name="destination" value="{{ old('destination', $plan?->destination ?? '') }}" required maxlength="120" placeholder="Contoh: Gunung Prau">
                            </label>
                            <label class="field"><span>Lokasi/Basecamp (opsional)</span>
                                <input name="location" value="{{ old('location', $plan?->location ?? '') }}" maxlength="160" placeholder="Contoh: Dieng, Wonosobo">
                            </label>
                            <label class="field"><span>Catatan tambahan (opsional)</span>
                                <input name="description" value="{{ old('description', $plan?->description ?? '') }}" maxlength="1000" placeholder="Contoh: sunrise di puncak">
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="trip-panel" x-show="step === 2" x-cloak>
                        <legend>Langkah 2 · Durasi &amp; peserta</legend>
                        <div class="form-grid">
                            <label class="field"><span>Durasi (hari)</span>
                                <input type="number" name="duration_days" min="1" max="60" value="{{ old('duration_days', $plan?->durationDays ?? 2) }}" required>
                            </label>
                            <label class="field"><span>Jumlah peserta</span>
                                <input type="number" name="participants" min="1" max="100" value="{{ old('participants', $plan?->participants ?? 2) }}" required>
                            </label>
                        </div>
                        <fieldset class="field">
                            <span>Level pengalaman</span>
                            <div class="trip-experience">
                                @foreach(\App\Enums\ExperienceLevel::cases() as $level)
                                    <label class="trip-experience-option">
                                        <input type="radio" name="experience" value="{{ $level->value }}" @checked(old('experience', $plan?->experience->value ?? 'pemula') === $level->value)>
                                        <span><strong>{{ $level->label() }}</strong><small>{{ $level->description() }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </fieldset>

                    <fieldset class="trip-panel" x-show="step === 3" x-cloak>
                        <legend>Langkah 3 · Mode &amp; tanggal</legend>
                        <div class="form-grid">
                            <label class="field"><span>Mode perlengkapan</span>
                                <select name="mode" required>
                                    <option value="sewa" @selected(old('mode', $plan?->mode->value ?? '') === 'sewa')>Sewa</option>
                                    <option value="beli" @selected(old('mode', $plan?->mode->value ?? '') === 'beli')>Beli</option>
                                </select>
                            </label>
                            <label class="field"><span>Mulai (untuk gear sewa)</span>
                                <input type="date" name="start_date" value="{{ old('start_date', $plan?->startDate ?? '') }}">
                            </label>
                            <label class="field"><span>Kembali (untuk gear sewa)</span>
                                <input type="date" name="end_date" value="{{ old('end_date', $plan?->endDate ?? '') }}">
                            </label>
                        </div>
                        <fieldset class="field">
                            <span>Kebutuhan khusus (opsional)</span>
                            <div class="trip-needs">
                                @foreach(\App\Enums\TripSpecialNeed::cases() as $need)
                                    <label class="trip-need"><input type="checkbox" name="special_needs[]" value="{{ $need->value }}" @checked(in_array($need->value, old('special_needs', $plan?->specialNeeds ?? []), true))> <span>{{ $need->label() }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <p class="field-hint">Tanggal hanya dipakai untuk mengecek ketersediaan gear sewa. Gear beli tidak terpengaruh tanggal.</p>
                    </fieldset>

                    <fieldset class="trip-panel" x-show="step === 4" x-cloak>
                        <legend>Langkah 4 · Ringkasan</legend>
                        <p class="page-lead">Periksa kembali rencanamu, lalu buat rekomendasi gear.</p>
                        <dl class="trip-summary">
                            <div><dt>Aktivitas</dt><dd><span x-text="summary.activity"></span></dd></div>
                            <div><dt>Destinasi</dt><dd x-text="summary.destination"></dd></div>
                            <div><dt>Durasi</dt><dd x-text="summary.duration + ' hari'"></dd></div>
                            <div><dt>Peserta</dt><dd x-text="summary.participants + ' orang'"></dd></div>
                            <div><dt>Level</dt><dd x-text="summary.experience"></dd></div>
                            <div><dt>Mode</dt><dd x-text="summary.mode"></dd></div>
                            <div x-show="summary.dates"><dt>Tanggal</dt><dd x-text="summary.dates"></dd></div>
                        </dl>
                    </fieldset>

                    <div class="trip-actions">
                        <button class="button" type="button" x-show="step > 1 && step < 5" @click="prev()">Kembali</button>
                        <button class="button button-primary" type="button" x-show="step < 4" @click="next()">Lanjut</button>
                        <button class="button button-primary" type="submit" x-show="step === 4">Buat rekomendasi</button>
                    </div>
                </form>

                @isset($results)
                    <div class="trip-results" x-show="step === 5" x-cloak>
                        <div class="section-heading">
                            <h2>Langkah 5 · Pilih gear</h2>
                            <p>{{ count($results) }} item cocok. Pilih yang ingin dimasukkan ke paket.</p>
                        </div>

                        @if(count($results) === 0)
                            <div class="notice notice-warning" role="status">Belum ada aturan cocok. Coba kombinasi lain.</div>
                            <button class="button" type="button" @click="step = 1">Ubah rencana</button>
                        @else
                            <form method="POST" action="{{ route('trip-planner.package.store') }}" class="trip-package-form">
                                @csrf
                                <input type="hidden" name="activity" value="{{ $plan->activity }}">
                                <input type="hidden" name="destination" value="{{ $plan->destination }}">
                                <input type="hidden" name="location" value="{{ $plan->location }}">
                                <input type="hidden" name="description" value="{{ $plan->description }}">
                                <input type="hidden" name="duration_days" value="{{ $plan->durationDays }}">
                                <input type="hidden" name="participants" value="{{ $plan->participants }}">
                                <input type="hidden" name="experience" value="{{ $plan->experience->value }}">
                                <input type="hidden" name="mode" value="{{ $plan->mode->value }}">
                                <input type="hidden" name="start_date" value="{{ $plan->startDate }}">
                                <input type="hidden" name="end_date" value="{{ $plan->endDate }}">
                                @foreach($plan->specialNeeds as $need)
                                    <input type="hidden" name="special_needs[]" value="{{ $need }}">
                                @endforeach

                                <div class="trip-result-list">
                                    @foreach($results as $index => $result)
                                        <article class="trip-result">
                                            <div class="trip-result-info">
                                                <p class="product-category">{{ $result['product']->category?->name ?? 'Outdoor' }}</p>
                                                <h3>{{ $result['product']->name }} — {{ $result['variant']->name }}</h3>
                                                <p class="trip-result-reason">{{ $result['reason'] }}</p>
                                                <p class="field-hint">
                                                    Saran jumlah: {{ $result['quantity'] }}.
                                                    @if($result['availability_checked'])
                                                        {{ $result['available'] ? 'Tersedia pada periode dipilih.' : 'Tidak tersedia pada periode dipilih.' }}
                                                    @else
                                                        Ketersediaan dicek saat checkout.
                                                    @endif
                                                </p>
                                                <a class="text-link" href="{{ route('catalog.show', $result['product']) }}">Lihat detail <span aria-hidden="true">→</span></a>
                                            </div>
                                            <div class="trip-result-pick">
                                                <label class="trip-need">
                                                    <input type="checkbox" name="items[{{ $index }}][variant_id]" value="{{ $result['variant']->getKey() }}" x-model="picked[{{ $index }}]">
                                                    <span>Tambahkan</span>
                                                </label>
                                                <label class="trip-result-fulfillment">
                                                    <span>Belanja sebagai</span>
                                                    <select name="items[{{ $index }}][fulfillment]">
                                                        @if($result['supports_rental'])
                                                            <option value="sewa" @selected($result['mode'] === 'sewa')>Sewa — {{ rupiah($result['rental_price']) }}/hari</option>
                                                        @endif
                                                        @if($result['supports_sale'])
                                                            <option value="beli" @selected($result['mode'] === 'beli')>Beli — {{ rupiah($result['sale_price']) }}/unit</option>
                                                        @endif
                                                    </select>
                                                </label>
                                                <label class="trip-result-qty">
                                                    <span>Jumlah</span>
                                                    <input type="number" name="items[{{ $index }}][quantity]" min="1" max="99" value="{{ $result['quantity'] }}">
                                                </label>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>

                                <label class="field"><span>Catatan paket (opsional)</span>
                                    <input name="notes" maxlength="500" placeholder="Contoh: tolong siapkan sebelum jam 5 pagi">
                                </label>

                                <div class="trip-actions">
                                    <button class="button" type="button" @click="step = 1">Ubah rencana</button>
                                    <button class="button button-primary" type="submit">Lanjut ke pratinjau paket</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endisset
            </div>

            <p class="field-hint">Catatan keselamatan: rekomendasi hanya panduan awal. Cek cuaca, kondisi jalur, kemampuan peserta, dan panduan keselamatan setempat sebelum berangkat.</p>
        </div>
    </section>
</x-layout>
