<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\RoleName;
use App\Models\EquipmentGuide;
use App\Models\GuideCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Guide Studio (admin) dan editorial publik: pratinjau aman, unggulan,
 * metadata SEO, dan halaman publik.
 */
class AdminGuideStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_preview_endpoint_returns_safe_html_and_minutes(): void
    {
        $response = $this->actingAs($this->owner())
            ->postJson(route('admin.guides.preview'), [
                'content' => "# Judul\n\n<script>alert(1)</script> dan @youtube[dQw4w9WgXcQ]",
            ])
            ->assertOk();

        $html = (string) $response->json('html');

        $this->assertSame(1, $response->json('minutes'));
        $this->assertStringContainsString('<h2>Judul</h2>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $html);
    }

    public function test_preview_normalizes_supported_youtube_url(): void
    {
        $response = $this->actingAs($this->owner())
            ->postJson(route('admin.guides.preview'), [
                'content' => 'https://youtu.be/dQw4w9WgXcQ?t=10',
            ])
            ->assertOk();

        $this->assertStringContainsString('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', (string) $response->json('html'));
        $this->assertStringNotContainsString('?t=10', (string) $response->json('html'));
    }

    public function test_owner_can_upload_inline_guide_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->owner())
            ->post(route('admin.guides.media.store'), [
                'image' => UploadedFile::fake()->image('prau.png'),
                'alt' => 'Pemandangan Prau',
            ])
            ->assertCreated();

        $path = (string) $response->json('path');
        $this->assertStringStartsWith('guide-inline/', $path);
        $this->assertSame('Pemandangan Prau', $response->json('alt'));
        Storage::disk('public')->assertExists($path);
    }

    public function test_guest_cannot_upload_inline_guide_image(): void
    {
        $this->post(route('admin.guides.media.store'), [
            'image' => UploadedFile::fake()->image('prau.png'),
        ])->assertRedirect(route('login'));
    }

    public function test_owner_can_mark_guide_as_featured(): void
    {
        $category = GuideCategory::factory()->create();

        $this->actingAs($this->owner())->post(route('admin.guides.store'), [
            'title' => 'Panduan Unggulan',
            'slug' => 'panduan-unggulan',
            'guide_category_id' => $category->id,
            'content' => '# Isi',
            'status' => GuideStatus::Published->value,
            'is_featured' => '1',
        ])->assertRedirect(route('admin.guides.index'));

        $this->assertDatabaseHas('equipment_guides', ['slug' => 'panduan-unggulan', 'is_featured' => 1]);
    }

    public function test_public_index_shows_featured_section(): void
    {
        EquipmentGuide::factory()->published()->featured()->create(['title' => 'Unggulan Prau']);
        EquipmentGuide::factory()->published()->create(['title' => 'Biasa Bromo']);

        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Panduan unggulan', false)
            ->assertSee('Unggulan Prau');
    }

    public function test_public_show_exposes_reading_time_and_related(): void
    {
        $category = GuideCategory::factory()->create();
        $guide = EquipmentGuide::factory()->published()->for($category, 'category')->create([
            'title' => 'Panduan Utama',
            'content' => '# Halo',
        ]);
        EquipmentGuide::factory()->published()->for($category, 'category')->create(['title' => 'Panduan Terkait']);

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('menit baca')
            ->assertSee('Panduan terkait')
            ->assertSee('Panduan Terkait');
    }

    public function test_seo_title_and_description_used_in_head(): void
    {
        $guide = EquipmentGuide::factory()->published()->create([
            'title' => 'Judul Asli',
            'seo_title' => 'SEO Judul Khusus',
            'seo_description' => 'Deskripsi SEO khusus panduan.',
        ]);

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('SEO Judul Khusus', false)
            ->assertSee('Deskripsi SEO khusus panduan.', false);
    }

    public function test_guest_cannot_reach_preview(): void
    {
        $this->postJson(route('admin.guides.preview'), ['content' => '# x'])
            ->assertUnauthorized();
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Owner);

        return $user;
    }
}
