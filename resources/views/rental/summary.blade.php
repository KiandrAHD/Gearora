<x-layout title="Ringkasan Booking">
    <section class="page-block">
        <div class="page-container summary-wrap">
            <p class="eyebrow">RINGKASAN BOOKING</p>
            <h1 class="page-title">Periksa pesananmu.</h1>
            <p class="page-lead">Harga dan ketersediaan di bawah dihitung di server. Ketersediaan akan diperiksa ulang saat pesanan dibuat.</p>

            @if(session('error'))<div class="notice notice-warning" role="alert">{{ session('error') }}</div>@endif

            <div class="summary-grid">
                <div class="summary-card">
                    <h2>{{ $product->name }}</h2>
                    <p class="product-category">Varian {{ $variant->name }} · SKU {{ $variant->sku }}</p>
                    <dl class="summary-list">
                        <div><dt>Periode</dt><dd>{{ $quote->period->start->translatedFormat('d M Y') }} → {{ $quote->period->end->translatedFormat('d M Y') }}</dd></div>
                        <div><dt>Durasi</dt><dd>{{ $quote->durationDays() }} hari</dd></div>
                        <div><dt>Jumlah</dt><dd>{{ $quantity }} unit</dd></div>
                        <div><dt>Ketersediaan</dt><dd>{{ $availability->availableQuantity() }} unit bebas pada periode ini</dd></div>
                    </dl>
                </div>
                <div class="summary-card summary-cost">
                    <h2>Rincian biaya</h2>
                    @foreach($quote->lines as $line)
                        <div class="cost-row"><span>{{ $line['variant_name'] }} × {{ $line['quantity'] }} × {{ $line['duration_days'] }} hari</span><strong>{{ rupiah($line['line_total']) }}</strong></div>
                    @endforeach
                    <div class="cost-row"><span>Subtotal sewa</span><strong>{{ rupiah($quote->subtotal) }}</strong></div>
                    <div class="cost-row"><span>Deposit</span><strong>{{ rupiah($quote->depositTotal) }}</strong></div>
                    <div class="cost-row cost-total"><span>Total</span><strong>{{ rupiah($quote->total) }}</strong></div>
                    <p class="field-hint">Metode pembayaran: {{ \App\Enums\PaymentMethod::from($method)->label() }}. Pembayaran manual, diverifikasi admin.</p>
                </div>
            </div>

            <div class="summary-actions">
                @auth
                    <form method="post" action="{{ route('customer.orders.store') }}">
                        @csrf
                        <button class="btn-primary" type="submit">Buat pesanan</button>
                    </form>
                    <p class="field-hint">Kamu masuk sebagai {{ auth()->user()->name }}.</p>
                @else
                    <a class="btn-primary" href="{{ route('login') }}">Masuk untuk melanjutkan</a>
                    <p class="field-hint">Pilihanmu disimpan. Setelah masuk atau daftar, kamu akan kembali ke halaman ini.</p>
                @endauth
                <form method="post" action="{{ route('booking.destroy') }}">
                    @csrf @method('DELETE')
                    <button class="text-link" type="submit">Batalkan pilihan</button>
                </form>
            </div>
        </div>
    </section>
</x-layout>
