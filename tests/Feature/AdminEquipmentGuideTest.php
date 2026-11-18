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

class AdminEquipmentGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_create_draft_guide(): void
    {
        $owner = $this->owner();
        $category = GuideCategory::factory()->create();

        $this->actingAs($owner)->post(route('admin.guides.store'), $this->payload($category, ['status' => 'draft']))
            ->assertRedirect(route('admin.guides.index'));

        $this->assertDatabaseHas('equipment_guides', [
            'title' => 'Panduan Aman',
            'status' => GuideStatus::Draft->value,
            'slug' => 'panduan-aman',
        ]);
    }

    public function test_owner_can_publish_guide_from_create_form(): void
    {
        $owner = $this->owner();
        $category = GuideCategory::factory()->create();

        $this->actingAs($owner)->post(route('admin.guides.store'), $this->payload($category, ['status' => 'published']))
            ->assertRedirect();

        $guide = EquipmentGuide::query()->firstOrFail();
        $this->assertSame(GuideStatus::Published, $guide->status);
        $this->assertNotNull($guide->published_at);
        $this->assertSame($owner->id, $guide->publisher_id);
    }

    public function test_title_change_keeps_existing_slug_when_slug_blank(): void
    {
        $owner = $this->owner();
        $guide = EquipmentGuide::factory()->published()->create(['slug' => 'url-tetap']);

        $this->actingAs($owner)->put(route('admin.guides.update', $guide), $this->payload(null, [
            'title' => 'Judul Baru',
            'slug' => '',
            'status' => 'published',
        ]))->assertRedirect();

        $this->assertDatabaseHas('equipment_guides', ['id' => $guide->id, 'title' => 'Judul Baru', 'slug' => 'url-tetap']);
    }

    public function test_cover_upload_stores_safe_image(): void
    {
        Storage::fake('public');
        $owner = $this->owner();
        $category = GuideCategory::factory()->create();

        $this->actingAs($owner)->post(route('admin.guides.store'), $this->payload($category, [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ]))->assertRedirect();

        $guide = EquipmentGuide::query()->firstOrFail();
        Storage::disk('public')->assertExists($guide->cover_image_path);
    }

    public function test_invalid_cover_is_rejected(): void
    {
        $owner = $this->owner();
        $category = GuideCategory::factory()->create();

        $this->actingAs($owner)->post(route('admin.guides.store'), $this->payload($category, [
            'cover' => UploadedFile::fake()->create('cover.txt', 10, 'text/plain'),
        ]))->assertSessionHasErrors('cover');
    }

    public function test_publish_unpublish_and_archive_actions_work(): void
    {
        $owner = $this->owner();
        $guide = EquipmentGuide::factory()->create();

        $this->actingAs($owner)->post(route('admin.guides.publish', $guide))->assertRedirect();
        $this->assertDatabaseHas('equipment_guides', ['id' => $guide->id, 'status' => 'published']);

        $this->actingAs($owner)->post(route('admin.guides.unpublish', $guide))->assertRedirect();
        $this->assertDatabaseHas('equipment_guides', ['id' => $guide->id, 'status' => 'draft']);

        $this->actingAs($owner)->post(route('admin.guides.archive', $guide))->assertRedirect();
        $this->assertDatabaseHas('equipment_guides', ['id' => $guide->id, 'status' => 'archived']);
    }

    public function test_customer_cannot_access_admin_guides(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.guides.index'))->assertForbidden();
    }

    public function test_owner_can_manage_guide_category(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('admin.guide-categories.store'), [
            'name' => 'Perawatan',
            'slug' => '',
            'sort_order' => 2,
            'is_active' => 1,
        ])->assertRedirect(route('admin.guide-categories.index'));

        $this->assertDatabaseHas('guide_categories', ['name' => 'Perawatan', 'slug' => 'perawatan']);
    }

    private function owner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        return $owner;
    }

    /** @param array<string, mixed> $overrides */
    private function payload(?GuideCategory $category, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Panduan Aman',
            'slug' => '',
            'guide_category_id' => $category?->id,
            'excerpt' => 'Ringkasan.',
            'content' => '# Konten'."\n\nIsi panduan.",
            'status' => 'draft',
            'seo_title' => null,
            'seo_description' => null,
        ], $overrides);
    }
}
