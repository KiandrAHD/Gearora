<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    public function test_button_renders_as_link_with_variant_classes(): void
    {
        $this->blade('<x-ui.button href="/katalog" variant="danger" size="sm">Hapus</x-ui.button>')
            ->assertSee('Hapus')
            ->assertSee('href="/katalog"', false)
            ->assertSee('bg-red-700')
            ->assertSee('min-h-10');
    }

    public function test_button_renders_as_button_with_type(): void
    {
        $this->blade('<x-ui.button type="submit" variant="outline">Simpan</x-ui.button>')
            ->assertSee('type="submit"', false)
            ->assertSee('border-forest')
            ->assertDontSee('href=', false);
    }

    public function test_badge_renders_variant(): void
    {
        $this->blade('<x-ui.badge variant="success">Lunas</x-ui.badge>')
            ->assertSee('Lunas')
            ->assertSee('bg-emerald-50')
            ->assertSee('rounded-full');
    }

    public function test_card_renders_title_description_and_footer_slot(): void
    {
        $this->blade(<<<'BLADE'
            <x-ui.card title="Ringkasan" description="Data operasional">
                Isi kartu
                <x-slot:footer>Kaki kartu</x-slot:footer>
            </x-ui.card>
            BLADE)
            ->assertSee('Ringkasan')
            ->assertSee('Data operasional')
            ->assertSee('Isi kartu')
            ->assertSee('Kaki kartu');
    }

    public function test_table_renders_headers_and_empty_message(): void
    {
        $this->blade('<x-ui.table :headers="[\'Kode\', \'Status\']" empty="Belum ada data" caption="Daftar" />')
            ->assertSee('Kode')
            ->assertSee('Status')
            ->assertSee('Belum ada data')
            ->assertSee('Daftar')
            ->assertSee('colspan="2"', false);
    }

    public function test_modal_renders_dialog_with_title_and_footer(): void
    {
        $this->blade(<<<'BLADE'
            <x-ui.modal id="konfirmasi" title="Konfirmasi hapus">
                Yakin ingin melanjutkan?
                <x-slot:footer>Tombol</x-slot:footer>
            </x-ui.modal>
            BLADE)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Konfirmasi hapus')
            ->assertSee('Yakin ingin melanjutkan?')
            ->assertSee('Tombol');
    }

    public function test_alert_uses_alert_role_for_danger_variant(): void
    {
        $this->blade('<x-ui.alert variant="danger" title="Gagal">Pesan penting</x-ui.alert>')
            ->assertSee('role="alert"', false)
            ->assertSee('Gagal')
            ->assertSee('Pesan penting');
    }

    public function test_alert_uses_status_role_for_info_variant(): void
    {
        $this->blade('<x-ui.alert variant="info">Informasi</x-ui.alert>')
            ->assertSee('role="status"', false)
            ->assertSee('Informasi');
    }

    public function test_pagination_renders_previous_next_and_current_page(): void
    {
        $paginator = new LengthAwarePaginator(
            items: range(1, 10),
            total: 30,
            perPage: 10,
            currentPage: 2,
        );

        $this->blade('<x-ui.pagination :paginator="$paginator" />', ['paginator' => $paginator])
            ->assertSee('Navigasi halaman')
            ->assertSee('Sebelumnya')
            ->assertSee('Berikutnya')
            ->assertSee('aria-current="page"', false);
    }

    public function test_pagination_is_hidden_for_single_page(): void
    {
        $paginator = new LengthAwarePaginator(
            items: range(1, 3),
            total: 3,
            perPage: 10,
            currentPage: 1,
        );

        $this->blade('<x-ui.pagination :paginator="$paginator" />', ['paginator' => $paginator])
            ->assertDontSee('Navigasi halaman');
    }

    public function test_empty_state_renders_title_description_and_action(): void
    {
        $this->blade(<<<'BLADE'
            <x-ui.empty-state title="Kosong" description="Belum ada isi">
                <a href="/katalog">Tambah</a>
            </x-ui.empty-state>
            BLADE)
            ->assertSee('Kosong')
            ->assertSee('Belum ada isi')
            ->assertSee('Tambah');
    }
}
