<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Siklus hidup foto produk: unggah, jadikan utama, hapus, dan fallback
 * `thumbnail_path` saat foto utama hilang. Semua file disimpan di disk publik
 * palsu; tidak ada file yang menyentuh disk nyata.
 */
class AdminProductImageLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_first_upload_becomes_primary_and_sets_thumbnail(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('admin.products.images.store', $product), [
                'image' => UploadedFile::fake()->image('tenda.jpg', 640, 480),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $image = $product->images()->firstOrFail();

        $this->assertTrue($image->is_primary);
        $this->assertSame($image->path, $product->refresh()->thumbnail_path);
        Storage::disk('public')->assertExists($image->path);
        $this->assertStringContainsString('products/'.$product->getKey(), $image->path);
    }

    public function test_set_primary_moves_flag_and_thumbnail_to_chosen_image(): void
    {
        $product = Product::factory()->create();
        $first = ProductImage::factory()->create([
            'product_id' => $product->getKey(),
            'path' => 'products/'.$product->getKey().'/a.jpg',
            'is_primary' => true,
        ]);
        $second = ProductImage::factory()->create([
            'product_id' => $product->getKey(),
            'path' => 'products/'.$product->getKey().'/b.jpg',
            'is_primary' => false,
        ]);
        $product->forceFill(['thumbnail_path' => $first->path])->save();

        $this->actingAs($this->owner())
            ->post(route('admin.products.images.primary', [$product, $second]))
            ->assertRedirect();

        $this->assertTrue($second->refresh()->is_primary);
        $this->assertFalse($first->refresh()->is_primary);
        $this->assertSame($second->path, $product->refresh()->thumbnail_path);
    }

    public function test_deleting_primary_promotes_next_image_and_updates_thumbnail(): void
    {
        $product = Product::factory()->create();
        $primary = ProductImage::factory()->create([
            'product_id' => $product->getKey(),
            'path' => 'products/'.$product->getKey().'/primary.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);
        $next = ProductImage::factory()->create([
            'product_id' => $product->getKey(),
            'path' => 'products/'.$product->getKey().'/next.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);
        $product->forceFill(['thumbnail_path' => $primary->path])->save();
        Storage::disk('public')->put($primary->path, 'x');

        $this->actingAs($this->owner())
            ->delete(route('admin.products.images.destroy', [$product, $primary]))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_images', ['id' => $primary->getKey()]);
        $this->assertTrue($next->refresh()->is_primary);
        $this->assertSame($next->path, $product->refresh()->thumbnail_path);
        Storage::disk('public')->assertMissing($primary->path);
    }

    public function test_deleting_last_image_clears_thumbnail(): void
    {
        $product = Product::factory()->create();
        $only = ProductImage::factory()->create([
            'product_id' => $product->getKey(),
            'path' => 'products/'.$product->getKey().'/only.jpg',
            'is_primary' => true,
        ]);
        $product->forceFill(['thumbnail_path' => $only->path])->save();

        $this->actingAs($this->owner())
            ->delete(route('admin.products.images.destroy', [$product, $only]))
            ->assertRedirect();

        $this->assertNull($product->refresh()->thumbnail_path);
        $this->assertSame(0, $product->images()->count());
    }

    public function test_image_from_other_product_is_rejected(): void
    {
        $product = Product::factory()->create();
        $other = Product::factory()->create();
        $foreign = ProductImage::factory()->create([
            'product_id' => $other->getKey(),
            'is_primary' => false,
        ]);

        $this->actingAs($this->owner())
            ->post(route('admin.products.images.primary', [$product, $foreign]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($foreign->refresh()->is_primary);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('admin.products.images.store', $product), [
                'image' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, $product->images()->count());
    }

    public function test_customer_cannot_upload_product_image(): void
    {
        $product = Product::factory()->create();
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)
            ->post(route('admin.products.images.store', $product), [
                'image' => UploadedFile::fake()->image('x.jpg'),
            ])
            ->assertForbidden();

        $this->assertSame(0, $product->images()->count());
    }

    public function test_uploaded_image_is_served_by_media_endpoint(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('admin.products.images.store', $product), [
                'image' => UploadedFile::fake()->image('foto.jpg', 200, 200),
            ]);

        $image = $product->images()->firstOrFail();

        $this->get(route('media.show', ['path' => $image->path]))->assertOk();
    }

    private function owner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        return $owner;
    }
}
