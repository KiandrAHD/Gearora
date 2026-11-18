<?php

namespace Database\Factories;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentProvider;
use App\Models\SalesPayment;
use App\Models\SalesPaymentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SalesPaymentAttempt>
 */
class SalesPaymentAttemptFactory extends Factory
{
    protected $model = SalesPaymentAttempt::class;

    public function definition(): array
    {
        return [
            'sales_payment_id' => SalesPayment::factory(),
            'provider' => PaymentProvider::Midtrans,
            'provider_order_id' => 'GRS-'.strtoupper(Str::random(10)),
            'provider_transaction_id' => null,
            'provider_payment_type' => null,
            'status' => PaymentAttemptStatus::Pending,
            'fraud_status' => null,
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'snap_token' => null,
            'redirect_url' => null,
            'idempotency_key' => null,
            'expires_at' => now()->addHour(),
            'provider_metadata' => null,
        ];
    }
}
