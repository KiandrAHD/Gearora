<?php

namespace App\Data;

use Carbon\CarbonInterface;

final class OverdueResult
{
    public function __construct(
        public readonly bool $isOverdue,
        public readonly int $daysLate,
        public readonly int $lateFee,
        public readonly bool $isLost,
        public readonly ?CarbonInterface $deadline,
        public readonly ?string $note,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_overdue' => $this->isOverdue,
            'days_late' => $this->daysLate,
            'late_fee' => $this->lateFee,
            'is_lost' => $this->isLost,
        ];
    }
}
