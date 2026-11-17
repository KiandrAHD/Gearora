<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Product;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;
use App\Services\RentalOrderService;
use App\Support\RentalPeriod;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: setiap halaman GET utama harus merender tanpa exception.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoCatalogSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        $product = Product::query()->firstOrFail();

        foreach (['/', '/katalog', '/katalog/'.$product->slug, '/panduan', '/rekomendasi-trip', '/masuk', '/daftar'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_booking_summary_renders_with_draft(): void
    {
        $variant = Product::query()->firstOrFail()->variants()->firstOrFail();
        $start = now()->addDays(5)->toDateString();
        $end = now()->addDays(7)->toDateString();

        $this->withSession(['rental_booking_draft' => [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
            'start_date' => $start,
            'end_date' => $end,
            'method' => PaymentMethod::Transfer->value,
            'notes' => null,
        ]])->get(route('booking.summary'))->assertOk();
    }

    public function test_customer_pages_render(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $variant = Product::query()->firstOrFail()->variants()->firstOrFail();
        $order = app(RentalOrderService::class)->create(
            $customer,
            [['variant_id' => $variant->getKey(), 'quantity' => 1]],
            RentalPeriod::from(now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString()),
            PaymentMethod::Transfer,
        );

        foreach (['/akun', '/akun/pesanan', '/akun/profil', route('customer.orders.show', $order)] as $url) {
            $this->actingAs($customer)->get($url)->assertOk();
        }
    }

    public function test_admin_pages_render(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        $category = Category::query()->firstOrFail();
        $product = Product::query()->firstOrFail();
        $asset = RentalAsset::query()->firstOrFail();

        $order = RentalOrder::factory()->create();
        $order->customer->assignRole(RoleName::Customer);

        $urls = [
            route('admin.dashboard'),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.categories.edit', $category),
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.products.edit', $product),
            route('admin.inventory.index'),
            route('admin.inventory.assets.create'),
            route('admin.inventory.assets.edit', $asset),
            route('admin.rentals.index'),
            route('admin.rentals.show', $order),
            route('admin.payments.index'),
            route('admin.customers.index'),
            route('admin.customers.show', $order->customer),
            route('admin.activity.index'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
    }
}
