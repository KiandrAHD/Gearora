<?php

namespace App\Data;

use App\Support\RentalPeriod;

/**
 * Rincian biaya rental yang dihitung di server.
 */
final class PricingQuote
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function __construct(
        public readonly RentalPeriod $period,
        public readonly array $lines,
        public readonly string $subtotal,
        public readonly string $depositTotal,
        public readonly string $total,
    ) {}

    public function durationDays(): int
    {
        return $this->period->days();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->period->startDate(),
            'end_date' => $this->period->endDate(),
            'duration_days' => $this->durationDays(),
            'lines' => $this->lines,
            'subtotal' => $this->subtotal,
            'deposit_total' => $this->depositTotal,
            'total' => $this->total,
        ];
    }
}
