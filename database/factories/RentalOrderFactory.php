<?php

namespace Database\Factories;

use App\Enums\RentalOrderStatus;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RentalOrder>
 */
class RentalOrderFactory extends Factory
{
    protected $model = RentalOrder::class;

    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 20))->startOfDay();
        $end = $start->copy()->addDays(fake()->numberBetween(1, 5));
        $days = (int) $start->diffInDays($end);

        return [
            'order_code' => 'GRR-'.$start->format('Ymd').'-'.strtoupper(Str::random(4)),
            'customer_id' => User::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'blocking_end_date' => $end->toDateString(),
            'duration_days' => $days,
            'subtotal' => 100000,
            'deposit_total' => 0,
            'total' => 100000,
            'order_status' => RentalOrderStatus::PendingPayment,
            'notes' => null,
        ];
    }

    public function status(RentalOrderStatus $status): static
    {
        return $this->state(fn () => ['order_status' => $status]);
    }
}
