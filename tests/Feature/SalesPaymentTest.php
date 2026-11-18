<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PaymentNotificationService;
use App\Services\SalesOrderService;
use App\Services\SalesPaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SalesPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_transfer_verification_requires_proof(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, PaymentMethod::Transfer);
        $payment = $order->payments()->firstOrFail();

        $this->expectException(RuntimeException::class);
        app(SalesPaymentService::class)->verify($payment, $customer);
    }

    public function test_transfer_verification_settles_and_confirms_order(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 5);
        $order = $this->order($customer, PaymentMethod::Transfer, $variant, 2);
        $payment = $order->payments()->firstOrFail();
        $payment->forceFill(['proof_path' => 'sales-payment-proofs/x.jpg'])->save();
        $payment->forceFill(['status' => PaymentStatus::WaitingVerification])->save();

        app(SalesPaymentService::class)->verify($payment, $customer);

        $this->assertSame(PaymentStatus::Verified, $payment->refresh()->status);
        $this->assertSame(SalesOrderStatus::Confirmed, $order->refresh()->order_status);
        $this->assertSame(3, (int) $variant->fresh()->stock_quantity);
    }

    public function test_cash_record_settles_order(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, PaymentMethod::Cash);
        $payment = $order->payments()->firstOrFail();

        app(SalesPaymentService::class)->recordCash($payment, $customer);

        $this->assertSame(PaymentStatus::Verified, $payment->refresh()->status);
        $this->assertSame(SalesOrderStatus::Confirmed, $order->refresh()->order_status);
    }

    public function test_mark_settled_is_idempotent_and_reduces_stock_once(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 10);
        $order = $this->order($customer, PaymentMethod::Cash, $variant, 3);
        $payment = $order->payments()->firstOrFail();

        $service = app(SalesPaymentService::class);
        $service->markSettled($payment, $customer, 'cash');
        $service->markSettled($payment->refresh(), $customer, 'cash');

        $this->assertSame(7, (int) $variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_amount_mismatch_blocks_verification(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, PaymentMethod::Transfer);
        $payment = $order->payments()->firstOrFail();
        $payment->forceFill([
            'proof_path' => 'proof.jpg',
            'amount' => (int) $payment->amount + 1000,
        ])->save();

        $this->expectException(RuntimeException::class);
        app(SalesPaymentService::class)->verify($payment, $customer);
    }

    public function test_midtrans_snap_uses_shared_gateway(): void
    {
        config([
            'gearora.payment.gateway_enabled' => true,
            'gearora.midtrans.server_key' => 'server-key',
            'gearora.midtrans.client_key' => 'client-key',
        ]);

        $customer = $this->customer();
        $order = $this->order($customer, PaymentMethod::Transfer);
        $payment = app(SalesPaymentService::class)->ensurePaymentFor($order, PaymentMethod::Midtrans);
        $payment->forceFill(['method' => PaymentMethod::Midtrans])->save();

        $attempt = app(SalesPaymentService::class)->startSnap($payment->refresh(), $customer);

        $this->assertNotNull($attempt->redirect_url);
        $this->assertStringContainsString('fake-snap', (string) $attempt->snap_token);
    }

    public function test_webhook_settles_sales_attempt_and_confirms_order(): void
    {
        config([
            'gearora.payment.gateway_enabled' => true,
            'gearora.midtrans.server_key' => 'server-key',
            'gearora.midtrans.client_key' => 'client-key',
        ]);

        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 8);
        $order = $this->order($customer, PaymentMethod::Transfer, $variant, 2);
        $payment = app(SalesPaymentService::class)->ensurePaymentFor($order, PaymentMethod::Midtrans);
        $payment->forceFill(['method' => PaymentMethod::Midtrans])->save();

        $attempt = app(SalesPaymentService::class)->startSnap($payment->refresh(), $customer);

        $orderId = $attempt->provider_order_id;
        $gross = number_format((int) $attempt->gross_amount, 2, '.', '');
        $signature = hash('sha512', $orderId.'200'.$gross.'server-key');

        $result = app(PaymentNotificationService::class)->handle([
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $gross,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'transaction_id' => 'trx-1',
            'payment_type' => 'bank_transfer',
        ]);

        $this->assertSame('settled', $result['status']);
        $this->assertSame(PaymentStatus::Verified, $payment->refresh()->status);
        $this->assertSame(SalesOrderStatus::Confirmed, $order->refresh()->order_status);
        $this->assertSame(6, (int) $variant->fresh()->stock_quantity);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['gearora.midtrans.server_key' => 'server-key']);

        $result = app(PaymentNotificationService::class)->handle([
            'order_id' => 'GRS-FAKE-0001',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'wrong',
            'transaction_status' => 'settlement',
            'transaction_id' => 'trx-x',
        ]);

        $this->assertSame('invalid_signature', $result['status']);
    }

    public function test_late_payment_after_cancellation_is_flagged_not_processed(): void
    {
        $customer = $this->customer();
        $variant = $this->saleVariant(stock: 5);
        $order = $this->order($customer, PaymentMethod::Transfer, $variant, 1);
        $payment = $order->payments()->firstOrFail();

        app(SalesOrderService::class)->cancel($order, $customer, 'Batal.');

        $service = app(SalesPaymentService::class);
        $service->markSettled($payment->refresh(), $customer, 'manual');

        $this->assertSame(PaymentStatus::Processing, $payment->refresh()->status);
        $this->assertSame(5, (int) $variant->fresh()->stock_quantity);
    }

    private function order(User $customer, PaymentMethod $method, ?ProductVariant $variant = null, int $quantity = 1)
    {
        $variant ??= $this->saleVariant();

        return app(SalesOrderService::class)->checkout($customer, [
            ['variant_id' => $variant->id, 'quantity' => $quantity],
        ], $method);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Customer);

        return $user;
    }

    private function saleVariant(int $stock = 10): ProductVariant
    {
        $product = Product::factory()->sale()->create(['sale_price' => 100000]);

        return ProductVariant::factory()->for($product)->create([
            'sale_price' => 100000,
            'stock_quantity' => $stock,
            'is_active' => true,
        ]);
    }
}
