<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesPayment>
 */
class SalesPaymentFactory extends Factory
{
    protected $model = SalesPayment::class;

    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'method' => PaymentMethod::Transfer,
            'amount' => 100000,
            'status' => PaymentStatus::Unpaid,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => ['method' => PaymentMethod::Cash]);
    }

    public function midtrans(): static
    {
        return $this->state(fn () => ['method' => PaymentMethod::Midtrans]);
    }
}
