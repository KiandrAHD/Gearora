<x-layout title="Rekomendasi Trip Gunung Prau">
    @php($plan = $plan ?? null)
    @php($initialStep = isset($results) ? 5 : 1)

    <section class="trip-planner-shell" x-data="tripWizard({ step: {{ $initialStep }} })">
        <aside class="trip-visual-panel" aria-label="Gunung Prau, Dieng">
            <img class="trip-visual-photo" src="{{ asset('images/trips/gunung-prau-sunrise.jpg') }}" alt="Sunrise dan lautan awan di Gunung Prau, Dieng" fetchpriority="high" width="1600" height="1255">
            <div class="trip-visual-shade" aria-hidden="true"></div>
            <img class="trip-visual-illustration" src="{{ asset('images/trips/pendaki-tenda.svg') }}" alt="" aria-hidden="true" width="1200" height="300">
            <div class="trip-visual-copy">
                <p class="trip-kicker">PERSONAL GEAR GUIDE</p>
                <h1>Ke mana langkahmu<br>akan pergi?</h1>
                <p>Susun bekal untuk sunrise Gunung Prau. Rekomendasi gear berasal dari aturan yang dikelola tim GEARORA.</p>
                <div class="trip-destination-mark" aria-label="Gunung Prau, Dieng, Jawa Tengah">
                    <span>GUNUNG PRAU</span><small>DIENG · JAWA TENGAH</small>
                </div>
            </div>
            <p class="trip-visual-credit">Foto Gunung Prau: CC0 1.0 · Wikimedia Commons</p>
        </aside>

        <section class="trip-workspace">
            <div class="trip-workspace-inner">
                <a class="trip-back-link" href="{{ route('home') }}">← Kembali ke GEARORA</a>

                @if ($errors->any())
                    <div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>
                @endif
                @if (session('status'))
                    <div class="notice" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="notice notice-warning" role="alert">{{ session('error') }}</div>
                @endif

                <ol class="trip-steps" aria-label="Progres perencanaan trip">
                    <template x-for="(label, index) in steps" :key="index">
                        <li class="trip-step" :class="{ 'is-active': step === index + 1, 'is-done': step > index + 1 }">
                            <span class="trip-step-num" x-text="index + 1"></span>
                            <span class="trip-step-label" x-text="label"></span>
                        </li>
                    </template>
                </ol>

                <form method="POST" action="{{ route('trip-planner.recommend') }}" class="trip-form" @submit.prevent="submitPlan($event)" novalidate>
                    @csrf

                    <fieldset class="trip-panel" x-show="step === 1" x-cloak>
                        <p class="trip-step-caption">LANGKAH 01 / 04</p>
                        <legend>Ceritakan rencana perjalananmu.</legend>
                        <p class="trip-panel-lead">Gunung Prau sudah dipilih. Tambahkan titik kumpul, jadwal, dan konteks trip supaya paket tersimpan lengkap.</p>
                        <div class="form-grid">
                            <label class="field"><span>Aktivitas</span>
                                <select name="activity" required>
                                    @foreach(\App\Enums\TripActivity::cases() as $activity)
                                        <option value="{{ $activity->value }}" @selected(old('activity', $plan?->activity ?? \App\Enums\TripActivity::Hiking->value) === $activity->value)>{{ $activity->label() }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="field"><span>Destinasi</span>
                                <input value="Gunung Prau · Dieng, Jawa Tengah" readonly aria-readonly="true">
                                <span class="field-hint">Terkunci di server untuk paket Prau.</span>
                            </label>
                            <label class="field"><span>Basecamp / lokasi</span>
                                @php($selectedBasecamp = old('basecamp', $plan?->basecamp ?? (in_array($plan?->location, config('gearora.trip.basecamps', []), true) ? $plan?->location : (filled($plan?->location) ? config('gearora.trip.basecamp_other_value') : ''))))
                                <select name="basecamp" required x-ref="basecampSelect">
                                    <option value="" disabled @selected($selectedBasecamp === '')>Pilih basecamp</option>
                                    @foreach(config('gearora.trip.basecamps', []) as $basecamp)
                                        <option value="{{ $basecamp }}" @selected($selectedBasecamp === $basecamp)>{{ $basecamp }}</option>
                                    @endforeach
                                    <option value="{{ config('gearora.trip.basecamp_other_value') }}" @selected($selectedBasecamp === config('gearora.trip.basecamp_other_value'))>Lainnya…</option>
                                </select>
                                <span class="field-hint">Basecamp resmi Gunung Prau. Pilih “Lainnya” bila titik kumpulmu berbeda.</span>
                            </label>
                            <label class="field" x-show="$refs.basecampSelect?.value === '{{ config('gearora.trip.basecamp_other_value') }}'" x-cloak>
                                <span>Nama basecamp lain</span>
                                <input name="basecamp_other" value="{{ old('basecamp_other', $plan?->basecamp_other ?? '') }}" maxlength="120" placeholder="Contoh: Basecamp Kaliurang" :required="$refs.basecampSelect?.value === '{{ config('gearora.trip.basecamp_other_value') }}'">
                            </label>
                            <label class="field"><span>Tanggal keberangkatan</span>
                                <input type="date" name="start_date" value="{{ old('start_date', $plan?->startDate ?? '') }}" min="{{ now()->toDateString() }}" required>
                                <span class="field-hint">Tidak boleh sebelum hari ini ({{ now()->translatedFormat('d M Y') }}).</span>
                            </label>
                            <label class="field"><span>Waktu berkumpul <em>opsional</em></span>
                                <input type="time" name="departure_time" value="{{ old('departure_time', $plan?->departureTime ?? '') }}">
                                <span class="field-hint">Bukan waktu pengambilan gear.</span>
                            </label>
                            <label class="field field-wide"><span>Catatan perjalanan <em>opsional</em></span>
                                <textarea name="description" rows="3" maxlength="1000" placeholder="Contoh: target sunrise, ada peserta anak, atau kebutuhan lain.">{{ old('description', $plan?->description ?? '') }}</textarea>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="trip-panel" x-show="step === 2" x-cloak>
                        <p class="trip-step-caption">LANGKAH 02 / 04</p>
                        <legend>Siapa yang ikut bertualang?</legend>
                        <p class="trip-panel-lead">Durasi menentukan tanggal kembali untuk gear sewa. Sistem menghitung tanggal tersebut di server.</p>
                        <div class="form-grid">
                            <label class="field"><span>Durasi perjalanan (hari)</span>
                                <input type="number" name="duration_days" min="1" max="60" value="{{ old('duration_days', $plan?->durationDays ?? 2) }}" required>
                            </label>
                            <label class="field"><span>Jumlah peserta</span>
                                <input type="number" name="participants" min="1" max="100" value="{{ old('participants', $plan?->participants ?? 2) }}" required>
                            </label>
                        </div>
                        <fieldset class="field trip-choice-field"><span>Pengalaman grup</span>
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
                        <p class="trip-step-caption">LANGKAH 03 / 04</p>
                        <legend>Apa yang dibutuhkan selama perjalanan?</legend>
                        <p class="trip-panel-lead">Pilih kebutuhan yang relevan. Aturan Admin akan memfilter rekomendasi yang cocok.</p>
                        <fieldset class="field trip-choice-field"><span>Kebutuhan khusus <em>opsional</em></span>
                            <div class="trip-needs">
                                @foreach(\App\Enums\TripSpecialNeed::cases() as $need)
                                    <label class="trip-need"><input type="checkbox" name="special_needs[]" value="{{ $need->value }}" @checked(in_array($need->value, old('special_needs', $plan?->specialNeeds ?? []), true))><span>{{ $need->label() }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <fieldset class="field trip-choice-field"><span>Preferensi gear</span>
                            <div class="trip-mode-options">
                                @foreach([\App\Enums\TripServiceMode::Sewa, \App\Enums\TripServiceMode::Beli, \App\Enums\TripServiceMode::Fleksibel] as $mode)
                                    <label class="trip-mode-option">
                                        <input type="radio" name="mode" value="{{ $mode->value }}" @checked(old('mode', $plan?->mode->value ?? 'sewa') === $mode->value)>
                                        <span><strong>{{ $mode->label() }}</strong><small>{{ $mode === \App\Enums\TripServiceMode::Fleksibel ? 'Pilih fulfillment yang valid pada tiap gear.' : ($mode === \App\Enums\TripServiceMode::Sewa ? 'Tanggal dicek untuk availability rental.' : 'Stok retail dicek saat checkout.') }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </fieldset>

                    <fieldset class="trip-panel" x-show="step === 4" x-cloak>
                        <p class="trip-step-caption">LANGKAH 04 / 04</p>
                        <legend>Periksa kembali rencana perjalananmu.</legend>
                        <dl class="trip-summary trip-summary-review">
                            <div><dt>Destinasi</dt><dd>Gunung Prau, Dieng</dd></div>
                            <div><dt>Basecamp</dt><dd x-text="summary.location || 'Belum diisi'"></dd></div>
                            <div><dt>Berangkat</dt><dd x-text="summary.startDate || 'Belum diisi'"></dd></div>
                            <div><dt>Waktu kumpul</dt><dd x-text="summary.departureTime || 'Belum diisi'"></dd></div>
                            <div><dt>Durasi</dt><dd x-text="summary.duration + ' hari' "></dd></div>
                            <div><dt>Peserta</dt><dd x-text="summary.participants + ' orang'"></dd></div>
                            <div><dt>Pengalaman</dt><dd x-text="summary.experience"></dd></div>
                            <div><dt>Mode gear</dt><dd x-text="summary.mode"></dd></div>
                            <div class="trip-summary-wide"><dt>Kebutuhan</dt><dd x-text="summary.needs || 'Tidak ada pilihan khusus'"></dd></div>
                            <div class="trip-summary-wide" x-show="summary.description"><dt>Catatan</dt><dd x-text="summary.description"></dd></div>
                        </dl>
                    </fieldset>

                    <div class="trip-actions">
                        <button class="button button-secondary" type="button" x-show="step > 1 && step < 5" @click="prev()">Kembali</button>
                        <button class="button button-primary" type="button" x-show="step < 4" @click="next()">Lanjutkan <span aria-hidden="true">→</span></button>
                        <button class="button button-primary" type="submit" x-show="step === 4">Lihat rekomendasi <span aria-hidden="true">→</span></button>
                    </div>
                </form>

                @isset($results)
                    <div class="trip-results" x-show="step === 5" x-cloak>
                        <p class="trip-step-caption">LANGKAH 05 / 05</p>
                        <div class="section-heading"><div><h2>Pilih perlengkapan petualanganmu.</h2><p>{{ count($results) }} gear direkomendasikan dari aturan aktif Admin.</p></div></div>
                        @if(count($results) === 0)
                            <div class="notice notice-warning" role="status">Belum ada aturan aktif yang cocok. Ubah rencana atau hubungi tim GEARORA.</div>
                            <button class="button button-secondary" type="button" @click="step = 1">Ubah rencana</button>
                        @else
                            <form method="POST" action="{{ route('trip-planner.package.store') }}" class="trip-package-form">
                                @csrf
                                @foreach(['activity' => $plan->activity, 'location' => $plan->location, 'basecamp' => in_array($plan->location, config('gearora.trip.basecamps', []), true) ? $plan->location : config('gearora.trip.basecamp_other_value'), 'basecamp_other' => in_array($plan->location, config('gearora.trip.basecamps', []), true) ? null : $plan->location, 'description' => $plan->description, 'duration_days' => $plan->durationDays, 'participants' => $plan->participants, 'experience' => $plan->experience->value, 'mode' => $plan->mode->value, 'departure_time' => $plan->departureTime, 'start_date' => $plan->startDate, 'end_date' => $plan->endDate] as $name => $value)
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endforeach
                                @foreach($plan->specialNeeds as $need)<input type="hidden" name="special_needs[]" value="{{ $need }}">@endforeach
                                <div class="trip-result-list">
                                    @foreach($results as $index => $result)
                                        @php($imagePath = $result['product']->displayImagePath())
                                        <article class="trip-result">
                                            <div class="trip-result-media">
                                                @if($imagePath)<img src="{{ media_url($imagePath) }}" alt="{{ $result['product']->name }}" loading="lazy" width="260" height="220">@else<div class="trip-result-media-fallback">GEARORA</div>@endif
                                            </div>
                                            <div class="trip-result-info">
                                                <p class="product-category">{{ $result['product']->category?->name ?? 'Outdoor' }}</p>
                                                <h3>{{ $result['product']->name }} <span>— {{ $result['variant']->name }}</span></h3>
                                                <p class="trip-result-reason">{{ $result['reason'] }}</p>
                                                <p class="field-hint">Saran jumlah: {{ $result['quantity'] }}. @if($result['availability_checked']){{ $result['available'] ? 'Tersedia pada periode trip.' : 'Tidak tersedia pada periode trip.' }}@else Ketersediaan final dicek saat checkout.@endif</p>
                                            </div>
                                            <div class="trip-result-pick">
                                                <label class="trip-add-toggle"><input type="checkbox" name="items[{{ $index }}][variant_id]" value="{{ $result['variant']->getKey() }}"><span>Tambahkan ke paket</span></label>
                                                <label class="trip-result-fulfillment"><span>Penuhi sebagai</span><select name="items[{{ $index }}][fulfillment]" @disabled(! $result['supports_rental'] && ! $result['supports_sale'])>
                                                    @if($result['supports_rental'])<option value="sewa" @selected($result['mode'] === 'sewa')>Sewa · {{ rupiah($result['rental_price']) }}/hari</option>@endif
                                                    @if($result['supports_sale'])<option value="beli" @selected($result['mode'] === 'beli')>Beli · {{ rupiah($result['sale_price']) }}/unit</option>@endif
                                                </select></label>
                                                <label class="trip-result-qty"><span>Jumlah</span><input type="number" name="items[{{ $index }}][quantity]" min="1" max="99" value="{{ $result['quantity'] }}"></label>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                                <label class="field"><span>Catatan paket <em>opsional</em></span><textarea name="notes" rows="2" maxlength="500" placeholder="Contoh: siapkan sebelum jam 5 pagi"></textarea></label>
                                <div class="trip-actions"><button class="button button-secondary" type="button" @click="step = 1">Ubah rencana</button><button class="button button-primary" type="submit">Pratinjau paket <span aria-hidden="true">→</span></button></div>
                            </form>
                        @endif
                    </div>
                @endisset
                <p class="trip-safety-note">Catatan keselamatan: rekomendasi adalah panduan awal. Cek cuaca, kondisi jalur, kemampuan peserta, dan aturan basecamp sebelum berangkat.</p>
            </div>
        </section>
    </section>
</x-layout>
