<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\RentalOrderService;
use App\Services\SalesOrderService;
use App\Support\RentalPeriod;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PHASE 8 — IDOR / ownership lintas pelanggan pada order & pembayaran.
 */
class CustomerOwnershipSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_cannot_view_or_cancel_other_customer_rental_order(): void
    {
        [$alice, $bob] = [$this->customer(), $this->customer()];
        $order = $this->rentalOrder($bob);

        $this->actingAs($alice)->get(route('customer.orders.show', $order))->assertForbidden();
        $this->actingAs($alice)->post(route('customer.orders.cancel', $order))->assertForbidden();
    }

    public function test_customer_cannot_submit_payment_proof_for_other_order(): void
    {
        Storage::fake('local');

        [$alice, $bob] = [$this->customer(), $this->customer()];
        $order = $this->rentalOrder($bob);

        $this->actingAs($alice)
            ->post(route('customer.orders.payment-proof.store', $order), [
                'proof' => UploadedFile::fake()->image('proof.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_view_or_cancel_other_customer_sales_order(): void
    {
        [$alice, $bob] = [$this->customer(), $this->customer()];
        $order = $this->salesOrder($bob);

        $this->actingAs($alice)->get(route('customer.sales.show', $order))->assertForbidden();
        $this->actingAs($alice)->post(route('customer.sales.cancel', $order))->assertForbidden();
    }

    public function test_customer_cannot_submit_sales_payment_proof_for_other_order(): void
    {
        Storage::fake('local');

        [$alice, $bob] = [$this->customer(), $this->customer()];
        $order = $this->salesOrder($bob);
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($alice)
            ->post(route('customer.sales.proof.store', [$order, $payment]), [
                'proof' => UploadedFile::fake()->image('proof.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_download_other_customer_payment_proof_via_admin_route(): void
    {
        Storage::fake('local');

        [$alice, $bob] = [$this->customer(), $this->customer()];
        $order = $this->rentalOrder($bob);
        $payment = $order->payments()->firstOrFail();
        $payment->forceFill([
            'proof_path' => 'payment-proofs/proof.jpg',
            'status' => PaymentStatus::WaitingVerification,
        ])->save();
        Storage::disk('local')->put('payment-proofs/proof.jpg', 'binary');

        // Customer tidak boleh mengakses endpoint bukti pada area admin.
        $this->actingAs($alice)->get(route('admin.payments.proof', $payment))->assertForbidden();
    }

    public function test_unknown_ids_return_not_found(): void
    {
        $alice = $this->customer();

        $this->actingAs($alice)->get(route('customer.orders.show', 999999))->assertNotFound();
        $this->actingAs($alice)->get(route('customer.sales.show', 999999))->assertNotFound();
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Customer);

        return $user;
    }

    private function rentalOrder(User $customer): RentalOrder
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);
        RentalAsset::factory()->for($variant, 'variant')->create();

        return app(RentalOrderService::class)->create(
            $customer,
            [['variant_id' => $variant->id, 'quantity' => 1]],
            RentalPeriod::from(now()->addDays(3)->toDateString(), now()->addDays(5)->toDateString()),
            PaymentMethod::Transfer,
        );
    }

    private function salesOrder(User $customer): SalesOrder
    {
        $product = Product::factory()->sale()->create(['sale_price' => 100000]);
        $variant = ProductVariant::factory()->for($product)->create([
            'sale_price' => 100000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        return app(SalesOrderService::class)->checkout($customer, [
            ['variant_id' => $variant->id, 'quantity' => 1],
        ], PaymentMethod::Transfer);
    }
}
