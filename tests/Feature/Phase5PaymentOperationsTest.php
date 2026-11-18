<?php

namespace Tests\Feature;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\RoleName;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Category;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;
use App\Services\DepositService;
use App\Services\InspectionService;
use App\Services\PaymentService;
use App\Services\RefundCalculationService;
use App\Services\RentalHandoverService;
use App\Services\RentalOrderService;
use App\Services\RentalOverdueService;
use App\Support\RentalPeriod;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class Phase5PaymentOperationsTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-PHASE5TESTKEY0000000000';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        config()->set('gearora.midtrans.server_key', self::SERVER_KEY);
        config()->set('gearora.midtrans.client_key', 'SB-Mid-client-PHASE5TESTKEY0000000000');
        config()->set('gearora.payment.gateway_enabled', true);
    }

    public function test_valid_webhook_settles_payment_confirms_order_and_collects_deposit(): void
    {
        [$order, $payment, $attempt] = $this->midtransOrderWithAttempt(deposit: true);

        $response = $this->postJson(route('payments.midtrans.notification'), $this->payload($attempt, 'settlement'));

        $response->assertOk()->assertJson(['status' => 'settled']);

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame(RentalOrderStatus::Confirmed, $order->order_status);
        $this->assertSame((int) $order->deposit_total, (int) $order->deposit->fresh()->collected_amount);
        $this->assertSame(PaymentAttemptStatus::Settlement, $attempt->fresh()->status);
    }

    public function test_webhook_with_invalid_signature_is_rejected_and_changes_nothing(): void
    {
        [$order, $payment, $attempt] = $this->midtransOrderWithAttempt();

        $payload = $this->payload($attempt, 'settlement');
        $payload['signature_key'] = str_repeat('0', 128);

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertStatus(401);

        $this->assertSame(PaymentStatus::Unpaid, $payment->fresh()->status);
        $this->assertSame(RentalOrderStatus::PendingPayment, $order->fresh()->order_status);
    }

    public function test_webhook_with_wrong_amount_is_rejected(): void
    {
        [$order, $payment, $attempt] = $this->midtransOrderWithAttempt();

        $payload = $this->payload($attempt, 'settlement');
        $payload['gross_amount'] = (string) (((int) $attempt->gross_amount) + 1);
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].self::SERVER_KEY);

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertStatus(422);

        $this->assertSame(PaymentStatus::Unpaid, $payment->fresh()->status);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        [$order, $payment, $attempt] = $this->midtransOrderWithAttempt(deposit: true);
        $payload = $this->payload($attempt, 'settlement');

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'duplicate']);

        $deposit = $order->fresh()->deposit;
        $this->assertSame((int) $order->deposit_total, (int) $deposit->collected_amount);
        $this->assertSame(1, $deposit->transactions()->count());
    }

    public function test_gateway_payment_cannot_be_manually_verified(): void
    {
        [, $payment] = $this->midtransOrderWithAttempt();
        $owner = $this->owner();

        $this->expectException(RuntimeException::class);

        app(PaymentService::class)->verify($payment, $owner);
    }

    public function test_workflow_only_status_cannot_be_reached_by_manual_transition(): void
    {
        $customer = $this->customer();
        $order = RentalOrder::factory()->for($customer, 'customer')->create();
        $order->forceFill(['order_status' => RentalOrderStatus::ReadyForPickup])->save();

        $this->expectException(InvalidOrderTransitionException::class);

        app(RentalOrderService::class)->transition($order->fresh(), RentalOrderStatus::PickedUp, $this->owner());
    }

    public function test_refund_calculation_follows_policy_tiers(): void
    {
        $calculator = app(RefundCalculationService::class);
        $at = CarbonImmutable::parse('2026-06-01 12:00:00', config('app.timezone'));

        $order = RentalOrder::factory()->create([
            'start_date' => '2026-06-04',
            'end_date' => '2026-06-06',
            'duration_days' => 2,
            'subtotal' => 100000,
            'deposit_total' => 20000,
            'total' => 120000,
        ]);

        $full = $calculator->calculate($order, $at);
        $this->assertSame(100, $full->rentalPercent);
        $this->assertSame(100000, $full->rentalRefund);
        $this->assertSame(20000, $full->depositRefund);
        $this->assertSame(120000, $full->total);

        $order->forceFill(['start_date' => '2026-06-03'])->save();
        $half = $calculator->calculate($order, $at);
        $this->assertSame(50, $half->rentalPercent);
        $this->assertSame(50000, $half->rentalRefund);
        $this->assertSame(70000, $half->total);

        $order->forceFill(['start_date' => '2026-06-01'])->save();
        $none = $calculator->calculate($order, $at);
        $this->assertSame(0, $none->rentalPercent);
        $this->assertSame(20000, $none->total);
    }

    public function test_deposit_ledger_collect_deduct_and_refund_keep_balance_consistent(): void
    {
        $customer = $this->customer();
        $actor = $this->owner();
        $order = RentalOrder::factory()->for($customer, 'customer')->create([
            'subtotal' => 100000,
            'deposit_total' => 200000,
            'total' => 300000,
        ]);

        $deposits = app(DepositService::class);
        $deposit = $deposits->ensureForOrder($order);

        $deposits->collect($deposit, 200000, 'Pembayaran deposit');
        $this->assertSame(200000, $deposit->fresh()->balance());

        $deducted = $deposits->deduct($deposit->fresh(), 50000, 'Kerusakan ringan', $actor);
        $this->assertSame(50000, $deducted);
        $this->assertSame(150000, $deposit->fresh()->balance());

        $refunded = $deposits->refund($deposit->fresh(), 150000, 'Pengembalian sisa deposit', $actor);
        $this->assertSame(150000, $refunded);

        $deposit->refresh();
        $this->assertSame(0, $deposit->balance());
        $this->assertSame(3, $deposit->transactions()->count());
    }

    public function test_overdue_late_fee_uses_grace_period_and_daily_rate_snapshot(): void
    {
        $order = RentalOrder::factory()->create([
            'start_date' => '2026-05-30',
            'end_date' => '2026-06-01',
            'duration_days' => 2,
            'subtotal' => 100000,
            'deposit_total' => 0,
            'total' => 100000,
        ]);

        $service = app(RentalOverdueService::class);

        $onTime = $service->forOrder($order, CarbonImmutable::parse('2026-06-01 20:30:00', config('app.timezone')));
        $this->assertFalse($onTime->isOverdue);

        $overdue = $service->forOrder($order, CarbonImmutable::parse('2026-06-01 22:00:00', config('app.timezone')));
        $this->assertTrue($overdue->isOverdue);
        $this->assertSame(1, $overdue->daysLate);
        $this->assertSame(50000, $overdue->lateFee);
    }

    public function test_handover_is_blocked_without_verified_payment(): void
    {
        $customer = $this->customer();
        $variant = $this->rentalVariant(1, requiresDeposit: true);
        $order = app(RentalOrderService::class)->create(
            $customer,
            [['variant_id' => $variant->id, 'quantity' => 1]],
            RentalPeriod::from(now()->addDays(3)->toDateString(), now()->addDays(5)->toDateString()),
            PaymentMethod::Midtrans,
        );
        $order->forceFill(['order_status' => RentalOrderStatus::ReadyForPickup])->save();

        $this->expectException(RuntimeException::class);

        app(RentalHandoverService::class)->handover($order->fresh(), [
            'identity_checked' => true,
            'customer_acknowledged' => true,
        ], $this->owner());
    }

    public function test_inspection_releases_only_the_inspected_unit(): void
    {
        $customer = $this->customer();
        $variant = $this->rentalVariant(2);
        $order = app(RentalOrderService::class)->create(
            $customer,
            [['variant_id' => $variant->id, 'quantity' => 2]],
            RentalPeriod::from(now()->addDays(1)->toDateString(), now()->addDays(3)->toDateString()),
            PaymentMethod::Transfer,
        );

        $order->forceFill(['order_status' => RentalOrderStatus::PickedUp])->save();

        $assignments = $order->assignments()->with('asset')->get();

        foreach ($assignments as $assignment) {
            $assignment->forceFill(['handed_over_at' => now(), 'returned_at' => now(), 'status' => 'returned'])->save();
            $assignment->asset->forceFill(['status' => RentalAssetStatus::InspectionPending])->save();
        }

        $first = $assignments->first();
        $second = $assignments->last();

        app(InspectionService::class)->finalizeReturn($first->fresh(), ['result' => 'pass', 'condition' => 'good'], $this->owner());

        $this->assertNotNull($first->fresh()->released_at);
        $this->assertSame(RentalAssetStatus::Available, $first->asset->fresh()->status);

        $this->assertNull($second->fresh()->released_at);
        $this->assertSame(RentalAssetStatus::InspectionPending, $second->asset->fresh()->status);
        $this->assertSame(RentalOrderStatus::PickedUp, $order->fresh()->order_status);
    }

    /**
     * @return array{0: RentalOrder, 1: Payment, 2: PaymentAttempt}
     */
    private function midtransOrderWithAttempt(bool $deposit = false): array
    {
        $customer = $this->customer();
        $variant = $this->rentalVariant(1, requiresDeposit: $deposit);

        $order = app(RentalOrderService::class)->create(
            $customer,
            [['variant_id' => $variant->id, 'quantity' => 1]],
            RentalPeriod::from(now()->addDays(3)->toDateString(), now()->addDays(5)->toDateString()),
            PaymentMethod::Midtrans,
        );

        $payment = $order->payments()->firstOrFail();

        $attempt = $payment->attempts()->create([
            'rental_order_id' => $order->getKey(),
            'provider' => PaymentProvider::Midtrans,
            'provider_order_id' => $order->order_code.'-A01',
            'status' => PaymentAttemptStatus::Pending,
            'gross_amount' => $payment->amountRupiah(),
            'currency' => 'IDR',
        ]);

        return [$order, $payment, $attempt];
    }

    /**
     * @return array<string, string>
     */
    private function payload(PaymentAttempt $attempt, string $transactionStatus): array
    {
        $statusCode = '200';
        $gross = (string) $attempt->gross_amount;

        return [
            'order_id' => $attempt->provider_order_id,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'transaction_status' => $transactionStatus,
            'fraud_status' => 'accept',
            'transaction_id' => 'txn-'.$attempt->getKey(),
            'payment_type' => 'bank_transfer',
            'currency' => 'IDR',
            'signature_key' => hash('sha512', $attempt->provider_order_id.$statusCode.$gross.self::SERVER_KEY),
        ];
    }

    private function owner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole(RoleName::Owner);

        return $owner;
    }

    private function customer(): User
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        return $customer;
    }

    private function rentalVariant(int $assets, bool $requiresDeposit = false): ProductVariant
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create([
            'requires_deposit' => $requiresDeposit,
            'deposit_amount' => $requiresDeposit ? 200000 : null,
        ]);
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);
        RentalAsset::factory()->count($assets)->for($variant, 'variant')->create();

        return $variant;
    }
}
