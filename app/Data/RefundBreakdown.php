<?php

namespace App\Data;

final class RefundBreakdown
{
    public function __construct(
        public readonly int $rentalAmount,
        public readonly int $depositAmount,
        public readonly int $rentalRefund,
        public readonly int $depositRefund,
        public readonly int $nonRefundable,
        public readonly int $total,
        public readonly int $rentalPercent,
        public readonly string $policyNote,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rental_amount' => $this->rentalAmount,
            'deposit_amount' => $this->depositAmount,
            'rental_refund' => $this->rentalRefund,
            'deposit_refund' => $this->depositRefund,
            'non_refundable' => $this->nonRefundable,
            'total' => $this->total,
            'rental_percent' => $this->rentalPercent,
            'policy_note' => $this->policyNote,
        ];
    }
}
