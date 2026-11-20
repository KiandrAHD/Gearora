<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\EquipmentGuide;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuideMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_owner_can_upload_inline_guide_image(): void
    {
        $this->actingAs($this->owner())
            ->postJson(route('admin.guides.media.store'), [
                'image' => UploadedFile::fake()->image('prau.webp', 1200, 800),
                'alt' => 'Tenda Gunung Prau',
            ])
            ->assertCreated()
            ->assertJsonPath('alt', 'Tenda Gunung Prau')
            ->assertJsonPath('path', fn (string $path): bool => str_starts_with($path, 'guide-inline/'))
            ->assertJsonPath('url', fn (string $url): bool => str_contains($url, '/media/'));

        $this->assertCount(1, Storage::disk('public')->allFiles('guide-inline'));
    }

    public function test_inline_upload_rejects_non_image(): void
    {
        $this->actingAs($this->owner())
            ->postJson(route('admin.guides.media.store'), [
                'image' => UploadedFile::fake()->create('payload.html', 10, 'text/html'),
            ])
            ->assertUnprocessable();
    }

    public function test_customer_cannot_upload_inline_image(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)
            ->postJson(route('admin.guides.media.store'), [
                'image' => UploadedFile::fake()->image('prau.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_editor_preview_existing_guide_requires_update_permission(): void
    {
        $guide = EquipmentGuide::factory()->create();
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)
            ->postJson(route('admin.guides.preview'), [
                'guide_id' => $guide->id,
                'content' => '# hacked',
            ])
            ->assertForbidden();
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Owner);

        return $user;
    }
}
