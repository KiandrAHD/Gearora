<x-layout title="Pratinjau Paket Trip">
    <section class="page-block">
        <div class="page-container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('trip-planner.index') }}">Trip Planner</a><span>/</span><span>Pratinjau paket</span>
            </nav>

            <p class="eyebrow">PRATINJAU PAKET</p>
            <h1 class="page-title">Periksa paket trip-mu.</h1>
            <p class="page-lead">Harga dan ketersediaan di bawah dihitung ulang server-side. Nominal final ditetapkan saat checkout.</p>

            @if(session('error'))<div class="notice notice-warning" role="alert">{{ session('error') }}</div>@endif
            @if($notice)<div class="notice notice-warning" role="alert">{{ $notice }}</div>@endif

            <div class="package-grid">
                <div class="package-main">
                    <section class="panel">
                        <h2>Rencana perjalanan</h2>
                        <dl class="trip-summary">
                            <div><dt>Aktivitas</dt><dd>{{ \App\Enums\TripActivity::tryFrom($plan->activity)?->label() ?? $plan->activity }}</dd></div>
                            <div><dt>Destinasi</dt><dd>{{ $plan->destination }}@if($plan->location) · {{ $plan->location }}@endif</dd></div>
                            <div><dt>Durasi</dt><dd>{{ $plan->durationDays }} hari</dd></div>
                            <div><dt>Peserta</dt><dd>{{ $plan->participants }} orang</dd></div>
                            <div><dt>Level</dt><dd>{{ $plan->experience->label() }}</dd></div>
                            <div><dt>Mode</dt><dd>{{ $plan->mode->label() }}</dd></div>
                            @if($plan->startDate && $plan->endDate)<div><dt>Tanggal</dt><dd>{{ $plan->startDate }} → {{ $plan->endDate }}</dd></div>@endif
                            @if($plan->departureTime)<div><dt>Waktu berkumpul</dt><dd>{{ $plan->departureTime }}</dd></div>@endif
                            @if($plan->specialNeeds !== [])<div class="trip-summary-wide"><dt>Kebutuhan khusus</dt><dd>{{ collect($plan->specialNeeds)->map(fn ($need) => \App\Enums\TripSpecialNeed::tryFrom($need)?->label() ?? $need)->implode(', ') }}</dd></div>@endif
                            @if($plan->description)<div class="trip-summary-wide"><dt>Catatan</dt><dd>{{ $plan->description }}</dd></div>@endif
                        </dl>
                    </section>

                    @if($rentalLines !== [])
                        <section class="panel">
                            <h2>Gear sewa</h2>
                            <table class="table-plain">
                                <thead><tr><th>Gear</th><th>Qty</th><th>Harga/hari</th><th>Durasi</th><th>Subtotal</th></tr></thead>
                                <tbody>
                                    @foreach($rentalLines as $line)
                                        <tr>
                                            <td>{{ $line['product_name'] }} — {{ $line['variant_name'] }}</td>
                                            <td>{{ $line['quantity'] }}</td>
                                            <td>{{ rupiah((int) $line['unit_price']) }}</td>
                                            <td>{{ $line['duration_days'] }} hari</td>
                                            <td>{{ rupiah((int) $line['line_total']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($rentalQuote && (float) $rentalQuote->depositTotal > 0)
                                <p class="field-hint">Termasuk deposit: {{ rupiah((int) $rentalQuote->depositTotal) }} (dikembalikan setelah gear kembali lengkap).</p>
                            @endif
                        </section>
                    @endif

                    @if($saleLines !== [])
                        <section class="panel">
                            <h2>Gear beli</h2>
                            <table class="table-plain">
                                <thead><tr><th>Gear</th><th>Qty</th><th>Harga/unit</th><th>Subtotal</th></tr></thead>
                                <tbody>
                                    @foreach($saleLines as $line)
                                        <tr>
                                            <td>{{ $line['variant']->product->name }} — {{ $line['variant']->name }}</td>
                                            <td>{{ $line['quantity'] }}</td>
                                            <td>{{ rupiah($line['unit_price']) }}</td>
                                            <td>{{ rupiah($line['line_total']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </section>
                    @endif

                    @if($rentalLines === [] && $saleLines === [])
                        <div class="notice notice-warning" role="status">Belum ada gear yang bisa ditampilkan. Ulangi pemilihan gear.</div>
                    @endif
                </div>

                <aside class="package-summary">
                    <h2>Ringkasan biaya</h2>
                    <dl class="package-totals">
                        <div><dt>Total sewa</dt><dd>{{ rupiah($rentalTotal) }}</dd></div>
                        <div><dt>Total beli</dt><dd>{{ rupiah($saleTotal) }}</dd></div>
                        <div class="package-total-grand"><dt>Perkiraan total</dt><dd>{{ rupiah($grandTotal) }}</dd></div>
                    </dl>

                    @auth
                        <form class="stack-form" method="POST" action="{{ route('trip-planner.package.checkout') }}">
                            @csrf
                            <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
                            <x-partials.payment-method-picker name="payment_method" :methods="$methods" :selected="\App\Enums\PaymentMethod::Transfer->value" />
                            <label><span>Catatan (opsional)</span><input name="notes" maxlength="500" value="{{ $notes }}"></label>
                            <button class="button button-primary" type="submit">Buat paket &amp; lanjut bayar</button>
                        </form>
                    @else
                        <p class="field-hint">Masuk atau daftar untuk menyimpan paket dan melanjutkan pembayaran.</p>
                        <a class="button button-primary" href="{{ route('login', ['redirect' => route('trip-planner.package.review', [], false)]) }}">Masuk untuk checkout</a>
                    @endauth

                    <form method="POST" action="{{ route('trip-planner.package.destroy') }}" class="package-cancel">
                        @csrf @method('DELETE')
                        <button class="text-link" type="submit">Batalkan pratinjau</button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
</x-layout>
