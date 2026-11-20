<?php

namespace App\Data;

use App\Enums\PaymentMethod;

/** Input checkout paket Trip Planner (sudah lolos Form Request). */
final readonly class TripPackageData
{
    /**
     * @param  array<int, array{variant_id:int, quantity:int, fulfillment:string}>  $items
     */
    public function __construct(
        public TripPlanData $plan,
        public array $items,
        public PaymentMethod $method,
        public ?string $notes = null,
        public ?string $checkoutKey = null,
    ) {}

    /** @return array<int, array{variant_id:int, quantity:int}> */
    public function itemsByFulfillment(string $fulfillment): array
    {
        return array_values(array_map(
            fn (array $item) => ['variant_id' => $item['variant_id'], 'quantity' => $item['quantity']],
            array_filter($this->items, fn (array $item) => $item['fulfillment'] === $fulfillment),
        ));
    }
}
