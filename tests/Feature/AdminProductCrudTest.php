<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_create_product_and_database_row_is_persisted(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('admin.products.store'), $this->productPayload($category, [
                'name' => 'Tenda Uji Lipat',
                'slug' => 'tenda-uji-lipat',
            ]))
            ->assertRedirect();

        $product = Product::query()->where('slug', 'tenda-uji-lipat')->firstOrFail();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Tenda Uji Lipat',
            'slug' => 'tenda-uji-lipat',
            'status' => ProductStatus::Active->value,
            'category_id' => $category->id,
        ]);
        $this->assertSame(['Kapasitas' => '4 orang'], $product->specifications);
    }

    public function test_owner_can_update_product_and_quick_edit_returns_to_index(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['type' => 'rental']);

        $this->actingAs($this->owner())
            ->put(route('admin.products.update', $product), $this->productPayload($category, [
                'name' => 'Nama Produk Baru',
                'slug' => 'nama-produk-baru',
                'brand' => 'Merek Baru',
                'status' => 'draft',
                'rental_price_per_day' => 99000,
                'return_to' => 'index',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nama Produk Baru',
            'slug' => 'nama-produk-baru',
            'brand' => 'Merek Baru',
            'status' => ProductStatus::Draft->value,
            'rental_price_per_day' => 99000,
        ]);
    }

    public function test_owner_can_create_variant_with_sale_stock_movement(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Varian Besar',
                'sku' => 'uji-besar-01',
                'rental_price_per_day' => 60000,
                'sale_price' => 500000,
                'stock_quantity' => 5,
                'is_active' => 1,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.products.edit', $product));

        $variant = ProductVariant::query()->where('sku', 'UJI-BESAR-01')->firstOrFail();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'product_id' => $product->id,
            'name' => 'Varian Besar',
            'stock_quantity' => 5,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'initial',
            'quantity_before' => 0,
            'quantity_after' => 5,
        ]);
    }

    public function test_owner_can_update_variant_and_stock_change_is_logged(): void
    {
        $variant = ProductVariant::factory()->create([
            'name' => 'Standar',
            'sku' => 'STD-001',
            'stock_quantity' => 2,
            'rental_price_per_day' => 50000,
        ]);

        $this->actingAs($this->owner())
            ->put(route('admin.products.variants.update', [$variant->product, $variant]), [
                'name' => 'Standar Baru',
                'sku' => 'STD-002',
                'rental_price_per_day' => 55000,
                'sale_price' => null,
                'stock_quantity' => 9,
                'sort_order' => 4,
            ])
            ->assertRedirect(route('admin.products.edit', $variant->product));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'name' => 'Standar Baru',
            'sku' => 'STD-002',
            'rental_price_per_day' => 55000,
            'stock_quantity' => 9,
            'sort_order' => 4,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'adjustment',
            'quantity_before' => 2,
            'quantity_after' => 9,
        ]);
    }

    public function test_variant_without_dependencies_is_deleted_permanently(): void
    {
        $variant = ProductVariant::factory()->create(['is_active' => true]);
        $variantId = $variant->id;
        $product = $variant->product;

        $this->actingAs($this->owner())
            ->delete(route('admin.products.variants.destroy', [$product, $variant]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('product_variants', ['id' => $variantId]);
    }

    public function test_variant_with_rental_assets_is_deactivated_not_deleted(): void
    {
        $variant = ProductVariant::factory()->create(['is_active' => true]);
        RentalAsset::factory()->count(3)->for($variant, 'variant')->create();
        $variantId = $variant->id;
        $product = $variant->product;

        $this->actingAs($this->owner())
            ->delete(route('admin.products.variants.destroy', [$product, $variant]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHas('status', fn (string $message) => str_contains($message, '3 unit rental'));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'is_active' => 0,
        ]);
        $this->assertDatabaseCount('rental_assets', 3);
    }

    public function test_variant_with_stock_history_is_deactivated_not_deleted(): void
    {
        $variant = ProductVariant::factory()->create(['is_active' => true, 'stock_quantity' => 4]);
        $this->actingAs($this->owner())->put(
            route('admin.products.variants.update', [$variant->product, $variant]),
            [
                'name' => $variant->name,
                'sku' => $variant->sku,
                'stock_quantity' => 6,
            ],
        );

        $this->actingAs($this->owner())
            ->delete(route('admin.products.variants.destroy', [$variant->product, $variant]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'is_active' => 0]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_product_without_dependencies_can_be_deleted_permanently(): void
    {
        $product = Product::factory()->create();
        $productId = $product->id;

        $this->actingAs($this->owner())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public function test_product_with_rental_assets_is_rejected_from_permanent_delete(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        RentalAsset::factory()->count(2)->for($variant, 'variant')->create();

        $this->actingAs($this->owner())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'tidak dapat dihapus permanen'));

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseCount('rental_assets', 2);
    }

    public function test_product_can_be_archived_and_is_hidden_from_public_catalog(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::Active]);

        $this->actingAs($this->owner())
            ->post(route('admin.products.archive', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => ProductStatus::Archived->value]);
        $this->get('/katalog')->assertOk()->assertDontSee($product->name);
    }

    public function test_product_validation_requires_rental_price_for_rental_type(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('admin.products.store'), $this->productPayload($category, [
                'type' => 'rental',
                'rental_price_per_day' => null,
            ]))
            ->assertSessionHasErrors('rental_price_per_day');
    }

    public function test_product_validation_rejects_duplicate_slug_and_unknown_category(): void
    {
        $existing = Product::factory()->create(['slug' => 'slug-dipakai']);
        $category = Category::factory()->create();

        $this->actingAs($this->owner())
            ->post(route('admin.products.store'), $this->productPayload($category, [
                'name' => 'Produk Duplikat',
                'slug' => $existing->slug,
                'category_id' => 99999,
            ]))
            ->assertSessionHasErrors(['slug', 'category_id']);
    }

    public function test_variant_validation_rejects_duplicate_sku_and_negative_price(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['sku' => 'SKU-SAMA']);

        $this->actingAs($this->owner())
            ->post(route('admin.products.variants.store', $product), [
                'name' => 'Varian Duplikat',
                'sku' => 'SKU-SAMA',
                'rental_price_per_day' => -1,
            ])
            ->assertSessionHasErrors(['sku', 'rental_price_per_day']);
    }

    public function test_customer_cannot_access_admin_products(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_warehouse_staff_cannot_delete_products_without_permission(): void
    {
        $warehouse = User::factory()->create();
        $warehouse->assignRole(RoleName::WarehouseStaff);
        $product = Product::factory()->create();

        $this->actingAs($warehouse)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($warehouse)->delete(route('admin.products.destroy', $product))->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_role_can_delete_product_with_delete_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_index_renders_view_edit_archive_and_delete_modals(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->owner())->get(route('admin.products.index'))->assertOk();

        $response->assertSee('product-view-'.$product->id, false);
        $response->assertSee('product-edit-'.$product->id, false);
        $response->assertSee('product-archive-'.$product->id, false);
        $response->assertSee('product-delete-'.$product->id, false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
    }

    public function test_admin_index_does_not_offer_permanent_delete_for_product_with_history(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        RentalAsset::factory()->for($variant, 'variant')->create();

        $response = $this->actingAs($this->owner())->get(route('admin.products.index'))->assertOk();

        $response->assertDontSee('product-delete-'.$product->id, false);
        $response->assertSee('Hapus permanen tidak tersedia');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'name' => 'Tenda Uji',
            'slug' => 'tenda-uji',
            'description' => 'Produk untuk pengujian CRUD.',
            'brand' => 'GEARORA Test',
            'type' => 'rental',
            'status' => 'active',
            'rental_price_per_day' => 75000,
            'sale_price' => null,
            'requires_deposit' => 0,
            'deposit_amount' => null,
            'is_featured' => 0,
            'specifications' => [
                ['key' => 'Kapasitas', 'value' => '4 orang'],
            ],
        ], $overrides);
    }

    private function owner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        return $owner;
    }
}