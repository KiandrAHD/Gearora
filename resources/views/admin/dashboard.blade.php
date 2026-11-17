@php
    $stats = [
        ['label' => 'Rental aktif', 'value' => '0', 'hint' => 'Data Phase 4'],
        ['label' => 'Jatuh tempo hari ini', 'value' => '0', 'hint' => 'Data Phase 5'],
        ['label' => 'Terlambat', 'value' => '0', 'hint' => 'Data Phase 5'],
        ['label' => 'Unit perawatan', 'value' => '0', 'hint' => 'Data Phase 3'],
    ];
@endphp
<x-layouts.admin title="Dashboard" subtitle="Kerangka Admin Dashboard GEARORA. Angka operasional akan diambil dari database pada phase berikutnya; tidak ada data dummy yang ditampilkan sebagai data nyata.">
    <div class="space-y-6">
        <x-ui.alert variant="warning" title="Admin Dashboard belum diamankan">
            Role, permission, dan middleware admin diimplementasikan pada Phase 2. Jangan menghubungkan route admin ke publik sebelum kontrol akses selesai.
        </x-ui.alert>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <x-ui.card padding="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 font-heading text-3xl font-semibold text-navy">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stat['hint'] }}</p>
                </x-ui.card>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <x-ui.card title="Pesanan terbaru" description="Ringkasan order rental dan sales order." padding="p-0">
                <div class="p-5">
                    <x-ui.table
                        :headers="['Kode', 'Pelanggan', 'Status', 'Total']"
                        caption="Pesanan terbaru"
                        empty="Belum ada pesanan pada database."
                    />
                </div>
                <x-slot:footer>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <x-ui.badge variant="neutral">0 pesanan</x-ui.badge>
                        <x-ui.button type="button" variant="outline" size="sm" disabled>Lihat semua</x-ui.button>
                    </div>
                </x-slot:footer>
            </x-ui.card>

            <x-ui.card title="Aksi cepat" description="Menu operasional disiapkan untuk phase berikutnya.">
                <div class="space-y-3">
                    <x-ui.button type="button" variant="primary" size="sm" block disabled>Buat pesanan manual</x-ui.button>
                    <x-ui.button type="button" variant="outline" size="sm" block disabled>Verifikasi pembayaran</x-ui.button>
                    <x-ui.button type="button" variant="outline" size="sm" block disabled>Catat pengembalian</x-ui.button>
                </div>
                <dl class="mt-6 space-y-3 border-t border-line pt-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-600">Status modul admin</dt>
                        <dd><x-ui.badge variant="warning">Skeleton</x-ui.badge></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-600">Koneksi database</dt>
                        <dd><x-ui.badge variant="success">Aktif</x-ui.badge></dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        <x-ui.card title="Aktivitas terbaru" description="Audit trail perubahan sensitif tersedia pada Phase 2.">
            <x-ui.empty-state
                icon="inbox"
                title="Belum ada aktivitas"
                description="Log aksi admin akan tampil di sini setelah modul autentikasi dan audit tersedia."
            />
        </x-ui.card>
    </div>
</x-layouts.admin>