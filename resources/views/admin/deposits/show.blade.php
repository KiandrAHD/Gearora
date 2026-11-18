<x-layouts.admin :title="'Deposit '.$deposit->order?->order_code" subtitle="Setiap mutasi deposit tercatat permanen pada ledger; saldo tidak pernah diubah langsung.">
    <div class="admin-grid-2">
        <section class="panel">
            <div class="panel-head"><h2>Ringkasan deposit</h2><x-partials.status-badge :status="$deposit->status" /></div>
            <dl class="summary-list">
                <div><dt>Order</dt><dd><a class="text-link" href="{{ route('admin.rentals.show', $deposit->order) }}">{{ $deposit->order?->order_code }}</a></dd></div>
                <div><dt>Pelanggan</dt><dd>{{ $deposit->order?->customer?->name }}</dd></div>
                <div><dt>Wajib</dt><dd>{{ rupiah($deposit->required_amount) }}</dd></div>
                <div><dt>Terkumpul</dt><dd>{{ rupiah($deposit->collected_amount) }}</dd></div>
                <div><dt>Terpakai</dt><dd>{{ rupiah($deposit->deducted_amount) }}</dd></div>
                <div><dt>Dikembalikan</dt><dd>{{ rupiah($deposit->refunded_amount) }}</dd></div>
                <div><dt>Hangus</dt><dd>{{ rupiah($deposit->forfeited_amount) }}</dd></div>
                <div><dt>Saldo aktif</dt><dd><strong>{{ rupiah($deposit->balance()) }}</strong></dd></div>
            </dl>
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Tindakan</h2></div>
            @if($deposit->balance() > 0)
                <form class="stack-form" method="post" action="{{ route('admin.deposits.refund', $deposit) }}">
                    @csrf
                    <label><span>Kembalikan ke pelanggan (Rp)</span><input name="amount" type="number" min="1" max="{{ $deposit->balance() }}" value="{{ $deposit->balance() }}" required></label>
                    <label><span>Alasan</span><input name="reason" type="text" maxlength="255" value="Pengembalian deposit setelah inspeksi." required></label>
                    <button class="btn-primary" type="submit">Kembalikan deposit</button>
                </form>
                <form class="stack-form" method="post" action="{{ route('admin.deposits.deduct', $deposit) }}">
                    @csrf
                    <label><span>Potong saldo (Rp)</span><input name="amount" type="number" min="1" max="{{ $deposit->balance() }}" required></label>
                    <label><span>Alasan</span><input name="reason" type="text" maxlength="255" required></label>
                    <button class="text-link" type="submit">Potong deposit</button>
                </form>
                <form class="stack-form" method="post" action="{{ route('admin.deposits.forfeit', $deposit) }}" onsubmit="return confirm('Tandai seluruh saldo deposit hangus?');">
                    @csrf
                    <label><span>Alasan hangus</span><input name="reason" type="text" maxlength="255" required></label>
                    <button class="text-link text-danger" type="submit">Tandai hangus</button>
                </form>
            @else
                <p class="field-hint">Saldo deposit sudah nol.</p>
            @endif
        </section>
    </div>

    <section class="panel">
        <div class="panel-head"><h2>Ledger transaksi</h2></div>
        <table class="data-table"><thead><tr><th>Kode</th><th>Tipe</th><th>Nominal</th><th>Saldo setelah</th><th>Alasan</th><th>Waktu</th></tr></thead><tbody>
            @forelse($deposit->transactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_code }}</td>
                    <td>{{ $transaction->type->label() }}</td>
                    <td>{{ rupiah($transaction->amount) }}</td>
                    <td>{{ rupiah($transaction->balance_after) }}</td>
                    <td>{{ $transaction->reason }}</td>
                    <td>{{ $transaction->created_at?->translatedFormat('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">Belum ada transaksi deposit.</td></tr>
            @endforelse
        </tbody></table>
    </section>
</x-layouts.admin>
