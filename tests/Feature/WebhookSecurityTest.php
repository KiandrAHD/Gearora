<?php

namespace Tests\Feature;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\RoleName;
use App\Enums\WebhookProcessingStatus;
use App\Models\Category;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;
use App\Services\PaymentNotificationService;
use App\Services\RentalOrderService;
use App\Support\MidtransSignature;
use App\Support\RentalPeriod;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — keamanan webhook Midtrans:
 * urutan validasi signature, anti event-poisoning, dan retry event Failed.
 */
class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-WEBHOOKSEC0000000000000';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        config()->set('gearora.midtrans.server_key', self::SERVER_KEY);
        config()->set('gearora.midtrans.client_key', 'SB-Mid-client-WEBHOOKSEC0000000000000');
        config()->set('gearora.payment.gateway_enabled', true);
    }

    public function test_invalid_signature_does_not_poison_later_valid_notification(): void
    {
        [$order, $payment, $attempt] = $this->midtransOrderWithAttempt();

        $payload = $this->payload($attempt, 'settlement');
        $validSignature = $payload['signature_key'];

        // 1. Signature salah: harus ditolak dan TIDAK mengklaim fingerprint valid.
        $payload['signature_key'] = str_repeat('0', 128);
        $rejected = app(PaymentNotificationService::class)->handle($payload);
        $this->assertSame('invalid_signature', $rejected['status']);
        $this->assertSame(PaymentStatus::Unpaid, $payment->fresh()->status);

        // 2. Notifikasi valid yang sama harus tetap dapat diproses.
        $payload['signature_key'] = $validSignature;
        $settled = app(PaymentNotificationService::class)->handle($payload);

        $this->assertSame('settled', $settled['status']);
        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
        $this->assertSame(RentalOrderStatus::Confirmed, $order->fresh()->order_status);
    }

    public function test_unknown_attempt_does_not_register_blocking_event(): void
    {
        $orderId = 'GRS-UNKNOWN-0001';
        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '100000',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'transaction_id' => 'trx-unknown',
            'payment_type' => 'bank_transfer',
            'currency' => 'IDR',
            'signature_key' => hash('sha512', $orderId.'200'.'100000'.self::SERVER_KEY),
        ];

        $result = app(PaymentNotificationService::class)->handle($payload);
        $this->assertSame('unknown_attempt', $result['status']);

        // Tidak ada event dengan event_hash normal yang mengunci payload ini.
        $this->assertDatabaseMissing('payment_webhook_events', [
            'event_hash' => MidtransSignature::eventHash($payload),
        ]);
    }

    public function test_failed_event_can_be_retried_and_settles(): void
    {
        [$order, $payment, $attempt] = $this->midtransOrderWithAttempt();

        $payload = $this->payload($attempt, 'settlement');

        // Simulasikan kegagalan pemrosesan sebelumnya dengan event Failed
        // memakai event_hash yang sama.
        PaymentWebhookEvent::query()->create([
            'event_hash' => MidtransSignature::eventHash($payload),
            'provider' => PaymentProvider::Midtrans,
            'provider_order_id' => $attempt->provider_order_id,
            'provider_transaction_id' => 'trx-failed',
            'notification_status' => 'settlement',
            'processing_status' => WebhookProcessingStatus::Failed,
            'failure_reason' => 'Simulated failure.',
            'payload_safe' => [],
            'received_at' => now(),
            'processed_at' => now(),
        ]);

        $result = app(PaymentNotificationService::class)->handle($payload);

        $this->assertSame('settled', $result['status']);
        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);

        $event = PaymentWebhookEvent::query()
            ->where('event_hash', MidtransSignature::eventHash($payload))
            ->firstOrFail();
        $this->assertSame(WebhookProcessingStatus::Processed, $event->processing_status);
        $this->assertSame(1, PaymentWebhookEvent::query()->count());
    }

    public function test_processed_event_is_not_reprocessed(): void
    {
        [, $payment, $attempt] = $this->midtransOrderWithAttempt();
        $payload = $this->payload($attempt, 'settlement');

        $first = app(PaymentNotificationService::class)->handle($payload);
        $second = app(PaymentNotificationService::class)->handle($payload);

        $this->assertSame('settled', $first['status']);
        $this->assertSame('duplicate', $second['status']);
        $this->assertSame(1, PaymentWebhookEvent::query()->count());
    }

    public function test_amount_mismatch_then_correct_amount_is_processed(): void
    {
        [, $payment, $attempt] = $this->midtransOrderWithAttempt();

        $bad = $this->payload($attempt, 'settlement');
        $bad['gross_amount'] = (string) ((int) $attempt->gross_amount + 5000);
        $bad['signature_key'] = hash('sha512', $bad['order_id'].$bad['status_code'].$bad['gross_amount'].self::SERVER_KEY);

        $mismatch = app(PaymentNotificationService::class)->handle($bad);
        $this->assertSame('amount_mismatch', $mismatch['status']);
        $this->assertSame(PaymentStatus::Unpaid, $payment->fresh()->status);

        // Notifikasi dengan nominal benar tetap dapat diproses (hash berbeda).
        $good = app(PaymentNotificationService::class)->handle($this->payload($attempt, 'settlement'));
        $this->assertSame('settled', $good['status']);
        $this->assertSame(PaymentStatus::Verified, $payment->fresh()->status);
    }

    /**
     * @return array{0: RentalOrder, 1: Payment, 2: PaymentAttempt}
     */
    private function midtransOrderWithAttempt(): array
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);
        RentalAsset::factory()->count(1)->for($variant, 'variant')->create();

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
}
