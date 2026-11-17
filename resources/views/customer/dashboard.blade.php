@php
    $summary = [
        ['label' => 'Pesanan aktif', 'value' => '0'],
        ['label' => 'Menunggu pembayaran', 'value' => '0'],
        ['label' => 'Selesai', 'value' => '0'],
    ];
@endphp
<x-layouts.customer title="Ringkasan akun" active="dashboard">
    <div class="space-y-6">
        <x-ui.alert variant="info" title="Autentikasi belum aktif">
            Halaman ini adalah skeleton layout pelanggan. Login, profil, dan data pesanan nyata diimplementasikan pada Phase 2 sampai Phase 4.
        </x-ui.alert>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($summary as $item)
                <x-ui.card padding="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</p>
                    <p class="mt-2 font-heading text-3xl font-semibold text-navy">{{ $item['value'] }}</p>
                </x-ui.card>
            @endforeach
        </div>

        <section id="pesanan" aria-labelledby="pesanan-heading">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 id="pesanan-heading" class="font-heading text-xl font-semibold text-navy">Pesanan saya</h2>
                <x-ui.badge variant="neutral">0 pesanan</x-ui.badge>
            </div>

            <x-ui.table
                :headers="['Kode', 'Periode', 'Status', 'Total']"
                caption="Daftar pesanan pelanggan"
                empty="Belum ada pesanan. Pesanan rental dan pembelian akan tampil di sini."
            />
        </section>

        <section id="profil" aria-labelledby="profil-heading">
            <x-ui.card title="Profil pelanggan" description="Data akun akan tersedia setelah autentikasi Phase 2.">
                <x-ui.empty-state
                    icon="box"
                    title="Profil belum tersedia"
                    description="Formulir profil membutuhkan data pengguna yang login."
                />
            </x-ui.card>
        </section>
    </div>
</x-layouts.customer>