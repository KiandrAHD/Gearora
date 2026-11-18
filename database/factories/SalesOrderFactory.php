<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(50, 500) * 1000;

        return [
            'order_code' => 'GRS-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'customer_id' => User::factory(),
            'subtotal' => $total,
            'discount_total' => 0,
            'total' => $total,
            'payment_status' => PaymentStatus::Unpaid,
            'order_status' => SalesOrderStatus::PendingPayment,
            'notes' => null,
            'expires_at' => now()->addMinutes(120),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => SalesOrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Verified,
            'confirmed_at' => now(),
        ]);
    }
}
