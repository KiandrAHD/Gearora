<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\RoleName;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalOrder;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UX pembayaran: pemilihan metode tampil jelas sebelum checkout, label tunai
 * adalah "bayar saat pengambilan" (bukan COD kirim), dan pencatatan tunai
 * admin idempotent serta menolak nominal tidak cocok.
 */
class PaymentMethodUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cart_checkout_renders_payment_method_picker_with_cash_pickup_label(): void
    {
        $customer = $this->customer();

        $product = Product::factory()->sale()->create(['name' => 'Tenda Beli', 'sale_price' => 250000]);
        $variant = ProductVariant::factory()->for($product)->create([
            'sale_price' => 250000,
            'is_active' => true,
            'stock_quantity' => 5,
        ]);

        $this->actingAs($customer)
            ->post(route('retail.cart.store'), ['variant_id' => $variant->getKey(), 'quantity' => 1])
            ->assertRedirect(route('retail.cart.index'));

        $response = $this->actingAs($customer)->get(route('retail.cart.index'))->assertOk();

        $response->assertSee('name="payment_method"', false);
        $response->assertSee('Bayar tunai saat pengambilan');
        $response->assertSee('Transfer bank manual');
    }

    public function test_product_detail_exposes_payment_method_choice_before_summary(): void
    {
        $product = Product::factory()->create(['name' => 'Tenda Sewa', 'rental_price_per_day' => 90000]);
        ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 90000]);

        $this->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('name="method" value="transfer"', false)
            ->assertSee('name="method" value="cash"', false)
            ->assertSee('Bayar tunai saat pengambilan');
    }

    public function test_cash_payment_can_only_be_recorded_once(): void
    {
        [$payment, $owner] = $this->cashPayment();
        $service = app(PaymentService::class);

        $service->recordCash($payment, $owner, 'Diterima petugas basecamp.');
        $service->recordCash($payment->fresh(), $owner, 'Diterima ulang.');

        $payment->refresh();

        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertNotNull($payment->settled_at);
    }

    public function test_cash_payment_with_amount_mismatch_is_rejected(): void
    {
        [$payment, $owner] = $this->cashPayment();
        $payment->forceFill(['amount' => ((int) $payment->amount) - 1])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nominal pembayaran tidak sesuai');

        app(PaymentService::class)->recordCash($payment->fresh(), $owner);
    }

    /**
     * @return array{0: Payment, 1: User}
     */
    private function cashPayment(): array
    {
        $customer = $this->customer();
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        $order = RentalOrder::factory()->for($customer, 'customer')->create([
            'order_status' => RentalOrderStatus::Confirmed,
        ]);

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Cash,
            'amount' => $order->total,
            'rental_amount' => $order->subtotal,
            'deposit_amount' => $order->deposit_total,
            'status' => PaymentStatus::Unpaid,
        ]);

        return [$payment, $owner];
    }

    private function customer(): User
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        return $customer;
    }
}
