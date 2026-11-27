<?php

namespace Tests\Feature;

use App\Models\EquipmentGuide;
use Database\Seeders\DemoGuidesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panduan editorial: seeder idempoten dengan 8 artikel nyata, halaman detail
 * punya daftar isi ber-anchor, dan landing menampilkan artikel unggulan.
 */
class GuideEditorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_idempotent_and_creates_eight_articles(): void
    {
        $this->seed(DemoGuidesSeeder::class);
        $this->seed(DemoGuidesSeeder::class);

        $this->assertSame(8, EquipmentGuide::query()->count());
        $this->assertSame(3, EquipmentGuide::query()->where('is_featured', true)->count());
    }

    public function test_landing_lists_editorial_articles_and_featured(): void
    {
        $this->seed(DemoGuidesSeeder::class);

        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Bekal pengetahuan')
            ->assertSee('Cara Memilih Tenda untuk Pendakian dan Camping')
            ->assertSee('Panduan unggulan');
    }

    public function test_detail_renders_toc_with_heading_anchors_and_safety_note(): void
    {
        $this->seed(DemoGuidesSeeder::class);

        $guide = EquipmentGuide::query()->where('is_featured', true)->firstOrFail();

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('Daftar isi')
            ->assertSee('Panduan bersifat informasi umum')
            ->assertSee('<h2 id=', false);
    }
}
