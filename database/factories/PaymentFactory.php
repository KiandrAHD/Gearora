<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\RentalOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'rental_order_id' => RentalOrder::factory(),
            'method' => PaymentMethod::Transfer,
            'amount' => 100000,
            'status' => PaymentStatus::Unpaid,
        ];
    }
}
