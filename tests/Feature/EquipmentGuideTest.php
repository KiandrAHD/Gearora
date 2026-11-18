<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Models\EquipmentGuide;
use App\Models\GuideCategory;
use App\Models\GuideSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_published_guides_only(): void
    {
        $published = EquipmentGuide::factory()->published()->create(['title' => 'Panduan Terbit']);
        EquipmentGuide::factory()->create(['title' => 'Panduan Draf']);
        EquipmentGuide::factory()->archived()->create(['title' => 'Panduan Arsip']);

        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Panduan Draf')
            ->assertDontSee('Panduan Arsip');
    }

    public function test_draft_guide_returns_not_found_publicly(): void
    {
        $guide = EquipmentGuide::factory()->create();

        $this->get(route('guides.show', $guide))->assertNotFound();
    }

    public function test_future_published_guide_returns_not_found(): void
    {
        $guide = EquipmentGuide::factory()->published()->create(['published_at' => now()->addDay()]);

        $this->get(route('guides.show', $guide))->assertNotFound();
    }

    public function test_show_renders_safe_markdown_and_escapes_html(): void
    {
        $guide = EquipmentGuide::factory()->published()->create([
            'content' => "# Aman\n\n**tebal** dan `kode` <script>alert('x')</script>",
        ]);

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('<h2>Aman</h2>', false)
            ->assertSee('<strong>tebal</strong>', false)
            ->assertSee('&lt;script&gt;alert', false)
            ->assertDontSee('<script>alert', false);
    }

    public function test_search_matches_title_and_content(): void
    {
        EquipmentGuide::factory()->published()->create(['title' => 'Merawat Tenda']);
        EquipmentGuide::factory()->published()->create(['title' => 'Kompor', 'content' => 'Panduan untuk carrier gunung.']);

        $this->get(route('guides.index', ['q' => 'carrier']))
            ->assertOk()
            ->assertSee('Kompor')
            ->assertDontSee('Merawat Tenda');
    }

    public function test_category_filter_applies(): void
    {
        $first = GuideCategory::factory()->create(['slug' => 'tenda']);
        $second = GuideCategory::factory()->create(['slug' => 'kompor']);
        EquipmentGuide::factory()->published()->for($first, 'category')->create(['title' => 'Panduan Tenda']);
        EquipmentGuide::factory()->published()->for($second, 'category')->create(['title' => 'Panduan Kompor']);

        $this->get(route('guides.index', ['kategori' => 'tenda']))
            ->assertOk()
            ->assertSee('Panduan Tenda')
            ->assertDontSee('Panduan Kompor');
    }

    public function test_sources_render_with_safe_external_link_attributes(): void
    {
        $guide = EquipmentGuide::factory()->published()->create();
        GuideSource::factory()->for($guide, 'guide')->create(['source_url' => 'https://example.test/source']);

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('Sumber')
            ->assertSee('rel="noopener noreferrer nofollow"', false);
    }

    public function test_guide_route_uses_stable_slug(): void
    {
        $guide = EquipmentGuide::factory()->published()->create(['slug' => 'panduan-stabil']);

        $this->get('/panduan/panduan-stabil')->assertOk()->assertSee($guide->title);
    }

    public function test_archived_status_not_public_even_with_published_date(): void
    {
        $guide = EquipmentGuide::factory()->create([
            'status' => GuideStatus::Archived,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('guides.show', $guide))->assertNotFound();
    }
}
