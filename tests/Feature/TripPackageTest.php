<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\TripPackage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paket Trip Planner: draft session, checkout sewa/beli/mixed, ownership,
 * dokumen HTML/PDF. Harga dan stok diuji via layanan order domain existing.
 */
class TripPackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_can_save_selected_gear_as_package_preview(): void
    {
        $variant = $this->rentalVariant();

        $this->post(route('trip-planner.package.store'), $this->payload([
            ['variant_id' => $variant->id, 'quantity' => 1, 'fulfillment' => 'sewa'],
        ]))
            ->assertRedirect(route('trip-planner.package.review'));

        $this->get(route('trip-planner.package.review'))
            ->assertOk()
            ->assertSee('Pratinjau paket')
            ->assertSee('Tenda Prau')
            ->assertSee('Masuk untuk checkout');
    }

    public function test_rental_package_checkout_creates_linked_rental_order(): void
    {
        $customer = $this->customer();
        $variant = $this->rentalVariant();
        RentalAsset::factory()->for($variant, 'variant')->create();

        $this->putDraft([
            ['variant_id' => $variant->id, 'quantity' => 1, 'fulfillment' => 'sewa'],
        ]);

        $this->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect();

        $package = TripPackage::query()->firstOrFail();
        $this->assertNotNull($package->rental_order_id);
        $this->assertNull($package->sales_order_id);
        $this->assertSame($customer->id, $package->customer_id);
        $this->assertDatabaseHas('rental_orders', ['id' => $package->rental_order_id]);
    }

    public function test_duplicate_checkout_with_same_session_key_creates_one_package(): void
    {
        $customer = $this->customer();
        $variant = $this->rentalVariant();
        RentalAsset::factory()->for($variant, 'variant')->create();

        $this->putDraft([
            ['variant_id' => $variant->id, 'quantity' => 1, 'fulfillment' => 'sewa'],
        ]);
        $this->session(['trip_package_checkout_key' => '90b8f86c-a28d-445e-84b9-a9ec01bf4c38']);

        $this->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect();

        // Request kedua membawa session yang sama seperti double click/retry.
        $this->withSession([
            'trip_package_draft' => session('trip_package_draft'),
            'trip_package_checkout_key' => '90b8f86c-a28d-445e-84b9-a9ec01bf4c38',
        ])
            ->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect();

        $this->assertSame(1, TripPackage::query()->count());
        $this->assertSame(1, RentalOrder::query()->count());
    }

    public function test_sales_package_checkout_creates_stock_reservation_and_sales_order(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 5);

        $this->putDraft([
            ['variant_id' => $variant->id, 'quantity' => 2, 'fulfillment' => 'beli'],
        ]);

        $this->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect();

        $package = TripPackage::query()->firstOrFail();
        $this->assertNull($package->rental_order_id);
        $this->assertNotNull($package->sales_order_id);
        $this->assertDatabaseHas('sales_stock_reservations', [
            'sales_order_id' => $package->sales_order_id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_mixed_package_creates_both_orders(): void
    {
        $customer = $this->customer();
        $rental = $this->rentalVariant();
        RentalAsset::factory()->for($rental, 'variant')->create();
        $sale = $this->saleVariant(stock: 5);

        $this->putDraft([
            ['variant_id' => $rental->id, 'quantity' => 1, 'fulfillment' => 'sewa'],
            ['variant_id' => $sale->id, 'quantity' => 2, 'fulfillment' => 'beli'],
        ]);

        $this->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect();

        $package = TripPackage::query()->firstOrFail();
        $this->assertNotNull($package->rental_order_id);
        $this->assertNotNull($package->sales_order_id);
        $this->assertContains(
            ['variant_id' => $sale->id, 'quantity' => 2, 'fulfillment' => 'beli'],
            $package->plan_snapshot['items'],
        );
    }

    public function test_checkout_requires_authenticated_customer(): void
    {
        $variant = $this->saleVariant();
        $this->putDraft([['variant_id' => $variant->id, 'quantity' => 1, 'fulfillment' => 'beli']]);

        $this->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('trip_packages', 0);
    }

    public function test_tampered_fulfillment_is_rejected_server_side(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant();
        $this->putDraft([['variant_id' => $variant->id, 'quantity' => 1, 'fulfillment' => 'sewa']]);

        $this->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('trip_packages', 0);
    }

    public function test_customer_cannot_view_another_customers_package_or_documents(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $package = TripPackage::factory()->for($owner, 'customer')->create();

        $this->actingAs($other)->get(route('customer.packages.show', $package))->assertForbidden();
        $this->actingAs($other)->get(route('customer.packages.document', $package))->assertForbidden();
        $this->actingAs($other)->get(route('customer.packages.pdf', $package))->assertForbidden();
    }

    public function test_owner_can_open_printable_document_and_pdf(): void
    {
        $customer = $this->customer();
        $package = TripPackage::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->get(route('customer.packages.document', $package))
            ->assertOk()
            ->assertSee('Dokumen Paket')
            ->assertSee($package->code);

        $this->actingAs($customer)
            ->get(route('customer.packages.pdf', $package))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_document_and_show_expose_plan_details_and_payment_state(): void
    {
        $customer = $this->customer();
        $variant = $this->rentalVariant();
        RentalAsset::factory()->for($variant, 'variant')->create();

        $this->putDraft(
            [['variant_id' => $variant->id, 'quantity' => 1, 'fulfillment' => 'sewa']],
            ['departure_time' => '04:30', 'description' => 'Target sunrise', 'special_needs' => ['cold_weather']],
        );

        $this->actingAs($customer)
            ->post(route('trip-planner.package.checkout'), ['payment_method' => PaymentMethod::Transfer->value])
            ->assertRedirect();

        $package = TripPackage::query()->firstOrFail();
        $this->assertSame('04:30', $package->departure_time);
        $this->assertSame(['cold_weather'], $package->specialNeeds());
        $this->assertSame('Target sunrise', $package->planDescription());

        $package->rentalOrder->payments()->create([
            'method' => PaymentMethod::Transfer->value,
            'amount' => $package->rentalOrder->total,
            'status' => PaymentStatus::WaitingVerification->value,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.packages.document', $package))
            ->assertOk()
            ->assertSee('04:30')
            ->assertSee('Cuaca dingin')
            ->assertSee('Target sunrise')
            ->assertSee('Menunggu Verifikasi Pembayaran');

        $this->actingAs($customer)
            ->get(route('customer.packages.show', $package))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi Pembayaran');
    }

    private function putDraft(array $items, array $extra = []): void
    {
        $this->withSession([
            'trip_package_draft' => array_merge($this->payload($items), $extra, ['items' => $items]),
        ]);
    }

    private function payload(array $items): array
    {
        return [
            'activity' => 'hiking',
            'destination' => 'Gunung Prau',
            'location' => 'Dieng',
            'duration_days' => 2,
            'participants' => 2,
            'experience' => ExperienceLevel::Pemula->value,
            'mode' => 'sewa',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'special_needs' => [],
            'items' => $items,
        ];
    }

    private function rentalVariant(): ProductVariant
    {
        $product = Product::factory()->create([
            'name' => 'Tenda Prau',
            'slug' => 'tenda-prau',
            'rental_price_per_day' => 100000,
        ]);

        return ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 100000]);
    }

    private function saleVariant(int $stock = 5): ProductVariant
    {
        $product = Product::factory()->sale()->create([
            'name' => 'Headlamp Prau',
            'slug' => 'headlamp-prau',
            'sale_price' => 200000,
        ]);

        return ProductVariant::factory()->for($product)->create(['sale_price' => 200000, 'stock_quantity' => $stock]);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Customer);

        return $user;
    }
}
